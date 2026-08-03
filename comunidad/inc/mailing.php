<?php
/**
 * Envios masivos a la lista de Emails captados.
 *
 * Por que existe: hasta ahora el unico correo que salia a la lista era el de
 * bienvenida, automatico. Para contar algo nuevo (una herramienta, una guia)
 * habia que hacerlo a mano o contratar un servicio.
 *
 * COMO SE MANDA, Y POR QUE ASI:
 *
 * 1. El correo sale por el mismo SMTP del hosting que todo lo demas. Con una
 *    lista chica alcanza. Cuando crezca hay que pasar a un servicio de envio,
 *    y ese cambio es cambiar el SMTP en el .env: esta pantalla no se toca.
 *
 * 2. Se manda de a TANDAS (MAILING_POR_TANDA). Un envio de 200 correos no
 *    entra en un pedido: el hosting corta los procesos largos. La cola vive en
 *    la tabla mailing_envios, asi que si el navegador se cierra en el medio el
 *    envio se retoma donde iba y a nadie le llega dos veces.
 *
 * 3. La conexion al SMTP se abre UNA vez por tanda y se reusa (SMTPKeepAlive).
 *    Abrir y cerrar por cada correo era el 80% del tiempo.
 *
 * 4. Todos llevan el enlace de baja firmado y las cabeceras List-Unsubscribe.
 *    No es cortesia: es lo que pide Gmail a quien manda correo masivo, y es la
 *    diferencia entre llegar a la bandeja o al spam. Ese enlace borra la
 *    direccion de la lista, no toca la cuenta.
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/correo.php';

/**
 * Cuantos correos por tanda.
 *
 * Bajo a proposito. Lo que manda no es lo que aguanta PHP sino el limite por
 * hora del hosting: si se lo pasa, empieza a rebotar todo, incluidos los
 * correos de las cuentas. Ante la duda, lento.
 */
const MAILING_POR_TANDA = 15;

/** Los grupos de destinatarios que se pueden elegir, y como se llaman en pantalla. */
function mailing_filtros() {
    return [
        'todos'     => 'Toda la lista',
        'registro'  => 'Solo los que se registraron',
        'cotizador' => 'Solo los del popup de la calculadora',
        'banner'    => 'Solo los del banner de la portada',
    ];
}

/** Condicion SQL del grupo elegido. Devuelve [where, argumentos]. */
function mailing_donde($filtro, $idioma) {
    $donde = [];
    $args  = [];
    if (isset(mailing_filtros()[$filtro]) && $filtro !== 'todos') {
        $donde[] = 'origen = ?';
        $args[]  = $filtro;
    }
    if ($idioma === 'es' || $idioma === 'en') {
        $donde[] = 'idioma = ?';
        $args[]  = $idioma;
    }
    return [$donde ? 'WHERE ' . implode(' AND ', $donde) : '', $args];
}

/** Cuanta gente entra en ese grupo. */
function mailing_contar($filtro, $idioma) {
    [$where, $args] = mailing_donde($filtro, $idioma);
    $stmt = com_db()->prepare("SELECT COUNT(*) FROM novedades_emails $where");
    $stmt->execute($args);
    return (int) $stmt->fetchColumn();
}

/**
 * Convierte lo que se escribio en el formulario a los parrafos del correo.
 *
 * El texto se escapa entero y despues se permiten dos cosas nada mas, que son
 * las que de verdad hacen falta y no pueden romper el diseno:
 *   **negrita**            y
 *   [texto del enlace](https://...)
 * Un renglon en blanco separa parrafos; uno solo es un salto de linea.
 */
