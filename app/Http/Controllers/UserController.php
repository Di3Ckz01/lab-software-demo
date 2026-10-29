<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use App\Support\DashboardAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(GoApiService $goApiService)
    {
        if (!DashboardAccess::userCan('users')) {
            abort(403, 'No tienes permisos para ver usuarios.');
        }

        $apiUsers = $goApiService->getAllUsers();

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        if (!is_array($apiUsers)) {
            $apiUsers = [];
            session()->flash('error', $this->apiErrorMessage($goApiService, 'No se pudo obtener la lista de usuarios desde la API.'));
        }

        $users = $this->normalizeUsers($apiUsers);

        return view('users.index', [
            'users' => $users,
            'dashboardOptions' => DashboardAccess::options(),
        ]);
    }

    public function create()
    {
        if (!DashboardAccess::userCan('users')) {
            abort(403, 'No tienes permisos para crear usuarios.');
        }

        return view('users.create', [
            'dashboardOptions' => DashboardAccess::options(),
            'defaultDashboardAccess' => ['pacientes-mes'],
        ]);
    }

    public function store(Request $request, GoApiService $goApiService)
    {
        if (!DashboardAccess::userCan('users')) {
            abort(403, 'No tienes permisos para crear usuarios.');
        }

        $validated = $this->validateUserPayload($request, true);

        $dashboardAccess = DashboardAccess::normalize(
            $validated['dashboardAccess'] ?? [],
            1
        );

        $permissionLevel = DashboardAccess::derivedPermissionLevel($dashboardAccess);

        $payload = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => $validated['password'],
            'status' => $validated['status'],
            'active' => $validated['status'] === 'active',

            // Nuevo esquema solicitado: permisos por tablero.
            'dashboardAccess' => $dashboardAccess,

            // Se mantiene para compatibilidad con el backend anterior.
            'permissionLevel' => $permissionLevel,
        ];

        $response = $goApiService->createUser($payload);

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        if (!$response) {
            return back()
                ->withErrors([
                    'email' => $this->apiErrorMessage($goApiService, 'No se pudo conectar con la API para crear el usuario.'),
                ])
                ->withInput();
        }

        $created = $response['created'] ?? $response['ok'] ?? $response['success'] ?? false;

        if ($created !== true) {
            return back()
                ->withErrors([
                    'email' => $response['message'] ?? 'No se pudo crear el usuario.',
                ])
                ->withInput();
        }

        return redirect()
            ->route('users.index')
            ->with('success', $response['message'] ?? 'Usuario creado correctamente.');
    }

    public function edit(string $user, GoApiService $goApiService)
    {
        if (!DashboardAccess::userCan('users')) {
            abort(403, 'No tienes permisos para editar usuarios.');
        }

        $apiUsers = $goApiService->getAllUsers();

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        if (!is_array($apiUsers)) {
            return redirect()
                ->route('users.index')
                ->with('error', $this->apiErrorMessage($goApiService, 'No se pudo obtener la información del usuario desde la API.'));
        }

        $targetUser = $this->findUserById($this->normalizeUsers($apiUsers), $user);

        if (!$targetUser) {
            return redirect()
                ->route('users.index')
                ->with('error', 'No se encontró el usuario solicitado.');
        }

        return view('users.edit', [
            'user' => $targetUser,
            'dashboardOptions' => DashboardAccess::options(),
        ]);
    }

    public function update(string $user, Request $request, GoApiService $goApiService)
    {
        if (!DashboardAccess::userCan('users')) {
            abort(403, 'No tienes permisos para editar usuarios.');
        }

        $validated = $this->validateUserPayload($request, false);

        $dashboardAccess = DashboardAccess::normalize(
            $validated['dashboardAccess'] ?? [],
            1
        );

        $this->preventSelfLockout($user, $validated['status'], $dashboardAccess);

        $permissionLevel = DashboardAccess::derivedPermissionLevel($dashboardAccess);

        $payload = [
            'id' => $user,
            '_id' => $user,
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'status' => $validated['status'],
            'active' => $validated['status'] === 'active',
            'dashboardAccess' => $dashboardAccess,
            'permissionLevel' => $permissionLevel,
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $response = $goApiService->updateUser($user, $payload);

        if ($redirect = $this->redirectIfApiSessionExpired()) {
            return $redirect;
        }

        if (!$response) {
            return back()
                ->withErrors([
                    'email' => $this->apiErrorMessage($goApiService, 'No se pudo conectar con la API para actualizar el usuario.'),
                ])
                ->withInput();
        }

        $explicitResult = $response['updated']
            ?? $response['modified']
            ?? $response['ok']
            ?? $response['success']
            ?? null;

        if ($explicitResult === false) {
            return back()
                ->withErrors([
                    'email' => $response['message'] ?? 'No se pudo actualizar el usuario.',
                ])
                ->withInput();
        }

        $this->refreshCurrentUserSessionIfNeeded($user, $payload);

        return redirect()
            ->route('users.index')
            ->with('success', $response['message'] ?? 'Usuario actualizado correctamente.');
    }

    private function validateUserPayload(Request $request, bool $creating): array
    {
        $validDashboardKeys = DashboardAccess::keys();

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'status' => ['required', 'in:active,inactive'],
            'dashboardAccess' => ['required', 'array', 'min:1'],
            'dashboardAccess.*' => ['required', 'string', Rule::in($validDashboardKeys)],
        ];

        $rules['password'] = $creating
            ? ['required', 'string', 'min:6']
            : ['nullable', 'string', 'min:6'];

        return $request->validate($rules, [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'status.required' => 'El estatus es obligatorio.',
            'dashboardAccess.required' => 'Selecciona al menos un tablero para el usuario.',
            'dashboardAccess.array' => 'Los permisos de tableros no tienen un formato válido.',
            'dashboardAccess.min' => 'Selecciona al menos un tablero para el usuario.',
            'dashboardAccess.*.in' => 'Uno de los tableros seleccionados no es válido.',
        ]);
    }

    private function preventSelfLockout(string $targetUserId, string $status, array $dashboardAccess): void
    {
        $authUser = session('auth_user', []);
        $authUserId = (string) ($authUser['id'] ?? '');

        if ($authUserId === '' || $authUserId !== (string) $targetUserId) {
            return;
        }

        if ($status !== 'active') {
            throw ValidationException::withMessages([
                'status' => 'No puedes dejar inactivo tu propio usuario administrador.',
            ]);
        }

        if (!in_array('users', $dashboardAccess, true)) {
            throw ValidationException::withMessages([
                'dashboardAccess' => 'No puedes quitarte tu propio acceso de administración de usuarios.',
            ]);
        }
    }

    private function refreshCurrentUserSessionIfNeeded(string $targetUserId, array $payload): void
    {
        $authUser = session('auth_user', []);
        $authUserId = (string) ($authUser['id'] ?? '');

        if ($authUserId === '' || $authUserId !== (string) $targetUserId) {
            return;
        }

        $authUser['name'] = $payload['name'];
        $authUser['email'] = $payload['email'];
        $authUser['status'] = $payload['status'];
        $authUser['permissionLevel'] = $payload['permissionLevel'];
        $authUser['dashboardAccess'] = $payload['dashboardAccess'];

        session(['auth_user' => $authUser]);
    }

    private function findUserById(array $users, string $id): ?array
    {
        foreach ($users as $user) {
            if ((string) ($user['id'] ?? '') === (string) $id) {
                return $user;
            }
        }

        return null;
    }

    private function normalizeUsers(array $apiUsers): array
    {
        $users = [];

        foreach ($apiUsers as $user) {
            if (!is_array($user)) {
                continue;
            }

            $permissionLevel = (int) ($user['permissionLevel'] ?? $user['permission_level'] ?? 1);

            $rawDashboardAccess = $user['dashboardAccess']
                ?? $user['dashboard_access']
                ?? $user['dashboards']
                ?? $user['permissions']['dashboards']
                ?? null;

            $dashboardAccess = DashboardAccess::normalize($rawDashboardAccess, $permissionLevel);

            $status = $user['status'] ?? null;

            if (!$status) {
                $status = ($user['active'] ?? false) ? 'active' : 'inactive';
            }

            $id = $user['_id'] ?? $user['id'] ?? $user['userId'] ?? $user['user_id'] ?? null;

            $users[] = [
                'id' => $id !== null ? (string) $id : null,
                'name' => $user['name'] ?? 'Sin nombre',
                'email' => $user['email'] ?? 'Sin correo',
                'permissionLevel' => $permissionLevel,
                'dashboardAccess' => $dashboardAccess,
                'dashboardLabels' => DashboardAccess::labelsFor($dashboardAccess),
                'status' => $status === 'active' ? 'active' : 'inactive',
                'role' => $user['role'] ?? null,
                'createdAt' => $user['created'] ?? $user['createdAt'] ?? '-',
                'createdBy' => $user['createdBy'] ?? 'Sistema',
                'lastLogin' => $user['lastLogin'] ?? null,
                'initials' => $this->initials($user['name'] ?? 'U'),
            ];
        }

        return $users;
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('') ?: 'U';
    }
}
