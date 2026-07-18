<?php
/** Pantalla para usuarios logueados SIN suscripción vigente. */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

$u = usuario_actual();
if ($u === null) {
    header('Location: login.php');
    exit;
}
if (suscripcion_activa()) {
    header('Location: index.php');
    exit;
}

ui_tarjeta_inicio('Suscripción');
?>
    <h1>Hola, <?php echo htmlspecialchars($u['nombre']); ?></h1>
    <p class="sub">Tu cuenta está creada, pero la suscripción todavía no está activa.</p>
    <div class="msg warn"><?php echo ui_icono('alerta', 16); ?>
      <span>Para habilitar tu acceso a la comunidad escribinos por WhatsApp y la activamos.</span>
    </div>
    <a class="btn" style="width:100%" href="<?php echo COMUNIDAD_WHATSAPP; ?>"
       target="_blank" rel="noopener"><?php echo ui_icono('whatsapp', 16); ?> Activar por WhatsApp</a>
    <p class="pie"><a href="logout.php">Cerrar sesión</a></p>
<?php ui_tarjeta_fin(); ?>
