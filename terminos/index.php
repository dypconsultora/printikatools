<?php
/**
 * Terminos y Condiciones: https://printikatools.com/terminos/
 *
 * Es la pagina legal del sitio. Cubre lo que mas preguntan y lo que mas
 * problemas trae si no esta escrito: como se renueva la suscripcion, como se
 * da de baja, y que pasa con los datos de la tarjeta (spoiler: no los tenemos
 * nosotros, los tiene la plataforma de pago).
 *
 * Por que el texto esta aca adentro y no en el diccionario de traduccion:
 * guias-en.json traduce frase por frase, y para un texto largo eso se
 * desincroniza al primer retoque. Aca cada seccion esta escrita entera en los
 * dos idiomas, una al lado de la otra, asi se ve de una si falta traducir algo.
 *
 * Al cambiar el texto, actualizar $actualizado: la fecha tiene que decir la
 * verdad, es lo primero que mira alguien que reclama.
 */
require_once dirname(__DIR__) . '/guias/inc/marco.php';

$base = 'https://printikatools.com';
$en   = guias_en();

// Ultima modificacion real del texto (no la fecha de hoy)
$actualizado = '2026-08-03';
$actualizado_txt = $en
    ? date('F j, Y', strtotime($actualizado))
    : date('d/m/Y', strtotime($actualizado));

$mail     = 'consultas@printikatools.com';
$telegram = 'https://t.me/+N5f7IcWPXihhMWQx';
$planes   = ($en ? '/en/' : '/') . '#planes';

