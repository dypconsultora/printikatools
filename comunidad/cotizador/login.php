<?php
/**
 * SELLO INERTE — retirado el 2026-07-26.
 *
 * Acá vivía el ingreso "PRO" del cotizador viejo, de antes de que existiera
 * la plataforma. Nunca se le fijó contraseña, así que no entraba nadie.
 * Hoy el acceso PRO real está en /comunidad (planes y suscripciones) y el
 * cotizador público quedó como calculadora gratuita, sin puerta de ingreso.
 *
 * Este archivo NO se borra del repositorio a propósito: el hosting sincroniza
 * archivos nuevos y modificados desde GitHub, pero no propaga los borrados.
 * Si se borrara de acá, la versión vieja seguiría viva en el servidor.
 *
 * El código original está en el historial de git. NO restaurarlo.
 */
http_response_code(410);
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Página retirada · Printika Tools</title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem;
       background:#0a0a0f;color:#e8e8f0;
       font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;line-height:1.6}
  .caja{max-width:400px;text-align:center}
  h1{font-size:1.25rem;font-weight:800;margin:0 0 .5rem}
  p{margin:0 0 1.5rem;font-size:.9rem;color:#8888a0}
  a{display:inline-block;background:#00D4FF;color:#04121a;text-decoration:none;font-weight:700;
    font-size:.9rem;padding:.8rem 1.4rem;border-radius:8px}
</style>
</head>
<body>
  <div class="caja">
    <h1>Esta página ya no existe</h1>
    <p>La calculadora sigue disponible y es gratis. Si tenés una cuenta de Printika Tools, entrá desde la comunidad.</p>
    <a href="/comunidad/cotizador/">Ir a la calculadora</a>
  </div>
</body>
</html>
