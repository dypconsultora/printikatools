# Calculadora 3D (cotizador público)

> **Documento histórico.** Describía la instalación del cotizador cuando era un
> producto aparte, con su propio ingreso "PRO" por usuario y contraseña.
>
> Desde el **26/07/2026** eso ya no existe:
>
> - `install.php` y `login.php` son sellos inertes (responden *410 Gone*). No
>   restaurarlos: el código viejo está en el historial de git.
> - El cotizador quedó como **calculadora pública y gratuita** en
>   `/comunidad/cotizador/`, sin ninguna puerta de ingreso.
> - El acceso pago real vive en la plataforma (`/comunidad`), con planes,
>   suscripciones y presupuestos separados por usuario.
> - Embebida en el panel (`?panel=1`), la calculadora usa
>   `comunidad/calculadora_api.php`, que sí guarda cada presupuesto en la
>   cuenta de quien lo hizo.
>
> La prueba que desbloquea las funciones PRO para cualquier visitante vence el
> **2 de septiembre de 2026** (`PRO_TRIAL_HASTA`, en `auth.php`). Al vencer,
> los candados vuelven solos.
