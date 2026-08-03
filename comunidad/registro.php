<?php
/**
 * Registro de nuevos usuarios. La cuenta se crea sin suscripción:
 * un administrador la habilita desde /comunidad/admin/.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';   // taller_captar_email()

// Sitio en acceso anticipado: sin la clave, todo lleva al "proximamente".
if (!com_preview_ok() && usuario_actual() === null) {
    header('Location: /');
    exit;
}

// El plan se anota apenas llega, no recien al crear la cuenta: si resulta que
// ya tenia usuario y se va a ingresar, la eleccion tiene que seguirlo hasta el
// pago en vez de perderse por el camino
if (isset($_GET['plan'])) com_plan_pendiente($_GET['plan']);

if (usuario_actual() !== null) {
    // Ya tiene cuenta y esta adentro: no se registra de nuevo, va derecho a pagar
    header('Location: ' . com_destino_ingreso());
    exit;
}

$error = '';
$creado = false;
// Plan elegido en la landing: gratis entra directo; mensual/anual va al pago
$plan = $_POST['plan'] ?? ($_GET['plan'] ?? 'gratis');
$planes_ok = COMUNIDAD_MENSUAL_VISIBLE ? ['gratis', 'mensual', 'anual'] : ['gratis', 'anual'];
if (!in_array($plan, $planes_ok, true)) $plan = 'gratis';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = mb_strtolower(trim($_POST['email'] ?? ''));
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';

    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } elseif (!com_db_ok()) {
        $error = 'La plataforma está en mantenimiento. Probá en unos minutos.';
    } elseif ($nombre === '' || mb_strlen($nombre) > 120) {
        $error = 'Ingresá tu nombre.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } elseif (($sug = com_email_sugerencia($email)) !== ''
              && ($_SESSION['reg_email_avisado'] ?? '') !== $email) {
        // Un dominio que parece mal escrito frena el alta UNA vez. Si la persona
        // manda la misma direccion de nuevo, se toma como que esta bien y sigue:
        // el aviso no puede ser una pared para alguien con un dominio raro pero
        // real. La red de verdad es el aviso en vivo de mas abajo; esto es para
        // cuando el navegador tiene el JavaScript bloqueado.
        $_SESSION['reg_email_avisado'] = $email;
        $error = 'Revisá el correo: ¿no será <strong>' . htmlspecialchars($sug) . '</strong>? '
               . 'Si está mal escrito nunca te va a llegar el correo de confirmación y la cuenta '
               . 'queda inservible. Corregilo y mandalo de nuevo — o, si está bien así, volvé a '
               . 'enviarlo igual y seguimos.';
    } elseif (strlen($pass) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($pass !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        try {
            com_verif_migrar();
            $stmt = com_db()->prepare(
                'INSERT INTO usuarios (nombre, email, pass_hash, rol, email_verificado, creado_en)
                 VALUES (?, ?, ?, ?, 0, NOW())'
            );
            $stmt->execute([$nombre, $email, password_hash($pass, PASSWORD_DEFAULT), 'miembro']);
            $nuevo_id = (int) com_db()->lastInsertId();

            // Sesión iniciada pero en espera: solo puede ver confirmar.php
            com_sesion();
            session_regenerate_id(true);
            $_SESSION['uid'] = $nuevo_id;

            // Quien se registra tambien es una direccion captada: va a la misma
            // lista que el popup del cotizador y el banner de la portada. En su
            // propio try porque si esto falla la cuenta ya esta creada y no hay
            // que arruinarle el registro por una lista de correo.
            try {
                taller_migrar();
                taller_captar_email($email, taller_idioma(), 'registro');
            } catch (Throwable $e) {
                error_log('[registro] captar email: ' . $e->getMessage());
            }

            // Correo de confirmación con el logo de Printika
            com_verif_enviar([
                'id' => $nuevo_id, 'nombre' => $nombre, 'email' => $email,
            ], $motivo_correo);
            $creado = true;
        } catch (PDOException $e) {
            // El enlace se lleva el plan: si ya tenía cuenta, ingresa y sigue
            // derecho al pago con el MISMO usuario, sin crear otro
            $aLogin = 'login.php' . ($plan !== 'gratis' ? '?plan=' . urlencode($plan) : '');
            $error = ($e->errorInfo[1] ?? 0) == 1062
                ? 'Ya tenés una cuenta con ese email. <a href="' . $aLogin . '">Ingresá y seguí con tu plan</a>'
                  . ' &mdash; no hace falta que te registres de nuevo.'
                : 'No se pudo crear la cuenta. Probá de nuevo.';
        }
    }
}

if ($creado) {
    // El plan elegido queda guardado para después de confirmar el correo
    if ($plan !== 'gratis') $_SESSION['plan_elegido'] = $plan;
    header('Location: confirmar.php');
    exit;
}

// Los precios salen de las constantes, no escritos a mano: asi no se puede
// volver a desfasar del resto del sitio (decia $170.000 y son $180.000).
$PLANES_TXT = [
    'gratis'  => 'Gratuito · $0',
    'mensual' => 'Mensual · $' . number_format(COMUNIDAD_PRECIO_MENSUAL, 0, ',', '.') . '/mes',
    'anual'   => 'Anual · $' . number_format(COMUNIDAD_PRECIO_ANUAL, 0, ',', '.') . '/año',
];

ui_tarjeta_inicio('Crear cuenta');
?>
    <h1>Crear cuenta</h1>
    <p class="sub">Sumate a la comunidad de impresión 3D</p>
    <p style="font-size:13px;margin:-6px 0 14px;text-align:center">
      <span style="display:inline-block;background:var(--accent-tinte,rgba(45,183,250,.12));color:var(--accent,#2db7fa);
            font-weight:600;padding:4px 12px;border-radius:999px">Plan elegido: <?php echo $PLANES_TXT[$plan]; ?></span>
      <?php if ($plan !== 'gratis'): ?><a href="registro.php" style="font-size:12px;margin-left:6px">cambiar</a><?php endif; ?>
    </p>

    <?php if (!com_db_ok()): ?>
      <div class="msg warn">La plataforma está en preparación. Muy pronto vas a poder registrarte.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="msg bad"><?php echo $error; ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan); ?>">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="120" required autofocus
               value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <p class="sug-email" id="sugEmail" hidden></p>
        <label for="password">Contraseña (mínimo 8 caracteres)</label>
        <?php ui_campo_password('password', 'password', 'minlength="8" required autocomplete="new-password"'); ?>
        <label for="password2">Repetir contraseña</label>
        <?php ui_campo_password('password2', 'password2', 'minlength="8" required autocomplete="new-password"'); ?>
        <button class="btn" type="submit">Crear cuenta</button>
      </form>
      <p class="pie">¿Ya tenés cuenta?
        <a href="login.php<?php echo $plan !== 'gratis' ? '?plan=' . urlencode($plan) : ''; ?>">Ingresá</a>
        <?php if ($plan !== 'gratis'): ?>y seguís con este plan<?php endif; ?></p>

      <style>
        .sug-email{margin:-8px 0 14px;font-size:13px;color:var(--warn,#f0b23c);line-height:1.5}
        .sug-email .sug-btn{background:none;border:0;padding:0;font:inherit;font-weight:700;
                            color:var(--accent);text-decoration:underline;cursor:pointer}
      </style>
      <script>
      /*
       * Aviso en vivo cuando el dominio del correo parece mal escrito.
       *
       * Es la misma comprobacion que hace el servidor en com_email_sugerencia(),
       * pero aca alcanza a avisarle ANTES de mandar el formulario, asi no pierde
       * lo que ya escribio. Las listas vienen de PHP para no tener dos copias
       * que se desincronicen.
       */
      (function () {
        var campo = document.getElementById('email');
        var caja  = document.getElementById('sugEmail');
        if (!campo || !caja) return;
        var D = <?php echo json_encode(com_email_dominios(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

        function distancia(a, b) {
          var fila = [], i, j, tmp, ant;
          for (j = 0; j <= b.length; j++) fila[j] = j;
          for (i = 1; i <= a.length; i++) {
            ant = fila[0]; fila[0] = i;
            for (j = 1; j <= b.length; j++) {
              tmp = fila[j];
              fila[j] = Math.min(fila[j] + 1, fila[j - 1] + 1,
                                 ant + (a.charAt(i - 1) === b.charAt(j - 1) ? 0 : 1));
              ant = tmp;
            }
          }
          return fila[b.length];
        }

        function sugerir(email) {
          var arroba = email.lastIndexOf('@');
          if (arroba < 0) return '';
          var antes = email.slice(0, arroba + 1);
          var dom   = email.slice(arroba + 1).toLowerCase().trim();
          if (!dom || D.conocidos.indexOf(dom) >= 0 || D.reales.indexOf(dom) >= 0) return '';

          var punto = dom.indexOf('.');
          var raiz  = punto < 0 ? dom : dom.slice(0, punto);
          var cola  = punto < 0 ? ''  : dom.slice(punto);

          for (var k = 0; k < D.conocidos.length; k++) {
            var bueno = D.conocidos[k];
            var raizBuena = bueno.slice(0, bueno.indexOf('.'));
            if (raiz === raizBuena) {
              return D.colas_malas.indexOf(cola) >= 0 ? antes + bueno : '';
            }
            if (distancia(dom, bueno) <= 2) return antes + bueno;
          }
          return '';
        }

        function revisar() {
          var s = sugerir(campo.value);
          if (!s) { caja.hidden = true; return; }
          // Se arma con nodos y no con innerHTML: lo que va adentro sale de lo
          // que la persona escribio
          caja.textContent = '¿Quisiste decir ';
          var b = document.createElement('button');
          b.type = 'button';
          b.className = 'sug-btn';
          b.textContent = s;
          b.addEventListener('click', function () {
            campo.value = s;
            caja.hidden = true;
            campo.focus();
          });
          caja.appendChild(b);
          caja.appendChild(document.createTextNode('?'));
          caja.hidden = false;
        }

        campo.addEventListener('blur', revisar);
        campo.addEventListener('input', function () { caja.hidden = true; });
        if (campo.value) revisar();     // vuelve del servidor con el correo puesto
      })();
      </script>
    <?php endif; ?>
<?php ui_tarjeta_fin(); ?>
