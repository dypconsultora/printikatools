<?php
// === Calculadora 3D · doble modo ===
//  - Sin sesión: versión FREE (abre directo; lo PRO muestra el cartel de suscripción).
//  - Con sesión (entrando por /login): versión PRO completa, todo desbloqueado.
//  - Prueba por tiempo limitado (PRO_TRIAL_HASTA en auth.php): todo lo PRO
//    habilitado para cualquiera, con contador regresivo. Al vencer, vuelven
//    los candados automáticamente.
require_once __DIR__ . '/auth.php';

// Modo panel: la calculadora embebida dentro de /comunidad para usuarios
// logueados de la plataforma. Desbloquea lo PRO (beneficio de estar
// registrado, incluso en el plan gratuito) y oculta el encabezado propio.
$enPanel = false;
$panelCsrf = '';
$panelMoneda = 'ARS';
if (isset($_GET['panel'])) {
    require_once dirname(__DIR__) . '/inc/auth.php';
    $usuarioPanel = usuario_actual();
    if ($usuarioPanel !== null) {
        $enPanel = true;
        $panelCsrf = com_csrf();
        if (in_array($usuarioPanel['moneda'] ?? '', ['ARS', 'USD', 'EUR'], true)) {
            $panelMoneda = $usuarioPanel['moneda'];
        }
    }
    // Cerrar la sesion ptools antes de abrir la sesion propia del cotizador
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

iniciar_sesion();
$csrf  = token_csrf();
$esPro = esta_logueado() || $enPanel;
$enTrial = !$esPro && trial_pro_activo();
$proHabilitado = $esPro || $enTrial;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Calculadora de Costos de Impresion 3D</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
  --bg-primary: #0a0a0f;
  --bg-secondary: #12121a;
  --bg-card: #1a1a26;
  --bg-card-hover: #1e1e2c;
  --bg-input: #14141e;
  --border-color: #2a2a3a;
  --border-focus: #00D4FF;
  --accent: #00D4FF;
  --accent-dim: rgba(0, 212, 255, 0.15);
  --accent-glow: rgba(0, 212, 255, 0.3);
  --text-primary: #e8e8f0;
  --text-secondary: #8888a0;
  --text-muted: #555570;
  --success: #00e676;
  --warning: #ffab40;
  --danger: #ff5252;
  --chart-material: #00D4FF;
  --chart-electric: #7c4dff;
  --chart-labor: #00e676;
  --chart-depreciation: #ffab40;
  --chart-additional: #ff5252;
  --chart-support: #ff80ab;
  --radius: 12px;
  --radius-sm: 8px;
  --shadow: 0 4px 24px rgba(0,0,0,0.4);
  --transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

/* === Tema claro (dia) === */
[data-theme="light"] {
  --bg-primary: #f4f7fb;
  --bg-secondary: #e9eef5;
  --bg-card: #ffffff;
  --bg-card-hover: #f6f9fd;
  --bg-input: #eff3f9;
  --border-color: #d6dee9;
  --border-focus: #0090c9;
  --accent: #0090c9;
  --accent-dim: rgba(0, 144, 201, 0.12);
  --accent-glow: rgba(0, 144, 201, 0.18);
  --text-primary: #16202e;
  --text-secondary: #51617a;
  --text-muted: #8494ab;
  --shadow: 0 4px 24px rgba(22, 32, 46, 0.08);
}
/* En claro el gradiente del titulo arranca oscuro (en dark arranca blanco) */
[data-theme="light"] .header h1 {
  background: linear-gradient(135deg, #16202e 30%, var(--accent));
  -webkit-background-clip: text;
  background-clip: text;
}

/* Banner de prueba PRO con contador regresivo (debajo del nombre) */
.trial-banner {
  max-width: 640px;
  margin: 0.9rem auto 0;
  padding: 0.55rem 1rem;
  background: var(--accent-dim);
  border: 1px solid var(--accent);
  border-radius: 30px;
  font-size: 0.8rem;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 8px;
}
.trial-banner strong { color: var(--text-primary); }
.trial-count {
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  color: var(--accent);
  white-space: nowrap;
}

/* Indicador de sesion PRO (arriba a la derecha) */
.pro-session {
  position: absolute;
  top: 0.7rem;
  right: 1rem;
  font-size: 0.72rem;
  color: var(--text-muted);
}
.pro-session strong { color: var(--accent); }
.pro-session a {
  color: var(--text-secondary);
  text-decoration: none;
  border-bottom: 1px dotted var(--text-muted);
}
.pro-session a:hover { color: var(--accent); }

/* Logo Printika a la izquierda del header */
.header-logo {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  left: 3.5rem; /* mismo margen hacia adentro que el selector dia/noche */
  display: inline-block;
  line-height: 0;
}
.header-logo img {
  height: 103px;
  width: auto;
  display: block;
}
/* version del logo segun tema: blanca en noche, oscura en dia */
.header-logo .logo-light { display: none; }
[data-theme="light"] .header-logo .logo-light { display: block; }
[data-theme="light"] .header-logo .logo-dark { display: none; }

/* pantallas medianas: un poco mas chico para no pisar el titulo */
@media (max-width: 1100px) {
  .header-logo img { height: 66px; }
  /* el logo puede pisar la etiqueta "Proyecto"; el placeholder ya la reemplaza */
  .project-name-bar label { display: none; }
}
@media (max-width: 700px) {
  .header-logo { top: 0.9rem; left: 1.25rem; transform: none; }
  .header-logo img { height: 45px; }
  /* bajar el titulo para que no choque con logo y selector */
  .header h1 { margin-top: 4rem; }
}

/* Selector dia/noche (las dos opciones a la vista).
   Centrado verticalmente con el logo y un poco alejado del borde. */
.ir-web {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  right: 9.5rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 16px;
  border-radius: 999px;
  background: var(--accent);
  color: #04222f;
  font-size: 0.82rem;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
  transition: filter 0.2s ease, transform 0.2s ease;
}
.ir-web:hover { filter: brightness(1.1); transform: translateY(-50%) scale(1.03); }
@media (max-width: 900px) {
  .ir-web { position: static; transform: none; display: inline-flex; margin: 0.75rem auto 0; }
}
body.en-panel .ir-web { display: none !important; }
.theme-switch {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  right: 3.5rem;
  display: inline-flex;
  gap: 2px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 30px;
  padding: 3px;
}
@media (max-width: 700px) {
  /* en movil el logo esta arriba (no centrado): alinear el selector con el */
  .theme-switch { top: 1.6rem; transform: none; right: 1.25rem; }
}
.theme-opt {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 28px;
  border: none;
  background: none;
  color: var(--text-muted);
  border-radius: 30px;
  cursor: pointer;
  transition: background var(--transition), color var(--transition);
}
.theme-opt:hover { color: var(--text-secondary); }
.theme-opt.active {
  background: var(--accent);
  color: var(--bg-card);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html {
  scroll-behavior: smooth;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  background: var(--bg-primary);
  color: var(--text-primary);
  line-height: 1.6;
  min-height: 100vh;
  transition: background 0.4s ease, color 0.4s ease;
}

/* Scrollbar */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

/* Header */
.header {
  background: linear-gradient(180deg, rgba(0,212,255,0.06) 0%, transparent 100%);
  border-bottom: 1px solid var(--border-color);
  padding: 2rem 1.5rem 1.5rem;
  text-align: center;
  position: sticky;
  top: 0;
  z-index: 100;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}

.header h1 {
  font-size: 1.6rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  background: linear-gradient(135deg, #fff 30%, var(--accent));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.25rem;
}

.header p {
  font-size: 0.8rem;
  color: var(--text-secondary);
  font-weight: 400;
}

.project-name-bar {
  max-width: 480px;
  margin: 1rem auto 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.project-name-bar label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-secondary);
  white-space: nowrap;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.project-name-bar input {
  flex: 1;
  background: var(--bg-input);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 0.5rem 0.75rem;
  color: var(--text-primary);
  font-family: inherit;
  font-size: 0.85rem;
  transition: border-color var(--transition);
}

.project-name-bar input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-dim);
}

/* entre 701px y 1100px el logo convive con el campo de proyecto: correrlo a la derecha */
@media (min-width: 701px) and (max-width: 1100px) {
  .project-name-bar { margin-left: max(16.5rem, calc((100% - 480px) / 2)); margin-right: 1rem; }
}

/* Layout */
.container {
  max-width: 720px;
  margin: 0 auto;
  padding: 1.5rem 1rem 4rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* Cards */
.card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 1.5rem;
  transition: border-color var(--transition), box-shadow var(--transition);
}

.card:hover {
  border-color: rgba(0,212,255,0.2);
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  font-size: 0.95rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
  color: var(--text-primary);
}

.card-title .icon {
  width: 32px;
  height: 32px;
  background: var(--accent-dim);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  flex-shrink: 0;
}

.card-title .badge,
.toggle-row .badge,
.trial-banner .badge {
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  background: linear-gradient(135deg, var(--accent), #7c4dff);
  color: #000;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}
.card-title .badge { margin-left: auto; }

/* Form elements */
.field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.field-grid .full-width {
  grid-column: 1 / -1;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.field label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.field input, .field select {
  background: var(--bg-input);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  padding: 0.6rem 0.75rem;
  color: var(--text-primary);
  font-family: inherit;
  font-size: 0.85rem;
  transition: border-color var(--transition), box-shadow var(--transition);
  width: 100%;
}

.field input:focus, .field select:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-dim);
}

.field select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%238888a0' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  padding-right: 2rem;
  cursor: pointer;
}

.field select option {
  background: var(--bg-card);
  color: var(--text-primary);
}

.field .unit {
  font-size: 0.7rem;
  color: var(--text-muted);
  font-weight: 400;
}

.field .computed {
  font-size: 0.85rem;
  color: var(--accent);
  font-weight: 600;
  padding: 0.6rem 0.75rem;
  background: var(--accent-dim);
  border-radius: var(--radius-sm);
  border: 1px solid rgba(0,212,255,0.15);
  min-height: 2.3rem;
  display: flex;
  align-items: center;
}

/* Toggle switch */
.toggle-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-top: 1px solid var(--border-color);
  margin-top: 0.75rem;
}

.toggle-row span {
  font-size: 0.8rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.toggle {
  position: relative;
  width: 44px;
  height: 24px;
  flex-shrink: 0;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
  position: absolute;
}

.toggle-slider {
  position: absolute;
  inset: 0;
  background: var(--border-color);
  border-radius: 12px;
  cursor: pointer;
  transition: background var(--transition);
}

.toggle-slider::before {
  content: '';
  position: absolute;
  width: 18px;
  height: 18px;
  left: 3px;
  top: 3px;
  background: var(--text-secondary);
  border-radius: 50%;
  transition: transform var(--transition), background var(--transition);
}

.toggle input:checked + .toggle-slider {
  background: var(--accent);
}

.toggle input:checked + .toggle-slider::before {
  transform: translateX(20px);
  background: #000;
}

.support-fields {
  overflow: hidden;
  max-height: 0;
  opacity: 0;
  transition: max-height 0.4s ease, opacity 0.3s ease, margin 0.3s ease;
  margin-top: 0;
}

.support-fields.active {
  max-height: 300px;
  opacity: 1;
  margin-top: 1rem;
}

/* Time display */
.time-display {
  text-align: center;
  padding: 0.75rem;
  background: var(--accent-dim);
  border-radius: var(--radius-sm);
  border: 1px solid rgba(0,212,255,0.1);
  margin-top: 0.75rem;
}

.time-display .time-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--accent);
  letter-spacing: -0.02em;
}

