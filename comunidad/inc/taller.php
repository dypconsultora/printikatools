<?php
/**
 * "Mi taller": clientes, productos y presupuestos por usuario.
 * Migración perezosa: cada página del taller llama a taller_migrar().
 */
require_once __DIR__ . '/auth.php';

function taller_migrar() {
    static $listo = false;
    if ($listo) return;
    $db = com_db();

    $db->exec("CREATE TABLE IF NOT EXISTS clientes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        telefono VARCHAR(50) NOT NULL DEFAULT '',
        email VARCHAR(190) NOT NULL DEFAULT '',
        notas TEXT NULL,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_usuario_nombre (usuario_id, nombre),
        CONSTRAINT fk_cli_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Moneda del taller elegida por el usuario (vacía = todavía no eligió)
    $stmt = $db->prepare("SELECT COUNT(*) c FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'moneda'");
    $stmt->execute();
    if (!(int) $stmt->fetch()['c']) {
        $db->exec("ALTER TABLE usuarios ADD COLUMN moneda VARCHAR(3) NOT NULL DEFAULT ''");
    }

    // Columnas nuevas de clientes (alta desde la pantalla Clientes)
    $stmt = $db->prepare("SELECT COUNT(*) c FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'empresa'");
    $stmt->execute();
    if (!(int) $stmt->fetch()['c']) {
        $db->exec("ALTER TABLE clientes
            ADD COLUMN empresa VARCHAR(150) NOT NULL DEFAULT '' AFTER email,
            ADD COLUMN direccion VARCHAR(200) NOT NULL DEFAULT '' AFTER empresa,
            ADD COLUMN ciudad VARCHAR(100) NOT NULL DEFAULT '' AFTER direccion,
            ADD COLUMN provincia VARCHAR(100) NOT NULL DEFAULT '' AFTER ciudad");
    }

    $db->exec("CREATE TABLE IF NOT EXISTS productos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT NULL,
        costo DECIMAL(12,2) NOT NULL DEFAULT 0,
        precio DECIMAL(12,2) NOT NULL DEFAULT 0,
        datos_json LONGTEXT NULL,
        creado_en DATETIME NOT NULL,
        actualizado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_usuario (usuario_id),
        CONSTRAINT fk_prod_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS presupuestos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        cliente_id BIGINT UNSIGNED NULL,
        cliente_nombre VARCHAR(150) NOT NULL DEFAULT '',
        estado ENUM('pendiente','vendido') NOT NULL DEFAULT 'pendiente',
        descuento_tipo ENUM('monto','porcentaje') NOT NULL DEFAULT 'monto',
        descuento_valor DECIMAL(12,2) NOT NULL DEFAULT 0,
        notas TEXT NULL,
        creado_en DATETIME NOT NULL,
        actualizado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_usuario_estado (usuario_id, estado),
        CONSTRAINT fk_pres_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE,
        CONSTRAINT fk_pres_cliente FOREIGN KEY (cliente_id)
            REFERENCES clientes(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS presupuesto_items (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        presupuesto_id BIGINT UNSIGNED NOT NULL,
        producto_id BIGINT UNSIGNED NULL,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT NULL,
        cantidad INT NOT NULL DEFAULT 1,
        precio_unit DECIMAL(12,2) NOT NULL DEFAULT 0,
        costo_unit DECIMAL(12,2) NOT NULL DEFAULT 0,
        datos_json LONGTEXT NULL,
        PRIMARY KEY (id),
        KEY idx_presupuesto (presupuesto_id),
        CONSTRAINT fk_item_presupuesto FOREIGN KEY (presupuesto_id)
            REFERENCES presupuestos(id) ON DELETE CASCADE,
        CONSTRAINT fk_item_producto FOREIGN KEY (producto_id)
            REFERENCES productos(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $listo = true;
}

/** Monedas disponibles del taller: código => [símbolo, decimales]. */
function taller_monedas() {
    return ['ARS' => ['$', 0], 'USD' => ['US$', 2], 'EUR' => ['€', 2]];
}

/** Moneda elegida por el usuario actual ('' si todavía no eligió). */
function taller_moneda_usuario() {
    $u = usuario_actual();
    $m = $u['moneda'] ?? '';
    return isset(taller_monedas()[$m]) ? $m : '';
}

/** Formato de precio en la moneda del taller (ARS por defecto): $ 12.450 / US$ 12,50 */
function taller_precio($n) {
    $m = taller_moneda_usuario() ?: 'ARS';
    [$simbolo, $dec] = taller_monedas()[$m];
    return $simbolo . ' ' . number_format((float) $n, $dec, ',', '.');
}

/** Guarda la moneda elegida. Devuelve true si es válida. */
function taller_guardar_moneda($usuario_id, $moneda) {
    if (!isset(taller_monedas()[$moneda])) return false;
    com_db()->prepare('UPDATE usuarios SET moneda=? WHERE id=?')->execute([$moneda, (int) $usuario_id]);
    return true;
}

/**
 * Popup de elección de moneda (se muestra si el usuario todavía no eligió)
 * + chip para cambiarla después. Llamar dentro del panel, requiere com_csrf().
 */
function taller_popup_moneda($forzar = false) {
    $actual = taller_moneda_usuario();
    $abierto = $forzar || $actual === '';
    ?>
    <style>
      .velo-moneda{position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.55);display:flex;
          align-items:center;justify-content:center;padding:20px}
      .caja-moneda{background:var(--surface);border:1px solid var(--bd);border-radius:var(--radio-g);
          padding:28px;max-width:460px;width:100%}
      .caja-moneda h2{font-size:17px;font-weight:700;margin-bottom:6px}
      .caja-moneda p{font-size:13.5px;color:var(--txt-2);margin-bottom:18px}
      .opciones-moneda{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
      .opciones-moneda button{background:var(--surface-2);border:1px solid var(--bd);border-radius:var(--radio);
          padding:16px 10px;color:var(--txt);cursor:pointer;font-family:inherit;text-align:center;
          transition:border-color .15s ease}
      .opciones-moneda button:hover{border-color:var(--accent)}
      .opciones-moneda button.actual{border-color:var(--accent);background:var(--accent-tinte)}
      .opciones-moneda b{display:block;font-size:18px}
      .opciones-moneda span{font-size:11.5px;color:var(--txt-2)}
      .chip-moneda{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--txt-2);
          background:var(--surface-2);border:1px solid var(--bd-suave);border-radius:99px;
          padding:4px 12px;cursor:pointer}
      .chip-moneda:hover{border-color:var(--bd)}
      .chip-moneda b{color:var(--txt)}
    </style>
    <div class="velo-moneda" id="veloMoneda" <?php echo $abierto ? '' : 'hidden'; ?>>
      <div class="caja-moneda">
        <h2>¿En qué moneda trabajás?</h2>
        <p>Todos tus presupuestos, productos y la calculadora del taller van a usar esta moneda.
           Podés cambiarla cuando quieras desde el chip de moneda.</p>
        <form method="post" class="opciones-moneda">
          <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
          <input type="hidden" name="accion" value="moneda">
          <button type="submit" name="moneda" value="ARS" class="<?php echo $actual === 'ARS' ? 'actual' : ''; ?>">
            <b>$</b><span>Peso argentino</span></button>
          <button type="submit" name="moneda" value="USD" class="<?php echo $actual === 'USD' ? 'actual' : ''; ?>">
            <b>US$</b><span>Dólar</span></button>
          <button type="submit" name="moneda" value="EUR" class="<?php echo $actual === 'EUR' ? 'actual' : ''; ?>">
            <b>€</b><span>Euro</span></button>
        </form>
        <?php if ($actual !== ''): ?>
          <p style="margin:14px 0 0;text-align:center">
            <button type="button" class="btn sec chico" onclick="document.getElementById('veloMoneda').hidden=true">Cancelar</button></p>
        <?php endif; ?>
      </div>
    </div>
    <?php
}

/** Chip que muestra la moneda actual y reabre el popup. */
function taller_chip_moneda() {
    $m = taller_moneda_usuario() ?: 'ARS';
    [$simbolo] = taller_monedas()[$m];
    echo '<button type="button" class="chip-moneda" onclick="document.getElementById(\'veloMoneda\').hidden=false">'
       . 'Moneda: <b>' . $m . ' (' . $simbolo . ')</b> · cambiar</button>';
}

/** Busca o crea el cliente por nombre para el usuario. Devuelve id o null. */
function taller_cliente_id($usuario_id, $nombre) {
    $nombre = trim($nombre);
    if ($nombre === '') return null;
    $stmt = com_db()->prepare('SELECT id FROM clientes WHERE usuario_id = ? AND nombre = ? LIMIT 1');
    $stmt->execute([$usuario_id, $nombre]);
    $fila = $stmt->fetch();
    if ($fila) return (int) $fila['id'];
    com_db()->prepare('INSERT INTO clientes (usuario_id, nombre, creado_en) VALUES (?, ?, NOW())')
        ->execute([$usuario_id, $nombre]);
    return (int) com_db()->lastInsertId();
}

/** Totales de un presupuesto: [subtotal, descuento, total]. */
function taller_totales($presupuesto, $items) {
    $subtotal = 0;
    foreach ($items as $it) {
        $subtotal += $it['precio_unit'] * $it['cantidad'];
    }
    $desc = (float) $presupuesto['descuento_valor'];
    $descuento = $presupuesto['descuento_tipo'] === 'porcentaje'
        ? $subtotal * min(100, max(0, $desc)) / 100
        : min($subtotal, max(0, $desc));
    return [$subtotal, $descuento, max(0, $subtotal - $descuento)];
}
