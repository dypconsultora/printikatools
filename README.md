# Printika Tools

Suite de herramientas de Printika 3D — https://printikatools.com

Primera herramienta: el **Cotizador 3D**, en `/comunidad/cotizador`.

## Estructura

```
printikatools/
├── index.html            Landing "Próximamente"
├── assets/
│   ├── css/              Estilos de la landing
│   └── img/              Logos (Printika Tools provisional + Printika 3D claro/oscuro)
├── lib/PHPMailer/        Envío de mails por SMTP
├── config.php            Loader del .env (SMTP) — sin secretos, va a git
├── .env                  Credenciales SMTP — NO va a git, subir a mano
└── comunidad/
    ├── index.html        Portada de la comunidad
    └── cotizador/        El cotizador (FREE + PRO + prueba + popup)
        ├── config.php    Credenciales de la base — NO va a git, subir a mano
        └── ...
```

## Despliegue en el servidor

1. Clonar/pullear este repo en la carpeta pública de printikatools.com.
2. Subir a mano (no están en git):
   - `.env` en la raíz del proyecto (credenciales SMTP)
   - `comunidad/cotizador/config.php` (credenciales de la base de datos)
3. Si la base es nueva: entrar a `/comunidad/cotizador/install.php` para crear
   las tablas y definir la clave PRO. Si se reutiliza la base de printika3d.com,
   no hace falta instalar nada (comparte usuarios y cotizaciones guardadas).
4. Probar `/comunidad/cotizador/chequeo.php` (conexión a la base) y borrarlo después.

## URLs

- `https://printikatools.com/` — landing Printika Tools
- `https://printikatools.com/comunidad/` — portada de la comunidad
- `https://printikatools.com/comunidad/cotizador/` — cotizador FREE
  (prueba PRO por tiempo limitado con contador)
- `https://printikatools.com/comunidad/cotizador/login` — acceso PRO
- Usuario PRO por defecto: `printika` (clave definida en la instalación)

## Notas

- La fecha de fin de la prueba PRO se cambia en `comunidad/cotizador/auth.php`
  (`PRO_TRIAL_HASTA`).
- El popup de novedades envía los emails a la casilla configurada en `.env`
  (`MAIL_TO`) vía `comunidad/cotizador/suscribir.php`.
- El cotizador requiere PHP + MySQL (no funciona con el `http-server`
  estático de desarrollo; para probarlo local usar `php -S 127.0.0.1:8765`
  si hay PHP instalado, o probar directo en el servidor).