.time-display .time-label {
  font-size: 0.7rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-top: 0.15rem;
}

/* Slider */
.slider-container {
  position: relative;
  padding: 0.5rem 0;
}

.slider-value-display {
  text-align: center;
  font-size: 2rem;
  font-weight: 800;
  color: var(--accent);
  margin-bottom: 0.5rem;
}

input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
  width: 100%;
  height: 6px;
  background: var(--border-color);
  border-radius: 3px;
  outline: none;
  cursor: pointer;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 22px;
  height: 22px;
  background: var(--accent);
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 0 10px var(--accent-glow);
  transition: transform 0.15s ease;
}

input[type="range"]::-webkit-slider-thumb:hover {
  transform: scale(1.15);
}

input[type="range"]::-moz-range-thumb {
  width: 22px;
  height: 22px;
  background: var(--accent);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 0 10px var(--accent-glow);
}

.slider-labels {
  display: flex;
  justify-content: space-between;
  font-size: 0.65rem;
  color: var(--text-muted);
  margin-top: 0.35rem;
}

/* Summary card */
.summary-card {
  background: linear-gradient(135deg, var(--bg-card) 0%, rgba(0,212,255,0.04) 100%);
  border: 1px solid rgba(0,212,255,0.25);
  box-shadow: 0 0 40px rgba(0,212,255,0.06);
}

.summary-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.55rem 0;
  font-size: 0.85rem;
}

.summary-line .label {
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.summary-line .label .dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.summary-line .value {
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.summary-divider {
  border: none;
  border-top: 1px solid var(--border-color);
  margin: 0.25rem 0;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0 0.5rem;
}

.summary-total .label {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
  color: var(--text-secondary);
}

.summary-total .value {
  font-size: 1rem;
  font-weight: 700;
}

.final-price {
  text-align: center;
  padding: 1.25rem 0;
  border-top: 2px solid var(--accent);
  margin-top: 0.5rem;
}

.final-price .label {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  font-weight: 700;
  color: var(--accent);
  margin-bottom: 0.25rem;
}

.final-price .price {
  font-size: 2.5rem;
  font-weight: 900;
  letter-spacing: -0.03em;
  background: linear-gradient(135deg, #fff, var(--accent));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.1;
}

.summary-metrics {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
}

.metric-box {
  background: var(--bg-input);
  border-radius: var(--radius-sm);
  padding: 0.75rem;
  text-align: center;
}

.metric-box .metric-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--accent);
}

.metric-box .metric-label {
  font-size: 0.65rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-top: 0.15rem;
}

/* Donut chart */
.chart-section {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--border-color);
}

.chart-section h3 {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--text-secondary);
  font-weight: 700;
  margin-bottom: 1rem;
  text-align: center;
}

.chart-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.25rem;
}

.donut-chart {
  width: 180px;
  height: 180px;
  border-radius: 50%;
  position: relative;
  background: conic-gradient(var(--chart-material) 0% 30%, var(--chart-electric) 30% 40%, var(--chart-labor) 40% 55%, var(--chart-depreciation) 55% 70%, var(--chart-additional) 70% 80%, var(--chart-support) 80% 100%);
  transition: background 0.5s ease;
}

.donut-chart::before {
  content: '';
  position: absolute;
  inset: 35%;
  background: var(--bg-card);
  border-radius: 50%;
}

.chart-legend {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem 1.5rem;
  width: 100%;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.legend-item .legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 3px;
  flex-shrink: 0;
}

.legend-item .legend-pct {
  margin-left: auto;
  font-weight: 600;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
}

/* Buttons */
.actions-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.7rem 1rem;
  border-radius: var(--radius-sm);
  font-family: inherit;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition);
  border: 1px solid var(--border-color);
  background: var(--bg-input);
  color: var(--text-primary);
}

.btn:hover {
  border-color: var(--accent);
  background: var(--accent-dim);
}

.btn:active {
  transform: scale(0.97);
}

.btn-primary {
  background: var(--accent);
  color: #000;
  border-color: var(--accent);
  font-weight: 700;
}

.btn-primary:hover {
  background: #33ddff;
  border-color: #33ddff;
  box-shadow: 0 0 20px var(--accent-glow);
}

.btn-full {
  grid-column: 1 / -1;
}

/* Currency selector */
.currency-bar {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.currency-btn {
  flex: 1;
  padding: 0.5rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border-color);
  background: var(--bg-input);
  color: var(--text-secondary);
  font-family: inherit;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all var(--transition);
  text-align: center;
}

.currency-btn.active {
  border-color: var(--accent);
  background: var(--accent-dim);
  color: var(--accent);
}

.currency-btn:hover:not(.active) {
  border-color: var(--text-muted);
}

/* Saved quotes */
.quotes-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 1rem;
}

.quote-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: var(--bg-input);
  border-radius: var(--radius-sm);
  border: 1px solid var(--border-color);
  transition: border-color var(--transition);
}

.quote-item:hover {
  border-color: var(--text-muted);
}

.quote-info {
  flex: 1;
  min-width: 0;
}

.quote-name {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.quote-meta {
  font-size: 0.65rem;
  color: var(--text-muted);
  margin-top: 0.1rem;
}

.quote-price {
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--accent);
  white-space: nowrap;
}

.quote-actions {
  display: flex;
  gap: 0.35rem;
}

.quote-actions button {
  background: none;
  border: none;
  color: var(--text-muted);
  cursor: pointer;
  padding: 0.25rem;
  font-size: 0.9rem;
  transition: color var(--transition);
  border-radius: 4px;
}

.quote-actions button:hover {
  color: var(--text-primary);
}

.quote-actions button.delete-btn:hover {
  color: var(--danger);
}

.no-quotes {
  text-align: center;
  padding: 1.5rem;
  color: var(--text-muted);
  font-size: 0.8rem;
  font-style: italic;
}

/* Toast notification */
.toast {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%) translateY(100px);
  background: var(--bg-card);
  border: 1px solid var(--accent);
  color: var(--text-primary);
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  font-size: 0.85rem;
  font-weight: 500;
  box-shadow: 0 8px 32px rgba(0,0,0,0.5);
  z-index: 1000;
  opacity: 0;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none;
}

