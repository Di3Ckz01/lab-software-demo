<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class DashboardAccess
{
    public static function options(): array
    {
        return config('dashboards.items', []);
    }

    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function nonAdminKeys(): array
    {
        return array_keys(array_filter(
            self::options(),
            fn (array $item) => ($item['admin'] ?? false) !== true
        ));
    }

    public static function adminKeys(): array
    {
        return array_keys(array_filter(
            self::options(),
            fn (array $item) => ($item['admin'] ?? false) === true
        ));
    }

    /**
     * Accesos heredados para usuarios antiguos que aún no tienen las nuevas
     * claves guardadas en dashboardAccess.
     */
    /**
     * Maintains historical dashboard permissions.
     * New modules must be explicitly authorized.
     */
    private static function legacyParentFor(string $dashboardKey): ?string
    {
        return [
            'consulta-externa' => 'estudios',
            'venopunciones' => 'productividad',
            'tv-dashboard' => 'opportunity-status',
            'sample-collection-tv' => 'sample-collection',
        ][$dashboardKey] ?? null;
    }

    /**
     * An explicit denial overrides historical permission inheritance.
     */
    private static function isExplicitlyDenied(
        mixed $rawAccess,
        string $dashboardKey
    ): bool {
        if (is_string($rawAccess)) {
            $decoded = json_decode($rawAccess, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            $rawAccess = $decoded;
        }

        if (!is_array($rawAccess)) {
            return false;
        }

        if (array_key_exists($dashboardKey, $rawAccess)) {
            $value = $rawAccess[$dashboardKey];

            if (is_array($value)) {
                $value = $value['enabled']
                    ?? $value['active']
                    ?? $value['allowed']
                    ?? false;
            }

            return filter_var(
                $value,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) !== true;
        }

        foreach ($rawAccess as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $key = $entry['key']
                ?? $entry['id']
                ?? $entry['name']
                ?? null;

            if ($key !== $dashboardKey) {
                continue;
            }

            $enabled = $entry['enabled']
                ?? $entry['active']
                ?? $entry['allowed']
                ?? true;

            return filter_var(
                $enabled,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            ) !== true;
        }

        return false;
    }

    public static function fallbackByLevel(int $permissionLevel): array
    {
        $items = self::options();
        $keys = [];

        foreach ($items as $key => $item) {
            if (($item['admin'] ?? false) === true) {
                continue;
            }

            // Las nuevas pantallas no se agregan automáticamente a usuarios
            // antiguos. Mantienen acceso por compatibilidad con su módulo padre.
            if (($item['default_access'] ?? true) !== true) {
                continue;
            }

            $keys[] = $key;
        }

        if ($permissionLevel >= 5) {
            $keys = array_merge($keys, self::adminKeys());
        }

        return array_values(array_unique($keys));
    }

    /**
     * Normalizes permissions received from the backend.
     *
     * Fallback applies only when permissions are not configured.
     * Explicit empty or unrecognized permissions never grant access.
     */
    public static function normalize(
        mixed $rawAccess,
        int $permissionLevel = 1
    ): array {
        $validKeys = self::keys();

        if (
            $rawAccess === null ||
            (is_string($rawAccess) && trim($rawAccess) === '')
        ) {
            return self::fallbackByLevel($permissionLevel);
        }

        if (is_string($rawAccess)) {
            $text = trim($rawAccess);
            $decoded = json_decode($text, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $rawAccess = $decoded;
            } elseif (
                str_starts_with($text, '[') ||
                str_starts_with($text, '{')
            ) {
                return [];
            } else {
                $rawAccess = array_map(
                    'trim',
                    explode(',', $text)
                );
            }
        }

        if (!is_array($rawAccess)) {
            return [];
        }

        $allowed = [];

        foreach ($rawAccess as $key => $value) {
            if (is_int($key)) {
                if (
                    is_string($value) &&
                    in_array($value, $validKeys, true)
                ) {
                    $allowed[] = $value;
                    continue;
                }

                if (!is_array($value)) {
                    continue;
                }

                $candidate = $value['key']
                    ?? $value['id']
                    ?? $value['name']
                    ?? null;

                $enabled = $value['enabled']
                    ?? $value['active']
                    ?? $value['allowed']
                    ?? true;

                if (
                    is_string($candidate) &&
                    in_array($candidate, $validKeys, true) &&
                    filter_var(
                        $enabled,
                        FILTER_VALIDATE_BOOLEAN,
                        FILTER_NULL_ON_FAILURE
                    ) === true
                ) {
                    $allowed[] = $candidate;
                }

                continue;
            }

            if (
                !is_string($key) ||
                !in_array($key, $validKeys, true)
            ) {
                continue;
            }

            $enabled = is_array($value)
                ? ($value['enabled']
                    ?? $value['active']
                    ?? $value['allowed']
                    ?? false)
                : $value;

            if (
                filter_var(
                    $enabled,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) === true
            ) {
                $allowed[] = $key;
            }
        }

        return array_values(array_unique($allowed));
    }

    /**
     * Checks access using explicit and legacy permissions.
     */
    public static function userCan(
        string $dashboardKey,
        ?array $user = null
    ): bool {
        $user = $user ?? session('auth_user');

        if (!$user || !in_array($dashboardKey, self::keys(), true)) {
            return false;
        }

        $rawAccess = $user['dashboardAccess'] ?? null;

        $permissionLevel = (int) (
            $user['permissionLevel'] ?? 1
        );

        $access = self::normalize(
            $rawAccess,
            $permissionLevel
        );

        if (in_array($dashboardKey, $access, true)) {
            return true;
        }

        if (self::isExplicitlyDenied($rawAccess, $dashboardKey)) {
            return false;
        }

        $legacyParent = self::legacyParentFor($dashboardKey);

        return $legacyParent !== null
            && in_array($legacyParent, $access, true);
    }

    public static function dashboardItems(?array $user = null): array
    {
        return self::visibleItems($user, false, 'dashboard');
    }

    public static function screenItems(?array $user = null): array
    {
        return self::visibleItems($user, false, 'screen');
    }

    public static function visibleItems(?array $user = null, ?bool $admin = null, ?string $section = null): array
    {
        $user = $user ?? session('auth_user');
        $items = self::options();

        return array_filter($items, function (array $item, string $key) use ($user, $admin, $section) {
            if ($admin !== null && (($item['admin'] ?? false) === true) !== $admin) {
                return false;
            }

            $itemSection = $item['section'] ?? ((($item['admin'] ?? false) === true) ? 'admin' : 'dashboard');
            if ($section !== null && $itemSection !== $section) {
                return false;
            }

            if (!Route::has($item['route'] ?? '')) {
                return false;
            }

            return self::userCan($key, $user);
        }, ARRAY_FILTER_USE_BOTH);
    }

    public static function firstAllowedRoute(?array $user = null): string
    {
        if (Route::has('dashboards.home')) {
            return 'dashboards.home';
        }

        $user = $user ?? session('auth_user');

        foreach (self::dashboardItems($user) as $item) {
            if (Route::has($item['route'])) {
                return $item['route'];
            }
        }

        foreach (self::screenItems($user) as $item) {
            if (Route::has($item['route'])) {
                return $item['route'];
            }
        }

        foreach (self::visibleItems($user, true) as $item) {
            if (Route::has($item['route'])) {
                return $item['route'];
            }
        }

        return 'login';
    }

    public static function labelsFor(array $access): array
    {
        $items = self::options();
        $labels = [];

        foreach ($access as $key) {
            if (isset($items[$key])) {
                $labels[$key] = $items[$key]['short_label'] ?? $items[$key]['label'] ?? $key;
            }
        }

        return $labels;
    }

    public static function derivedPermissionLevel(array $access): int
    {
        return in_array('users', $access, true) ? 5 : 1;
    }
}