function mailing_parrafos($texto) {
    $texto  = str_replace(["\r\n", "\r"], "\n", (string) $texto);
    $bloques = preg_split('/\n\s*\n/', trim($texto));
    $salida  = [];

    foreach ($bloques as $b) {
        if (trim($b) === '') continue;
        $p = htmlspecialchars($b, ENT_QUOTES, 'UTF-8');

        $p = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $p);

        // Solo http y https: un enlace javascript: en un correo no sirve para
        // nada bueno. El escapado de arriba dejo los "&" como "&amp;", asi que
        // hay que devolverlos antes de meterlos en el href.
        $p = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/i',
            function ($m) {
                $url = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
                if (!preg_match('~^https?://~i', $url)) return $m[0];
                return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
                     . '" style="color:#0c7ab8;text-decoration:underline">' . $m[1] . '</a>';
            },
            $p
        );

        $salida[] = nl2br($p);
    }
    return $salida;
}

/** El mismo texto pero plano, para los lectores que no muestran HTML. */
function mailing_texto_plano($m) {
    if (trim((string) $m['html_propio']) !== '') {
        return trim(strip_tags((string) $m['html_propio']));
    }
    $t = trim((string) $m['cuerpo']);
    $t = preg_replace('/\*\*(.+?)\*\*/s', '$1', $t);
    $t = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/i', '$1 ($2)', $t);
    if ($m['boton_texto'] !== '' && $m['boton_url'] !== '') {
        $t .= "\n\n" . $m['boton_texto'] . ': ' . $m['boton_url'];
    }
    return trim(($m['titulo'] !== '' ? $m['titulo'] . "\n\n" : '') . $t);
}

/**
 * El HTML final para una direccion.
 *
 * Cada persona recibe SU enlace de baja, firmado con su direccion: por eso el
 * correo se arma de a uno y no una vez para todos.
 */
function mailing_html($m, $email, $idioma = 'es') {
    $baja = com_baja_url($email);
    $en   = $idioma === 'en';
    // El texto lo escribe ella en un idioma; esto es solo el marco del correo
    // (la bajada del logo, el pie, el enlace de baja), que sigue al de la persona
    $pie = $en
        ? 'You are getting this because you left your address at printikatools.com.'
        : 'Recibís este correo porque dejaste tu dirección en printikatools.com.';

    if (trim((string) $m['html_propio']) !== '') {
        // HTML propio: se manda tal cual, pero el pie con la baja se agrega
        // igual. Un envio masivo sin salida no se manda nunca.
        return (string) $m['html_propio']
            . '<div style="max-width:560px;margin:18px auto 26px;font-family:Arial,Helvetica,sans-serif;'
            . 'font-size:12px;line-height:1.6;color:#8a95a8;text-align:center">'
            . htmlspecialchars($pie) . '<br>'
            . '<a href="' . htmlspecialchars($baja, ENT_QUOTES) . '" style="color:#8a95a8">'
            . ($en ? 'Unsubscribe' : 'Darme de baja') . '</a>'
            . '</div>';
    }

    $boton = ($m['boton_texto'] !== '' && $m['boton_url'] !== '')
        ? ['texto' => $m['boton_texto'], 'url' => $m['boton_url']]
        : null;

    return correo_plantilla(
        $m['titulo'] !== '' ? $m['titulo'] : $m['asunto'],
        mailing_parrafos($m['cuerpo']),
        $boton,
        $pie,
        '',
        $en ? 'en' : 'es',
        $baja
    );
}

/** Un mailing por id, o null. */
function mailing_get($id) {
    $stmt = com_db()->prepare('SELECT * FROM mailings WHERE id = ?');
    $stmt->execute([(int) $id]);
    return $stmt->fetch() ?: null;
}

