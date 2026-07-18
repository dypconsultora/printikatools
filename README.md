# Printika Tools

Suite de herramientas de Printika 3D. Primera herramienta: el **Cotizador 3D**.

## Estructura

```
printikatools/
├── index.html         Landing "Próximamente"
├── assets/
│   ├── css/           Estilos de la landing
│   └── img/           Logos (Printika Tools provisional + Printika 3D claro/oscuro)
├── lib/PHPMailer/     Envío de mails por SMTP
├── config.php         Loader del .env (SMTP) — sin secretos, va a git
├── .env               Credenciales SMTP — NO va a git, subir a mano
└── cotizador/         El cotizador (FREE + PRO + prueba + popup)
    ├── config.php     Credenciales de la base — NO va a git, subir a mano
    └── ...
```

## Despliegue en el servidor

1. Clonar/pullear este repo en la carpeta pública del sitio.
2. Subir a mano (no están en git):
   - `.env` en la raíz del proyecto (credenciales SMTP)
   - `cotizador/config.php` (credenciales de la base de datos)
3. Si la base es nueva: entrar a `/cotizador/install.php` para crear las
   tablas y definir la clave PRO. Si se reutiliza la base de printika3d.com,
   no hace falta instalar nada (comparte usuarios y cotizaciones guardadas).
4. Probar `/cotizador/chequeo.php` (conexión a la base) y borrarlo después.

## URLs

- `/` — landing Printika Tools
- `/cotizador/` — versión FREE (prueba PRO por tiempo limitado con contador)
- `/cotizador/login` — acceso PRO con usuario y clave
- Usuario PRO por defecto: `printika` (clave definida en la instalación)

## Notas

- La fecha de fin de la prueba PRO se cambia en `cotizador/auth.php`
  (`PRO_TRIAL_HASTA`).
- El popup de novedades envía los emails a la casilla configurada en `.env`
  (`MAIL_TO`) vía `cotizador/suscribir.php`.
- El cotizador requiere PHP + MySQL (no funciona con el `http-server`
  estático de desarrollo; para probarlo local usar `php -S 127.0.0.1:8765`
  si hay PHP instalado, o probar directo en el servidor).
