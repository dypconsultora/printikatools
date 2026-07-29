# Printika Tools

Sitio y plataforma de **printikatools.com**: herramientas + comunidad de impresión 3D.
Lo maneja **Adriana** (no programadora). Este archivo es el resumen del proyecto para
arrancar una sesión nueva sin tener que redescubrir todo.

## Cómo hablarle a Adriana

- **Castellano rioplatense, de vos.** Nada de "tú" ni de español neutro.
- **Sin jerga.** Ella no programa: no le sirve "refactoricé el endpoint", le sirve
  "arreglé la pantalla de carga de STL para que no se corte".
- **Explicar el porqué** cuando una decisión la afecta (por ejemplo por qué la
  carpeta de backups no tiene dirección web).
- Cuando algo depende de ella (credenciales, links de PayPal, probar un pago real),
  **decírselo claro y en una lista corta**.

## El negocio

| Plan | Precio | Dónde está definido |
|---|---|---|
| Gratis | $0 | — |
| Pro mensual | ARS 18.000 | `COMUNIDAD_PRECIO_MENSUAL` en `comunidad/inc/bootstrap.php` |
| Pro anual | ARS 180.000 | `COMUNIDAD_PRECIO_ANUAL` (2 meses de regalo) |

> ⚠️ **El plan mensual está oculto** desde el 2026-07-29, a pedido de Adriana y por
> unos días. El interruptor es `COMUNIDAD_MENSUAL_VISIBLE` en `bootstrap.php`: en
> `true` vuelve a aparecer en todo el sitio. Las únicas dos excepciones, porque son
> texto plano y no pasan por PHP, son `llms.txt` y `pricing.md`: ahí hay que reponerlo
> a mano (los dos tienen una nota adentro). **Ella tiene que avisar cuándo volver a
> mostrarlo.** A quien ya lo tenga contratado no le cambia nada.

**Qué entra en cada plan.** Gratis: calculadora y recursos, nada más. Pro: además la
librería STL, todo Mi taller y el soporte por Telegram. Quién es Pro se marca con la
última bandera de cada item en `ui_menu()`, y la pantalla se protege con
`requerir_miembro()`. Las dos cosas van juntas: la bandera pinta el candado, la
función es la que realmente bloquea.

Al tocar un candado no se muestra un cartel genérico: `comunidad/inc/vistazo.php`
tiene, por sección, qué es y para qué sirve, más una captura real de esa pantalla
(`assets/img/vistazo/`, una por idioma). Se regeneran entrando como admin con datos
de ejemplo y sacando la foto sin el menú lateral.

Los precios **siempre** salen de esas constantes. Ya hubo dos bugs por escribirlos
a mano en una pantalla y que quedaran desactualizados.

Cobro por **Mercado Pago** (`preapproval`). El flujo es: se registran → los manda a
pagar → el webhook confirma → se activa la cuenta sola.

**Nadie se registra dos veces.** El que ya tiene cuenta gratis y quiere pasar a pago
usa la misma: el plan elegido se anota en la sesión (`com_plan_pendiente()`) apenas
aparece un `?plan=` y sobrevive al login, al 2FA y a la confirmación del correo;
cuando la persona queda adentro, `com_destino_ingreso()` la manda al checkout en vez
de al panel. El webhook activa por `external_reference = usuario_id:plan`, así que el
pago se le suma **al usuario que ya existía**.
En inglés el cobro iba a ser por PayPal, **los links todavía son de mentira**
(`paypal.com/CAMBIAR-mensual|anual` en `en/`).

## Cómo está armado

- **PHP 8.4 + MySQL** con PDO y consultas preparadas. Sin framework, sin composer.
- **Sin dependencias externas en el navegador**: fuentes, GSAP, jsPDF, todo servido
  desde el propio dominio. La CSP (en `com_cabeceras_seguridad()`) lo obliga.
- **Migraciones perezosas**: `taller_migrar()` en `comunidad/inc/taller.php` crea las
  tablas y agrega columnas nuevas consultando `information_schema`. No hay archivos
  de migración numerados: se agrega el bloque ahí y corre solo la próxima vez que
  alguien entra al panel.