/** Guarda un borrador nuevo (todavia sin cola ni destinatarios). */
function mailing_guardar($d, $id = 0) {
    $campos = [
        mb_substr(trim($d['asunto'] ?? ''), 0, 200),
        mb_substr(trim($d['titulo'] ?? ''), 0, 200),
        (string) ($d['cuerpo'] ?? ''),
        mb_substr(trim($d['boton_texto'] ?? ''), 0, 80),
        mb_substr(trim($d['boton_url'] ?? ''), 0, 300),
        (string) ($d['html_propio'] ?? ''),
        isset(mailing_filtros()[$d['filtro'] ?? '']) ? $d['filtro'] : 'todos',
        in_array($d['idioma'] ?? '', ['es', 'en'], true) ? $d['idioma'] : 'ambos',
    ];
    if ($id > 0) {
        $campos[] = (int) $id;
        com_db()->prepare('UPDATE mailings SET asunto=?, titulo=?, cuerpo=?, boton_texto=?,
                           boton_url=?, html_propio=?, filtro=?, idioma=? WHERE id=? AND estado=\'borrador\'')
            ->execute($campos);
        return (int) $id;
    }
    com_db()->prepare('INSERT INTO mailings (asunto, titulo, cuerpo, boton_texto, boton_url,
                       html_propio, filtro, idioma, creado_en)
                       VALUES (?,?,?,?,?,?,?,?, NOW())')->execute($campos);
    return (int) com_db()->lastInsertId();
}

/**
 * Arma la cola: congela AHORA a quien le va a llegar.
 *
 * Se congela a proposito. Si la lista se consultara en cada tanda, alguien que
 * se anota en el medio del envio podria recibirlo o no segun el momento, y
 * alguien que se da de baja mientras se manda podria seguir recibiendolo.
 */
