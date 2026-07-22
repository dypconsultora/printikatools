<?php
/**
 * Calculadora dentro del panel: el cotizador completo embebido con el menú
 * lateral. Solo pide estar logueado — la calculadora es parte de todos los
 * planes, incluso el gratuito. El link público /comunidad/cotizador/ sigue
 * abierto para quien llega desde afuera.
 */
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/ui.php';

if (usuario_actual() === null) {
    header('Location: login.php');
    exit;
}
$u = usuario_actual();

ui_panel_inicio('Calculadora', $u, 'Calculadora');
?>
    <style>
      .contenido{max-width:none;padding:0;display:flex;flex-direction:column;height:100vh}
      .calc-marco{flex:1;width:100%;border:0;display:block}
    </style>
    <iframe class="calc-marco" src="cotizador/?panel=1" title="Calculadora de costos 3D"></iframe>
<?php ui_panel_fin(); ?>