$SECCIONES = $en ? [

  ['id' => 'que-es', 'titulo' => '1. What Printika Tools is', 'html' => <<<HTML
    <p>Printika Tools (<strong>printikatools.com</strong>) is a website and a web platform with
       tools for 3D printing workshops: a cost calculator, quotes, clients, materials stock,
       sales, statistics, an STL file library and training content. It is operated from the
       Argentine Republic.</p>
    <p>By creating an account or using the site you accept these Terms and Conditions. If you do
       not agree with them, please do not use the service.</p>
HTML],

  ['id' => 'cuenta', 'titulo' => '2. Your account', 'html' => <<<HTML
    <ul>
      <li>The public calculator can be used without an account. Everything else needs one.</li>
      <li>You must be at least 18 years old and give real details. We verify the email address:
          without confirming it, the account does not become active.</li>
      <li>The account is personal. One account is for one person or one workshop, and it may not
          be shared, lent or transferred.</li>
      <li>You are responsible for your password and for what happens with your account. We
          recommend turning on two-factor by email, which is available in Settings.</li>
      <li>If you notice that someone else got into your account, tell us right away.</li>
      <li>You can ask us to close your account whenever you want.</li>
    </ul>
HTML],

  ['id' => 'planes', 'titulo' => '3. Plans and prices', 'html' => <<<HTML
    <ul>
      <li><strong>Free</strong>: the cost calculator and the resources.</li>
      <li><strong>Pro</strong>: everything above plus the STL library, the whole My Workshop
          section (quotes, products, clients, stock, sales, statistics) and Telegram support.
          It can be billed monthly or yearly.</li>
    </ul>
    <p>The prices in force for each plan are the ones published on the
       <a href="{$planes}">plans page</a>, in Argentine pesos (ARS). That page is the one that
       counts: if you ever see a different figure somewhere else on the site, the plans page wins.</p>
    <p>What each plan includes may change as the platform grows. If something substantial is
       taken away from a plan you are paying for, we will tell you first.</p>
HTML],

  ['id' => 'suscripcion', 'titulo' => '4. Subscription and automatic renewal', 'html' => <<<HTML
    <p>This is the part worth reading twice, because it is the one that surprises people.</p>
    <ul>
      <li><strong>The Pro subscription renews automatically.</strong> The monthly plan is charged
          every month; the yearly plan is charged every 12 months, on the same day you signed up.</li>
      <li>It keeps renewing on its own <strong>until you cancel it</strong>. There is no minimum
          term and no penalty for leaving.</li>
      <li>The charge is made by the payment platform, using the payment method you chose there.</li>
      <li>If a charge fails, the payment platform retries it for a few days. If it still does not
          go through, the account goes back to the Free plan: your data stays where it is and
          nothing is deleted.</li>
      <li>When the paid period ends, the Pro screens lock, but your account and everything you
          loaded into it remain. If you subscribe again, you find it all as you left it.</li>
    </ul>
HTML],

  ['id' => 'baja', 'titulo' => '5. How to cancel', 'html' => <<<HTML
    <p>You can cancel whenever you want, without giving a reason and without calling anyone:</p>
    <ul>
      <li>From your account on the payment platform, in the subscriptions section, cancelling the
          Printika Tools subscription.</li>
      <li>Or by writing to us at <a href="mailto:{$mail}">{$mail}</a> or on
          <a href="{$telegram}" target="_blank" rel="noopener">Telegram</a>. We cancel it for you.</li>
    </ul>
    <p><strong>Cancelling stops the next renewal.</strong> You keep your Pro access until the end
       of the period you already paid for — you do not lose the days that are left.</p>
HTML],

  ['id' => 'arrepentimiento', 'titulo' => '6. Right to withdraw (first 10 days)', 'html' => <<<HTML
    <p>Because this is bought online, Argentine consumer law (section 34 of Act No. 24,240) gives
       you <strong>10 calendar days from the moment you subscribe</strong> to change your mind, at
       no cost and without having to explain yourself.</p>
    <p>To use it, write to <a href="mailto:{$mail}">{$mail}</a> from the email address of your
       account, or message us on <a href="{$telegram}" target="_blank" rel="noopener">Telegram</a>,
       and say you want to withdraw. We cancel the subscription and refund what you paid, through
       the same payment method you used.</p>
HTML],

  ['id' => 'reintegros', 'titulo' => '7. Refunds', 'html' => <<<HTML
    <ul>
      <li>Within those first 10 days, the full amount is refunded.</li>
      <li>After that, periods that have already started are not refunded: what is cancelled is the
          renewal that was coming.</li>
      <li>If we charged you by mistake, twice, or after you had already cancelled, we refund it —
          that is our error, not a policy.</li>
      <li>If a failure on our side leaves you unable to use the service for a long stretch, write
          to us and we will make it right, either with a refund or with extra time.</li>
    </ul>
HTML],

  ['id' => 'precios', 'titulo' => '8. Price changes', 'html' => <<<HTML
    <p>Prices may be updated. If yours changes, we will tell you by email <strong>at least 30 days
       before</strong> the renewal it applies to, so you can decide calmly.</p>
    <p>The yearly plan keeps the price you paid for the whole 12-month period. If the new price
       does not work for you, cancel before the renewal date and you will not be charged.</p>
HTML],

  ['id' => 'pagos', 'titulo' => '9. Payments and card details', 'html' => <<<HTML
    <p><strong>We never see your card details, and we do not store them.</strong> That is precisely
       why payments go through a payment platform.</p>
    <ul>
      <li>The whole payment happens on the payment platform's own site — Mercado Pago for
          Argentina. The card number, the expiry date and the security code are typed there,
          never on Printika Tools.</li>
      <li>Those details <strong>never travel through our servers and are not saved in our
          database</strong>. We could not show them to you even if you asked.</li>
      <li>All we receive from the payment platform is the confirmation that the payment went
          through, and with that we activate the account.</li>
      <li>You manage, change or remove your card from your account on the payment platform.</li>
      <li><strong>We will never ask you for card details</strong> by email, on Telegram, by phone
          or on any form on this site. If somebody does it in our name, it is not us — tell us.</li>
    </ul>
HTML],

  ['id' => 'datos', 'titulo' => '10. Personal data and privacy', 'html' => <<<HTML
    <p><strong>What we keep.</strong> Your name and email address, and whatever you load into your
       workshop: quotes, clients, products, stock, sales, settings. Plus the technical minimum
       needed to keep the account safe, such as a record of sign-ins.</p>
    <p><strong>What we use it for.</strong> To give you the service, to write to you about your own
       account (confirmation, access codes, subscription notices) and, if you signed up for it, to
       send you news.</p>
    <p><strong>What we do not do.</strong> We do not sell your data, and we do not hand it over to
       anyone to advertise to you. The providers involved in running the service — hosting, email,
       payment platform — only get what they need to do their part.</p>
    <p><strong>Your rights.</strong> Under Act No. 25,326 on personal data protection you may ask
       for access to your data and have it corrected, updated or deleted, free of charge. Write to
       <a href="mailto:{$mail}">{$mail}</a> and we take care of it.</p>
    <p>The Agency for Access to Public Information, as the supervisory body of Act No. 25,326, has
       the power to hear complaints and claims brought by people whose rights are affected by
       breaches of the rules in force on personal data protection.</p>
    <p><strong>News emails.</strong> Every one of them carries an unsubscribe link in the footer,
       and one click is enough. Emails about your account (confirming your address, access codes,
       payment notices) cannot be unsubscribed from, because they are part of the service.</p>
    <p><strong>Cookies.</strong> We only use the ones the site needs to work: your session, the
       language you chose and the light or dark theme. There are no advertising cookies and no
       third-party trackers.</p>
    <p><strong>Deleting your account.</strong> Ask us and we delete your data. What we have to keep
       for tax or accounting reasons is the only exception.</p>
HTML],

  ['id' => 'contenidos', 'titulo' => '11. STL library, guides and videos', 'html' => <<<HTML
    <ul>
      <li>The STL files are there for you to <strong>print in your workshop, and you may sell the
          printed pieces</strong>. That is what the library is for.</li>
      <li>What you may not do is <strong>pass on the file</strong>: no sharing it, reselling it,
          uploading it anywhere else or lending your account so that other people can download it.
          Doing that is grounds for closing the account.</li>
      <li>The guides, the videos and the platform's texts and images belong to Printika Tools. You
          may read them and use them for your own work; you may not copy them onto another site.</li>
      <li><strong>What you load is yours.</strong> Your quotes, your clients, your photos and your
          numbers belong to you. We only use them to show them back to you inside the platform.</li>
    </ul>
HTML],

  ['id' => 'calculadora', 'titulo' => '12. The calculator gives estimates', 'html' => <<<HTML
    <p>The calculator works out a price from the figures you give it: what filament costs you, how
       much the print weighs, how long it takes, what electricity costs, what your time is worth.
       <strong>The result is only as good as those figures.</strong></p>
    <p>Reference values that come loaded — printer wattage, machine prices, exchange rates — are
       surveyed by hand and updated every so often, so they may lag behind reality. Check them
       against your own numbers before quoting.</p>
    <p>It is a tool to help you decide, not a guarantee of profit. What you charge, and how it
       turns out, is your call.</p>
HTML],

  ['id' => 'disponibilidad', 'titulo' => '13. Availability and backups', 'html' => <<<HTML
    <p>We work to keep the service up, but we cannot promise it will never go down: there is
       maintenance, and there are hosting provider failures that are out of our hands.</p>
    <p>We take backups of the database. Even so, for anything important, download or export your
       quotes: a copy of your own has never hurt anyone.</p>
HTML],

  ['id' => 'uso', 'titulo' => '14. Misuse and suspension', 'html' => <<<HTML
    <p>We may suspend or close an account that shares its credentials, tries to break into or
       overload the system, redistributes the library files, or uses the service for anything
       illegal or harmful to other people.</p>
    <p>If that ever happens by mistake, write to us and we will look into it. We would rather sort
       it out than lose you.</p>
HTML],

  ['id' => 'cambios', 'titulo' => '15. Changes to these terms', 'html' => <<<HTML
    <p>We may update these Terms. The date at the top always says when they last changed. If the
       change is a significant one, we will tell you by email or inside the platform before it
       applies. Carrying on using the service after that means you accept the new version.</p>
HTML],

  ['id' => 'ley', 'titulo' => '16. Governing law and claims', 'html' => <<<HTML
    <p>These Terms are governed by the laws of the Argentine Republic. For anyone acting as a
       consumer, the courts of the consumer's own place of residence have jurisdiction.</p>
    <p>Before that, write to us: most things get sorted out with an email. You may also turn to
       the national consumer protection authority (Defensa del Consumidor).</p>
HTML],

  ['id' => 'contacto', 'titulo' => '17. Contact', 'html' => <<<HTML
    <p>Email: <a href="mailto:{$mail}">{$mail}</a><br>
       Telegram: <a href="{$telegram}" target="_blank" rel="noopener">Printika Tools community</a></p>
    <p>We answer during business hours, from the Argentine Republic.</p>
HTML],

] : [

  ['id' => 'que-es', 'titulo' => '1. Qué es Printika Tools', 'html' => <<<HTML
    <p>Printika Tools (<strong>printikatools.com</strong>) es un sitio y una plataforma web con
       herramientas para talleres de impresión 3D: calculadora de costos, presupuestos, clientes,
       stock de materiales, ventas, estadísticas, librería de archivos STL y contenidos de
       formación. Se opera desde la República Argentina.</p>
    <p>Al crear una cuenta o usar el sitio aceptás estos Términos y Condiciones. Si no estás de
       acuerdo con algo de lo que sigue, no uses el servicio.</p>
HTML],

  ['id' => 'cuenta', 'titulo' => '2. Tu cuenta', 'html' => <<<HTML
    <ul>
      <li>La calculadora pública se puede usar sin cuenta. Todo lo demás necesita una.</li>
      <li>Tenés que ser mayor de 18 años y dar datos reales. El correo se verifica: sin
          confirmarlo, la cuenta no queda activa.</li>
      <li>La cuenta es personal. Es para una persona o un taller, y no se comparte, ni se presta,
          ni se transfiere.</li>
      <li>Sos responsable de tu contraseña y de lo que pase con tu cuenta. Te recomendamos activar
          el doble factor por correo, que está en Configuración.</li>
      <li>Si notás que alguien más entró a tu cuenta, avisanos enseguida.</li>
      <li>Podés pedir la baja de tu cuenta cuando quieras.</li>
    </ul>
HTML],

  ['id' => 'planes', 'titulo' => '3. Planes y precios', 'html' => <<<HTML
    <ul>
      <li><strong>Gratis</strong>: la calculadora de costos y los recursos.</li>
      <li><strong>Pro</strong>: todo lo anterior más la librería STL, todo Mi taller
          (presupuestos, productos, clientes, stock, ventas, estadísticas) y el soporte por
          Telegram. Se puede pagar por mes o por año.</li>
    </ul>
    <p>Los precios vigentes de cada plan son los que figuran en la
       <a href="{$planes}">página de planes</a>, expresados en pesos argentinos (ARS). Esa página
       es la que vale: si alguna vez ves otro número en otro lado del sitio, manda la página de
       planes.</p>
    <p>Lo que incluye cada plan puede cambiar a medida que la plataforma crece. Si a un plan que
       estás pagando se le saca algo importante, te avisamos antes.</p>
HTML],

  ['id' => 'suscripcion', 'titulo' => '4. La suscripción y la renovación automática', 'html' => <<<HTML
    <p>Esta es la parte que conviene leer dos veces, porque es la que más sorprende.</p>
    <ul>
      <li><strong>La suscripción Pro se renueva sola.</strong> El plan mensual se cobra todos los
          meses; el anual, cada 12 meses, el mismo día en que te suscribiste.</li>
      <li>Se sigue renovando <strong>hasta que la des de baja</strong>. No hay permanencia mínima
          ni penalidad por irte.</li>
      <li>El cobro lo hace la plataforma de pago, con el medio que hayas elegido ahí.</li>
      <li>Si un cobro falla, la plataforma de pago reintenta durante unos días. Si aun así no se
          concreta, la cuenta vuelve al plan Gratis: tus datos quedan donde estaban y no se borra
          nada.</li>
      <li>Cuando termina el período pagado, las pantallas Pro se bloquean, pero tu cuenta y todo
          lo que cargaste siguen ahí. Si volvés a suscribirte, lo encontrás tal cual lo dejaste.</li>
    </ul>
HTML],

  ['id' => 'baja', 'titulo' => '5. Cómo darla de baja', 'html' => <<<HTML
    <p>Podés darla de baja cuando quieras, sin dar explicaciones y sin llamar a nadie:</p>
    <ul>
      <li>Desde tu cuenta en la plataforma de pago, en la sección de suscripciones, cancelando la
          suscripción a Printika Tools.</li>
      <li>O escribiéndonos a <a href="mailto:{$mail}">{$mail}</a> o por
          <a href="{$telegram}" target="_blank" rel="noopener">Telegram</a>. La damos de baja
          nosotros.</li>
    </ul>
    <p><strong>La baja corta la renovación siguiente.</strong> Seguís con el acceso Pro hasta que
       termine el período que ya pagaste: no perdés los días que te quedan.</p>
HTML],

  ['id' => 'arrepentimiento', 'titulo' => '6. Botón de arrepentimiento (los primeros 10 días)', 'html' => <<<HTML
    <p>Como la contratación es online, la ley argentina de defensa del consumidor (artículo 34 de
       la Ley 24.240) te da <strong>10 días corridos desde que te suscribís</strong> para
       arrepentirte, sin ningún costo y sin tener que dar motivos.</p>
    <p>Para usarlo, escribinos a <a href="mailto:{$mail}">{$mail}</a> desde el correo de tu cuenta,
       o mandanos un mensaje por
       <a href="{$telegram}" target="_blank" rel="noopener">Telegram</a>, diciendo que te querés
       arrepentir. Cancelamos la suscripción y te devolvemos lo que pagaste, por el mismo medio con
       el que pagaste.</p>
HTML],

  ['id' => 'reintegros', 'titulo' => '7. Reintegros', 'html' => <<<HTML
    <ul>
      <li>Dentro de esos primeros 10 días se devuelve el importe completo.</li>
      <li>Pasado ese plazo, no se devuelven los períodos que ya empezaron: lo que se corta es la
          renovación que venía.</li>
      <li>Si te cobramos por error, dos veces, o después de que ya habías dado de baja, te lo
          devolvemos. Eso es un error nuestro, no una política.</li>
      <li>Si una falla nuestra te deja sin poder usar el servicio por un tiempo largo, escribinos
          y lo compensamos, con la devolución o con días extra.</li>
    </ul>
HTML],

  ['id' => 'precios', 'titulo' => '8. Cambios de precio', 'html' => <<<HTML
    <p>Los precios se pueden actualizar. Si cambia el tuyo, te avisamos por correo
       <strong>con al menos 30 días de anticipación</strong> a la renovación en la que se aplica,
       para que puedas decidir con tiempo.</p>
    <p>El plan anual mantiene el precio que pagaste durante los 12 meses del período. Si el precio
       nuevo no te sirve, das de baja antes de la fecha de renovación y no se te cobra.</p>
HTML],

  ['id' => 'pagos', 'titulo' => '9. Pagos y datos de la tarjeta', 'html' => <<<HTML
    <p><strong>Nunca vemos los datos de tu tarjeta, y no los guardamos.</strong> Justamente por eso
       el pago se hace a través de una plataforma de pago.</p>
    <ul>
      <li>Todo el pago ocurre en el sitio de la plataforma de pago —Mercado Pago para Argentina—.
          El número de la tarjeta, el vencimiento y el código de seguridad se escriben ahí, nunca
          en Printika Tools.</li>
      <li>Esos datos <strong>no pasan por nuestros servidores ni quedan en nuestra base</strong>.
          No podríamos mostrártelos ni aunque nos los pidieras.</li>
      <li>De la plataforma de pago solo recibimos el aviso de que el pago se hizo, y con eso
          activamos la cuenta.</li>
      <li>Tu tarjeta la administrás, cambiás o borrás desde tu cuenta en la plataforma de pago.</li>
      <li><strong>Nunca te vamos a pedir los datos de la tarjeta</strong> por correo, por Telegram,
          por teléfono ni en un formulario de este sitio. Si alguien lo hace en nuestro nombre, no
          somos nosotros: avisanos.</li>
    </ul>
HTML],

  ['id' => 'datos', 'titulo' => '10. Datos personales y privacidad', 'html' => <<<HTML
    <p><strong>Qué guardamos.</strong> Tu nombre y tu correo, y lo que vos cargues en tu taller:
       presupuestos, clientes, productos, stock, ventas, configuración. Además, lo técnico mínimo
       para cuidar la cuenta, como el registro de los ingresos.</p>
    <p><strong>Para qué lo usamos.</strong> Para darte el servicio, para escribirte sobre tu propia
       cuenta (confirmación, códigos de acceso, avisos de la suscripción) y, si te anotaste, para
       mandarte novedades.</p>
    <p><strong>Qué no hacemos.</strong> No vendemos tus datos ni se los pasamos a nadie para que te
       haga publicidad. Los proveedores que intervienen para que esto funcione —hosting, correo,
       plataforma de pago— solo acceden a lo necesario para su parte.</p>
    <p><strong>Tus derechos.</strong> Por la Ley 25.326 de protección de datos personales podés
       pedir acceder a tus datos y que los corrijamos, actualicemos o suprimamos, de forma gratuita.
       Escribinos a <a href="mailto:{$mail}">{$mail}</a> y lo resolvemos.</p>
    <p>La Agencia de Acceso a la Información Pública, en su carácter de órgano de control de la Ley
       25.326, tiene la atribución de atender las denuncias y reclamos que interpongan quienes
       resulten afectados en sus derechos por incumplimiento de las normas vigentes en materia de
       protección de datos personales.</p>
    <p><strong>Correos de novedades.</strong> Todos llevan un enlace de baja en el pie, y con un
       clic alcanza. Los correos de tu cuenta (confirmar la dirección, códigos de acceso, avisos de
       pago) no se pueden dar de baja porque son parte del servicio.</p>
    <p><strong>Cookies.</strong> Usamos solo las que el sitio necesita para funcionar: tu sesión, el
       idioma que elegiste y el modo día o noche. No hay cookies de publicidad ni rastreadores de
       terceros.</p>
    <p><strong>Borrar tu cuenta.</strong> Pedilo y borramos tus datos. La única excepción es lo que
       tengamos que conservar por obligaciones fiscales o contables.</p>
HTML],

  ['id' => 'contenidos', 'titulo' => '11. Librería STL, guías y videos', 'html' => <<<HTML
    <ul>
      <li>Los archivos STL están para que los <strong>imprimas en tu taller, y podés vender las
          piezas impresas</strong>. Para eso está la librería.</li>
      <li>Lo que no podés hacer es <strong>pasar el archivo</strong>: ni compartirlo, ni revenderlo,
          ni subirlo a otro lado, ni prestar tu cuenta para que otros lo bajen. Hacerlo es motivo
          para cerrar la cuenta.</li>
      <li>Las guías, los videos y los textos e imágenes de la plataforma son de Printika Tools.
          Podés leerlos y usarlos para tu trabajo; no podés copiarlos en otro sitio.</li>
      <li><strong>Lo que vos cargás es tuyo.</strong> Tus presupuestos, tus clientes, tus fotos y
          tus números son tuyos. Nosotros solo los usamos para mostrártelos dentro de la
          plataforma.</li>
    </ul>
HTML],

  ['id' => 'calculadora', 'titulo' => '12. La calculadora da estimaciones', 'html' => <<<HTML
    <p>La calculadora saca un precio a partir de los números que vos le das: cuánto te cuesta el
       filamento, cuánto pesa la pieza, cuánto tarda, cuánto vale la luz, cuánto vale tu tiempo.
       <strong>El resultado vale lo que valgan esos datos.</strong></p>
    <p>Los valores de referencia que vienen cargados —consumo y precio de las impresoras, tipo de
       cambio— están relevados a mano y se actualizan cada tanto, así que pueden estar atrasados.
       Contrastalos con tus propios números antes de cotizar.</p>
    <p>Es una herramienta para ayudarte a decidir, no una garantía de rentabilidad. Cuánto cobrás,
       y cómo te va, lo decidís vos.</p>
HTML],

  ['id' => 'disponibilidad', 'titulo' => '13. Disponibilidad y copias de seguridad', 'html' => <<<HTML
    <p>Trabajamos para que el servicio esté siempre disponible, pero no podemos prometerte que
       nunca se caiga: hay mantenimientos, y hay fallas del proveedor de hosting que no dependen
       de nosotros.</p>
    <p>Hacemos copias de seguridad de la base de datos. Aun así, lo importante descargalo o
       exportalo: tener una copia propia nunca le hizo mal a nadie.</p>
HTML],

  ['id' => 'uso', 'titulo' => '14. Uso indebido y suspensión', 'html' => <<<HTML
    <p>Podemos suspender o cerrar una cuenta que comparta sus credenciales, que intente vulnerar o
       sobrecargar el sistema, que redistribuya los archivos de la librería, o que use el servicio
       para algo ilegal o que perjudique a otras personas.</p>
    <p>Si alguna vez pasa por error, escribinos y lo revisamos. Preferimos arreglarlo antes que
       perderte.</p>
HTML],

  ['id' => 'cambios', 'titulo' => '15. Cambios en estos términos', 'html' => <<<HTML
    <p>Podemos actualizar estos Términos. La fecha de arriba siempre dice cuándo cambiaron por
       última vez. Si el cambio es importante, te avisamos por correo o dentro de la plataforma
       antes de que se aplique. Seguir usando el servicio después de eso significa que aceptás la
       versión nueva.</p>
HTML],

  ['id' => 'ley', 'titulo' => '16. Ley aplicable y reclamos', 'html' => <<<HTML
    <p>Estos Términos se rigen por las leyes de la República Argentina. Para quien actúe como
       consumidor, son competentes los tribunales del domicilio del propio consumidor.</p>
    <p>Antes de eso, escribinos: la mayoría de las cosas se resuelven con un correo. También podés
       recurrir a Defensa del Consumidor.</p>
HTML],

  ['id' => 'contacto', 'titulo' => '17. Contacto', 'html' => <<<HTML
    <p>Correo: <a href="mailto:{$mail}">{$mail}</a><br>
       Telegram: <a href="{$telegram}" target="_blank" rel="noopener">comunidad de Printika Tools</a></p>
    <p>Respondemos en horario comercial, desde la República Argentina.</p>
HTML],

];

