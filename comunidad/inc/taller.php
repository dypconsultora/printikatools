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

/** Formato de moneda ARS: $ 12.450 */
function taller_precio($n) {
    return '$ ' . number_format((float) $n, 0, ',', '.');
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
