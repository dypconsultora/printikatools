<?php
/**
 * Configuración del taller: datos del negocio, logo propio (usado en los
 * PDF de presupuestos) y moneda de trabajo.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';
require_once __DIR__ . '/inc/taller.php';
require_once __DIR__ . '/inc/mp.php';
require_once __DIR__ . '/inc/correo.php';

requerir_miembro();
$u = usuario_actual();
taller_migrar();
$uid = (int) $u['id'];

$aviso = '';
$error = '';

com_2fa_migrar();

// Doble factor por correo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'dosfa') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $activar = !empty($_POST['dosfa_activo']);
        $correo  = mb_strtolower(trim($_POST['dosfa_email'] ?? ''));
        if ($activar && $correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo para el código no es válido.';
        } else {
            com_db()->prepare('UPDATE usuarios SET dosfa_activo = ?, dosfa_email = ? WHERE id = ?')
                ->execute([$activar ? 1 : 0, $correo, $uid]);
            $stmt = com_db()->prepare('SELECT * FROM usuarios WHERE id=?');
            $stmt->execute([$uid]);
            $u = $stmt->fetch();
            $aviso = $activar
                ? 'Doble factor activado. La próxima vez que entres te vamos a pedir un código.'
                : 'Doble factor desactivado.';
        }
    }
}

/**
 * Textos de "Tu suscripción", en los dos idiomas.
 *
 * No pasan por el traductor de JavaScript (ptools-en.js) porque llevan fechas
 * adentro: una frase con la fecha del mes que viene nunca va a coincidir con
 * una clave fija del diccionario. Van armados desde acá, como los meses de
 * Estadísticas, leyendo el idioma de la cookie.
 */
$en_ui = taller_idioma() === 'en';
$fmt_fecha = fn($f) => $f ? date($en_ui ? 'm/d/Y' : 'd/m/Y', strtotime($f)) : '';

