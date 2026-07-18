# Calculadora de Costos 3D — Instalación en el servidor

App en PHP + MySQL. Las cotizaciones se guardan **centralizadas en el servidor** y el
acceso está protegido por contraseña.

## Archivos del proyecto

| Archivo | Para qué sirve |
|---|---|
| `index.php` | La calculadora (protegida por login). |
| `login.php` / `logout.php` | Ingreso y salida. |
| `api.php` | Guarda / lista / borra cotizaciones (JSON). |
| `config.php` | **El único que tenés que editar**: datos de la base de datos. |
| `db.php`, `auth.php` | Conexión y seguridad (no tocar). |
| `install.php` | Instalador. Se usa una vez y **se borra**. |
| `.htaccess` | Configuración de Apache. |

## Pasos

### 1. Crear la base de datos en el hosting
En cPanel → **Bases de datos MySQL**:
1. Creá una base nueva (ej. `dyp_calc3d`).
2. Creá un usuario MySQL con su contraseña.
3. Asigná el usuario a la base con **TODOS los privilegios**.

Anotá: nombre de la base, usuario y contraseña.

### 2. Editar `config.php`
Abrí `config.php` y completá:

```php
define('DB_HOST', 'localhost');        // normalmente "localhost"
define('DB_NAME', 'dyp_calc3d');       // tu base
define('DB_USER', 'dyp_usuario');      // tu usuario MySQL
define('DB_PASS', 'tu_contraseña');    // contraseña del usuario MySQL
```

También cambiá `APP_SECRET` por cualquier texto largo y aleatorio.

> En muchos hosting el nombre real lleva un prefijo, ej. `cpaneluser_dyp_calc3d`.
> Usá el nombre completo que muestra cPanel.

### 3. Subir los archivos
Subí **todos** los archivos de esta carpeta por FTP o el Administrador de Archivos,
dentro de la carpeta pública. Por ejemplo:
- `public_html/calculadora/` → quedará en `tu-sitio.com/calculadora/`

> Asegurate de subir también los archivos ocultos `.htaccess`.

### 4. Ejecutar el instalador
Entrá en el navegador a:

```
https://tu-sitio.com/calculadora/install.php
```

- Verás un chequeo (PHP, MySQL, conexión).
- Definí la **contraseña** de acceso a la calculadora.
- Tocá **Instalar**: crea las tablas y guarda la contraseña.

### 5. Borrar el instalador
Por seguridad, **borrá `install.php`** del servidor después de instalar.

### 6. Listo
Entrá a `https://tu-sitio.com/calculadora/`, ingresá la contraseña y a usar.
Las cotizaciones que guardes quedan en el servidor y las ves desde cualquier dispositivo.

---

## Notas
- **Cambiar la contraseña más adelante:** volvé a subir `install.php`, abrilo, ingresá la
  contraseña actual + la nueva, y borralo otra vez.
- **Requisitos:** PHP 7.0 o superior y MySQL/MariaDB (cualquier hosting compartido típico).
- Las tablas que se crean son `cotizaciones` (los presupuestos) y `app_config` (la contraseña).
