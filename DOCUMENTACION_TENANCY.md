# Documentación de Arquitectura Multitenant Laravel

Esta documentación detalla la arquitectura implementada utilizando `stancl/tenancy` en un proyecto Laravel con Jetstream/Fortify. El objetivo principal es tener una aplicación central (landing page) y múltiples tenants (clientes/empresas), donde **el inicio de sesión y el dashboard** residen exclusivamente en los dominios de los tenants.

## 1. Estructura General

La arquitectura divide la aplicación en dos contextos:

1.  **Contexto Central (`routes/web.php`)**:
    - Dominios: `landing.com`, `localhost`
    - Funcionalidad: Landing page, registro de empresas (tenants), administración global.
    - **No tiene acceso** a rutas de login de usuarios (Jetstream) por defecto.

2.  **Contexto Tenant (`routes/tenant.php`)**:
    - Dominios: `cliente1.landing.com`, `cliente2.landing.com`
    - Funcionalidad: Inicio de sesión (Login), Dashboard, lógica de negocio del cliente.
    - **Base de datos separada** para cada tenant (aislamiento de datos).

---

## 2. Configuración de Rutas y Login (Jetstream/Fortify)

El requerimiento principal es que **el Login y el Dashboard de Jetstream existan SOLO para los tenants**, y no en la página central.

Para lograr esto, seguimos estos pasos:

### Paso 1: Evitar el registro automático de rutas
Por defecto, Jetstream y Fortify registran sus rutas (`/login`, `/register`, `/dashboard`) de forma global. Debemos deshabilitar esto para tener control manual.

1.  **Fortify**: En `app/Providers/FortifyServiceProvider.php`, método `register()`:
    ```php
    public function register(): void
    {
        // Ignora las rutas por defecto para que no aparezcan en el dominio central
        Fortify::ignoreRoutes();
    }
    ```

2.  **Jetstream**: En `app/Providers/JetstreamServiceProvider.php`, método `boot()`:
    ```php
    public function boot(): void
    {
        $this->configurePermissions();

        // Ignora las rutas de Jetstream/Livewire por defecto
        Jetstream::ignoreRoutes();
        // ...
    }
    ```

### Paso 2: Registrar las rutas manualmente en `routes/tenant.php`
Ahora que las rutas "no existen", debemos crearlas explícitamente dentro del grupo de middleware de los tenants.

En `routes/tenant.php`:

```php
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // ... otras rutas tenant ...

    // 1. Incluir rutas de Fortify (Login, Registro, etc.)
    require base_path('vendor/laravel/fortify/routes/routes.php');

    // 2. Incluir rutas de Jetstream (Dashboard, Perfil, Livewire)
    Route::middleware([
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
    ])->group(function () {
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');

        require base_path('vendor/laravel/jetstream/routes/livewire.php');
    });
});
```

**Resultado**: Si intentas acceder a `/login` en el dominio central (ej. `localhost:8000/login`), obtendrás un error 404 (o la ruta no existirá). Si accedes a un subdominio (ej. `tenant1.localhost:8000/login`), verás el formulario de login.

---

## 3. Guía de Instalación para Nuevo Proyecto

Si necesitas replicar esta arquitectura desde cero, sigue estos pasos:

### A. Instalación Básica
1.  Crear proyecto Laravel:
    ```bash
    laravel new mi-proyecto
    cd mi-proyecto
    ```
2.  Instalar Jetstream (con Livewire):
    ```bash
    composer require laravel/jetstream
    php artisan jetstream:install livewire
    npm install && npm run build
    php artisan migrate
    ```

### B. Instalar Tenancy
Sigue la documentación oficial (https://tenancyforlaravel.com/docs/v3/quickstart/), resumida aquí:

1.  Instalar paquete:
    ```bash
    composer require stancl/tenancy
    ```

2.  Publicar archivos de configuración:
    ```bash
    php artisan tenancy:install
    ```

3.  Registrar el ServiceProvider (si no es automático en Laravel 11+, verificar en `bootstrap/providers.php` o `config/app.php`).

4.  Configurar el modelo `Tenant`:
    Mueve `app/Models/Tenant.php` (si se creó) o edítalo. Asegúrate de que use las columnas personalizadas si las necesitas (como 'plan', 'email', etc.).
    ```php
    // app/Models/Tenant.php
    use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
    use Stancl\Tenancy\Contracts\TenantWithDatabase;
    use Stancl\Tenancy\Database\Concerns\HasDatabase;
    use Stancl\Tenancy\Database\Concerns\HasDomains;

    class Tenant extends BaseTenant implements TenantWithDatabase
    {
        use HasDatabase, HasDomains;
        // ...
    }
    ```

5.  Configurar `config/tenancy.php`:
    - Define tu modelo de tenant: `'tenant_model' => App\Models\Tenant::class,`
    - Define tus dominios centrales:
      ```php
      'central_domains' => [
          '127.0.0.1',
          'localhost',
          'midominio.com',
      ],
      ```

### C. Migraciones
La arquitectura separa las migraciones en dos carpetas:
1.  `database/migrations`: Tablas de sistema central (incluyendo la tabla `tenants` y `domains`).
2.  `database/migrations/tenant`: Tablas de la aplicación del cliente (users, posts, etc.).

**Importante**:
- Mueve la migración `create_users_table.php` (y `password_resets`, etc.) de `database/migrations` a `database/migrations/tenant`. Esto es crucial porque **los usuarios pertenecen a los tenants**, no al sistema central (en esta arquitectura).
- Mueve también las migraciones de sesiones, cache, y las tablas de Jetstream/Fortify (`two_factor_authentication`, etc.) a `database/migrations/tenant`.

### D. Ejecución
1.  Correr migraciones centrales:
    ```bash
    php artisan migrate
    ```

2.  Crear un tenant (puedes usar `php artisan tinker`):
    ```php
    $tenant = App\Models\Tenant::create(['id' => 'foo']);
    $tenant->domains()->create(['domain' => 'foo.localhost']);
    ```
    *Al crear el tenant, el paquete ejecutará automáticamente las migraciones de `database/migrations/tenant` dentro de la nueva base de datos del tenant.*

3.  Acceder:
    - Ve a `http://foo.localhost:8000/login`.
    - Registra un usuario (este usuario vivirá solo en la BD del tenant 'foo').

---

## 4. Revisión del Código Actual
Basado en la revisión del proyecto actual:
- La configuración de `routes/tenant.php` es **correcta** para aislar el login.
- `FortifyServiceProvider` y `JetstreamServiceProvider` tienen correctamente configurado `ignoreRoutes()`.
- Se recomienda asegurar que las migraciones de `users` estén en la carpeta `tenant` para evitar que se creen en la base de datos central innecesariamente.
