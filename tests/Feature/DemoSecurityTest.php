<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Comprueba las barreras del acceso local de demostración.
 *
 * No utiliza credenciales, registros de pacientes ni backend real.
 * Las pruebas de escritura se detienen en el middleware.
 */
final class DemoSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configuración aislada de cada prueba.
        $this->app->instance('env', 'local');

        config()->set('vcare_demo.enabled', true);
        config()->set('services.go_api.base_url', '');
        config()->set('services.go_api.use_mock', true);

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'localhost',
        ]);
        // Simular una solicitud legitima con token CSRF valido.
        // No se desactiva el middleware de seguridad.
        $csrfToken = bin2hex(random_bytes(20));
        $this->withSession(['_token' => $csrfToken]);
        $this->withHeader('X-CSRF-TOKEN', $csrfToken);
    }

    private function demoSession(): array
    {
        return [
            'vcare_demo_session' => true,
            'auth_user' => [
                'id' => 'demo-local',
                'name' => 'Usuario de prueba',
                'role' => 'demo',
                'permissionLevel' => 1,
                'dashboardAccess' => ['users', 'supplier-studies'],
            ],
        ];
    }

    public function test_demo_is_available_on_localhost(): void
    {
        $this->get('/demo')->assertOk();
    }

    public function test_demo_entry_creates_a_synthetic_session(): void
    {
        $this->post('/demo/entrar')
            ->assertRedirect(route('dashboards.home'))
            ->assertSessionHas('vcare_demo_session', true)
            ->assertSessionHas('auth_user')
            ->assertSessionMissing('go_api_token');
    }

    public function test_demo_is_unavailable_in_production(): void
    {
        $this->app->instance('env', 'production');

        $this->get('/demo')->assertNotFound();
    }

    public function test_demo_is_unavailable_from_remote_address(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '192.0.2.50',
            'HTTP_HOST' => 'localhost',
        ]);

        $this->get('/demo')->assertNotFound();
    }

    public function test_demo_is_unavailable_with_real_api_configured(): void
    {
        config()->set(
            'services.go_api.base_url',
            'https://example.invalid'
        );

        $this->get('/demo')->assertNotFound();
    }

    public function test_demo_is_unavailable_when_disabled(): void
    {
        config()->set('vcare_demo.enabled', false);

        $this->get('/demo')->assertNotFound();
    }

    public function test_demo_cannot_assign_supplier_studies(): void
    {
        $this->withSession($this->demoSession())
            ->post('/dashboards/supplier-studies/assign-study', [])
            ->assertForbidden();
    }

    public function test_demo_cannot_create_users(): void
    {
        $this->withSession($this->demoSession())
            ->post('/usuarios', [])
            ->assertForbidden();
    }

    public function test_demo_cannot_update_users(): void
    {
        $this->withSession($this->demoSession())
            ->put('/usuarios/123', [])
            ->assertForbidden();
    }

    /**
     * El tablero de Órdenes debe responder con datos locales
     * sin efectuar solicitudes mediante el cliente HTTP Laravel.
     */
    public function test_demo_orders_do_not_call_external_api(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $demo = $this->demoSession();
        $demo['auth_user']['dashboardAccess'] =
            \App\Support\DashboardAccess::keys();

        $this->withSession($demo)
            ->get('/dashboards/pacientes-mes')
            ->assertOk();

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    /**
     * Toma de muestra debe utilizar datos ficticios
     * sin consultar el backend mediante Laravel HTTP.
     */
    public function test_demo_sample_collection_does_not_call_external_api(): void
    {
        \Illuminate\Support\Facades\Http::fake();

        $demo = $this->demoSession();
        $demo['auth_user']['dashboardAccess'] =
            \App\Support\DashboardAccess::keys();

        $this->withSession($demo)
            ->get('/dashboards/toma-muestra')
            ->assertOk();

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
    public function test_demo_session_is_rejected_outside_local(): void
    {
        $this->app->instance('env', 'production');

        $this->withSession($this->demoSession())
            ->get('/inicio')
            ->assertRedirect(route('login'));
    }
}