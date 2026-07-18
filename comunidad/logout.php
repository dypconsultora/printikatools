<?php
/** Cierra la sesión de la plataforma. */
require_once __DIR__ . '/inc/auth.php';
com_logout();
header('Location: login.php');
exit;