$titulo = $en
    ? 'Terms and Conditions | Printika Tools'
    : 'Términos y Condiciones | Printika Tools';
$desc = $en
    ? 'Terms and conditions of Printika Tools: plans and subscriptions, automatic renewal, payments and card details, personal data and how to cancel.'
    : 'Términos y condiciones de Printika Tools: planes y suscripciones, renovación automática, pagos y datos de la tarjeta, datos personales y cómo darse de baja.';

guia_inicio([
    'titulo'      => $titulo,
    'descripcion' => $desc,
    'url'         => '/terminos/',
    'tipo'        => 'listado',
    'tiene_ingles' => true,
    'migas'       => [
        [$en ? 'Home' : 'Inicio', $base . '/'],
        [$en ? 'Terms and Conditions' : 'Términos y Condiciones', $base . '/terminos/'],
    ],
    'jsonld'      => [[
        '@type'         => 'WebPage',
        '@id'           => $base . ($en ? '/en' : '') . '/terminos/#pagina',
        'name'          => $en ? 'Terms and Conditions' : 'Términos y Condiciones',
        'description'   => $desc,
        'inLanguage'    => $en ? 'en' : 'es-AR',
        'dateModified'  => $actualizado,
        'isPartOf'      => ['@type' => 'WebSite', 'name' => 'Printika Tools', 'url' => $base . '/'],
    ]],
]);
?>

