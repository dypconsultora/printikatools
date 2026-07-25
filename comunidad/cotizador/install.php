<?php
/**
 * Instalador RETIRADO por seguridad (2026-07-25).
 * Estaba accesible sin autenticación y permitía fijar la clave PRO.
 * No ejecuta nada: responde 410 Gone.
 */
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
exit('410 — El instalador fue retirado por seguridad.');
