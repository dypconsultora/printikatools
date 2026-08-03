# Printika Tools

Sitio y plataforma de **printikatools.com**: herramientas + comunidad de impresión 3D.
Lo maneja **Adriana** (no programadora). Este archivo es el resumen del proyecto para
arrancar una sesión nueva sin tener que redescubrir todo.

> ⚠️ **Este archivo es público**: se sirve en `printikatools.com/CLAUDE.md` y no se
> puede tapar (un `.htaccess` en la raíz rompe el deploy entero). Escribir acá las
> reglas y el porqué, **nunca** claves, rutas exactas de lo sensible ni nada que le
> sirva a alguien que quiera entrar. Las notas privadas de Adriana van a su bóveda de
> Obsidian, que está fuera del repositorio a propósito.

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

> **Los tres planes están a la venta.** El plan mensual se puede sacar y reponer con
> `COMUNIDAD_MENSUAL_VISIBLE` en `bootstrap.php`; ese interruptor solo alcanza para
> todo lo que arma PHP. Las dos excepciones son `llms.txt` y `pricing.md`, que son
> texto plano: ahí hay que editarlo a mano en los dos sentidos. A quien ya lo tenga
> contratado no le cambia nada en ningún caso. Estuvo oculto del 2026-07-29 al
> 2026-08-03, a pedido de Adriana.

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
│   │   ├── bootstrap.php  Config, PDO, cabeceras, precios, firma de enlaces
│   │   ├── auth.php       Sesión, registro, verificación por mail, 2FA, plan pendiente
│   │   ├── ui.php         Header, menú lateral, footer, iconos: TODA la pantalla
│   │   ├── taller.php     Migraciones + helpers (meses, idioma, plurales)
│   │   ├── correo.php     Plantilla HTML de los mails y envío por SMTP
│   │   ├── vistazo.php    Qué se le muestra al plan gratis en cada candado
│   │   ├── mp.php         Mercado Pago
│   │   ├── guias.php      Bloques de las guías (texto / imagen / YouTube)
│   │   └── idioma.php     Traductor de la landing
│   ├── cotizador/         LA CALCULADORA (ver abajo: es también la del panel)
│   │   └── impresoras.php Ficha de cada impresora: watts, precio, vida útil
│   ├── admin/             Panel de administración (solo rol admin)
│   └── uploads/           STL, recursos, imágenes de guías, backups
├── baja.php               Darse de baja de novedades (enlace firmado del correo)
├── novedades.php          Recibe el correo del banner de la portada
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
   Por eso lo que se dio de baja quedó como archivos que responden 410 en vez de
   borrarse.
4. **No revivir nada que hoy responda 410.** Están así a propósito; si alguna vez hace
   falta mirar el original, está en el historial de git.

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

## Las pantallas, y por qué son como son

Cosas que del código solo no se deducen, y que si se "arreglan" sin saber se rompen.

**La calculadora del panel ES el cotizador.** `comunidad/calculadora.php` son 25
líneas que lo embeben en un marco con `?panel=1`. **No hay dos calculadoras**: todo
cambio sale en las dos a la vez. Para probar algo adentro antes de publicarlo se
envuelve en `if ($enPanel)`. Dentro del panel el usuario es PRO automáticamente;
afuera hay una prueba abierta hasta el 2026-09-02 (`PRO_TRIAL_HASTA`), y cuando venza
las secciones PRO se bloquean solas con la clase `pro-locked`.

**Las impresoras** tienen su ficha en `comunidad/cotizador/impresoras.php`: watts,
precio, vida útil y mantenimiento anual, **en pesos**, relevados por Adriana. Es el
único lugar donde están esos números; las dos listas de la pantalla (Electricidad y
Depreciación) se arman desde ahí, así que no pueden desincronizarse. Elegir la
impresora en cualquiera de las dos completa la otra. Los valores en dólares y euros
se calculan solos con `COT_USD_ARS` / `COT_EUR_ARS`, que **hay que actualizar cada
tanto**: el selector de moneda de la calculadora solo cambia el símbolo, no convierte.

**Recursos** son tres solapas, **todas de videos de YouTube**: YouTube, Plataforma y
Mantenimientos. Los PDF se retiraron de la pantalla, pero la tabla `recursos_pdf` y
los archivos **siguen ahí** (sacar la pantalla se deshace; borrar los datos no).
**Plataforma se ve con cualquier plan**, incluido el gratuito: son los videos de cómo
usar el sistema. Por eso ahí el selector de "quién lo puede ver" se esconde y el
servidor fuerza el acceso a `todos` aunque llegue otra cosa.

**Stock de materiales** es **solo visual**: no toca los presupuestos ni la calculadora.
Antes marcar un presupuesto como vendido descontaba gramos solo; se sacó porque a ella
le desarmaba una planilla que lleva a ojo. La columna "Disponible" es **cuántos rollos
hay** de esa marca, tipo y color, no gramos. Las columnas de peso quedaron en la tabla
sin usarse, a propósito.