.toast.show {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

/* Print styles */
/* ============================================================
   EXPORTAR PDF — documento de presupuesto (solo se imprime esto)
   ============================================================ */
#printDoc { display: none; }

@media print {
  /* Margen de pagina en 0: elimina la fecha/titulo/URL que el navegador
     imprime en los margenes. El margen visual vive dentro del documento. */
  @page { margin: 0; }
  body { background: #fff !important; }
  /* Ocultar la app completa: solo se imprime el documento */
  body > *:not(#printDoc) { display: none !important; }

  #printDoc {
    display: block;
    padding: 16mm 15mm;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: #17202e;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* Encabezado del documento: logo + datos del presupuesto */
  .pd-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding-bottom: 18px;
    border-bottom: 2px solid #17202e;
  }
  .pd-logo { height: 60px; width: auto; }
  .pd-meta { text-align: right; }
  .pd-kicker {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.24em;
    text-transform: uppercase;
    color: #0b78b5;
  }
  .pd-project {
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #101b29;
    margin-top: 4px;
  }
  .pd-date { font-size: 12px; color: #64748c; margin-top: 5px; }

  /* Ficha tecnica de la pieza */
  .pd-specs { display: flex; gap: 36px; margin: 20px 0 4px; }
  .pd-spec { font-size: 11px; color: #64748c; text-transform: uppercase; letter-spacing: 0.06em; }
  .pd-spec strong {
    display: block;
    font-size: 15px;
    font-weight: 700;
    color: #101b29;
    margin-top: 3px;
    text-transform: none;
    letter-spacing: 0;
    font-variant-numeric: tabular-nums;
  }

  /* Detalle de costos: filas con lineas divisorias */
  .pd-rows { margin-top: 22px; }
  .pd-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 2px;
    border-bottom: 1px solid #e5eaf1;
    font-size: 13.5px;
  }
  .pd-row .l { color: #44536b; }
  .pd-row .v { font-weight: 600; color: #101b29; font-variant-numeric: tabular-nums; }
  .pd-row.sub { border-top: 2px solid #17202e; border-bottom: none; padding-top: 13px; margin-top: 2px; }
  .pd-row.sub .l, .pd-row.sub .v { font-weight: 700; color: #101b29; }

  /* Precio final destacado */
  .pd-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 16px;
    padding: 15px 20px;
    background: #eaf4fb;
    border: 1px solid #bdd9ec;
    border-radius: 10px;
  }
  .pd-total .l {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #0b6ca8;
  }
  .pd-total .v {
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: #0b6ca8;
    font-variant-numeric: tabular-nums;
  }

  /* Bloque Mercado Libre (solo si esta activo) */
  .pd-meli { margin-top: 22px; }
  .pd-meli .pd-h {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #64748c;
    padding-bottom: 6px;
  }

}

/* Responsive */
@media (max-width: 600px) {
  .header { padding: 1.5rem 1rem 1rem; }
  .header h1 { font-size: 1.25rem; }
  .container { padding: 1rem 0.75rem 3rem; }
  .card { padding: 1.25rem; }
  .field-grid { grid-template-columns: 1fr; }
  .field-grid .full-width { grid-column: 1; }
  .actions-grid { grid-template-columns: 1fr; }
  .btn-full { grid-column: 1; }
  .chart-legend { grid-template-columns: 1fr; }
  .summary-metrics { grid-template-columns: 1fr; }
  .final-price .price { font-size: 2rem; }
  .currency-bar { flex-wrap: wrap; }
  .currency-btn { flex: 0 0 calc(33.33% - 0.34rem); }
}

/* Animate on load */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}

.card {
  animation: fadeUp 0.5s ease both;
}

.card:nth-child(1) { animation-delay: 0.03s; }
.card:nth-child(2) { animation-delay: 0.06s; }
.card:nth-child(3) { animation-delay: 0.09s; }
.card:nth-child(4) { animation-delay: 0.12s; }
.card:nth-child(5) { animation-delay: 0.15s; }
.card:nth-child(6) { animation-delay: 0.18s; }
.card:nth-child(7) { animation-delay: 0.21s; }
.card:nth-child(8) { animation-delay: 0.24s; }
.card:nth-child(9) { animation-delay: 0.27s; }
.card:nth-child(10) { animation-delay: 0.30s; }

/* === Version FREE: secciones PRO bloqueadas === */
.pro-locked { cursor: pointer; }
.pro-locked input, .pro-locked select, .pro-locked .toggle,
.pro-locked button.pro-btn { pointer-events: none; }
.pro-locked .field-grid, .pro-locked .toggle-row,
.toggle-row.pro-locked { opacity: 0.55; }
/* badge PRO dentro de una fila de toggle */
.toggle-row .badge { margin-left: 6px; vertical-align: middle; }

.pro-modal {
  position: fixed;
  inset: 0;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(5, 5, 12, 0.75);
  backdrop-filter: blur(4px);
  z-index: 1000;
}
.pro-modal.open { display: flex; }
.pro-modal__card {
  width: 100%;
  max-width: 420px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 2rem 1.75rem;
  text-align: center;
  box-shadow: 0 0 60px var(--accent-glow);
  animation: fadeUp 0.3s ease both;
}
.pro-modal__badge {
  display: inline-block;
  background: var(--accent-dim);
  color: var(--accent);
  border: 1px solid var(--accent);
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  padding: 4px 12px;
  border-radius: 20px;
  margin-bottom: 0.9rem;
}
.pro-modal__card h2 {
  font-size: 1.25rem;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 0.6rem;
}
.pro-modal__card p {
  color: var(--text-secondary);
  font-size: 0.92rem;
  line-height: 1.6;
  margin-bottom: 1.4rem;
}
.pro-modal__wa {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  background: #25D366;
  color: #fff;
  font-weight: 700;
  font-size: 0.95rem;
  padding: 13px 20px;
  border-radius: 10px;
  text-decoration: none;
  transition: filter 0.2s, transform 0.2s;
}
.pro-modal__wa:hover { filter: brightness(1.08); transform: translateY(-1px); }
.pro-modal__close {
  display: block;
  width: 100%;
  margin-top: 0.7rem;
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 0.85rem;
  padding: 8px;
  cursor: pointer;
}
.pro-modal__close:hover { color: var(--text-secondary); }

/* Popup de novedades (mismo estilo que el cartel PRO) */
.news-label {
  display: block;
  text-align: left;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.4rem;
}
.news-input {
  width: 100%;
  background: var(--bg-input);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 0.75rem 0.9rem;
  color: var(--text-primary);
  font-family: inherit;
  font-size: 0.92rem;
  outline: none;
  margin-bottom: 1rem;
  transition: border-color var(--transition), box-shadow var(--transition);
}
.news-input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-dim);
}
.pro-modal__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  background: var(--accent);
  color: #fff;
  border: none;
  font-family: inherit;
  font-weight: 700;
  font-size: 0.95rem;
  padding: 13px 20px;
  border-radius: 10px;
  cursor: pointer;
  transition: filter 0.2s, transform 0.2s;
}
.pro-modal__cta:hover { filter: brightness(1.1); transform: translateY(-1px); }
.pro-modal__cta:disabled { opacity: 0.6; cursor: default; transform: none; }
</style>
<script>
  window.APP = { api: <?php echo json_encode($enPanel ? '../calculadora_api.php' : 'api.php'); ?>, csrf: <?php echo json_encode($enPanel ? $panelCsrf : $csrf); ?>, panel: <?php echo $enPanel ? 'true' : 'false'; ?>, moneda: <?php echo json_encode($panelMoneda); ?>, pro: <?php echo $proHabilitado ? 'true' : 'false'; ?>, sesion: <?php echo $esPro ? 'true' : 'false'; ?>, trial: <?php echo $enTrial ? 'true' : 'false'; ?>, trialEnd: <?php echo PRO_TRIAL_HASTA * 1000; ?> };
  // Aplicar el tema guardado antes del primer pintado (evita destello)
  (function () {
    try {
<?php if ($enPanel): ?>
      // Modo panel: seguir el tema del menu lateral de la plataforma
      var t = localStorage.getItem('ptools_tema');
      document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
      window.addEventListener('storage', function (e) {
        if (e.key === 'ptools_tema') {
          document.documentElement.setAttribute('data-theme', e.newValue === 'light' ? 'light' : 'dark');
        }
      });
<?php else: ?>
      if (localStorage.getItem('calc3d-theme') === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
      }
<?php endif; ?>
    } catch (e) {}
  })();
</script>
<?php if ($enPanel): ?>
<style>
/* === Modo panel: el panel ya pone logo, titulo y selector de tema === */
body.en-panel .header-logo, body.en-panel .theme-switch,
body.en-panel .header h1, body.en-panel .header p { display: none !important; }
body.en-panel .header { padding: 1.4rem 1.5rem 0.5rem; }
body.en-panel .header .project-name-bar { margin-top: 0; }
body.en-panel #newsModal { display: none !important; }
</style>
<?php endif; ?>
</head>
<body<?php echo $enPanel ? ' class="en-panel"' : ''; ?>>

<header class="header">
  <?php if ($esPro && !$enPanel): ?>
    <span class="pro-session">Modo <strong>PRO</strong> &middot; <a href="logout.php">Salir</a></span>
  <?php endif; ?>
  <a class="header-logo" href="https://printikatools.com/" title="Printika Tools">
    <img src="../../assets/img/printika-tools.svg" alt="Printika Tools" class="logo-light">
    <img src="../../assets/img/printika-tools-dark.svg" alt="Printika Tools" class="logo-dark">
  </a>
  <?php if (!$enPanel): ?>
  <a class="ir-web" href="https://printikatools.com/">Ir a la web &rarr;</a>
  <?php endif; ?>
  <div class="theme-switch" role="group" aria-label="Elegir tema">
    <button type="button" class="theme-opt" data-theme-opt="light" aria-label="Modo dia" title="Modo dia">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
    </button>
    <button type="button" class="theme-opt active" data-theme-opt="dark" aria-label="Modo noche" title="Modo noche">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
    </button>
  </div>
  <h1>Calculadora de Costos 3D</h1>
  <p>Calcula el precio justo para tus impresiones 3D</p>
  <div class="project-name-bar">
    <label for="projectName"><?php echo $enPanel ? 'Producto' : 'Proyecto'; ?></label>
    <input type="text" id="projectName" placeholder="<?php echo $enPanel ? 'Nombre del producto o pieza...' : 'Nombre del proyecto...'; ?>">
  </div>
  <?php if ($enTrial): ?>
  <div class="trial-banner" id="trialBanner">
    <span class="badge">PRO</span>
    Versi&oacute;n PRO habilitada por tiempo limitado &middot; se deshabilita el <strong>02/09/2026</strong>
    <span class="trial-count" id="trialCount">&hellip;</span>
  </div>
  <?php endif; ?>
</header>

<main class="container">

  <!-- Currency selector -->
  <div class="card" style="padding: 1rem 1.5rem;">
    <div class="currency-bar" style="margin-bottom:0;">
      <button class="currency-btn active" data-currency="ARS" data-symbol="$">ARS $</button>
      <button class="currency-btn" data-currency="USD" data-symbol="US$">USD US$</button>
      <button class="currency-btn" data-currency="EUR" data-symbol="€">EUR &euro;</button>
    </div>
  </div>

  <!-- 1. Material -->
  <section class="card" id="sec-material">
    <div class="card-title">
      <span class="icon">&#127912;</span>
      Configuracion de Material
    </div>
    <div class="field-grid">
      <div class="field full-width">
        <label for="materialType">Tipo de material</label>
        <select id="materialType">
          <option value="PLA">PLA</option>
          <option value="ABS">ABS</option>
          <option value="PETG">PETG</option>
          <option value="TPU">TPU</option>
          <option value="Nylon">Nylon</option>
          <option value="Resina">Resina</option>
          <option value="ASA">ASA</option>
          <option value="PC">Policarbonato (PC)</option>
          <option value="HIPS">HIPS</option>
          <option value="PVA">PVA</option>
          <option value="CF-Nylon">Nylon + Fibra de Carbono</option>
          <option value="Wood-PLA">PLA Madera</option>
          <option value="Flex">Flexible</option>
        </select>
      </div>
      <div class="field">
        <label for="materialWeight">Peso usado <span class="unit">(g)</span></label>
        <input type="number" id="materialWeight" value="50" min="0" step="1">
      </div>
      <div class="field">
        <label for="spoolPrice">Precio del carrete <span class="unit" id="spoolPriceUnit">($)</span></label>
        <input type="number" id="spoolPrice" value="15000" min="0" step="100">
      </div>
      <div class="field">
        <label for="spoolWeight">Peso del carrete <span class="unit">(g)</span></label>
        <input type="number" id="spoolWeight" value="1000" min="1" step="1">
      </div>
      <div class="field">
        <label>Costo por gramo</label>
        <div class="computed" id="costPerGram">$0.00</div>
      </div>
      <div class="field">
        <label>Costo total material</label>
        <div class="computed" id="totalMaterialCost">$0.00</div>
      </div>
    </div>
    <div class="toggle-row" id="supportProRow">
      <span>Incluir material de soporte <span class="badge">PRO</span></span>
      <label class="toggle">
        <input type="checkbox" id="supportToggle">
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="support-fields" id="supportFields">
      <div class="field-grid">
        <div class="field">
          <label for="supportWeight">Peso soporte <span class="unit">(g)</span></label>
          <input type="number" id="supportWeight" value="10" min="0" step="1">
        </div>
        <div class="field">
          <label for="supportMaterial">Material soporte</label>
          <select id="supportMaterial">
            <option value="same">Mismo material</option>
            <option value="PVA">PVA (soluble)</option>
            <option value="HIPS">HIPS (soluble)</option>
            <option value="PLA">PLA</option>
          </select>
        </div>
        <div class="field">
          <label for="supportSpoolPrice">Precio carrete soporte <span class="unit" id="supportPriceUnit">($)</span></label>
          <input type="number" id="supportSpoolPrice" value="20000" min="0" step="100">
        </div>
        <div class="field">
          <label for="supportSpoolWeight">Peso carrete soporte <span class="unit">(g)</span></label>
          <input type="number" id="supportSpoolWeight" value="1000" min="1" step="1">
        </div>
      </div>
    </div>
  </section>

  <!-- 2. Tiempo -->
  <section class="card" id="sec-time">
    <div class="card-title">
      <span class="icon">&#9200;</span>
      Tiempo de Impresion
    </div>
    <div class="field-grid">
      <div class="field">
        <label for="printHours">Horas</label>
        <input type="number" id="printHours" value="3" min="0" step="1">
      </div>
      <div class="field">
        <label for="printMinutes">Minutos</label>
        <input type="number" id="printMinutes" value="30" min="0" max="59" step="1">
      </div>
    </div>
    <div class="time-display">
      <div class="time-value" id="totalTimeDisplay">3h 30m</div>
      <div class="time-label">Tiempo total de impresion</div>
    </div>
  </section>

  <!-- 3. Electricidad -->
  <section class="card" id="sec-electric">
    <div class="card-title">
      <span class="icon">&#9889;</span>
      Costos de Electricidad
    </div>
    <div class="field">
      <label for="printerModel">Modelo de impresora</label>
      <select id="printerModel">
        <option value="">Otro / Personalizado</option>
          <option value="Bambu Lab A1 Mini (45 W)">Bambu Lab A1 Mini (45 W)</option>
          <option value="Bambu Lab A1 (95 W)">Bambu Lab A1 (95 W)</option>
          <option value="Bambu Lab P1P (80 W)">Bambu Lab P1P (80 W)</option>
          <option value="Bambu Lab P1S (100 W)">Bambu Lab P1S (100 W)</option>
          <option value="Bambu Lab P2S (130 W)">Bambu Lab P2S (130 W)</option>
          <option value="Bambu Lab X1 Carbon (120 W)">Bambu Lab X1 Carbon (120 W)</option>
          <option value="Bambu Lab H2S (210 W)">Bambu Lab H2S (210 W)</option>
          <option value="Bambu Lab H2D (210 W)">Bambu Lab H2D (210 W)</option>
          <option value="Bambu Lab H2C (210 W)">Bambu Lab H2C (210 W)</option>
          <option value="Prusa MK3S+ (80 W)">Prusa MK3S+ (80 W)</option>
          <option value="Prusa MK4 (100 W)">Prusa MK4 (100 W)</option>
          <option value="Creality Ender 3 V2 (110 W)">Creality Ender 3 V2 (110 W)</option>
          <option value="Creality Ender 3 S1 (120 W)">Creality Ender 3 S1 (120 W)</option>
          <option value="Creality K1 (100 W)">Creality K1 (100 W)</option>
          <option value="Creality K1C (100 W)">Creality K1C (100 W)</option>
          <option value="Creality K1 Max (200 W)">Creality K1 Max (200 W)</option>
          <option value="Anycubic Kobra 2 (75 W)">Anycubic Kobra 2 (75 W)</option>
          <option value="Anycubic Vyper (80 W)">Anycubic Vyper (80 W)</option>
          <option value="SnapMaker U1 (130 W)">SnapMaker U1 (130 W)</option>
          <option value="Elegoo Saturn 3 (resina) (75 W)">Elegoo Saturn 3 (resina) (75 W)</option>
          <option value="Elegoo Saturn 4 (resina) (75 W)">Elegoo Saturn 4 (resina) (75 W)</option>
          <option value="Voron 2.4 (350mm DIY) (225 W)">Voron 2.4 (350mm DIY) (225 W)</option>
      </select>
      <div style="font-size:.78rem;color:var(--text-secondary,#8888a0);margin-top:.4rem">
        Elegí tu modelo y autocompletamos el consumo (W). Si no está en la lista, dejá "Otro / Personalizado".</div>
    </div>
    <div class="field-grid">
      <div class="field">
        <label for="printerWatts">Consumo <span class="unit">(Watts)</span></label>
        <input type="number" id="printerWatts" value="200" min="0" step="10">
      </div>
      <div class="field">
        <label for="electricRate">Tarifa electrica <span class="unit" id="electricRateUnit">($/kWh)</span></label>
        <input type="number" id="electricRate" value="239.17" min="0" step="0.01">
      </div>
      <div class="field full-width">
        <label>Costo electrico total</label>
        <div class="computed" id="totalElectricCost">$0.00</div>
      </div>
    </div>
  </section>

  <!-- 4. Mano de obra -->
  <section class="card" id="sec-labor">
    <div class="card-title">
      <span class="icon">&#128736;</span>
      Costos de Mano de Obra
      <span class="badge">PRO</span>
    </div>
    <div class="field-grid">
      <div class="field">
        <label for="prepTime">Preparacion <span class="unit">(min)</span></label>
        <input type="number" id="prepTime" value="<?php echo $proHabilitado ? 15 : 0; ?>" min="0" step="1">
      </div>
      <div class="field">
        <label for="postTime">Post-proceso <span class="unit">(min)</span></label>
        <input type="number" id="postTime" value="<?php echo $proHabilitado ? 10 : 0; ?>" min="0" step="1">
      </div>
      <div class="field">
        <label for="laborRate">Tarifa por hora <span class="unit" id="laborRateUnit">($)</span></label>
        <input type="number" id="laborRate" value="<?php echo $proHabilitado ? 3000 : 0; ?>" min="0" step="100">
      </div>
      <div class="field">
        <label>Costo mano de obra</label>
        <div class="computed" id="totalLaborCost">$0.00</div>
      </div>
    </div>
  </section>

  <!-- 5. Depreciacion -->
  <section class="card" id="sec-depreciation">
    <div class="card-title">
      <span class="icon">&#128424;</span>
      Depreciacion de la Maquina
      <span class="badge">PRO</span>
    </div>
    <div class="field-grid">
      <div class="field">
        <label for="printerCost">Costo impresora <span class="unit" id="printerCostUnit">($)</span></label>
        <input type="number" id="printerCost" value="<?php echo $proHabilitado ? 500000 : 0; ?>" min="0" step="1000">
      </div>
      <div class="field">
        <label for="printerLifespan">Vida util <span class="unit">(horas)</span></label>
        <input type="number" id="printerLifespan" value="2000" min="1" step="100">
      </div>
      <div class="field">
        <label for="maintenanceCost">Mantenimiento anual <span class="unit" id="maintenanceCostUnit">($)</span></label>
        <input type="number" id="maintenanceCost" value="<?php echo $proHabilitado ? 30000 : 0; ?>" min="0" step="1000">
      </div>
      <div class="field">
        <label>Depreciacion por hora</label>
        <div class="computed" id="depPerHour">$0.00</div>
      </div>
      <div class="field full-width">
        <label>Costo depreciacion total</label>
        <div class="computed" id="totalDepCost">$0.00</div>
      </div>
    </div>
  </section>

  <!-- 6. Costos adicionales -->
  <section class="card" id="sec-additional">
    <div class="card-title">
      <span class="icon">&#128230;</span>
      Costos Adicionales
      <span class="badge">PRO</span>
    </div>
    <div class="field-grid">
      <div class="field">
        <label for="packagingCost">Empaquetado <span class="unit" id="packagingUnit">($)</span></label>
        <input type="number" id="packagingCost" value="0" min="0" step="10">
      </div>
      <div class="field">
        <label for="shippingCost">Envio <span class="unit" id="shippingUnit">($)</span></label>
        <input type="number" id="shippingCost" value="0" min="0" step="10">
      </div>
      <div class="field">
        <label for="failureRate">Tasa de fallos <span class="unit">(%)</span></label>
        <input type="number" id="failureRate" value="<?php echo $proHabilitado ? 5 : 0; ?>" min="0" max="100" step="1">
      </div>
      <div class="field">
        <label for="otherCosts">Otros costos fijos <span class="unit" id="otherCostsUnit">($)</span></label>
        <input type="number" id="otherCosts" value="0" min="0" step="10">
      </div>
    </div>
  </section>

  <!-- 7. Mercado Libre -->
  <section class="card" id="sec-meli">
    <div class="card-title">
      <span class="icon">&#128722;</span>
      Comisiones Mercado Libre
      <span class="badge">PRO</span>
    </div>
    <div class="toggle-row" style="border-top:none; margin-top:0; padding-top:0;">
      <span>Vender por Mercado Libre</span>
      <label class="toggle">
        <input type="checkbox" id="meliToggle">
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div id="meliSection" style="display:none;">
      <div class="field-grid">
        <div class="field">
          <label for="meliListingType">Tipo de publicacion</label>
          <select id="meliListingType">
            <option value="clasica">Clasica</option>
            <option value="premium" selected>Premium</option>
          </select>
        </div>
        <div class="field">
          <label for="meliCommission">Comision ML <span class="unit">(%)</span></label>
          <input type="number" id="meliCommission" value="14" min="0" max="50" step="0.5">
        </div>
        <div class="field">
          <label for="meliFixedFee">Cargo fijo por unidad <span class="unit" id="meliFixedFeeUnit">($)</span></label>
          <input type="number" id="meliFixedFee" value="0" min="0" step="100">
        </div>
        <div class="field full-width">
          <label>Comision estimada sobre precio final</label>
          <div class="computed" id="meliCostDisplay" style="color:#ffe600;">$0.00</div>
        </div>
      </div>
      <div style="padding:0.5rem 0.25rem; font-size:0.7rem; color:var(--text-secondary); line-height:1.4;">
        Comisiones ML Argentina (Ene 2026): Clasica 11,8%-17,1% | Premium 12%-17% segun categoria.
        Cargos fijos: hasta $15.000 = $1.095/u | $15k-$25k = $2.190/u | $25k-$33k = $2.628/u.
        <strong>Verifica siempre en tu cuenta de vendedor.</strong>
      </div>
    </div>
  </section>

  <!-- 8. Margen -->
  <section class="card" id="sec-margin">
    <div class="card-title">
      <span class="icon">&#128200;</span>
      Margen de Ganancia
    </div>
    <div class="toggle-row" style="border-top:none; margin-top:0; padding-top:0;">
      <span>Usar precio fijo</span>
      <label class="toggle">
        <input type="checkbox" id="fixedPriceToggle">
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div id="marginSliderSection">
      <div class="slider-value-display" id="marginDisplay">30%</div>
      <div class="slider-container">
        <input type="range" id="marginSlider" min="0" max="600" value="30" step="1">
        <div class="slider-labels">
          <span>0%</span>
          <span>150%</span>
          <span>300%</span>
          <span>450%</span>
          <span>600%</span>
        </div>
      </div>
      <div style="text-align:center; margin-top:0.75rem;">
        <span style="font-size:0.8rem; color:var(--text-secondary);">Ganancia: </span>
        <span style="font-size:1rem; font-weight:700; color:var(--success);" id="marginAmount">$0.00</span>
      </div>
    </div>
    <div id="fixedPriceSection" style="display:none;">
      <div class="field-grid">
        <div class="field">
          <label for="fixedPrice">Precio fijo <span class="unit" id="fixedPriceUnit">($)</span></label>
          <input type="number" id="fixedPrice" value="0" min="0" step="100">
        </div>
        <div class="field">
          <label>Margen resultante</label>
          <div class="computed" id="computedMarginPct">0%</div>
        </div>
      </div>
    </div>
  </section>

  <!-- 8. Resumen -->
  <section class="card summary-card" id="sec-summary">
    <div class="card-title">
      <span class="icon">&#128202;</span>
      Resumen de Costos
    </div>

    <div class="summary-line">
      <span class="label"><span class="dot" style="background:var(--chart-material)"></span> Costo de material</span>
      <span class="value" id="sumMaterial">$0.00</span>
    </div>
    <div class="summary-line" id="sumSupportRow" style="display:none;">
      <span class="label"><span class="dot" style="background:var(--chart-support)"></span> Material de soporte</span>
      <span class="value" id="sumSupport">$0.00</span>
    </div>
    <div class="summary-line">
      <span class="label"><span class="dot" style="background:var(--chart-electric)"></span> Costo electrico</span>
      <span class="value" id="sumElectric">$0.00</span>
    </div>
    <div class="summary-line">
      <span class="label"><span class="dot" style="background:var(--chart-labor)"></span> Mano de obra</span>
      <span class="value" id="sumLabor">$0.00</span>
    </div>
    <div class="summary-line">
      <span class="label"><span class="dot" style="background:var(--chart-depreciation)"></span> Depreciacion maquina</span>
      <span class="value" id="sumDep">$0.00</span>
    </div>
    <div class="summary-line">
      <span class="label"><span class="dot" style="background:var(--chart-additional)"></span> Costos adicionales</span>
      <span class="value" id="sumAdditional">$0.00</span>
    </div>

    <hr class="summary-divider">

    <div class="summary-total">
      <span class="label">Subtotal (costo)</span>
      <span class="value" id="sumSubtotal">$0.00</span>
    </div>
    <div class="summary-line">
      <span class="label" style="color:var(--success);">+ Margen de ganancia</span>
      <span class="value" style="color:var(--success);" id="sumMargin">$0.00</span>
    </div>

    <div class="final-price">
      <div class="label">Precio Final</div>
      <div class="price" id="finalPrice">$0.00</div>
    </div>

    <div id="meliSummaryBlock" style="display:none;">
      <hr class="summary-divider">
      <div style="text-align:center; margin-bottom:0.5rem;">
        <span style="font-size:0.75rem; color:#ffe600; font-weight:600; text-transform:uppercase; letter-spacing:1px;">Mercado Libre</span>
      </div>
      <div class="summary-line">
        <span class="label" style="color:#ffe600;">Comision ML</span>
        <span class="value" style="color:#ffe600;" id="sumMeliPct">0%</span>
      </div>
      <div class="summary-line">
        <span class="label" style="color:#ffe600;">+ Comision sobre precio</span>
        <span class="value" style="color:#ffe600;" id="sumMeliAmount">$0.00</span>
      </div>
      <div class="summary-line" id="sumMeliFixedRow" style="display:none;">
        <span class="label" style="color:#ffe600;">+ Cargo fijo ML</span>
        <span class="value" style="color:#ffe600;" id="sumMeliFixed">$0.00</span>
      </div>
      <div class="summary-line">
        <span class="label" style="color:#ffe600; font-weight:700;">Total comisiones ML</span>
        <span class="value" style="color:#ffe600; font-weight:700;" id="sumMeliTotal">$0.00</span>
      </div>
      <div class="final-price" style="margin-top:0.75rem; border:2px solid #ffe600; border-radius:12px; padding:1rem;">
        <div class="label" style="font-size:0.75rem; color:#ffe600;">Publicar en Mercado Libre a</div>
        <div class="price" id="meliMLPrice" style="color:#ffe600; font-size:2rem;">$0.00</div>
      </div>
      <div style="text-align:center; margin-top:0.75rem; padding:0.5rem; background:rgba(255,255,255,0.05); border-radius:8px;">
        <div style="font-size:0.7rem; color:var(--text-secondary); margin-bottom:0.25rem;">Despues de comisiones te queda</div>
        <span style="font-size:1rem; font-weight:700; color:var(--success);" id="meliNetCheck">$0.00</span>
        <span style="font-size:0.75rem; color:var(--text-secondary);"> (tu precio final)</span>
        <div style="margin-top:0.25rem;">
          <span style="font-size:0.7rem; color:var(--text-secondary);">Tu ganancia: </span>
          <span style="font-size:0.85rem; font-weight:700; color:var(--success);" id="meliMarginCheck">$0.00</span>
        </div>
      </div>
    </div>

    <div class="summary-metrics">
      <div class="metric-box">
        <div class="metric-value" id="metricPerGram">$0.00</div>
        <div class="metric-label">Costo por gramo</div>
      </div>
      <div class="metric-box">
        <div class="metric-value" id="metricPerHour">$0.00</div>
        <div class="metric-label">Costo por hora</div>
      </div>
    </div>

    <!-- Chart -->
    <div class="chart-section">
      <h3>Distribucion de costos</h3>
      <div class="chart-wrapper">
        <div class="donut-chart" id="donutChart"></div>
        <div class="chart-legend" id="chartLegend"></div>
      </div>
    </div>
  </section>

  <!-- 9. Actions -->
  <section class="card actions-card" id="sec-actions">
    <div class="card-title">
      <span class="icon">&#128640;</span>
      Acciones
      <span class="badge">PRO</span>
    </div>
    <div class="actions-grid">
      <?php if ($enPanel): ?>
      <button class="btn btn-primary" onclick="guardarProducto()">&#128230; Guardar como producto</button>
      <button class="btn btn-primary" onclick="crearPresupuesto()">&#129534; Crear presupuesto</button>
      <?php endif; ?>
      <button class="btn btn-primary" onclick="saveQuote()">&#128190; Guardar Cotizacion</button>
      <button class="btn" onclick="exportPDF()">&#128196; Exportar PDF</button>
      <button class="btn" onclick="shareQuote()">&#128203; Compartir</button>
      <button class="btn" onclick="resetAll()">&#128260; Reiniciar</button>
    </div>
  </section>

  <!-- Saved Quotes (funcion PRO: oculta en la version FREE) -->
  <section class="card quotes-card" id="sec-quotes"<?php if (!$esPro) echo ' style="display:none;"'; ?>>
    <div class="card-title">
      <span class="icon">&#128209;</span>
      Cotizaciones Guardadas
    </div>
    <div id="quotesList">
      <div class="no-quotes">No hay cotizaciones guardadas</div>
    </div>
  </section>

</main>

<!-- Cartel de suscripcion PRO -->
<div class="pro-modal" id="proModal" role="dialog" aria-modal="true" aria-labelledby="proModalTitle">
  <div class="pro-modal__card">
    <span class="pro-modal__badge">PRO</span>
    <h2 id="proModalTitle">Funci&oacute;n exclusiva de la versi&oacute;n PRO</h2>
    <p>Para habilitar la versi&oacute;n PRO contactanos para habilitar la suscripci&oacute;n.</p>
    <a class="pro-modal__wa" href="https://wa.me/5491131373425?text=Hola!%20Quiero%20habilitar%20la%20versi%C3%B3n%20PRO%20de%20la%20calculadora%203D" target="_blank" rel="noopener">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.625.846 5.059 2.284 7.034L.789 23.492a.5.5 0 00.611.611l4.458-1.495A11.952 11.952 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-2.37 0-4.567-.68-6.434-1.852l-.448-.29-2.648.888.888-2.648-.29-.448A9.96 9.96 0 012 12C2 6.486 6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"/></svg>
      Contactar por WhatsApp
    </a>
    <button class="pro-modal__close" onclick="closeProModal()">Cerrar</button>
  </div>
</div>

<!-- Popup de novedades: captura de email -->
<div class="pro-modal" id="newsModal" role="dialog" aria-modal="true" aria-labelledby="newsModalTitle">
  <form class="pro-modal__card" id="newsForm" novalidate>
    <span class="pro-modal__badge">NOVEDADES</span>
    <h2 id="newsModalTitle">&iexcl;No te pierdas lo que viene!</h2>
    <p>Para enterarte de nuevas herramientas y novedades dejanos tu email.</p>
    <label class="news-label" for="newsEmail">Ingres&aacute; tu email</label>
    <input class="news-input" type="email" id="newsEmail" name="email" placeholder="tu@email.com" autocomplete="email" required>
    <input type="text" id="newsHoney" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" aria-hidden="true">
    <button class="pro-modal__cta" id="newsSubmit" type="submit">Quiero enterarme</button>
    <button class="pro-modal__close" id="newsClose" type="button">Ahora no</button>
  </form>
</div>

<!-- Documento de presupuesto para Exportar PDF (oculto en pantalla) -->
<div id="printDoc" aria-hidden="true"></div>

<div class="toast" id="toast"></div>

<script>
(function() {
  'use strict';

  // State
  let currency = { code: 'ARS', symbol: '$' };
  const $  = id => document.getElementById(id);
  const val = id => parseFloat($(id).value) || 0;
  const fmt = n => currency.symbol + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  // Cotizaciones cargadas desde el servidor
  let quotesCache = [];

  // Helper para hablar con api.php
  async function apiCall(action, payload) {
    const opts = { method: 'GET', headers: {} };
    if (payload !== undefined) {
      opts.method = 'POST';
      opts.headers['Content-Type'] = 'application/json';
      opts.headers['X-CSRF-Token'] = (window.APP && window.APP.csrf) || '';
      opts.body = JSON.stringify(payload);
    }
    const base = (window.APP && window.APP.api) || 'api.php';
    const res = await fetch(base + '?action=' + action, opts);
    if (res.status === 401) { throw new Error('Funcion no habilitada'); }
    return res.json();
  }

  // Currency buttons
  document.querySelectorAll('.currency-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.currency-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currency.code = btn.dataset.currency;
      currency.symbol = btn.dataset.symbol;
      // Update unit labels
      document.querySelectorAll('#spoolPriceUnit, #supportPriceUnit, #electricRateUnit, #laborRateUnit, #printerCostUnit, #maintenanceCostUnit, #packagingUnit, #shippingUnit, #otherCostsUnit, #fixedPriceUnit, #meliFixedFeeUnit').forEach(el => {
        el.textContent = '(' + currency.symbol + ')';
      });
      if ($('electricRateUnit')) $('electricRateUnit').textContent = '(' + currency.symbol + '/kWh)';
      calculate();
    });
  });

  // En el panel, arrancar con la moneda del taller (elegida en Configuracion)
  if (window.APP && APP.panel && APP.moneda && APP.moneda !== 'ARS') {
    const btnMoneda = document.querySelector('.currency-btn[data-currency="' + APP.moneda + '"]');
    if (btnMoneda) btnMoneda.click();
  }

  // === Acciones del panel: conectar la calculadora con el taller ===
  window.guardarProducto = async function () {
    const nombre = $('projectName').value.trim();
    if (!nombre) {
      showToast('Escribi el nombre del producto en el campo de arriba');
      window.scrollTo({ top: 0, behavior: 'smooth' });
      $('projectName').focus();
      return;
    }
    const c = window.__calc || {};
    try {
      const r = await apiCall('producto', {
        nombre,
        costo: c.costo || 0,
        precio: c.precio || 0,
        datos: { material: c.material || '', peso_g: c.peso_g || 0, horas: c.horas || 0, minutos: c.minutos || 0 }
      });
      if (!r || !r.ok) throw new Error((r && r.error) || 'Error');
      showToast(r.actualizado ? 'Producto actualizado en tu catalogo' : 'Producto creado en tu catalogo');
    } catch (e) {
      showToast('No se pudo guardar: ' + e.message);
    }
  };

  window.crearPresupuesto = function () {
    const c = window.__calc || {};
    const nombre = $('projectName').value.trim() || 'Impresion 3D';
    try {
      localStorage.setItem('ptools_pieza_calculadora', JSON.stringify({
        nombre,
        precio: c.precio || 0,
        costo: c.costo || 0,
        datos: { material: c.material || '', peso_g: c.peso_g || 0, horas: c.horas || 0, minutos: c.minutos || 0 }
      }));
    } catch (e) {}
    // URL absoluta: el clic nace dentro del iframe y lo relativo se
    // resolveria contra /cotizador/ (pagina inexistente)
    window.top.location.href = new URL('../presupuesto.php?desde=calculadora', window.location.href).href;
  };

  // Support toggle
  $('supportToggle').addEventListener('change', function() {
    $('supportFields').classList.toggle('active', this.checked);
    calculate();
  });

  // Fixed price toggle
  $('fixedPriceToggle').addEventListener('change', function() {
    $('marginSliderSection').style.display = this.checked ? 'none' : 'block';
    $('fixedPriceSection').style.display = this.checked ? 'block' : 'none';
    calculate();
  });

  // MercadoLibre toggle
  $('meliToggle').addEventListener('change', function() {
    $('meliSection').style.display = this.checked ? 'block' : 'none';
    $('meliSummaryBlock').style.display = this.checked ? 'block' : 'none';
    calculate();
  });

  // Margin slider
  $('marginSlider').addEventListener('input', function() {
    $('marginDisplay').textContent = this.value + '%';
    calculate();
  });

  // Bind all inputs to recalculate
  // Modelo de impresora: autocompleta el consumo detectando el "(NNN W)" del nombre
  function aplicarModeloImpresora() {
    const sel = $('printerModel');
    if (!sel) return;
    const m = (sel.value || '').match(/\((\d+)\s*W\)/i);
    if (m) { $('printerWatts').value = m[1]; $('printerWatts').disabled = true; }
    else { $('printerWatts').disabled = false; }
  }
  window.aplicarModeloImpresora = aplicarModeloImpresora;
  if ($('printerModel')) {
    try {
      const guardado = localStorage.getItem('calc3d_modelo_impresora');
      if (guardado !== null) $('printerModel').value = guardado;
    } catch (e) {}
    aplicarModeloImpresora();
    $('printerModel').addEventListener('change', () => {
      try { localStorage.setItem('calc3d_modelo_impresora', $('printerModel').value); } catch (e) {}
      aplicarModeloImpresora();
      calculate();
    });
  }

  document.querySelectorAll('input[type="number"], input[type="range"], select').forEach(el => {
    el.addEventListener('input', calculate);
    el.addEventListener('change', calculate);
  });

  function calculate() {
    // Print time
    const hours = val('printHours');
    const minutes = val('printMinutes');
    const totalHours = hours + minutes / 60;
    const totalMinutes = hours * 60 + minutes;
    $('totalTimeDisplay').textContent = Math.floor(totalHours) + 'h ' + Math.round(minutes) + 'm';

    // Material cost
    const spoolPrice = val('spoolPrice');
    const spoolWeight = val('spoolWeight') || 1;
    const costPerGram = spoolPrice / spoolWeight;
    const materialWeight = val('materialWeight');
    const materialCost = costPerGram * materialWeight;
    $('costPerGram').textContent = fmt(costPerGram);
    $('totalMaterialCost').textContent = fmt(materialCost);

    // Support material cost
    let supportCost = 0;
    if ($('supportToggle').checked) {
      const sw = val('supportWeight');
      const sm = $('supportMaterial').value;
      let sCostPerGram;
      if (sm === 'same') {
        sCostPerGram = costPerGram;
      } else {
        const sSpoolPrice = val('supportSpoolPrice');
        const sSpoolWeight = val('supportSpoolWeight') || 1;
        sCostPerGram = sSpoolPrice / sSpoolWeight;
      }
      supportCost = sCostPerGram * sw;
    }

    // Electricity
    const watts = val('printerWatts');
    const rate = val('electricRate');
    const electricCost = (watts / 1000) * totalHours * rate;
    $('totalElectricCost').textContent = fmt(electricCost);

    // Labor
    const prepMin = val('prepTime');
    const postMin = val('postTime');
    const laborRate = val('laborRate');
    const laborCost = ((prepMin + postMin) / 60) * laborRate;
    $('totalLaborCost').textContent = fmt(laborCost);

    // Depreciation
    const printerCost = val('printerCost');
    const lifespan = val('printerLifespan') || 1;
    const maintenance = val('maintenanceCost');
    // Assume ~1500 hours/year use for maintenance spread
    const maintenancePerHour = maintenance / 1500;
    const depPerHour = (printerCost / lifespan) + maintenancePerHour;
    const depTotal = depPerHour * totalHours;
    $('depPerHour').textContent = fmt(depPerHour);
    $('totalDepCost').textContent = fmt(depTotal);

    // Additional costs
    const packaging = val('packagingCost');
    const shipping = val('shippingCost');
    const otherCosts = val('otherCosts');
    const additionalCosts = packaging + shipping + otherCosts;

    // Failure rate
    const failureRate = val('failureRate') / 100;

    // Subtotal before failure rate
    const baseCost = materialCost + supportCost + electricCost + laborCost + depTotal + additionalCosts;
    // Apply failure rate: cost / (1 - failureRate)
    const subtotal = failureRate < 1 ? baseCost / (1 - failureRate) : baseCost * 100;

    // Margin
    let margin, finalPrice;
    if ($('fixedPriceToggle').checked) {
      finalPrice = val('fixedPrice');
      margin = finalPrice - subtotal;
      const pct = subtotal > 0 ? ((margin / subtotal) * 100) : 0;
      $('computedMarginPct').textContent = pct.toFixed(1) + '%';
      $('computedMarginPct').style.color = margin >= 0 ? 'var(--success)' : 'var(--danger)';
    } else {
      const marginPct = val('marginSlider') / 100;
      margin = subtotal * marginPct;
      finalPrice = subtotal + margin;
    }
    $('marginAmount').textContent = fmt(margin);

    // Summary
    $('sumMaterial').textContent = fmt(materialCost);
    $('sumSupportRow').style.display = $('supportToggle').checked ? 'flex' : 'none';
    $('sumSupport').textContent = fmt(supportCost);
    $('sumElectric').textContent = fmt(electricCost);
    $('sumLabor').textContent = fmt(laborCost);
    $('sumDep').textContent = fmt(depTotal);

    // Additional includes failure adjustment
    const failureAdj = subtotal - baseCost;
    const totalAdditionalDisplay = additionalCosts + failureAdj;
    $('sumAdditional').textContent = fmt(totalAdditionalDisplay);

    $('sumSubtotal').textContent = fmt(subtotal);
    $('sumMargin').textContent = fmt(margin);
    $('finalPrice').textContent = fmt(finalPrice);

    // MercadoLibre - calcular precio de publicacion sumando comisiones
    if ($('meliToggle').checked) {
      const meliPct = val('meliCommission') / 100;
      const meliFixed = val('meliFixedFee');
      // Precio ML = lo que necesitas cobrar para que despues de la comision te quede tu precio final
      // precioML - (precioML * meliPct) - meliFixed = finalPrice
      // precioML = (finalPrice + meliFixed) / (1 - meliPct)
      const precioML = meliPct < 1 ? (finalPrice + meliFixed) / (1 - meliPct) : finalPrice;
      const meliCommAmount = precioML * meliPct;
      const meliTotal = meliCommAmount + meliFixed;

      $('meliCostDisplay').textContent = fmt(meliTotal);
      $('sumMeliPct').textContent = val('meliCommission').toFixed(1) + '%';
      $('sumMeliAmount').textContent = fmt(meliCommAmount);
      $('sumMeliFixedRow').style.display = meliFixed > 0 ? 'flex' : 'none';
      $('sumMeliFixed').textContent = fmt(meliFixed);
      $('sumMeliTotal').textContent = fmt(meliTotal);
      $('meliMLPrice').textContent = fmt(precioML);
      $('meliNetCheck').textContent = fmt(finalPrice);
      $('meliMarginCheck').textContent = fmt(margin);
    }

    // Metrics
    const totalWeight = materialWeight + (($('supportToggle').checked) ? val('supportWeight') : 0);
    $('metricPerGram').textContent = totalWeight > 0 ? fmt(finalPrice / totalWeight) : fmt(0);
    $('metricPerHour').textContent = totalHours > 0 ? fmt(finalPrice / totalHours) : fmt(0);

    // Donut chart
    updateChart(materialCost + supportCost, electricCost, laborCost, depTotal, totalAdditionalDisplay);

    // Ultimo calculo en numeros crudos (lo usan las acciones del panel)
    window.__calc = {
      costo: Math.round(subtotal * 100) / 100,
      precio: Math.round(finalPrice * 100) / 100,
      material: $('materialType').value,
      peso_g: totalWeight,
      horas: hours,
      minutos: minutes
    };
  }

  function updateChart(mat, elec, labor, dep, add) {
    const total = mat + elec + labor + dep + add;
    if (total <= 0) {
      $('donutChart').style.background = 'var(--border-color)';
      $('chartLegend').innerHTML = '';
      return;
    }

    const segments = [
      { name: 'Material', value: mat, color: 'var(--chart-material)' },
      { name: 'Electricidad', value: elec, color: 'var(--chart-electric)' },
      { name: 'Mano de obra', value: labor, color: 'var(--chart-labor)' },
      { name: 'Depreciacion', value: dep, color: 'var(--chart-depreciation)' },
      { name: 'Adicionales', value: add, color: 'var(--chart-additional)' },
    ].filter(s => s.value > 0);

    let cumPct = 0;
    const gradientParts = [];
    segments.forEach(seg => {
      const pct = (seg.value / total) * 100;
      gradientParts.push(`${seg.color} ${cumPct}% ${cumPct + pct}%`);
      seg.pct = pct;
      cumPct += pct;
    });

    $('donutChart').style.background = `conic-gradient(${gradientParts.join(', ')})`;

    $('chartLegend').innerHTML = segments.map(s =>
      `<div class="legend-item">
        <span class="legend-dot" style="background:${s.color}"></span>
        ${s.name}
        <span class="legend-pct">${s.pct.toFixed(1)}%</span>
      </div>`
    ).join('');
  }

  // Toast
  function showToast(msg) {
    const t = $('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
  }

  // Save quote (en el servidor)
  window.saveQuote = async function() {
    const name = $('projectName').value.trim() || 'Sin nombre';
    const price = $('finalPrice').textContent;

    const data = {
      name,
      price,
      currency: currency.code,
      date: new Date().toLocaleDateString('es-AR'),
      inputs: {}
    };

    // Save all input values
    document.querySelectorAll('input[type="number"], input[type="range"], select, input[type="text"]').forEach(el => {
      if (el.id) data.inputs[el.id] = el.value;
    });
    data.inputs._supportToggle = $('supportToggle').checked;
    data.inputs._fixedPriceToggle = $('fixedPriceToggle').checked;
    data.inputs._meliToggle = $('meliToggle').checked;
    data.inputs._currency = currency.code;

    try {
      const r = await apiCall('save', data);
      if (!r || !r.ok) throw new Error((r && r.error) || 'Error');
      await renderQuotes();
      showToast('Cotizacion guardada correctamente');
    } catch (e) {
      showToast('No se pudo guardar: ' + e.message);
    }
  };

  // Load quote (desde el cache traido del servidor)
  window.loadQuote = function(id) {
    setTimeout(function(){ if (window.aplicarModeloImpresora) window.aplicarModeloImpresora(); }, 0);
    const q = quotesCache.find(q => String(q.id) === String(id));
    if (!q) return;

    // Set currency first
    if (q.inputs._currency) {
      const btn = document.querySelector(`.currency-btn[data-currency="${q.inputs._currency}"]`);
      if (btn) btn.click();
    }

    // Restore inputs
    Object.keys(q.inputs).forEach(key => {
      if (key.startsWith('_')) return;
      const el = $(key);
      if (el) el.value = q.inputs[key];
    });

    if (q.inputs._supportToggle) {
      $('supportToggle').checked = true;
      $('supportFields').classList.add('active');
    } else {
      $('supportToggle').checked = false;
      $('supportFields').classList.remove('active');
    }

    if (q.inputs._fixedPriceToggle) {
      $('fixedPriceToggle').checked = true;
      $('marginSliderSection').style.display = 'none';
      $('fixedPriceSection').style.display = 'block';
    } else {
      $('fixedPriceToggle').checked = false;
      $('marginSliderSection').style.display = 'block';
      $('fixedPriceSection').style.display = 'none';
    }

    if (q.inputs._meliToggle) {
      $('meliToggle').checked = true;
      $('meliSection').style.display = 'block';
      $('meliSummaryBlock').style.display = 'block';
    } else {
      $('meliToggle').checked = false;
      $('meliSection').style.display = 'none';
      $('meliSummaryBlock').style.display = 'none';
    }

    $('marginDisplay').textContent = $('marginSlider').value + '%';
    calculate();
    showToast('Cotizacion cargada');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Delete quote (en el servidor)
  window.deleteQuote = async function(id) {
    if (!confirm('¿Eliminar esta cotizacion?')) return;
    try {
      const r = await apiCall('delete', { id });
      if (!r || !r.ok) throw new Error((r && r.error) || 'Error');
      await renderQuotes();
      showToast('Cotizacion eliminada');
    } catch (e) {
      showToast('No se pudo eliminar: ' + e.message);
    }
  };

  // Render quotes (lista traida del servidor)
  async function renderQuotes() {
    let quotes = [];
    try {
      const r = await apiCall('list');
      quotes = Array.isArray(r) ? r : (r && r.quotes) || [];
    } catch (e) {
      $('quotesList').innerHTML = '<div class="no-quotes">No se pudieron cargar las cotizaciones</div>';
      return;
    }
    quotesCache = quotes;
    if (quotes.length === 0) {
      $('quotesList').innerHTML = '<div class="no-quotes">No hay cotizaciones guardadas</div>';
      return;
    }
    $('quotesList').innerHTML = quotes.map(q =>
      `<div class="quote-item">
        <div class="quote-info">
          <div class="quote-name">${escapeHtml(q.name)}</div>
          <div class="quote-meta">${q.date} &middot; ${q.currency || 'ARS'}</div>
        </div>
        <div class="quote-price">${q.price}</div>
        <div class="quote-actions">
          <button title="Cargar" onclick="loadQuote(${q.id})">&#128194;</button>
          <button class="delete-btn" title="Eliminar" onclick="deleteQuote(${q.id})">&#128465;</button>
        </div>
      </div>`
    ).join('');
  }

  function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
  }

  // Export PDF
  // Exportar PDF: arma un documento de presupuesto limpio (solo los
  // costos realmente usados; los que estan en 0 no se incluyen) y lo
  // manda a imprimir. En pantalla nunca se ve: vive en @media print.
  function buildPrintDoc() {
    const doc = $('printDoc');
    if (!doc) return;
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const txt = (id) => { const el = $(id); return el ? el.textContent.trim() : ''; };
    const nonZero = (t) => parseInt(String(t).replace(/[^\d]/g, '') || '0', 10) > 0;
    const visible = (id) => { const el = $(id); return !!el && el.style.display !== 'none'; };

    const nombre = $('projectName').value.trim() || 'Impresion 3D';
    const fecha = new Date().toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const material = $('materialType').value;
    const peso = $('materialWeight').value || '0';
    const horas = $('printHours').value || '0';
    const minutos = $('printMinutes').value || '0';

    const filas = [];
    filas.push(['Material', txt('sumMaterial')]);
    if (visible('sumSupportRow') && nonZero(txt('sumSupport'))) filas.push(['Material de soporte', txt('sumSupport')]);
    if (nonZero(txt('sumElectric'))) filas.push(['Electricidad', txt('sumElectric')]);
    if (nonZero(txt('sumLabor'))) filas.push(['Mano de obra', txt('sumLabor')]);
    if (nonZero(txt('sumDep'))) filas.push(['Depreciacion de maquina', txt('sumDep')]);
    if (nonZero(txt('sumAdditional'))) filas.push(['Costos adicionales', txt('sumAdditional')]);

    let html =
      '<div class="pd-top">' +
        '<img class="pd-logo" src="../../assets/img/printika-tools.svg" alt="Printika Tools">' +
        '<div class="pd-meta">' +
          '<div class="pd-kicker">Presupuesto</div>' +
          '<div class="pd-project">' + esc(nombre) + '</div>' +
          '<div class="pd-date">' + esc(fecha) + ' &middot; ' + esc(currency.code) + '</div>' +
        '</div>' +
      '</div>' +
      '<div class="pd-specs">' +
        '<div class="pd-spec">Material<strong>' + esc(material) + '</strong></div>' +
        '<div class="pd-spec">Peso de la pieza<strong>' + esc(peso) + ' g</strong></div>' +
        '<div class="pd-spec">Tiempo de impresion<strong>' + esc(horas) + ' h ' + esc(minutos) + ' m</strong></div>' +
        '<div class="pd-spec">Costo por gramo<strong>' + esc(txt('metricPerGram')) + '</strong></div>' +
      '</div>' +
      '<div class="pd-rows">' +
        filas.map((f) => '<div class="pd-row"><span class="l">' + esc(f[0]) + '</span><span class="v">' + esc(f[1]) + '</span></div>').join('') +
        '<div class="pd-row sub"><span class="l">Subtotal (costo)</span><span class="v">' + esc(txt('sumSubtotal')) + '</span></div>' +
        '<div class="pd-row"><span class="l">Margen de ganancia</span><span class="v">' + esc(txt('sumMargin')) + '</span></div>' +
      '</div>' +
      '<div class="pd-total"><span class="l">Precio final</span><span class="v">' + esc(txt('finalPrice')) + '</span></div>';

    if (visible('meliSummaryBlock')) {
      html +=
        '<div class="pd-meli">' +
          '<div class="pd-h">Mercado Libre</div>' +
          '<div class="pd-row"><span class="l">Comision (' + esc(txt('sumMeliPct')) + ')</span><span class="v">' + esc(txt('sumMeliTotal')) + '</span></div>' +
          '<div class="pd-row sub"><span class="l">Publicar en Mercado Libre a</span><span class="v">' + esc(txt('meliMLPrice')) + '</span></div>' +
        '</div>';
    }

    doc.innerHTML = html;
  }

  window.exportPDF = function() {
    buildPrintDoc();
    setTimeout(() => window.print(), 60);
  };

  // Share
  window.shareQuote = function() {
    const name = $('projectName').value.trim() || 'Impresion 3D';
    const price = $('finalPrice').textContent;
    const subtotal = $('sumSubtotal').textContent;
    const material = $('sumMaterial').textContent;
    const hours = val('printHours');
    const minutes = val('printMinutes');

    let meliInfo = '';
    if ($('meliToggle').checked) {
      meliInfo = `
Comision ML: ${$('sumMeliTotal').textContent}
PRECIO EN MERCADO LIBRE: ${$('meliMLPrice').textContent}
Te queda neto: ${$('meliNetCheck').textContent}`;
    }

    const text = `--- Cotizacion 3D ---
Proyecto: ${name}
Material: ${$('materialType').value} - ${val('materialWeight')}g
Tiempo: ${hours}h ${minutes}m
Costo material: ${material}
Subtotal: ${subtotal}
PRECIO FINAL: ${price}${meliInfo}
---`;

    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(() => {
        showToast('Resumen copiado al portapapeles');
      }).catch(() => {
        fallbackCopy(text);
      });
    } else {
      fallbackCopy(text);
    }
  };

  function fallbackCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('Resumen copiado al portapapeles');
  }

  // Reset
  window.resetAll = function() {
    const defaults = {
      projectName: '',
      materialType: 'PLA',
      materialWeight: '50',
      spoolPrice: '15000',
      spoolWeight: '1000',
      supportWeight: '10',
      supportMaterial: 'same',
      supportSpoolPrice: '20000',
      supportSpoolWeight: '1000',
      printHours: '3',
      printMinutes: '30',
      printerWatts: '200',
      electricRate: '239.17',
      // Campos PRO: en FREE arrancan en 0 (no suman al precio); en PRO con valores tipicos
      prepTime: IS_PRO ? '15' : '0',
      postTime: IS_PRO ? '10' : '0',
      laborRate: IS_PRO ? '3000' : '0',
      printerCost: IS_PRO ? '500000' : '0',
      printerLifespan: '2000',
      maintenanceCost: IS_PRO ? '30000' : '0',
      packagingCost: '0',
      shippingCost: '0',
      failureRate: IS_PRO ? '5' : '0',
      otherCosts: '0',
      marginSlider: '30',
      fixedPrice: '0',
      meliCommission: '14',
      meliFixedFee: '0',
      meliListingType: 'premium'
    };

    Object.keys(defaults).forEach(key => {
      const el = $(key);
      if (el) el.value = defaults[key];
    });

    $('supportToggle').checked = false;
    $('supportFields').classList.remove('active');
    $('fixedPriceToggle').checked = false;
    $('marginSliderSection').style.display = 'block';
    $('fixedPriceSection').style.display = 'none';
    $('marginDisplay').textContent = '30%';
    $('meliToggle').checked = false;
    $('meliSection').style.display = 'none';
    $('meliSummaryBlock').style.display = 'none';

    calculate();
    showToast('Valores reiniciados');
  };

  // === Bloqueos PRO ===
  //  IS_PRO: campos PRO habilitados (sesion o prueba por tiempo limitado).
  //  IS_SESION: sesion PRO real (login) — unica que puede usar las Acciones.
  const IS_PRO = !!(window.APP && window.APP.pro);
  const IS_SESION = !!(window.APP && window.APP.sesion);
  const proModal = $('proModal');
  window.showProModal = function (e) {
    if (e && e.preventDefault) { e.preventDefault(); e.stopPropagation(); }
    proModal.classList.add('open');
  };
  window.closeProModal = function () { proModal.classList.remove('open'); };
  proModal.addEventListener('click', (ev) => { if (ev.target === proModal) closeProModal(); });
  document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') closeProModal(); });

  if (!IS_PRO) {
    // Secciones PRO: los controles no reciben eventos (pointer-events none),
    // el click cae en la seccion y abre el cartel. Tab/teclado tambien bloqueado.
    ['sec-additional', 'sec-meli', 'sec-labor', 'sec-depreciation', 'supportProRow'].forEach((id) => {
      const sec = document.getElementById(id);
      if (!sec) return;
      sec.classList.add('pro-locked');
      sec.addEventListener('click', showProModal);
      sec.addEventListener('focusin', (ev) => { ev.target.blur(); showProModal(); });
    });
  }

  if (!IS_SESION) {
    // Acciones PRO: guardar, exportar y compartir muestran el cartel
    // (tambien durante la prueba: son exclusivas de la suscripcion).
    window.saveQuote = showProModal;
    window.exportPDF = showProModal;
    window.shareQuote = showProModal;
  }

  // === Selector dia/noche ===
  const themeBtns = document.querySelectorAll('.theme-opt');
  function applyTheme(t) {
    if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
    else document.documentElement.removeAttribute('data-theme');
    themeBtns.forEach((b) => b.classList.toggle('active', b.dataset.themeOpt === t));
    try { localStorage.setItem('calc3d-theme', t); } catch (e) {}
  }
  themeBtns.forEach((b) => b.addEventListener('click', () => applyTheme(b.dataset.themeOpt)));
  // marcar el boton activo segun el tema ya aplicado en el <head>
  themeBtns.forEach((b) => b.classList.toggle('active',
    b.dataset.themeOpt === (document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark')));

  // === Popup de novedades (captura de email) ===
  // Se muestra en CADA visita/recarga, apenas entra el usuario.
  const newsModal = $('newsModal');
  if (newsModal) {
    const cerrarNews = () => newsModal.classList.remove('open');
    setTimeout(() => newsModal.classList.add('open'), 1200);
    newsModal.addEventListener('click', (ev) => { if (ev.target === newsModal) cerrarNews(); });
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') cerrarNews(); });
    $('newsClose').addEventListener('click', cerrarNews);

    // Reiniciar: ademas de resetear los valores, vuelve a mostrar el popup
    const resetOriginal = window.resetAll;
    window.resetAll = function () {
      resetOriginal();
      newsModal.classList.add('open');
    };
    $('newsForm').addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const email = $('newsEmail').value.trim();
      if (!email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
        showToast('Ingresa un email valido');
        return;
      }
      const btn = $('newsSubmit');
      btn.disabled = true;
      try {
        const res = await fetch('suscribir.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': (window.APP && window.APP.csrf) || '' },
          body: JSON.stringify({ email: email, website: $('newsHoney').value })
        });
        const r = await res.json();
        if (!r || !r.ok) throw new Error((r && r.error) || 'Error');
        cerrarNews();
        showToast('Gracias! Te vamos a avisar de las novedades.');
      } catch (e) {
        showToast('No se pudo enviar: ' + e.message);
        btn.disabled = false;
      }
    });
  }

  // === Contador regresivo de la prueba PRO ===
  const trialCount = $('trialCount');
  if (trialCount && window.APP.trialEnd) {
    const fin = window.APP.trialEnd;
    const pad = (n) => String(n).padStart(2, '0');
    function tickTrial() {
      let ms = fin - Date.now();
      if (ms <= 0) { location.reload(); return; } // vencio: vuelven los candados
      const d = Math.floor(ms / 86400000);
      const h = Math.floor(ms / 3600000) % 24;
      const m = Math.floor(ms / 60000) % 60;
      const s = Math.floor(ms / 1000) % 60;
      trialCount.textContent = 'Quedan ' + d + (d === 1 ? ' dia ' : ' dias ') + pad(h) + ':' + pad(m) + ':' + pad(s);
    }
    tickTrial();
    setInterval(tickTrial, 1000);
  }

  // Init (las cotizaciones guardadas son solo de la sesion PRO)
  if (IS_SESION) renderQuotes();
  calculate();
})();
</script>
</body>
</html>