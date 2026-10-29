<?php

namespace App\Http\Controllers;

use App\Services\GoApiService;
use App\Support\DashboardAccess;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session()->has('auth_user') && session()->has('go_api_token')) {
            return redirect()->route('dashboards.home');
        }

        if (session()->has('auth_user') && !session()->has('go_api_token')) {
            session()->forget([
                'auth_user',
                'auth_token',
                'go_api_token',
                'go_api_user',
                'api_session_expired',
                'api_session_message',
                'api_last_error',
            ]);
        }

        return view('auth.login');
    }

    public function login(Request $request, GoApiService $goApiService)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $email = strtolower(trim($request->input('email')));
        $password = $request->input('password');

        /*
        |--------------------------------------------------------------------------
        | Login real contra API Go
        |--------------------------------------------------------------------------
        | Laravel manda email/password a POST /login.
        | Si la API responde authenticated=true y token, guardamos el token en sesión.
        */
        $apiLogin = $goApiService->login($email, $password);

        if (!$apiLogin) {
            return back()
                ->withErrors([
                    'login' => $goApiService->lastErrorMessage() ?: 'Correo o contraseña incorrectos, o no se pudo autenticar contra la API.',
                ])
                ->withInput();
        }

        $apiUser = $apiLogin['user'] ?? [];

        $role = $apiUser['role'] ?? 'user';

        $permissionLevelFromApi = $apiUser['permissionLevel']
            ?? $apiUser['permission_level']
            ?? null;

        $permissionLevel = $permissionLevelFromApi !== null
            ? (int) $permissionLevelFromApi
            : match ($role) {
                'admin' => 5,
                'manager' => 3,
                default => 1,
            };

        $rawDashboardAccess = $apiUser['dashboardAccess']
            ?? $apiUser['dashboard_access']
            ?? $apiUser['dashboards']
            ?? $apiUser['permissions']['dashboards']
            ?? null;

        $dashboardAccess = DashboardAccess::normalize($rawDashboardAccess, $permissionLevel);

        $authUser = [
            'id' => $apiUser['_id'] ?? $apiUser['id'] ?? null,
            'name' => $apiUser['name'] ?? $email,
            'email' => $apiUser['email'] ?? $email,
            'role' => $role,

            // Se conserva para compatibilidad con la API actual.
            'permissionLevel' => $permissionLevel,

            // Nuevo esquema: permisos por tablero.
            'dashboardAccess' => $dashboardAccess,
            'status' => 'active',
        ];

        session([
            'auth_user' => $authUser,

            // Lo dejo por compatibilidad con tu frontend actual.
            'auth_token' => $apiLogin['token'],

            // Este es el token real que usará GoApiService en cada consulta.
            'go_api_token' => $apiLogin['token'],
            'go_api_user' => $apiUser,
        ]);
        
        // Regenera el identificador de sesión después de autenticar.
        $request->session()->regenerate();

        return redirect()->route('dashboards.home');
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'auth_user',
            'auth_token',
            'go_api_token',
            'go_api_user',
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
