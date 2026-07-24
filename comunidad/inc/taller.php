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

    $db->exec("CREATE TABLE IF NOT EXISTS movimientos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        tipo ENUM('ingreso','gasto') NOT NULL,
        concepto VARCHAR(200) NOT NULL,
        monto DECIMAL(12,2) NOT NULL DEFAULT 0,
        fecha DATE NOT NULL,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_usuario_fecha (usuario_id, fecha),
        CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Datos del taller del usuario (Configuración): nombre, teléfono y logo propio
    $stmt = $db->prepare("SELECT COUNT(*) c FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'taller_nombre'");
    $stmt->execute();
    if (!(int) $stmt->fetch()['c']) {
        $db->exec("ALTER TABLE usuarios
            ADD COLUMN taller_nombre VARCHAR(150) NOT NULL DEFAULT '',
            ADD COLUMN taller_telefono VARCHAR(50) NOT NULL DEFAULT '',
            ADD COLUMN logo_ext VARCHAR(5) NOT NULL DEFAULT ''");
    }

    // Fecha en que el presupuesto se marcó vendido (para Ventas/Estadísticas)
    $stmt = $db->prepare("SELECT COUNT(*) c FROM information_schema.COLUMNS
                           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'presupuestos' AND COLUMN_NAME = 'vendido_en'");
    $stmt->execute();
    if (!(int) $stmt->fetch()['c']) {
        $db->exec("ALTER TABLE presupuestos ADD COLUMN vendido_en DATETIME NULL AFTER estado");
        // Los ya vendidos toman su última actualización como fecha de venta
        $db->exec("UPDATE presupuestos SET vendido_en = actualizado_en WHERE estado = 'vendido' AND vendido_en IS NULL");
    }

    $db->exec("CREATE TABLE IF NOT EXISTS rollos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        marca VARCHAR(100) NOT NULL,
        tipo VARCHAR(20) NOT NULL DEFAULT 'PLA',
        color VARCHAR(60) NOT NULL,
        peso_original INT NOT NULL DEFAULT 1000,
        peso_disponible INT NOT NULL DEFAULT 1000,
        costo_kilo DECIMAL(12,2) NOT NULL DEFAULT 0,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_usuario_tipo (usuario_id, tipo),
        CONSTRAINT fk_rollo_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS insumos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        tipo VARCHAR(100) NOT NULL DEFAULT '',
        cantidad DECIMAL(12,2) NOT NULL DEFAULT 0,
        unidad VARCHAR(30) NOT NULL DEFAULT 'unidades',
        aviso_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_usuario (usuario_id),
        CONSTRAINT fk_insumo_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS stock_descuentos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        presupuesto_id BIGINT UNSIGNED NOT NULL,
        rollo_id BIGINT UNSIGNED NOT NULL,
        gramos INT NOT NULL,
        PRIMARY KEY (id),
        KEY idx_presupuesto (presupuesto_id),
        CONSTRAINT fk_sd_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE,
        CONSTRAINT fk_sd_rollo FOREIGN KEY (rollo_id)
            REFERENCES rollos(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS calc_cotizaciones (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id BIGINT UNSIGNED NOT NULL,
        nombre VARCHAR(255) NOT NULL,
        precio VARCHAR(64) NOT NULL DEFAULT '',
        moneda VARCHAR(8) NOT NULL DEFAULT 'ARS',
        datos_json LONGTEXT,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_usuario (usuario_id),
        CONSTRAINT fk_calccot_usuario FOREIGN KEY (usuario_id)
            REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Planes: columna en suscripciones (legado sin plan = mensual, acceso total)
    $col = $db->query("SELECT COUNT(*) c FROM information_schema.columns
                       WHERE table_schema = DATABASE() AND table_name = 'suscripciones' AND column_name = 'plan'")->fetch();
    if ((int) $col['c'] === 0) {
        $db->exec("ALTER TABLE suscripciones ADD COLUMN plan VARCHAR(10) NOT NULL DEFAULT 'mensual' AFTER estado");
    }

    $db->exec("CREATE TABLE IF NOT EXISTS stl_items (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        nombre VARCHAR(150) NOT NULL,
        categoria VARCHAR(80) NOT NULL DEFAULT '',
        archivo_ext VARCHAR(10) NOT NULL,
        imagen_ext VARCHAR(10) NOT NULL DEFAULT '',
        tam_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        descargas INT UNSIGNED NOT NULL DEFAULT 0,
        publicado TINYINT(1) NOT NULL DEFAULT 1,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS config (
        clave VARCHAR(60) NOT NULL,
        valor TEXT,
        PRIMARY KEY (clave)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS novedades_emails (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS recursos_pdf (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        titulo VARCHAR(150) NOT NULL,
        descripcion VARCHAR(300) NOT NULL DEFAULT '',
        imagen_ext VARCHAR(10) NOT NULL DEFAULT '',
        tam_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        descargas INT UNSIGNED NOT NULL DEFAULT 0,
        publicado TINYINT(1) NOT NULL DEFAULT 1,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS recursos_videos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        titulo VARCHAR(150) NOT NULL,
        descripcion VARCHAR(300) NOT NULL DEFAULT '',
        youtube_id VARCHAR(20) NOT NULL,
        imagen_ext VARCHAR(10) NOT NULL DEFAULT '',
        publicado TINYINT(1) NOT NULL DEFAULT 1,
        creado_en DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Quién ve cada recurso: 'todos' (incluye plan gratis) o 'pago'
    foreach (['recursos_pdf', 'recursos_videos'] as $t) {
        $col = $db->query("SELECT COUNT(*) c FROM information_schema.columns
                           WHERE table_schema = DATABASE() AND table_name = '$t' AND column_name = 'acceso'")->fetch();
        if ((int) $col['c'] === 0) {
            $db->exec("ALTER TABLE $t ADD COLUMN acceso VARCHAR(10) NOT NULL DEFAULT 'todos' AFTER publicado");
        }
    }

    $db->exec("CREATE TABLE IF NOT EXISTS stl_archivos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        stl_id BIGINT UNSIGNED NOT NULL,
        orden TINYINT UNSIGNED NOT NULL DEFAULT 2,
        ext VARCHAR(10) NOT NULL,
        tam_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY idx_stl (stl_id),
        CONSTRAINT fk_stlarch_item FOREIGN KEY (stl_id)
            REFERENCES stl_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $listo = true;
}

/** Tipos de rollo disponibles en Stock. */
function taller_tipos_rollo() {
    return ['PLA', 'PETG', 'ABS', 'TPU', 'Resina', 'Otro'];
}

/** Mapea el material de la calculadora al tipo de rollo del stock. */
function taller_material_a_tipo($material) {
    $directos = ['PLA', 'PETG', 'ABS', 'TPU', 'Resina'];
    if (in_array($material, $directos, true)) return $material;
    if ($material === 'Wood-PLA') return 'PLA';
    return 'Otro';
}

/** Descuenta del stock (FIFO por rollo más viejo) los gramos de un presupuesto vendido. */
function taller_stock_descontar($usuario_id, $presupuesto_id) {
    $db = com_db();
    // Evitar doble descuento si ya se registró
    $stmt = $db->prepare('SELECT COUNT(*) c FROM stock_descuentos WHERE presupuesto_id=?');
    $stmt->execute([(int) $presupuesto_id]);
    if ((int) $stmt->fetch()['c'] > 0) return;

    $stmt = $db->prepare('SELECT cantidad, datos_json FROM presupuesto_items WHERE presupuesto_id=?');
    $stmt->execute([(int) $presupuesto_id]);
    foreach ($stmt->fetchAll() as $item) {
        $datos = $item['datos_json'] ? json_decode($item['datos_json'], true) : null;
        $gramos = (int) round((float) ($datos['peso_g'] ?? 0) * (int) $item['cantidad']);
        if ($gramos <= 0) continue;
        $tipo = taller_material_a_tipo($datos['material'] ?? '');

        $rollos = $db->prepare('SELECT id, peso_disponible FROM rollos
                                 WHERE usuario_id=? AND tipo=? AND peso_disponible > 0
                                 ORDER BY creado_en ASC, id ASC');
        $rollos->execute([(int) $usuario_id, $tipo]);
        foreach ($rollos->fetchAll() as $rollo) {
            if ($gramos <= 0) break;
            $usar = min($gramos, (int) $rollo['peso_disponible']);
            $db->prepare('UPDATE rollos SET peso_disponible = peso_disponible - ? WHERE id=?')
               ->execute([$usar, (int) $rollo['id']]);
            $db->prepare('INSERT INTO stock_descuentos (usuario_id, presupuesto_id, rollo_id, gramos) VALUES (?,?,?,?)')
               ->execute([(int) $usuario_id, (int) $presupuesto_id, (int) $rollo['id'], $usar]);
            $gramos -= $usar;
        }
        // Si no alcanzó el stock, el resto queda sin descontar
    }
}

/** Devuelve al stock lo descontado por un presupuesto (al volverlo a pendiente). */
function taller_stock_restaurar($usuario_id, $presupuesto_id) {
    $db = com_db();
    $stmt = $db->prepare('SELECT rollo_id, gramos FROM stock_descuentos WHERE presupuesto_id=? AND usuario_id=?');
    $stmt->execute([(int) $presupuesto_id, (int) $usuario_id]);
    foreach ($stmt->fetchAll() as $d) {
        $db->prepare('UPDATE rollos SET peso_disponible = LEAST(peso_original, peso_disponible + ?) WHERE id=?')
           ->execute([(int) $d['gramos'], (int) $d['rollo_id']]);
    }
    $db->prepare('DELETE FROM stock_descuentos WHERE presupuesto_id=? AND usuario_id=?')
       ->execute([(int) $presupuesto_id, (int) $usuario_id]);
}

/** Registra el cambio de estado manteniendo la fecha de venta y el stock. */
function taller_cambiar_estado($usuario_id, $presupuesto_id, $estado) {
    if ($estado === 'vendido') {
        com_db()->prepare("UPDATE presupuestos SET estado='vendido', vendido_en=COALESCE(vendido_en, NOW()),
                           actualizado_en=NOW() WHERE id=? AND usuario_id=?")
            ->execute([(int) $presupuesto_id, (int) $usuario_id]);
        taller_stock_descontar($usuario_id, $presupuesto_id);
    } else {
        com_db()->prepare("UPDATE presupuestos SET estado='pendiente', vendido_en=NULL,
                           actualizado_en=NOW() WHERE id=? AND usuario_id=?")
            ->execute([(int) $presupuesto_id, (int) $usuario_id]);
        taller_stock_restaurar($usuario_id, $presupuesto_id);
    }
}

/**
 * Movimientos de un mes: manuales + presupuestos vendidos (ingreso automático).
 * Devuelve filas ordenadas por fecha desc con: tipo, concepto, monto, fecha,
 * origen ('manual'|'presupuesto'), id (del movimiento) y presupuesto_id.
 */
function taller_movimientos_mes($usuario_id, $anio, $mes) {
    $desde = sprintf('%04d-%02d-01', $anio, $mes);
    $hasta = date('Y-m-t', strtotime($desde));

    $stmt = com_db()->prepare(
        "SELECT id, tipo, concepto, monto, fecha, 'manual' AS origen, NULL AS presupuesto_id
           FROM movimientos WHERE usuario_id=? AND fecha BETWEEN ? AND ?");
    $stmt->execute([(int) $usuario_id, $desde, $hasta]);
    $filas = $stmt->fetchAll();

    $stmt = com_db()->prepare(
        "SELECT p.id AS presupuesto_id, p.cliente_nombre, DATE(p.vendido_en) AS fecha,
                COALESCE(SUM(i.precio_unit * i.cantidad),0) AS subtotal,
                p.descuento_tipo, p.descuento_valor
           FROM presupuestos p
      LEFT JOIN presupuesto_items i ON i.presupuesto_id = p.id
          WHERE p.usuario_id=? AND p.estado='vendido' AND DATE(p.vendido_en) BETWEEN ? AND ?
       GROUP BY p.id");
    $stmt->execute([(int) $usuario_id, $desde, $hasta]);
    foreach ($stmt->fetchAll() as $pfila) {
        [, , $total] = taller_totales($pfila, [['precio_unit' => $pfila['subtotal'], 'cantidad' => 1]]);
        $filas[] = [
            'id' => null, 'tipo' => 'ingreso',
            'concepto' => 'Presupuesto vendido · ' . ($pfila['cliente_nombre'] ?: 'Sin nombre'),
            'monto' => $total, 'fecha' => $pfila['fecha'],
            'origen' => 'presupuesto', 'presupuesto_id' => (int) $pfila['presupuesto_id'],
        ];
    }
    usort($filas, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
    return $filas;
}

/** Totales [ingresos, gastos, cant_ingresos, cant_gastos] de un mes. */
function taller_resumen_mes($usuario_id, $anio, $mes) {
    $ing = $gas = $ci = $cg = 0;
    foreach (taller_movimientos_mes($usuario_id, $anio, $mes) as $m) {
        if ($m['tipo'] === 'ingreso') { $ing += $m['monto']; $ci++; }
        else { $gas += $m['monto']; $cg++; }
    }
    return [$ing, $gas, $ci, $cg];
}

/** Carpeta de logos subidos (fuera de git; sobrevive a los deploys). */
function taller_logo_dir() {
    return __DIR__ . '/../uploads/logos';
}

/** Ruta web del logo del usuario, o null si no subió ninguno. */
function taller_logo_url($usuario, $prefijo = '') {
    $ext = $usuario['logo_ext'] ?? '';
    if ($ext === '') return null;
    $archivo = taller_logo_dir() . '/logo-' . (int) $usuario['id'] . '.' . $ext;
    if (!is_file($archivo)) return null;
    // El numero de version evita caches viejos al reemplazar el logo
    return $prefijo . 'uploads/logos/logo-' . (int) $usuario['id'] . '.' . $ext . '?v=' . filemtime($archivo);
}

const TALLER_MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

/** Barra de navegación de mes (flechas + selector de últimos 24 meses + Exportar CSV). */
function taller_nav_mes($pagina, $anio, $mes) {
    $ts = mktime(0, 0, 0, $mes, 1, $anio);
    $prev = date('Y-m', strtotime('-1 month', $ts));
    $next = date('Y-m', strtotime('+1 month', $ts));
    $es_actual = date('Y-m', $ts) >= date('Y-m');
    ?>
    <style>
      .nav-mes{display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap}
      .nav-mes .flecha{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;
          background:var(--surface);border:1px solid var(--bd);border-radius:var(--radio);color:var(--txt-2)}
      .nav-mes .flecha:hover{color:var(--txt);border-color:var(--raised)}
      .nav-mes .flecha.off{opacity:.35;pointer-events:none}
      .nav-mes select{width:auto;font-weight:600}
      .nav-mes .csv{margin-left:auto}
    </style>
    <div class="nav-mes">
      <a class="flecha" href="<?php echo $pagina; ?>?mes=<?php echo $prev; ?>" aria-label="Mes anterior">&lsaquo;</a>
      <select onchange="location.href='<?php echo $pagina; ?>?mes='+this.value" aria-label="Mes">
        <?php for ($i = 0; $i < 24; $i++):
            $t = strtotime("-{$i} month", mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y')));
            $v = date('Y-m', $t); ?>
          <option value="<?php echo $v; ?>" <?php echo $v === sprintf('%04d-%02d', $anio, $mes) ? 'selected' : ''; ?>>
            <?php echo TALLER_MESES[(int) date('n', $t)] . ' ' . date('Y', $t); ?></option>
        <?php endfor; ?>
      </select>
      <a class="flecha<?php echo $es_actual ? ' off' : ''; ?>" href="<?php echo $pagina; ?>?mes=<?php echo $next; ?>" aria-label="Mes siguiente">&rsaquo;</a>
      <a class="btn sec csv" href="<?php echo $pagina; ?>?mes=<?php echo sprintf('%04d-%02d', $anio, $mes); ?>&csv=1">Exportar CSV</a>
    </div>
    <?php
}

/**
 * Modelos de impresora con su consumo típico. El "(NNN W)" del nombre es el
 * que la calculadora detecta para autocompletar el consumo.
 */
function taller_impresoras() {
    return [
        'Bambu Lab A1 Mini (45 W)', 'Bambu Lab A1 (95 W)', 'Bambu Lab P1P (80 W)',
        'Bambu Lab P1S (100 W)', 'Bambu Lab P2S (130 W)', 'Bambu Lab X1 Carbon (120 W)',
        'Bambu Lab H2S (210 W)', 'Bambu Lab H2D (210 W)', 'Bambu Lab H2C (210 W)',
        'Prusa MK3S+ (80 W)', 'Prusa MK4 (100 W)',
        'Creality Ender 3 V2 (110 W)', 'Creality Ender 3 S1 (120 W)',
        'Creality K1 (100 W)', 'Creality K1C (100 W)', 'Creality K1 Max (200 W)',
        'Anycubic Kobra 2 (75 W)', 'Anycubic Vyper (80 W)',
        'SnapMaker U1 (130 W)',
        'Elegoo Saturn 3 (resina) (75 W)', 'Elegoo Saturn 4 (resina) (75 W)',
        'Voron 2.4 (350mm DIY) (225 W)',
    ];
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
      .velo-moneda[hidden]{display:none !important}
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
        <form method="post" class="opciones-moneda" id="formMoneda">
          <input type="hidden" name="csrf" value="<?php echo com_csrf(); ?>">
          <input type="hidden" name="accion" value="moneda">
          <button type="submit" name="moneda" value="ARS" class="<?php echo $actual === 'ARS' ? 'actual' : ''; ?>">
            <b>$</b><span>Peso argentino</span></button>
          <button type="submit" name="moneda" value="USD" class="<?php echo $actual === 'USD' ? 'actual' : ''; ?>">
            <b>US$</b><span>Dólar</span></button>
          <button type="submit" name="moneda" value="EUR" class="<?php echo $actual === 'EUR' ? 'actual' : ''; ?>">
            <b>€</b><span>Euro</span></button>
        </form>
        <p class="estado-guardado" id="monedaEstado" style="margin:12px 0 0;font-size:12.5px;color:var(--txt-3);text-align:center"></p>
        <?php if ($actual !== ''): ?>
          <p style="margin:14px 0 0;text-align:center">
            <button type="button" class="btn sec chico" onclick="document.getElementById('veloMoneda').hidden=true">Cancelar</button></p>
        <?php endif; ?>
      </div>
    </div>
    <script>
    (function(){
      var DATOS = { ARS: { s: '$', d: 0 }, USD: { s: 'US$', d: 2 }, EUR: { s: '€', d: 2 } };
      var form = document.getElementById('formMoneda');
      form.addEventListener('submit', function(ev){
        ev.preventDefault();
        var boton = ev.submitter || form.querySelector('button[type=submit]');
        var codigo = boton.value;
        var cuerpo = new FormData(form);
        cuerpo.set('moneda', codigo);
        document.getElementById('monedaEstado').textContent = 'Guardando...';
        fetch(window.location.href, { method: 'POST', body: cuerpo, credentials: 'same-origin' })
          .then(function(r){
            if (!r.ok) throw new Error('estado ' + r.status);
            // Cerrar y avisar a la pagina (sin recargar, para no perder trabajo)
            document.getElementById('veloMoneda').hidden = true;
            document.getElementById('monedaEstado').textContent = '';
            var chip = document.querySelector('.chip-moneda b');
            if (chip) chip.textContent = codigo + ' (' + DATOS[codigo].s + ')';
            document.querySelectorAll('#formMoneda button[type=submit]').forEach(function(b){
              b.classList.toggle('actual', b.value === codigo);
            });
            window.dispatchEvent(new CustomEvent('ptools:moneda', { detail: DATOS[codigo] }));
          })
          .catch(function(){
            // Si fetch falla, guardado clasico con recarga (submit() no re-dispara este handler)
            form.submit();
          });
      });
    })();
    </script>
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
