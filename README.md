# LAB DEMO

Plataforma de demostración para monitorear la operación de un laboratorio clínico con **datos totalmente ficticios**. Incluye seguimiento de órdenes y estudios, productividad, toma de muestra, analítica y tableros para pantallas operativas.

> **Demostración exclusivamente local:** no está diseñada para exponerse en Internet ni para manejar información clínica real.

## Funcionalidades

- Dashboard de inicio con módulos e indicadores.
- Tableros de órdenes, estudios, consulta externa y productividad.
- Toma de muestra: tiempos de espera, atención y desempeño por puerta.
- Analítica de citas, pacientes y tiempos operativos.
- Pantallas de seguimiento para TV.
- Reportes de trazabilidad en PDF.
- Sesión demo sin credenciales clínicas y operaciones administrativas de escritura bloqueadas.

## Tecnologías

PHP 8.2+ · Laravel 12 · JavaScript · HTML/CSS · Vite 6 · Composer · Node.js/npm · DOMPDF

## Capturas

Las capturas muestran exclusivamente información de ejemplo.

### Dashboard principal

![Dashboard principal](docs/capturas/02-dashboard.png)

### Operación y analítica

| Toma de muestra | Analítica |
|---|---|
| ![Toma de muestra](docs/capturas/04-toma-muestra.png) | ![Analítica](docs/capturas/06-analitica.png) |

| Pantalla para TV | Estudios |
|---|---|
| ![Pantalla TV](docs/capturas/05-pantalla-tv.png) | ![Estudios](docs/capturas/03-estudios.png) |

### Acceso

![Inicio de sesión](docs/capturas/01-login.png)

## Instalación local

Requisitos: **PHP 8.2 o superior**, Composer, Node.js y npm, con las extensiones de PHP requeridas por Laravel.

```bash
git clone https://github.com/Di3Ckz01/lab-software-demo.git
cd lab-software-demo
composer install
npm ci
npm run build
```

Durante `composer install` se crean automáticamente las carpetas de `storage/` y `bootstrap/cache/` que Laravel necesita.

Crea tu archivo local de configuración **sin subirlo a Git**:

```powershell
# Windows PowerShell
Copy-Item .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8001
```

En macOS/Linux puedes usar `cp .env.example .env` en lugar de `Copy-Item`.

Abre **http://127.0.0.1:8001/demo** y elige «Entrar a la demostración». Para navegar desde el login, usa su botón «Entrar a la demostración».

Configuración indispensable en `.env`:

```dotenv
APP_ENV=local
APP_DEBUG=false
APP_URL=http://127.0.0.1:8001
GO_API_BASE_URL=
GO_API_USE_MOCK=true
VCARE_DEMO_ENABLED=true
```

**No agregues credenciales reales, tokens ni datos de pacientes.** El acceso demo verifica el entorno local, la dirección loopback y que la URL del backend esté vacía; las operaciones de escritura protegidas responden 403.

## Pruebas

```bash
php artisan test --filter=DemoSecurityTest
```

En la validación local previa se aprobaron **12 pruebas y 19 aserciones**. Este resultado no equivale a una auditoría de seguridad integral ni significa que la suite se ejecute automáticamente en GitHub.

## Avisos y licencias

- **Código específico de esta aplicación:** no se ha otorgado una licencia abierta de reutilización. Se mantienen reservados los derechos de sus titulares; la autorización para distribuirlo públicamente y cualquier atribución correspondiente deben verificarse por separado. El campo `license: proprietary` de Composer **no** sustituye esa autorización.
- **Laravel y otras dependencias:** conservan sus propias licencias, independientes de la aplicación.
- **Tipografía Poppins:** se incluye bajo **SIL Open Font License 1.1**; consulta el texto íntegro y aviso de derechos en [public/fonts/poppins/OFL.txt](public/fonts/poppins/OFL.txt), procedente de [Google Fonts](https://github.com/google/fonts/blob/main/ofl/poppins/OFL.txt).
- **Manuales y logotipos institucionales:** no se distribuyen en esta edición. La ayuda indica cuándo no está disponible el PDF.

Este repositorio **no es un servicio desplegado** ni ofrece una demo pública accesible por Internet.