**Librería STL**: siempre **cuatro por fila** (abajo de 1200 px baja de a una). El
orden lo decide Adriana arrastrando las filas en Cargar STL, y se guarda en la columna
`orden`; la fecha solo desempata a los que nunca se movieron.

**Emails captados** (`admin/emails.php`) junta las direcciones de las **tres** puertas:
el popup del cotizador, el banner de la portada y **el registro de una cuenta**. Las tres
dan de alta con la misma función, `taller_captar_email()`, que además guarda de dónde
salió cada una en la columna `origen`. Esa columna no es decorativa: el correo de
bienvenida invita a crearse una cuenta gratis, así que **a los de `origen = 'registro'`
no se les manda** — el panel los muestra como "Ya tiene cuenta", el contador de "sin
bienvenida" los deja afuera y, si igual quedan marcados en un envío, se saltean. Si
alguien dejó el mail en el cotizador y después se registró, su origen pasa a `registro`
y **no vuelve atrás**. A los otros dos orígenes les llega el correo de bienvenida con los
planes, en el idioma en que estaba la persona, **una sola vez**. En el pie de ese correo hay un
enlace de baja **firmado** (`com_baja_token()`): al tocarlo la dirección se borra sola.
Los correos de la cuenta (confirmar, código de acceso) **no** llevan baja.

**Borrar una cuenta desde Suscripciones** es definitivo y se lleva todo: la base tiene
las claves en cascada (presupuestos, clientes, productos, stock, ventas, suscripciones) y
la pantalla además le saca la dirección de Emails captados y el logo del disco. **La
propia cuenta no se puede borrar**, ni marcándola ni forzando el id: quedaría el panel sin
nadie que pueda entrar. Detalle de armado: el formulario del borrado vive **fuera** de la
tabla y las tildes se le enganchan con `form="borrar-lote"`. Si envolviera la tabla, los
formularios que ya tiene cada fila (activar, desactivar, rol) quedarían anidados y el
navegador los tira.

**El aviso de correo mal escrito** (`com_email_sugerencia()`) usa dos criterios distintos,
y conviene no unificarlos: si el nombre del dominio está bien y falla el final
(`gmail.con`, `gmail.co`) avisa, **pero si el final es un país de verdad (`yahoo.es`) no
toca nada**; y si el nombre está mal, compara con `levenshtein` y tolerancia 2, que es lo
que cuesta una transposición tipo "gmial". Hay una lista de dominios reales que caen cerca
(`ymail.com`, `email.com`) para no acusarlos. El aviso principal es en vivo en el
formulario; el del servidor es para navegadores con el JavaScript bloqueado y **frena una
sola vez**: si mandan la misma dirección de nuevo, pasa. Un dominio raro pero real no
puede quedar afuera por un aviso.

**La baja de la suscripción no corta el acceso.** El botón está en Configuración > Tu
suscripción. Primero se le manda a Mercado Pago la orden de cancelar el `preapproval`, y
**recién si MP la acepta** se marca de nuestro lado: al revés, la persona se iría
convencida de que no le cobran más y le seguiría llegando el débito. Lo que se marca es
`cancelada_en`; el plan sigue andando hasta `hasta`, que es hasta donde pagó, y ese día
cae solo porque `plan_usuario()` ya exige `hasta >= hoy`. **No convertirlo en un corte
inmediato**: sería quedarse con días pagos —con el anual, con meses— y es lo contrario
de lo que dicen los Términos. El webhook hace lo mismo cuando MP avisa "cancelled".
El número de la suscripción en MP vive en `suscripciones.mp_preapproval`; las viejas lo
tienen adentro de `notas` y `mp_preapproval_de()` lo rescata de ahí.

**Términos y Condiciones** (`terminos/index.php`, y `/en/terminos/` con el mismo truco que
la landing) es la única pantalla donde el texto en inglés está escrito **adentro del
archivo**, al lado del castellano, en vez de pasar por `guias-en.json`. El diccionario
traduce frase por frase y un texto legal largo se desincroniza al primer retoque. Usa el
marco de las guías (`guia_inicio()` / `guia_fin()`), así que hereda encabezado y pie.
La fecha de "Última actualización" es la variable `$actualizado`, **a mano**: tiene que
decir cuándo cambió el texto de verdad, no la fecha de hoy. El enlace vive en el bloque
"Plataforma" de los tres pies (portada, guías y plataforma).

**Mailing** (`admin/mailing.php` + `inc/mailing.php`) manda a la lista de Emails captados.
Tres decisiones que **no hay que "simplificar"**: el envío va de a tandas de 15 pedidas
desde el navegador, porque el hosting corta los procesos largos y 200 correos en un
pedido no terminan nunca; la cola vive en `mailing_envios`, así que si se cierra la
pestaña el envío se retoma donde iba y a nadie le llega dos veces; y la conexión al SMTP
se abre **antes** del lote con `smtpConnect()`, porque si el fallo aparece adentro del
`try` de cada correo se marcan todos como "error" y la lista queda quemada sin poder
reintentarla. Tres fallos seguidos cortan la tanda: eso ya no es una dirección mala, es
el servidor. Todos los correos llevan el enlace de baja firmado y las cabeceras
`List-Unsubscribe`. **Ojo con el CSS del panel**: `.grupo` ya existe en `ui.php` (los
títulos del menú lateral) y usarla acá deformaba el menú; por eso las clases propias van
con prefijo `ml-`.