<main class="guia legal">
  <div class="cont">
    <h1><?php echo $en ? 'Terms and Conditions' : 'Términos y Condiciones'; ?></h1>
    <p class="legal-fecha"><?php echo ($en ? 'Last updated: ' : 'Última actualización: ')
                                    . htmlspecialchars($actualizado_txt); ?></p>

    <div class="resumen">
      <?php if ($en): ?>
        <p><strong>The short version.</strong> The Pro subscription renews on its own, monthly or
           yearly, until you cancel it. You can cancel at any time, with no penalty, and you keep
           your access until the end of the period you paid for. Within the first 10 days you can
           withdraw and get your money back.</p>
        <p>Your card details are typed on the payment platform, never here: we neither see them
           nor store them.</p>
      <?php else: ?>
        <p><strong>En dos líneas.</strong> La suscripción Pro se renueva sola, por mes o por año,
           hasta que la des de baja. Podés darla de baja cuando quieras, sin penalidad, y conservás
           el acceso hasta que termine el período que pagaste. Dentro de los primeros 10 días podés
           arrepentirte y te devolvemos la plata.</p>
        <p>Los datos de tu tarjeta se escriben en la plataforma de pago, nunca acá: nosotros ni los
           vemos ni los guardamos.</p>
      <?php endif; ?>
    </div>

    <nav class="legal-indice" aria-label="<?php echo $en ? 'Sections' : 'Secciones'; ?>">
      <ul>
        <?php foreach ($SECCIONES as $s): ?>
          <li><a href="#<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['titulo']); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <?php foreach ($SECCIONES as $s): ?>
      <h2 id="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['titulo']); ?></h2>
      <?php echo $s['html']; ?>
    <?php endforeach; ?>
  </div>
</main>

<style>
  /* El indice: una lista de anclas, en dos columnas cuando entra */
  .legal .legal-fecha{font-size:14px;color:var(--txt-3);margin-bottom:22px}
  .legal .legal-indice{margin:26px 0 34px}
  /* Dos columnas con column-count y no con grid: asi los titulos de dos
     renglones no dejan un hueco al lado, y ademas se lee 1..9 y despues 10..17 */
  .legal .legal-indice ul{list-style:none;margin:0;padding:0;column-count:2;column-gap:24px}
  .legal .legal-indice li{padding-left:0;margin:0 0 6px;font-size:15px;break-inside:avoid}
  .legal .legal-indice li::before{display:none}
  .legal .legal-indice a{color:var(--txt-2);border-bottom:0}
  .legal .legal-indice a:hover{color:var(--accent)}
  /* Que el titulo no quede tapado por la barra fija al saltar desde el indice */
  .legal h2{scroll-margin-top:96px}
  @media (max-width:640px){
    .legal .legal-indice ul{column-count:1}
  }
</style>

<?php guia_fin(); ?>
