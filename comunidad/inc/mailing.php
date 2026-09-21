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
        'todos'       => 'Toda la lista',
        'registro'    => 'Solo los que se registraron',
        'nunca_entro' => 'Se registraron y nunca entraron',
        'cotizador'   => 'Solo los del popup de la calculadora',
        'banner'      => 'Solo los del banner de la portada',
        'no_pagan'    => 'Todos menos los que pagan o pagaron',
    ];
}

/**
 * Condicion SQL del grupo elegido. Devuelve [where, argumentos].
 *
 * Casi todos los grupos son un valor de la columna "origen". La excepcion es
 * "nunca_entro", que hay que ir a buscar a la tabla de usuarios: son los que
 * crearon la cuenta, no la usaron nunca y no estan pagando. A esos tiene
 * sentido invitarlos a entrar; a alguien que ya paga, no.
 */
function mailing_donde($filtro, $idioma) {
    $donde = [];
    $args  = [];

    if ($filtro === 'nunca_entro') {
        $donde[] = "email IN (
            SELECT u.email FROM usuarios u
             WHERE u.ultimo_login IS NULL AND u.rol <> 'admin'
               AND NOT EXISTS (SELECT 1 FROM suscripciones s
                                WHERE s.usuario_id = u.id AND s.estado = 'activa'
                                  AND (s.hasta IS NULL OR s.hasta >= CURDATE())))";
    } elseif ($filtro === 'no_pagan') {
        // Para ofertas como el primer mes gratis: afuera el que paga hoy y
        // tambien el que pago alguna vez, porque a ese la promo no le aplica
        // (mp_puede_mes_gratis) y el correo le prometeria algo que no le damos.
        $donde[] = "email NOT IN (
            SELECT u.email FROM usuarios u
             WHERE u.rol = 'admin'
                OR EXISTS (SELECT 1 FROM suscripciones s
                            WHERE s.usuario_id = u.id AND s.plan IN ('mensual','anual')))";
    } elseif (isset(mailing_filtros()[$filtro]) && $filtro !== 'todos') {
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
        $baja,
        mailing_banner_url($m['banner_url'] ?? '')
    );
}

/** Donde van a parar las imagenes que se suben desde la pantalla de Mailing. */
function mailing_img_dir() { return dirname(__DIR__) . '/uploads/mailing'; }

/**
 * Las imagenes que se pueden elegir como banner.
 *
 * Salen de dos lados: las que vienen con el sitio (assets/img/mailing, que van
 * a git y se despliegan) y las que sube ella desde el panel (uploads/mailing,
 * que viven solo en el servidor). Para la pantalla son lo mismo.
 */
function mailing_imagenes() {
    $raiz  = dirname(__DIR__, 2);
    $donde = [
        '/assets/img/mailing'      => $raiz . '/assets/img/mailing',
        '/comunidad/uploads/mailing' => mailing_img_dir(),
    ];
    $salida = [];
    foreach ($donde as $url => $dir) {
        if (!is_dir($dir)) continue;
        foreach (glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $ruta) {
            $salida[] = ['url' => $url . '/' . basename($ruta), 'nombre' => basename($ruta),
                         'cuando' => filemtime($ruta)];
        }
    }
    // Las ultimas subidas primero: es lo que se acaba de cargar
    usort($salida, fn($a, $b) => $b['cuando'] <=> $a['cuando']);
    return $salida;
}

/**
 * Guarda una imagen subida y devuelve su direccion, o '' si no se pudo.
 *
 * La achica siempre a 1120 de ancho y la reescribe en JPEG. Es lo que hace la
 * diferencia entre un correo que llega y uno que no: una foto sacada con el
 * celular pesa varios MB, y un correo de ese tamano lo cortan los servidores
 * antes de que lo abra nadie.
 */
function mailing_guardar_imagen($archivo, &$error = null) {
    $error = '';
    if (empty($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) return '';

    if (($archivo['size'] ?? 0) > 8 * 1024 * 1024) {
        $error = 'La imagen no puede superar los 8 MB.';
        return '';
    }
    $info = @getimagesize($archivo['tmp_name']);
    $abrir = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png'  => 'imagecreatefrompng',
        'image/webp' => 'imagecreatefromwebp',
    ];
    if (!$info || !isset($abrir[$info['mime']]) || !function_exists($abrir[$info['mime']])) {
        $error = 'La imagen tiene que ser JPG, PNG o WEBP.';
        return '';
    }

    $origen = @$abrir[$info['mime']]($archivo['tmp_name']);
    if (!$origen) { $error = 'No pudimos leer esa imagen.'; return ''; }

    [$ancho, $alto] = $info;
    $tope  = 1120;
    $nuevo = $ancho > $tope ? $tope : $ancho;          // nunca se agranda
    $altoN = (int) round($alto * ($nuevo / $ancho));

    $lienzo = imagecreatetruecolor($nuevo, $altoN);
    // Fondo blanco: los PNG con transparencia, en JPEG, salen con manchas negras
    imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
    imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $nuevo, $altoN, $ancho, $alto);

    if (!is_dir(mailing_img_dir()) && !@mkdir(mailing_img_dir(), 0755, true)) {
        imagedestroy($origen); imagedestroy($lienzo);
        $error = 'No pudimos guardar la imagen en el servidor.';
        return '';
    }
    $nombre = 'ml-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.jpg';
    $ok = imagejpeg($lienzo, mailing_img_dir() . '/' . $nombre, 72);
    imagedestroy($origen);
    imagedestroy($lienzo);

    if (!$ok) { $error = 'No pudimos guardar la imagen en el servidor.'; return ''; }
    return '/comunidad/uploads/mailing/' . $nombre;
}

