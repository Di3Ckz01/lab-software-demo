<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#eef6ff">
    <title>Iniciar sesión | LAB DEMO</title>

    <style>
        :root {
            --vcare-blue: #0b63f6;
            --vcare-blue-dark: #084ec4;
            --vcare-red: #ff4058;
            --vcare-text: #111827;
            --vcare-muted: #64748b;
            --vcare-line: #d9e4f2;
            --vcare-card: rgba(255, 255, 255, .92);
            --vcare-shadow: 0 26px 75px rgba(15, 23, 42, .22);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
            color: var(--vcare-text);
            background: #edf6ff;
            -webkit-font-smoothing: antialiased;
        }

        button,
        input {
            font: inherit;
        }

        button {
            cursor: pointer;
        }

        .login-page {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 38px 22px;
            overflow: hidden;
            background:
                linear-gradient(90deg, rgba(5, 28, 66, .18), rgba(255, 255, 255, .08), rgba(5, 28, 66, .12)),
                url("{{ asset('images/login-lab-vcare.png') }}") center center / cover no-repeat;
        }

        .login-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at center, rgba(255, 255, 255, .08), transparent 45%),
                linear-gradient(180deg, rgba(239, 247, 255, .08), rgba(210, 230, 250, .32));
            pointer-events: none;
        }

        .login-page::after {
            content: "";
            position: absolute;
            inset: 0;
            backdrop-filter: blur(1px);
            pointer-events: none;
        }

        .login-wrapper {
            position: relative;
            z-index: 2;
            width: min(100%, 520px);
        }

        .login-card {
            width: 100%;
            padding: 46px 46px 38px;
            border: 1px solid rgba(219, 228, 242, .9);
            border-radius: 24px;
            background: var(--vcare-card);
            box-shadow: var(--vcare-shadow);
            backdrop-filter: blur(18px);
        }

        .login-brand {
            display: flex;
            justify-content: center;
            margin-bottom: 26px;
        }

        .login-brand img {
            width: 245px;
            max-width: 82%;
            height: auto;
            display: block;
        }

        .login-title {
            margin: 0;
            color: #14213d;
            text-align: center;
            font-size: clamp(30px, 4vw, 38px);
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: -.04em;
        }

        .login-subtitle {
            margin: 10px 0 32px;
            color: var(--vcare-muted);
            text-align: center;
            font-size: 14px;
        }

        .alert-error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border: 1px solid rgba(255, 64, 88, .24);
            border-radius: 12px;
            color: #b42335;
            background: rgba(255, 64, 88, .08);
            font-size: 12px;
            font-weight: 750;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group > label {
            display: block;
            margin-bottom: 8px;
            color: #172554;
            font-size: 12px;
            font-weight: 850;
        }

        .input-shell {
            position: relative;
        }

        .input-shell > svg {
            position: absolute;
            left: 16px;
            top: 50%;
            width: 19px;
            height: 19px;
            fill: none;
            stroke: #71839c;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .input-shell input {
            width: 100%;
            height: 54px;
            padding: 0 48px 0 48px;
            border: 1px solid var(--vcare-line);
            border-radius: 13px;
            outline: 0;
            color: var(--vcare-text);
            background: rgba(255, 255, 255, .96);
            font-size: 14px;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .input-shell input:focus {
            border-color: rgba(11, 99, 246, .72);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(11, 99, 246, .12);
        }

        .input-shell input::placeholder {
            color: #9aa8ba;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 0;
            border-radius: 10px;
            color: #71839c;
            background: transparent;
            transform: translateY(-50%);
        }

        .toggle-password:hover {
            color: var(--vcare-blue);
            background: rgba(11, 99, 246, .08);
        }

        .toggle-password svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .field-error {
            margin-top: 6px;
            color: #b42335;
            font-size: 11px;
            font-weight: 650;
        }

        .login-options {
            margin: 2px 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            font-size: 13px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            cursor: pointer;
        }

        .remember input {
            width: 17px;
            height: 17px;
            margin: 0;
            accent-color: var(--vcare-blue);
        }

        .link-button {
            padding: 0;
            border: 0;
            color: var(--vcare-blue);
            background: transparent;
            font-size: 13px;
            font-weight: 850;
        }

        .link-button:hover {
            text-decoration: underline;
        }

        .btn-login,
        .btn-emergency {
            width: 100%;
            min-height: 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 11px;
            border-radius: 13px;
            font-size: 14px;
            font-weight: 900;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .btn-login {
            border: 1px solid var(--vcare-blue);
            color: #fff;
            background: linear-gradient(135deg, #1777ff, #045ee8);
            box-shadow: 0 15px 28px rgba(11, 99, 246, .28);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 34px rgba(11, 99, 246, .34);
        }

        .btn-login:disabled {
            cursor: wait;
            opacity: .72;
            transform: none;
        }

        .btn-login svg,
        .btn-emergency svg {
            width: 19px;
            height: 19px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .login-divider {
            margin: 24px 0;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 13px;
            color: #8a98aa;
            font-size: 12px;
            font-weight: 750;
        }

        .login-divider::before,
        .login-divider::after {
            content: "";
            height: 1px;
            background: #dfe7f2;
        }

        .btn-emergency {
            border: 1px solid rgba(11, 99, 246, .32);
            color: #0b63f6;
            background: rgba(255, 255, 255, .84);
            box-shadow: 0 8px 18px rgba(15, 23, 42, .05);
        }

        .btn-emergency svg {
            color: var(--vcare-red);
        }

        .btn-emergency:hover {
            background: rgba(239, 246, 255, .95);
            transform: translateY(-1px);
        }

        .login-protection {
            margin-top: 24px;
            color: rgba(31, 41, 55, .82);
            text-align: center;
            font-size: 12px;
            line-height: 1.5;
            text-shadow: 0 1px 2px rgba(255, 255, 255, .48);
        }

        .login-protection strong {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            margin-bottom: 4px;
            color: #334155;
            font-size: 13px;
        }

        .login-protection svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .privacy-button {
            display: inline-block;
            margin-top: 3px;
            padding: 0;
            border: 0;
            color: var(--vcare-blue);
            background: transparent;
            font-size: 12px;
            font-weight: 850;
        }

        .privacy-button:hover {
            text-decoration: underline;
        }

        .login-footer {
            position: fixed;
            left: 24px;
            right: 24px;
            bottom: 16px;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            color: rgba(51, 65, 85, .72);
            font-size: 11px;
            text-shadow: 0 1px 2px rgba(255, 255, 255, .5);
            pointer-events: none;
        }

        .login-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(3, 27, 70, .36);
            backdrop-filter: blur(6px);
        }

        .login-modal-backdrop.is-visible {
            display: flex;
        }

        .login-modal {
            width: min(100%, 430px);
            padding: 26px;
            border: 1px solid var(--vcare-line);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 24px 65px rgba(3, 27, 70, .22);
        }

        .login-modal-icon {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: var(--vcare-blue);
            background: rgba(11, 99, 246, .09);
        }

        .login-modal-icon svg {
            width: 24px;
            height: 24px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .login-modal h3 {
            margin: 16px 0 8px;
            color: var(--vcare-text);
            font-size: 20px;
        }

        .login-modal p {
            margin: 0 0 20px;
            color: var(--vcare-muted);
            font-size: 13px;
            line-height: 1.65;
        }

        .login-modal-close {
            width: 100%;
            min-height: 44px;
            border: 0;
            border-radius: 11px;
            color: #fff;
            background: var(--vcare-blue);
            font-size: 13px;
            font-weight: 850;
        }

        @media (max-width: 700px) {
            .login-page {
                padding: 28px 14px;
                align-items: center;
            }

            .login-card {
                padding: 34px 24px 28px;
                border-radius: 20px;
            }

            .login-brand img {
                width: 205px;
            }

            .login-title {
                font-size: 29px;
            }

            .login-subtitle {
                margin-bottom: 26px;
            }

            .login-options {
                align-items: flex-start;
                flex-direction: column;
                gap: 11px;
            }

            .login-footer {
                position: relative;
                left: auto;
                right: auto;
                bottom: auto;
                margin-top: 20px;
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 4px;
            }
        }

        @media (max-height: 760px) and (min-width: 701px) {
            .login-page {
                padding-top: 24px;
                padding-bottom: 24px;
            }

            .login-card {
                padding-top: 34px;
                padding-bottom: 30px;
            }

            .login-brand {
                margin-bottom: 18px;
            }

            .login-subtitle {
                margin-bottom: 22px;
            }

            .form-group {
                margin-bottom: 14px;
            }

            .login-divider {
                margin: 17px 0;
            }

            .login-protection {
                margin-top: 16px;
            }
        }
    </style>
</head>

<body>
@include('partials.context-help-button', ['contextHelpKey' => 'login', 'standalone' => true])
<div class="login-page">
    <main class="login-wrapper">
        <section class="login-card" aria-label="Inicio de sesión">
            <div class="login-brand">
                <span aria-label="Laboratorio Demo" style="display:inline-block;padding:7px 12px;border-radius:9px;background:#e8f1ff;color:#174b92;font-weight:800;font-size:15px;letter-spacing:.07em;white-space:nowrap;">LAB DEMO</span>
            </div>

            <h1 class="login-title">Bienvenido</h1>
            <p class="login-subtitle">Ingresa tus credenciales para continuar</p>

            @if ($errors->has('login'))
                <div class="alert-error">{{ $errors->first('login') }}</div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" id="loginForm">
                @csrf

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <div class="input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 6h16v12H4z"/>
                            <path d="m4 7 8 6 8-6"/>
                        </svg>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="demo@ejemplo.invalid"
                            autocomplete="email"
                            required
                            autofocus
                        >
                    </div>
                    @error('email')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="5" y="10" width="14" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingresa tu contraseña"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar contraseña">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="login-options">
                    <label class="remember">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        <span>Recordarme</span>
                    </label>

                    <button type="button" class="link-button" data-modal="forgotModal">
                        ¿Olvidaste tu contraseña?
                    </button>
                </div>

                <button type="submit" class="btn-login">
                    <span>Iniciar sesión</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 12h14m-5-5 5 5-5 5"/>
                    </svg>
                </button>
            </form>

            <div class="login-divider">o</div>

            <a href="{{ route('demo.index') }}"
   class="btn-emergency"
   style="text-decoration:none;"
   aria-label="Entrar al modo demostración local">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="9"/>
        <path d="m10 8 6 4-6 4z"/>
    </svg>
    <span>Entrar a la demostración</span>
</a>
        </section>

        <div class="login-protection">
            <strong>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="5" y="10" width="14" height="11" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
                Tu información está protegida
            </strong>
            <div>Cumplimos con las mejores prácticas de seguridad y privacidad.</div>
            <button type="button" class="privacy-button" data-modal="privacyModal">
                Ver políticas de privacidad
            </button>
        </div>
    </main>

    <footer class="login-footer">
        <span>LAB DEMO · Plataforma de demostración · Datos ficticios</span>
        <span>Versión 2.0.0</span>
    </footer>
</div>

<div class="login-modal-backdrop" id="forgotModal" aria-hidden="true">
    <div class="login-modal" role="dialog" aria-modal="true" aria-labelledby="forgotTitle">
        <span class="login-modal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M12 17h.01M9.5 9a2.5 2.5 0 1 1 3.1 2.4c-.6.2-.6.8-.6 1.6"/>
                <circle cx="12" cy="12" r="9"/>
            </svg>
        </span>
        <h3 id="forgotTitle">Recuperación de contraseña</h3>
        <p>
            La recuperación automática todavía no está conectada en este proyecto.
            Solicita al administrador del sistema el restablecimiento de tu acceso.
        </p>
        <button type="button" class="login-modal-close">Entendido</button>
    </div>
</div>

<div class="login-modal-backdrop" id="emergencyModal" aria-hidden="true">
    <div class="login-modal" role="dialog" aria-modal="true" aria-labelledby="emergencyTitle">
        <span class="login-modal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M12 3 3 20h18L12 3Z"/>
                <path d="M12 9v5m0 3h.01"/>
            </svg>
        </span>
        <h3 id="emergencyTitle">Acceso de emergencia</h3>
        <p>
            El flujo de emergencia queda listo visualmente para conectarlo
            cuando se defina la regla de acceso en backend.
        </p>
        <button type="button" class="login-modal-close">Cerrar</button>
    </div>
</div>

<div class="login-modal-backdrop" id="privacyModal" aria-hidden="true">
    <div class="login-modal" role="dialog" aria-modal="true" aria-labelledby="privacyTitle">
        <span class="login-modal-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M12 3 4 6v6c0 5 3.4 8.3 8 10 4.6-1.7 8-5 8-10V6l-8-3Z"/>
                <path d="m9 12 2 2 4-5"/>
            </svg>
        </span>
        <h3 id="privacyTitle">Privacidad y seguridad</h3>
        <p>
            El sistema utiliza sesiones, tokens de acceso y permisos por tablero.
            La política institucional completa puede enlazarse aquí cuando esté disponible.
        </p>
        <button type="button" class="login-modal-close">Cerrar</button>
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');

    togglePassword?.addEventListener('click', function () {
        const isVisible = passwordInput.type === 'text';

        passwordInput.type = isVisible ? 'password' : 'text';
        this.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    });

    loginForm?.addEventListener('submit', function () {
        const button = this.querySelector('.btn-login');
        const buttonText = button?.querySelector('span');

        if (button && buttonText) {
            button.disabled = true;
            buttonText.textContent = 'Validando acceso...';
        }
    });

    function closeModal(modal) {
        if (!modal) return;

        modal.classList.remove('is-visible');
        modal.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('[data-modal]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = document.getElementById(this.dataset.modal);

            if (!modal) return;

            modal.classList.add('is-visible');
            modal.setAttribute('aria-hidden', 'false');
            modal.querySelector('.login-modal-close')?.focus();
        });
    });

    document.querySelectorAll('.login-modal-backdrop').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal || event.target.closest('.login-modal-close')) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('.login-modal-backdrop.is-visible').forEach(closeModal);
    });
</script>
@include('partials.context-help-drawer', ['contextHelpKey' => 'login'])
</body>
</html>