function mailing_encolar($id) {
    $m = mailing_get($id);
    if (!$m || $m['estado'] !== 'borrador') return 0;

    [$where, $args] = mailing_donde($m['filtro'], $m['idioma']);
    $db = com_db();
    $db->prepare("DELETE FROM mailing_envios WHERE mailing_id = ?")->execute([(int) $id]);

    $stmt = $db->prepare("SELECT email, idioma FROM novedades_emails $where ORDER BY id");
    $stmt->execute($args);
    $ins = $db->prepare('INSERT INTO mailing_envios (mailing_id, email, idioma, estado)
                         VALUES (?, ?, ?, \'pendiente\')');
    $n = 0;
    foreach ($stmt->fetchAll() as $f) {
        $ins->execute([(int) $id, $f['email'], $f['idioma']]);
        $n++;
    }
    $db->prepare("UPDATE mailings SET estado='enviando', total=?, enviados=0, fallados=0 WHERE id=?")
       ->execute([$n, (int) $id]);
    return $n;
}

/**
 * Manda una tanda. Devuelve [enviados, fallados, cuantos quedan].
 *
 * Abre una sola conexion al SMTP y la reusa para toda la tanda. Si la conexion
 * no se puede abrir, no marca nada: los correos quedan pendientes y se
 * reintentan en la tanda siguiente.
 */
function mailing_tanda($id, $cuantos = MAILING_POR_TANDA) {
    $m = mailing_get($id);
    if (!$m || $m['estado'] !== 'enviando') return [0, 0, 0];

    $cfg = correo_config();
    if (!$cfg) return [0, 0, mailing_pendientes($id)];

    $db = com_db();
    $stmt = $db->prepare("SELECT id, email, idioma FROM mailing_envios
                           WHERE mailing_id = ? AND estado = 'pendiente'
                           ORDER BY id LIMIT " . max(1, (int) $cuantos));
    $stmt->execute([(int) $id]);
    $lote = $stmt->fetchAll();
    if (!$lote) {
        mailing_cerrar($id);
        return [0, 0, 0];
    }

    $base = dirname(__DIR__, 2);
    require_once $base . '/lib/PHPMailer/Exception.php';
    require_once $base . '/lib/PHPMailer/PHPMailer.php';
    require_once $base . '/lib/PHPMailer/SMTP.php';

    $enviados = 0;
    $fallados = 0;
    $marcar = $db->prepare('UPDATE mailing_envios SET estado = ?, enviado_en = NOW() WHERE id = ?');

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host          = $cfg['smtp_host'];
        $mail->SMTPAuth      = true;
        $mail->Username      = $cfg['smtp_user'];
        $mail->Password      = $cfg['smtp_pass'];
        $mail->SMTPSecure    = $cfg['smtp_secure'];
        $mail->Port          = (int) $cfg['smtp_port'];
        $mail->CharSet       = 'UTF-8';
        $mail->Timeout       = 15;
        $mail->SMTPKeepAlive = true;          // una sola conexion para toda la tanda
        $mail->isHTML(true);
        $mail->setFrom($cfg['from_email'], 'Printika Tools');

        $logo = $base . '/assets/img/printika-tools-mail.png';
        if (is_readable($logo)) {
            $mail->addEmbeddedImage($logo, 'logoprintika', 'printika-tools.png');
        }

        // Abrir la conexion ANTES del lote y a proposito.
        //
        // Si no, el fallo de conexion aparece adentro del try de cada correo y
        // los marca a todos como "error": el servidor de correo se cayo cinco
        // minutos y la lista entera queda quemada, sin forma de reintentarla.
        // Asi, si no conecta, no se marca a nadie y quedan pendientes.
        if (!$mail->smtpConnect()) {
            error_log('[mailing] el SMTP no acepto la conexion');
            return [0, 0, mailing_pendientes($id)];
        }

        $texto = mailing_texto_plano($m);
        $seguidos_mal = 0;

        foreach ($lote as $fila) {
            try {
                $baja = com_baja_url($fila['email']);
                $mail->clearAddresses();
                $mail->clearCustomHeaders();
                $mail->addAddress($fila['email']);
                $mail->Subject = $m['asunto'];
                $mail->Body    = mailing_html($m, $fila['email'], $fila['idioma']);
                $mail->AltBody = $texto;
                // Las dos cabeceras que Gmail y Yahoo le exigen al correo masivo
                $mail->addCustomHeader('List-Unsubscribe', '<' . $baja . '>');
                $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                $mail->send();
                $marcar->execute(['ok', $fila['id']]);
                $enviados++;
                $seguidos_mal = 0;
            } catch (Throwable $e) {
                error_log('[mailing] ' . $fila['email'] . ': ' . $e->getMessage());
                $marcar->execute(['error', $fila['id']]);
                $fallados++;
                // Tres seguidos ya no es una direccion mala: es el servidor. Se
                // corta la tanda y el resto queda pendiente para mas tarde,
                // en vez de quemar la lista entera contra una pared.
                if (++$seguidos_mal >= 3) {
                    error_log('[mailing] tres fallos seguidos, se corta la tanda');
                    break;
                }
            }
        }
        $mail->smtpClose();
    } catch (Throwable $e) {
        // No se pudo ni abrir la conexion: no se marca nada y se reintenta
        error_log('[mailing] no se pudo conectar al SMTP: ' . $e->getMessage());
        return [0, 0, mailing_pendientes($id)];
    }

    $db->prepare('UPDATE mailings SET enviados = enviados + ?, fallados = fallados + ? WHERE id = ?')
       ->execute([$enviados, $fallados, (int) $id]);

    $quedan = mailing_pendientes($id);
    if ($quedan === 0) mailing_cerrar($id);
    return [$enviados, $fallados, $quedan];
}

/** Cuantos faltan mandar. */
function mailing_pendientes($id) {
    $stmt = com_db()->prepare("SELECT COUNT(*) FROM mailing_envios
                                WHERE mailing_id = ? AND estado = 'pendiente'");
    $stmt->execute([(int) $id]);
    return (int) $stmt->fetchColumn();
}

/** Marca el envio como terminado. */
function mailing_cerrar($id) {
    com_db()->prepare("UPDATE mailings SET estado='enviado', terminado_en=NOW() WHERE id=?")
        ->execute([(int) $id]);
}

/** Manda una sola copia de prueba a una direccion, sin tocar la cola. */
function mailing_prueba($m, $para, &$error = null) {
    return correo_enviar(
        $para,
        '',
        '[PRUEBA] ' . $m['asunto'],
        mailing_html($m, $para),
        mailing_texto_plano($m),
        $error,
        com_baja_url($para)
    );
}