**Al guardar algo que además manda un correo, guardar primero.** Un correo que falla se
reenvía desde el panel; una dirección perdida no se recupera. Ya pasó al revés.

## El celular

- **La plataforma**: el menú lateral es un **cajón** que entra desde el costado, con
  una barra fija arriba. Antes se apilaba encima del contenido y había que bajar
  821 px para llegar al título.
- **La portada**: los enlaces de sección no entran, así que hay un menú desplegable
  con todo (incluidos Guías, Precios, FAQ y la Calculadora, que antes desaparecían).
  El corte está en **1240 px, no en 1100**: en castellano las palabras son más largas
  y el encabezado se desbordaba entre 1101 y 1250.
- Al tocar cualquier cosa, **medir contra la base de datos, no contra la pantalla**.
  El arrastre de la Librería STL movía la fila a la vista pero no guardaba, y eso solo
  se vio comparando con la base.

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
- La carpeta de copias de seguridad está **cerrada al navegador** con su propio
  `.htaccess`, y las descargas pasan por el panel con sesión de admin y con el nombre
  del archivo validado. **No aflojar ninguna de las tres cosas**: lo que hay adentro
  es lo más sensible del sistema.
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
- Confirmar si el mantenimiento de las impresoras es anual: su planilla dice anual y
  los números solo cierran así (2–9% del precio por año), pero al pasarla dijo
  "mensual". Cargado como **anual**.
- **Cambiar el remitente de los correos** al `.env` **del servidor**. La casilla
  `consultas@printikatools.com` ya existe y el sitio ya la muestra como correo de
  contacto, pero los correos siguen **saliendo** desde `consultas@printika3d.com`, y ese
  desajuste es de las señales de spam más fuertes que hay. El `.env` no va a git ni lo
  toca el deploy: hay que editarlo en el hosting (`SMTP_USER`, `SMTP_PASS`, `MAIL_FROM`,
  `MAIL_FROM_NAME`, `MAIL_TO`, `MAIL_TO_NAME` y `ALLOWED_ORIGIN`). La clave de la casilla
  la carga ella, nunca por chat.

**Ofrecido y esperando su respuesta:**
- Traducir el **panel de administración**, que sigue entero en castellano (~100
  textos). No se hizo porque es la única que lo usa.
- Una pantalla en el panel para que ella misma edite precios de impresoras y el tipo
  de cambio, sin depender de nadie.
- Achicado automático de la foto de portada de los STL al subirla (hoy hay que
  llevarla a 800×600 a mano; el sistema no la achica).

**Opcionales:**
- Pedir indexación en Search Console de `/guias/` y de la primera guía.
- Enriquecer la primera guía con un error común y una anécdota propia.

## La bóveda de Adriana (Obsidian)

Sus notas viven en `/Users/adrianavallay/Desktop/DyP Web/dyp-wiki/dyp-wiki`, **fuera
del repositorio a propósito**: todo lo que está adentro del repo se publica en
internet, y ahí anota cosas del negocio.

No se lee sola ni hay que leerla siempre. Si ella menciona una idea o un pendiente que
tiene anotado, se le pide la ruta o se lee esa carpeta. Reparto:

- **Ahí**: sus ideas, decisiones de negocio, pendientes que dependen de ella, datos.
- **Acá**: cómo funciona el sistema y por qué. **Lo mismo no va en los dos lados**, o
  se desincroniza.

Si ella resuelve algo que estaba en su lista de pendientes, conviene recordarle que lo
tache en `001 Pendientes.md`.

## Cómo trabajar en este proyecto

Lo que funcionó en las sesiones anteriores, y conviene repetir:

- **Probar mirando, no suponiendo.** Levantar un PHP local, abrir la pantalla y
  medirla. Varios errores aparecieron solo así: el campo de 21 px de alto en el
  celular, el arrastre que no guardaba, la clase CSS repetida que deformaba otra cosa.
- **Barrer en vez de ir de a uno.** Cuando ella reporta un texto sin traducir o una
  pantalla que se desborda, buscar TODOS los casos iguales de una pasada. Siempre
  aparecen más.
- **Limpiar los datos de prueba** antes de cerrar, y no dejar servidores corriendo.
- **Nunca mandar correos de prueba a direcciones inventadas**: salen de verdad por su
  SMTP y le llegan avisos falsos a la casilla.
- Al terminar cada cosa: `php -l`, mirar la pantalla, commit en castellano explicando
  **el porqué**, `git push origin main`, esperar los ~4 minutos y **confirmar en
  producción**.