```
printikatools/
├── index.php              La landing (castellano). GSAP + ScrollTrigger.
├── en/index.php           La MISMA landing en inglés (define LANDING_EN e incluye la de arriba)
├── guias/                 Las guías públicas + su sitemap dinámico
│   ├── inc/marco.php      Header/footer de las guías y el traductor al inglés
│   └── <slug>/index.php   Una carpeta por guía publicada
├── en/guias/              Las guías en inglés (mismo truco que la landing)
├── assets/
│   ├── lang/*.json        Diccionarios castellano → inglés
│   ├── js/lib/            GSAP 3.12.5 y ScrollTrigger 3.12.5, propios
│   └── img/               Logos (hay versión castellano e inglés) y favicon
├── comunidad/             LA PLATAFORMA (login, taller, panel)
│   ├── inc/
│   │   ├── bootstrap.php  Config, PDO, cabeceras de seguridad, precios
│   │   ├── auth.php       Sesión, registro, verificación por mail, 2FA
│   │   ├── ui.php         Header, menú lateral, footer, iconos: TODA la pantalla
│   │   ├── taller.php     Migraciones + helpers (meses, idioma)
│   │   ├── correo.php     Plantilla HTML de los mails y envío por SMTP
│   │   ├── mp.php         Mercado Pago
│   │   ├── guias.php      Bloques de las guías (texto / imagen / YouTube)
│   │   └── idioma.php     Traductor de la landing
│   ├── cotizador/         La calculadora pública (código viejo, sesión propia)
│   ├── admin/             Panel de administración (solo rol admin)
│   └── uploads/           STL, recursos, imágenes de guías, backups
├── lib/PHPMailer/         Envío por SMTP
└── config.php             Carga el .env (SMTP). El .env NO va a git.
```

### Lo que no va a git

- `.env` (SMTP) en la raíz
- `comunidad/cotizador/config.php` y `comunidad/config.php` (base de datos)
- Todo `comunidad/uploads/`

## Reglas del hosting (Ferozo) — leer antes de tocar nada

El servidor **se actualiza solo** desde la rama `main` de GitHub, y tarda ~4 minutos.

1. **NUNCA poner un `.htaccess` en la raíz del repositorio.** Rompe el deploy entero:
   deja de bajar los cambios y no avisa. La referencia de lo que iría ahí está en
   `htaccess-referencia.txt`, sin aplicar.
2. Un `.htaccess` **dentro de una subcarpeta sí funciona** (probado en producción:
   `guias/.htaccess`).
3. **El deploy no borra archivos.** Si se borra algo del repo, en el servidor queda.
   Por eso los instaladores viejos son "muñones" que responden 410 en lugar de estar
   borrados.
4. **No restaurar nunca** `cotizador/install.php`, `comunidad/instalar.php` ni
   `cotizador/login.php`. Son muñones 410 a propósito; el original está en el historial
   de git si alguna vez hace falta mirarlo.

## Los dos idiomas

Hay tres mecanismos distintos según qué se traduce, y conviene no confundirlos:

1. **Landing y guías (lo que Google indexa)** → se traduce **en el servidor**.
   Una sola copia del HTML en castellano; `/en/` incluye el mismo archivo con una
   constante definida y le pasa un diccionario JSON por encima
   (`landing_traducir()`, `guias_traducir()`). Así no hay dos landings que mantener.
   - Ojo: el traductor **conserva los espacios de los bordes**. Sin eso salían cosas
     como "Updated on26/07/2026".
2. **La plataforma** → JavaScript (`assets/js/ptools-en.js`) sobre el HTML ya armado.
3. **Textos que arma PHP** (los meses de Estadísticas, por ejemplo) → no los puede
   traducir el JavaScript, así que el idioma viaja en la **cookie `ptools_idioma`**
   y lo lee `taller_idioma()`.

La landing y el cotizador además **detectan el idioma del navegador** la primera vez.

## La portada tiene que verse aunque el JavaScript no corra

La pantalla de carga (`#cargador`) tapa toda la pantalla, y lo que entra animado
arranca en `opacity:0`. Si el JavaScript no corre, eso deja el sitio **invisible**:
pasó, y se nota más en Windows, donde los antivirus de empresa y los bloqueadores
frenan scripts mucho más seguido. Hay tres redes, y **ninguna se puede sacar**:

1. `<html class="sin-js">` y un script en línea que la cambia por `con-js`. El CSS
   `.sin-js` esconde el cargador y muestra todo. Si el JavaScript está bloqueado,
   la clase nunca cambia y el sitio se ve completo.
2. Una animación CSS que a los 9 segundos desvanece el cargador sola, por si el
   JavaScript arranca y se cae antes de sacarlo. No depende de ningún script.
3. Si GSAP no cargó, el propio JavaScript muestra lo que estaba escondido.

**`prefers-reduced-motion` no apaga todo.** Windows lo activa seguido (lo prende
"Ajustar para obtener el mejor rendimiento", el Escritorio remoto y varios
perfiles de ahorro). Antes cortaba todas las animaciones y la página quedaba
muerta. Ahora se conservan las apariciones hechas **solo con opacidad** y se
dejan afuera los movimientos, que son los que de verdad marean.

## Estilo del código

- **Comentarios en castellano**, y explican *por qué*, no *qué*. Sin acentos en los
  comentarios de archivos nuevos (por prolijidad con los editores del hosting), pero
  con acentos en todo lo que ve el usuario.
- Nombres de funciones en castellano, con prefijo por módulo: `com_`, `taller_`,
  `ui_`, `guia_`, `bk_`, `cot_`.
- Las pantallas del panel se arman con `ui_panel_inicio()` / `ui_panel_fin()` y usan
  "recuadros" (`.caja`). Cualquier pantalla nueva tiene que verse igual que el resto.
- **Todo lo que sale a pantalla pasa por `htmlspecialchars()`.**

## Al terminar un cambio

El ciclo que se usa siempre en este proyecto:

1. `php -l` sobre lo tocado.
2. Levantar un PHP local y **mirar la pantalla de verdad** (no confiar en que compiló).
3. Commit con mensaje **en castellano**, explicando el porqué.
4. `git push origin main` → esperar los ~4 minutos → **confirmar en producción**.

Los mensajes de commit van en castellano, en modo `tipo(alcance): que cambio`, y el
cuerpo cuenta el motivo. Ejemplos reales del repo:
`fix(idioma): espacios al traducir y la pastilla de idioma sin estilo`.

## Seguridad — cosas que ya se decidieron y no hay que revertir

- **Credenciales por chat, jamás.** El Access Token de Mercado Pago lo carga ella
  directo en el panel. Si alguna vez lo pega en el chat, hay que decirle que lo
  regenere.
- `comunidad/uploads/backups/` está **cerrada al navegador** con su propio
  `.htaccess`, porque el dump `.sql` tiene los correos y las contraseñas cifradas de
  todos los usuarios. Se bajan sólo desde `admin/backups.php`, con sesión de admin, y
  sólo nombres que matcheen
  `/^printikatools-(base|archivos)-[\w-]+\.(sql|zip)$/`.
- Los archivos grandes (STL) **suben en pedazos de 2 MB** (`admin/stl_trozo.php`)
  porque el hosting corta las subidas largas. Tope: 200 MB.
- El webhook de Mercado Pago **valida la firma** antes de activar nada.

## SEO

- `sitemap.xml` es un **índice** que apunta a `sitemap-paginas.xml` (fijo) y a
  `guias/sitemap.php` (dinámico: se arma solo con las guías publicadas). Publicar una
  guía nueva **no requiere tocar nada** ni avisarle a Search Console.
- Las guías llevan JSON-LD (`Article`, `FAQPage`, `BreadcrumbList`, `CollectionPage`)
  y `hreflang` entre castellano e inglés.
- Hay un `llms.txt` en la raíz para los buscadores con IA.

## Pendientes conocidos

**De ella (frenan cosas):**
- Links reales de PayPal para la landing en inglés (US$15 / US$150).
- Probar una subida de STL real de los archivos que antes se cortaban.
- Hacer un pago de prueba de punta a punta y confirmar que el webhook activa la cuenta.

**Opcionales:**
- Pedir indexación en Search Console de `/guias/` y de la primera guía.
- Enriquecer la primera guía con un error común y una anécdota propia.