/**
 * La direccion completa del banner.
 *
 * Se guarda empezando con "/" (una imagen nuestra) para que la vista previa
 * funcione en cualquier servidor, pero al correo hay que mandarle la direccion
 * entera: adentro de Gmail no existe "nuestro" servidor.
 */
function mailing_banner_url($url) {
    $url = trim((string) $url);
    if ($url === '') return '';
    if (strncmp($url, '/', 1) === 0) return 'https://printikatools.com' . $url;
    return preg_match('~^https?://~i', $url) ? $url : '';
}

/** Un mailing por id, o null. */
function mailing_get($id) {
    $stmt = com_db()->prepare('SELECT * FROM mailings WHERE id = ?');
    $stmt->execute([(int) $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Guarda un borrador.
 *
 * Si el id que llega es de un mailing YA ENVIADO, no se lo pisa: se guarda una
 * copia nueva como borrador. Asi "editar" uno viejo sirve para reusarlo sin
 * que el historial pase a mentir sobre lo que se mando ese dia.
 */
function mailing_guardar($d, $id = 0) {
    if ($id > 0) {
        $m = mailing_get($id);
        if (!$m || $m['estado'] !== 'borrador') $id = 0;
    }
    $campos = [
        mb_substr(trim($d['asunto'] ?? ''), 0, 200),
        mb_substr(trim($d['titulo'] ?? ''), 0, 200),
        (string) ($d['cuerpo'] ?? ''),
        mb_substr(trim($d['boton_texto'] ?? ''), 0, 80),
        mb_substr(trim($d['boton_url'] ?? ''), 0, 300),
        mb_substr(trim($d['banner_url'] ?? ''), 0, 300),
        (string) ($d['html_propio'] ?? ''),
        isset(mailing_filtros()[$d['filtro'] ?? '']) ? $d['filtro'] : 'todos',
        in_array($d['idioma'] ?? '', ['es', 'en'], true) ? $d['idioma'] : 'ambos',
    ];
    if ($id > 0) {
        $campos[] = (int) $id;
        com_db()->prepare('UPDATE mailings SET asunto=?, titulo=?, cuerpo=?, boton_texto=?,
                           boton_url=?, banner_url=?, html_propio=?, filtro=?, idioma=? WHERE id=?')
            ->execute($campos);
        return (int) $id;
    }
    com_db()->prepare('INSERT INTO mailings (asunto, titulo, cuerpo, boton_texto, boton_url,
                       banner_url, html_propio, filtro, idioma, creado_en)
                       VALUES (?,?,?,?,?,?,?,?,?, NOW())')->execute($campos);
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

/**
 * Deja escrito, UNA sola vez, el borrador para los que se registraron y nunca
 * entraron.
 *
 * Es el primer mailing que hacia falta y no tenia sentido que lo escribiera
 * ella de cero. Queda como borrador: no se manda solo. Si lo borra, no vuelve
 * a aparecer, porque la marca queda guardada en config.
 *
 * Los precios salen de las constantes y NO se escriben a mano. Ojo igual: acá
 * quedan congelados adentro del texto, asi que si cambian hay que retocar el
 * borrador antes de mandarlo.
 */
function mailing_semilla() {
    if (cfg_get('mailing_semilla_nunca_entro')) return 0;
    cfg_set('mailing_semilla_nunca_entro', date('Y-m-d H:i:s'));

    $mes = '$' . number_format(COMUNIDAD_PRECIO_MENSUAL, 0, ',', '.');
    $ano = '$' . number_format(COMUNIDAD_PRECIO_ANUAL, 0, ',', '.');
    $precios = COMUNIDAD_MENSUAL_VISIBLE
        ? "Sale $mes por mes, o $ano por año con dos meses de regalo."
        : "Sale $ano por año, con dos meses de regalo.";

    $cuerpo = <<<TXT
    Hace un tiempo creaste tu cuenta gratis en Printika Tools y todavía no entraste. Te escribimos por si se te traspapeló: **la calculadora de costos ya está adentro esperándote**, y con el plan gratuito la usás sin límite.

    Es la que te dice cuánto te cuesta **de verdad** una impresión. Suma las seis cosas que casi nadie cuenta: el material, la luz que consume la máquina, su desgaste, tu tiempo, las impresiones que salen mal y tu ganancia. Cobrar solo el filamento es el error más caro que se puede cometer en un taller.

    La tenés completa en pesos, dólares o euros, con 22 impresoras ya cargadas, y te podés bajar el presupuesto en PDF para mandárselo al cliente.

    Si además querés dejar de llevar las cuentas en un cuaderno, el **plan completo** suma presupuestos con tu logo, clientes, productos, stock de filamento, ventas y estadísticas mes a mes. $precios Podés verlos en [la página de planes](https://printikatools.com/#planes).

    Cualquier duda, respondé este correo y te contestamos.
    TXT;

    return mailing_guardar([
        'asunto'      => 'Creaste tu cuenta y todavía no la usaste',
        'titulo'      => 'Tu calculadora de costos ya está lista',
        'cuerpo'      => preg_replace('/^    /m', '', $cuerpo),
        'boton_texto' => 'Entrar a mi cuenta',
        'boton_url'   => 'https://printikatools.com/comunidad/login.php',
        'banner_url'  => '/assets/img/mailing/banner-calculadora.jpg',
        'filtro'      => 'nunca_entro',
        'idioma'      => 'es',
    ]);
}

/**
 * Borrador de la promo del primer mes gratis (septiembre 2026). Queda en el
 * historial para que ella lo revise y lo mande; no se envia solo. Los precios
 * y la fecha de cierre salen de las constantes, asi no pueden quedar distintos
 * de lo que despues cobra el checkout.
 */
function mailing_semilla_promo() {
    if (cfg_get('mailing_semilla_promo_mes_gratis')) return 0;
    cfg_set('mailing_semilla_promo_mes_gratis', date('Y-m-d H:i:s'));
    if (!com_promo_activa()) return 0;

    $mes   = '$' . number_format(COMUNIDAD_PRECIO_MENSUAL, 0, ',', '.');
    $cierre = com_fecha_larga(COMUNIDAD_PROMO_HASTA);

    $apertura = mailing_promo_apertura();
    $cuerpo = <<<TXT
    $apertura

    Te suscribís hoy y no pagás nada: tenés un mes entero para usar todo el taller. Recién al mes te llega el primer cobro de $mes, y de ahí sigue mes a mes. Si no te convence, lo das de baja antes desde tu cuenta y no se te cobra nada.

    Con el plan Pro tenés la calculadora completa (luz, desgaste de la máquina, mano de obra, fallos y comisiones de Mercado Libre), presupuestos con tu logo en PDF, clientes, productos, stock de filamento, ventas y estadísticas mes a mes, la librería de modelos STL y el soporte por Telegram.

    Si ya tenés cuenta gratis, entrá con tu mismo correo: no hace falta registrarse de nuevo. **La promo vale hasta el $cierre.**

    Cualquier duda, respondé este correo y te contestamos.
    TXT;

    return mailing_guardar([
        'asunto'      => 'Tu primer mes de Printika Pro, gratis',
        'titulo'      => 'Probá Printika Pro un mes gratis',
        'cuerpo'      => preg_replace('/^    /m', '', $cuerpo),
        'boton_texto' => 'Quiero mi mes gratis',
        'boton_url'   => 'https://printikatools.com/comunidad/registro.php?plan=mensual',
        'banner_url'  => '/assets/img/mailing/banner-mes-gratis.jpg',
        'filtro'      => 'no_pagan',
        'idioma'      => 'es',
    ]);
}

/**
 * "28 de septiembre", o "lunes 28 de septiembre" con $con_dia.
 * A mano porque el idioma del servidor no se puede dar por sentado.
 */
function com_fecha_larga($fecha, $con_dia = false) {
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto',
              'septiembre','octubre','noviembre','diciembre'];
    $dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $t = strtotime($fecha);
    return ($con_dia ? $dias[(int) date('w', $t)] . ' ' : '')
         . (int) date('j', $t) . ' de ' . $meses[(int) date('n', $t) - 1];
}

/** Arranque del correo de la promo: la fecha de cierre sale de la constante. */
function mailing_promo_apertura() {
    return 'Hasta el ' . com_fecha_larga(COMUNIDAD_PROMO_HASTA, true)
         . ', el plan **Printika Pro** tiene **el primer mes gratis**.';
}

/**
 * Arreglo de una vez: el borrador de la promo se escribio diciendo "Por diez
 * dias", y entre la prueba con Mercado Pago y el envio quedaron ocho. Se
 * reemplaza por la fecha de cierre, que es la que de verdad manda.
 *
 * Solo toca el borrador si sigue con el texto original: si ella ya lo reescribio
 * a mano, no se le pisa nada.
 */
function mailing_arreglo_promo_fecha() {
    if (cfg_get('mailing_promo_fecha_arreglada')) return;
    cfg_set('mailing_promo_fecha_arreglada', date('Y-m-d H:i:s'));
    com_db()->prepare("UPDATE mailings SET cuerpo = REPLACE(cuerpo, ?, ?)
                        WHERE estado = 'borrador' AND cuerpo LIKE ?")
        ->execute([
            'Por diez días, el plan **Printika Pro** tiene **el primer mes gratis**.',
            mailing_promo_apertura(),
            '%Por diez días, el plan **Printika Pro** tiene **el primer mes gratis**.%',
        ]);
}

/**
 * La misma promo, en ingles. Queda de borrador para que ella lo mande cuando
 * quiera: las cuentas en ingles son pocas y las maneja aparte.
 *
 * Dos cosas de las que no se puede escapar y por eso estan escritas en el
 * correo: el cobro sale igual por Mercado Pago y en pesos (los enlaces de
 * PayPal siguen siendo de mentira), y el precio en dolares es orientativo
 * porque depende del cambio del dia.
 */
function mailing_semilla_promo_en() {
    if (cfg_get('mailing_semilla_promo_mes_gratis_en')) return 0;
    cfg_set('mailing_semilla_promo_mes_gratis_en', date('Y-m-d H:i:s'));
    if (!com_promo_activa()) return 0;

    $ars    = 'ARS ' . number_format(COMUNIDAD_PRECIO_MENSUAL, 0, '.', ',');
    // El precio en dolares es el que ya figura en la landing en ingles y en
    // pricing.md. Si alguna vez cambia, cambia en los tres lados.
    $usd    = 'US$15';
    $cierre = com_fecha_larga_en(COMUNIDAD_PROMO_HASTA);

    $cuerpo = <<<TXT
    Until $cierre, **Printika Pro** comes with **your first month free**.

    You subscribe today and pay nothing: a full month to use the whole workshop. The first charge only arrives a month later, and then it renews monthly. If it is not for you, cancel from your account before then and you are charged nothing at all.

    Printika Pro gives you the full cost calculator (electricity, machine wear, labour, failure rate and marketplace fees), professional quotes as PDF with your logo, clients, products, filament stock, sales and monthly statistics, the STL model library and support over Telegram.

    Already have a free account? Just sign in with the same email, no need to register again. **The offer ends on $cierre.**

    A heads-up on billing: payment goes through Mercado Pago and is charged in Argentine pesos, $ars per month, which is the $usd you see on the site. Any card works.

    Any questions, just reply to this email.
    TXT;

    return mailing_guardar([
        'asunto'      => 'Your first month of Printika Pro, free',
        'titulo'      => 'Try Printika Pro free for a month',
        'cuerpo'      => preg_replace('/^    /m', '', $cuerpo),
        'boton_texto' => 'Start my free month',
        'boton_url'   => 'https://printikatools.com/comunidad/registro.php?plan=mensual',
        'banner_url'  => '/assets/img/mailing/banner-mes-gratis-en.jpg',
        'filtro'      => 'no_pagan',
        'idioma'      => 'en',
    ]);
}

/** "Monday, September 28", para los correos en ingles. */
function com_fecha_larga_en($fecha) {
    $meses = ['January','February','March','April','May','June','July','August',
              'September','October','November','December'];
    $dias  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $t = strtotime($fecha);
    return $dias[(int) date('w', $t)] . ', ' . $meses[(int) date('n', $t) - 1] . ' ' . (int) date('j', $t);
}

/**
 * Arreglo de una vez: el borrador de la semilla se creo antes de que los
 * mailings pudieran llevar imagen, asi que quedo sin banner. Si sigue ahi y
 * sigue vacio, se le pone. Si ella ya eligio otra imagen o lo dejo a proposito
 * sin ninguna, no se toca: solo corre una vez.
 */
function mailing_banner_semilla() {
    if (cfg_get('mailing_banner_semilla')) return;
    cfg_set('mailing_banner_semilla', date('Y-m-d H:i:s'));
    com_db()->prepare("UPDATE mailings SET banner_url = ?
                        WHERE estado = 'borrador' AND banner_url = ?
                          AND asunto = 'Creaste tu cuenta y todavía no la usaste'")
        ->execute(['/assets/img/mailing/banner-calculadora.jpg', '']);
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