/**
 * Baja de la suscripción.
 *
 * El orden importa: primero se le avisa a Mercado Pago, y recién si MP lo
 * acepta se marca de nuestro lado. Al revés, alguien podría quedarse con el
 * cartel de "diste de baja" mientras le sigue llegando el débito todos los
 * meses. Si MP falla no se toca nada y se le dice que pruebe de nuevo.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'baja_susc') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = $en_ui ? 'Your session expired, please try again.' : 'La sesión expiró, probá de nuevo.';
    } else {
        $susc = mp_suscripcion($uid);
        if (!$susc || !empty($susc['cancelada_en'])) {
            $error = $en_ui ? 'We could not find an active subscription to cancel.'
                            : 'No encontramos una suscripción activa para dar de baja.';
        } else {
            $pre = mp_preapproval_de($susc);
            if ($pre !== '' && !mp_cancelar_en_mp($pre)) {
                $error = $en_ui
                    ? 'We could not reach Mercado Pago, so we cancelled nothing: we do not want to '
                      . 'leave the charge running. Try again in a few minutes, or write to us and '
                      . 'we will cancel it for you.'
                    : 'No pudimos avisarle a Mercado Pago, así que no dimos de baja nada: '
                      . 'no queremos dejarte con el cobro andando. Probá de nuevo en unos minutos '
                      . 'o escribinos y la damos de baja nosotros.';
            } else {
                mp_baja_plan($uid);
                $hasta_txt = $fmt_fecha($susc['hasta']);
                $aviso = $en_ui
                    ? 'Done, your subscription is cancelled. You will not be charged again.'
                      . ($hasta_txt ? ' You keep the full plan until ' . $hasta_txt . '.' : '')
                    : 'Listo, diste de baja tu suscripción. No se te cobra más.'
                      . ($hasta_txt ? ' Seguís con el plan completo hasta el ' . $hasta_txt . '.' : '');

                // La constancia se manda DESPUES de guardar: un correo que falla se
                // reenvia, una baja que no quedo registrada le sigue cobrando a alguien.
                if (correo_disponible()) {
                    $plan_txt = ($susc['plan'] ?? '') === 'anual'
                        ? ($en_ui ? 'Printika Pro Annual' : 'Printika Pro Anual')
                        : 'Printika Pro';
                    $titulo = $en_ui ? 'Your subscription was cancelled' : 'Diste de baja tu suscripción';
                    $parrafos = $en_ui ? [
                        'We registered the cancellation of your <strong>' . htmlspecialchars($plan_txt)
                            . '</strong> plan on ' . $fmt_fecha(date('Y-m-d')) . '.',
                        'You will not be charged again.' . ($hasta_txt
                            ? ' Your full plan stays on until ' . $hasta_txt
                              . '; that day your account moves to the Free plan and nothing you loaded gets deleted.'
                            : ''),
                        'If you change your mind, you can subscribe again whenever you want.',
                    ] : [
                        'Registramos la baja de tu plan <strong>' . htmlspecialchars($plan_txt)
                            . '</strong> el ' . $fmt_fecha(date('Y-m-d')) . '.',
                        'No se te va a cobrar más.' . ($hasta_txt
                            ? ' Seguís con el plan completo hasta el ' . $hasta_txt
                              . '; ese día tu cuenta pasa al plan Gratis y no se borra nada de lo que cargaste.'
                            : ''),
                        'Si cambiás de idea, podés volver a suscribirte cuando quieras.',
                    ];
                    $pie = $en_ui ? 'Keep this email as your cancellation receipt.'
                                  : 'Guardá este correo como constancia de la baja.';
                    $asunto = $en_ui ? 'Cancellation receipt · Printika Tools'
                                     : 'Constancia de baja · Printika Tools';
                    $html = correo_plantilla($titulo, $parrafos, null, $pie, '', $en_ui ? 'en' : 'es');
                    $texto = strip_tags(implode("\n\n", $parrafos));
                    correo_enviar($u['email'], $u['nombre'], $asunto, $html, $texto);
                }
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'moneda') {
    if (com_csrf_ok($_POST['csrf'] ?? '')) {
        taller_guardar_moneda($uid, $_POST['moneda'] ?? '');
    }
    header('Location: configuracion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === '') {
    if (!com_csrf_ok($_POST['csrf'] ?? '')) {
        $error = 'La sesión expiró, probá de nuevo.';
    } else {
        $nombre   = mb_substr(trim($_POST['nombre'] ?? ''), 0, 120);
        $taller   = mb_substr(trim($_POST['taller_nombre'] ?? ''), 0, 150);
        $telefono = mb_substr(trim($_POST['taller_telefono'] ?? ''), 0, 50);

        if ($nombre === '') {
            $error = 'Ingresá tu nombre.';
        } else {
            $logo_ext = $u['logo_ext'] ?? '';

            // Quitar el logo actual
            if (!empty($_POST['quitar_logo']) && $logo_ext !== '') {
                @unlink(taller_logo_dir() . '/logo-' . $uid . '.' . $logo_ext);
                $logo_ext = '';
            }

            // Subida de logo nuevo (PNG o JPG para que funcione en el PDF)
            if (!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
                $info = @getimagesize($_FILES['logo']['tmp_name']);
                $tipos = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
                if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                    $error = 'El logo no puede superar los 2 MB.';
                } elseif (!$info || !isset($tipos[$info['mime']])) {
                    $error = 'El logo tiene que ser una imagen PNG o JPG.';
                } else {
                    $ext_nueva = $tipos[$info['mime']];
                    if (!is_dir(taller_logo_dir())) {
                        mkdir(taller_logo_dir(), 0755, true);
                    }
                    if ($logo_ext !== '' && $logo_ext !== $ext_nueva) {
                        @unlink(taller_logo_dir() . '/logo-' . $uid . '.' . $logo_ext);
                    }
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], taller_logo_dir() . '/logo-' . $uid . '.' . $ext_nueva)) {
                        $logo_ext = $ext_nueva;
                    } else {
                        $error = 'No se pudo guardar el logo. Probá de nuevo.';
                    }
                }
            }

            if ($error === '') {
                com_db()->prepare('UPDATE usuarios SET nombre=?, taller_nombre=?, taller_telefono=?, logo_ext=? WHERE id=?')
                    ->execute([$nombre, $taller, $telefono, $logo_ext, $uid]);
                $aviso = 'Configuración guardada.';
                // Refrescar los datos en pantalla
                $stmt = com_db()->prepare('SELECT * FROM usuarios WHERE id=?');
                $stmt->execute([$uid]);
                $u = $stmt->fetch();
            }
        }
    }
}

$logo_url = taller_logo_url($u);

// Se lee despues de procesar el POST, para que al dar de baja la tarjeta ya se
// vea dada de baja sin tener que recargar. El admin no tiene suscripcion.
$plan_actual = plan_usuario();
$susc_actual = in_array($plan_actual, ['mensual', 'anual'], true) ? mp_suscripcion($uid) : null;
$susc_hasta  = $susc_actual ? $fmt_fecha($susc_actual['hasta']) : '';
$susc_nombre = $plan_actual === 'anual'
    ? ($en_ui ? 'Printika Pro Annual' : 'Printika Pro Anual')
    : 'Printika Pro';

ui_panel_inicio('Configuración', $u, 'Configuración');
?>
    <style>.contenido{max-width:none}</style>
    <h1>Configuración</h1>
    <p class="bajada">Los datos de tu taller: aparecen en los PDF de tus presupuestos.</p>

    <?php if ($aviso): ?><div class="msg ok"><?php echo ui_icono('check', 16); ?><span><?php echo htmlspecialchars($aviso); ?></span></div><?php endif; ?>
    <?php if ($error): ?><div class="msg bad"><?php echo ui_icono('alerta', 16); ?><span><?php echo htmlspecialchars($error); ?></span></div><?php endif; ?>

    <style>
      .conf-grilla{display:grid;grid-template-columns:minmax(0,1fr) 380px;gap:20px;align-items:start}
      .tarjeta-s{background:var(--surface);border:1px solid var(--bd-suave);border-radius:var(--radio-g);padding:20px}
      .tarjeta-s h2{font-size:15px;font-weight:600;margin-bottom:4px}
      .tarjeta-s .nota{font-size:13px;color:var(--txt-2);margin-bottom:8px}
      .fila-2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
      .logo-zona{display:flex;gap:16px;align-items:center;margin-top:12px;flex-wrap:wrap}
      .logo-prev{width:180px;height:80px;border:1px dashed var(--bd);border-radius:var(--radio);
          background:var(--surface-2);display:flex;align-items:center;justify-content:center;overflow:hidden}
      .logo-prev img{max-width:100%;max-height:100%;object-fit:contain}
      .logo-prev span{font-size:12px;color:var(--txt-3)}
      input[type=file]{height:auto;padding:8px 12px;font-size:13px}
      .check-linea{display:flex;align-items:center;gap:8px;margin-top:10px;font-size:13px;color:var(--txt-2)}
      .check-linea input{width:auto;height:auto}
      .pie-form{display:flex;justify-content:flex-end;margin-top:16px}
      .moneda-linea{display:flex;align-items:center;gap:12px;margin-top:8px}
      @media (max-width:1000px){ .conf-grilla{grid-template-columns:1fr} }
    </style>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
      <div class="conf-grilla">
        <div class="tarjeta-s">
          <h2>Datos del taller</h2>
          <p class="nota">El nombre y teléfono del taller aparecen al pie de tus PDF.</p>
          <label for="c-nombre">Tu nombre *</label>
          <input id="c-nombre" type="text" name="nombre" maxlength="120" required
                 value="<?php echo htmlspecialchars($u['nombre']); ?>">
          <div class="fila-2">
            <span><label for="c-taller">Nombre del taller / negocio</label>
              <input id="c-taller" type="text" name="taller_nombre" maxlength="150"
                     placeholder="Ej: Printika 3D" value="<?php echo htmlspecialchars($u['taller_nombre'] ?? ''); ?>"></span>
            <span><label for="c-tel">Teléfono / WhatsApp</label>
              <input id="c-tel" type="tel" name="taller_telefono" maxlength="50"
                     placeholder="+54 9 11..." value="<?php echo htmlspecialchars($u['taller_telefono'] ?? ''); ?>"></span>
          </div>

          <label style="margin-top:16px">Moneda del taller</label>
          <div class="moneda-linea"><?php taller_chip_moneda(); ?></div>
        </div>

        <div class="tarjeta-s">
          <h2>Logo para tus PDF</h2>
          <p class="nota">PNG o JPG (máx. 2 MB). Si no cargás ninguno, usamos el de Printika Tools.</p>
          <div class="logo-zona">
            <div class="logo-prev">
              <?php if ($logo_url): ?>
                <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Tu logo">
              <?php else: ?>
                <span>Sin logo propio</span>
              <?php endif; ?>
            </div>
            <div style="flex:1;min-width:200px">
              <input type="file" name="logo" accept="image/png,image/jpeg">
              <?php if ($logo_url): ?>
                <label class="check-linea" for="quitarLogo">
                  <input id="quitarLogo" type="checkbox" name="quitar_logo" value="1">
                  Quitar mi logo y volver al de Printika Tools
                </label>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <div class="pie-form"><button class="btn" type="submit">Guardar configuración</button></div>
    </form>

    <div class="tarjeta-s" style="margin-top:20px;max-width:720px">
      <h2>Doble factor por correo</h2>
      <p class="nota">Con esto activado, además de tu contraseña te pedimos un código de 6 dígitos
         que te llega por correo cada vez que entrás. Es la mejor protección para tu cuenta.</p>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
        <input type="hidden" name="accion" value="dosfa">
        <label class="check-linea" for="dosfaActivo" style="margin-bottom:12px">
          <input id="dosfaActivo" type="checkbox" name="dosfa_activo" value="1"
                 <?php echo com_2fa_activo($u) ? 'checked' : ''; ?>>
          Pedirme un código por correo al entrar
        </label>
        <label for="dosfaEmail">¿A qué correo te mandamos el código?</label>
        <input id="dosfaEmail" type="email" name="dosfa_email" maxlength="190"
               placeholder="<?php echo htmlspecialchars($u['email']); ?> (el de tu cuenta)"
               value="<?php echo htmlspecialchars($u['dosfa_email'] ?? ''); ?>">
        <p class="nota" style="margin-top:6px">Dejalo vacío para usar el correo de tu cuenta.
           Podés poner otro si preferís recibir los códigos en una casilla distinta.</p>
        <div class="pie-form"><button class="btn" type="submit">Guardar</button></div>
      </form>
    </div>

    <?php if ($susc_actual): ?>
    <style>
      /* La baja no es una accion de todos los dias: el boton no compite con
         los de guardar, pero se ve y no hay que ir a buscarla a otro lado */
      .baja-btn{background:transparent;border:1px solid var(--bad);color:var(--bad)}
      .baja-btn:hover{background:var(--bad-tinte);border-color:var(--bad)}
    </style>
    <div class="tarjeta-s" style="margin-top:20px;max-width:720px">
      <h2><?php echo $en_ui ? 'Your subscription' : 'Tu suscripción'; ?></h2>
      <?php if (!empty($susc_actual['cancelada_en'])):
              $baja_txt = $fmt_fecha($susc_actual['cancelada_en']); ?>
        <p class="nota">
          <?php if ($en_ui): ?>
            You cancelled your subscription on <strong><?php echo $baja_txt; ?></strong>.
            You will not be charged again.
            <?php if ($susc_hasta): ?>
              You keep the full plan until <strong><?php echo $susc_hasta; ?></strong>; that day
              your account moves to the Free plan and nothing you loaded gets deleted.
            <?php endif; ?>
          <?php else: ?>
            Diste de baja tu suscripción el <strong><?php echo $baja_txt; ?></strong>.
            No se te cobra más.
            <?php if ($susc_hasta): ?>
              Seguís con el plan completo hasta el <strong><?php echo $susc_hasta; ?></strong>;
              ese día tu cuenta pasa al plan Gratis y no se borra nada de lo que cargaste.
            <?php endif; ?>
          <?php endif; ?>
        </p>
        <div class="pie-form"><a class="btn sec" href="suscripcion.php"><?php
          echo $en_ui ? 'Subscribe again' : 'Volver a suscribirme'; ?></a></div>
      <?php else: ?>
        <p class="nota">
          <?php if ($en_ui): ?>
            Your <strong><?php echo $susc_nombre; ?></strong> plan is active<?php
              echo $susc_hasta ? ' until ' . $susc_hasta : ''; ?>, and it renews on its own
            through Mercado Pago.
          <?php else: ?>
            Tenés el plan <strong><?php echo $susc_nombre; ?></strong> activo<?php
              echo $susc_hasta ? ' hasta el ' . $susc_hasta : ''; ?>, y se renueva solo
            por Mercado Pago.
          <?php endif; ?>
        </p>
        <p class="nota">
          <?php if ($en_ui): ?>
            If you cancel it, Mercado Pago stops charging you right away<?php
              echo $susc_hasta ? ' and you keep the full plan until <strong>' . $susc_hasta . '</strong>' : ''; ?>.
            There is no penalty and you can subscribe again whenever you want.
          <?php else: ?>
            Si lo das de baja, Mercado Pago deja de cobrarte enseguida<?php
              echo $susc_hasta ? ' y conservás el plan completo hasta el <strong>' . $susc_hasta . '</strong>' : ''; ?>.
            No hay penalidad y podés volver a suscribirte cuando quieras.
          <?php endif; ?>
        </p>
        <?php
        // El confirm es la ultima red antes de tocar el cobro de alguien: dice
        // que pasa, no solo "¿estas seguro?"
        $confirmar = $en_ui
            ? 'Cancel your subscription? Mercado Pago stops charging you'
              . ($susc_hasta ? ' and you keep the full plan until ' . $susc_hasta : '') . '.'
            : '¿Dar de baja tu suscripción? Mercado Pago deja de cobrarte'
              . ($susc_hasta ? ' y seguís con el plan completo hasta el ' . $susc_hasta : '') . '.';
        ?>
        <form method="post" onsubmit="return confirm('<?php echo htmlspecialchars($confirmar, ENT_QUOTES); ?>');">
          <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
          <input type="hidden" name="accion" value="baja_susc">
          <div class="pie-form"><button class="btn baja-btn" type="submit"><?php
            echo $en_ui ? 'Cancel my subscription' : 'Dar de baja mi suscripción'; ?></button></div>
        </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php taller_popup_moneda(); ?>
<?php ui_panel_fin(); ?>
