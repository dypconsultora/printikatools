<?php
/**
 * Guias: lo que comparten el administrador y las paginas publicas.
 *
 * El cuerpo de cada guia se guarda como una lista de bloques en JSON. Cada
 * bloque tiene un tipo (titulo, texto, lista, destacado, formula, imagen,
 * video) y su contenido. Asi la administradora arma la nota con recuadros,
 * sin escribir HTML, y nosotros controlamos exactamente que se publica.
 */

/** Tipos de bloque que se pueden usar, con el nombre que ve la administradora. */
function guia_tipos() {
    return [
        'titulo'    => ['Título de sección', 'Los seis costos que forman el precio'],
        'texto'     => ['Texto', 'Escribí acá el párrafo. Dejá un renglón en blanco para separar párrafos.'],
        'lista'     => ['Lista', "Un ítem por renglón\nOtro ítem\nY otro más"],
        'destacado' => ['Recuadro destacado', 'Lo más importante de la nota. Es lo que Google suele mostrar en los resultados.'],
        'formula'   => ['Fórmula', 'Costo del material = (precio del rollo ÷ peso del rollo) × gramos'],
        'imagen'    => ['Imagen', ''],
        'video'     => ['Video de YouTube', ''],
    ];
}

/** Carpeta donde viven las imagenes de las guias. */
function guia_dir() {
    return dirname(__DIR__) . '/uploads/guias';
}

/** Direccion publica de una imagen de guia. */
function guia_img_url($guia_id, $bloque_id, $ext) {
    return '/comunidad/uploads/guias/g' . (int) $guia_id . '-' . $bloque_id . '.' . $ext;
}

/**
 * Convierte un titulo en una direccion web: "¿Cuánto cobrar?" -> "cuanto-cobrar".
 */
function guia_slug($texto) {
    $t = mb_strtolower(trim($texto), 'UTF-8');
    $t = strtr($t, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
                    'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','ç'=>'c']);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    $t = trim($t, '-');
    return mb_substr($t, 0, 90) ?: 'guia';
}

/** Saca el ID de un link de YouTube. Devuelve '' si no reconoce el formato. */
function guia_youtube_id($url) {
    $url = trim($url);
    if ($url === '') return '';
    if (preg_match('/^[\w-]{11}$/', $url)) return $url;
    $patrones = [
        '~youtu\.be/([\w-]{11})~',
        '~youtube\.com/watch\?(?:[^#]*&)?v=([\w-]{11})~',
        '~youtube\.com/shorts/([\w-]{11})~',
        '~youtube\.com/embed/([\w-]{11})~',
        '~youtube\.com/live/([\w-]{11})~',
    ];
    foreach ($patrones as $p) {
        if (preg_match($p, $url, $m)) return $m[1];
    }
    return '';
}

/** Lee los bloques guardados. Devuelve siempre un array. */
function guia_bloques($guia) {
    $b = json_decode($guia['cuerpo_json'] ?? '', true);
    return is_array($b) ? $b : [];
}

/**
 * Convierte en enlaces las direcciones que aparezcan en el texto de una guia.
 *
 * Se le pasa texto YA ESCAPADO. La administradora escribe texto plano, no HTML,
 * asi que sin esto una direccion pegada quedaba como letras muertas.
 *
 * Entiende dos formas:
 *   https://printikatools.com/          la direccion sola
 *   [lo que se lee](https://...)        con el texto que uno quiera
 *
 * Todos abren en una pestana nueva: quien esta leyendo una guia no tiene que
 * perderla por tocar un enlace. Y todos llevan rel="noopener", que es lo que
 * evita que la pagina de destino pueda tocar la nuestra.
 *
 * Solo http y https. Cualquier otra cosa (javascript:, data:) se deja como
 * texto: el contenido lo carga ella, pero no hay razon para abrir esa puerta.
 */
function guia_enlaces($escapado) {
    $guardado = [];
    $guardar = function ($url, $texto) use (&$guardado) {
        // El escapado dejo los "&" como "&amp;", que es lo correcto adentro de
        // un href, asi que la direccion se usa tal cual vino
        $guardado[] = '<a href="' . $url . '" target="_blank" rel="noopener">' . $texto . '</a>';
        return "\x02" . (count($guardado) - 1) . "\x03";
    };

    // 1) [texto](direccion) — primero, para que su direccion no la agarre el paso 2
    $salida = preg_replace_callback(
        '~\[([^\]\n]+)\]\((https?://[^)\s]+)\)~i',
        fn($m) => $guardar($m[2], $m[1]),
        $escapado
    );

    // 2) La direccion sola. Los signos del final no son parte de la direccion:
    //    "mira https://printikatools.com/." termina en punto, no en barra-punto.
    $salida = preg_replace_callback(
        '~(?<![\w@/])(https?://[^\s<]+)~i',
        function ($m) use ($guardar) {
            $url = rtrim($m[1], '.,;:!?)');
            $cola = substr($m[1], strlen($url));
            return $guardar($url, $url) . $cola;
        },
        $salida
    );

    return preg_replace_callback('~\x02(\d+)\x03~', fn($m) => $guardado[(int) $m[1]] ?? '', $salida);
}

/**
 * Dibuja el cuerpo de una guia.
 *
 * Todo el texto sale escapado: la administradora escribe texto plano, no HTML.
 * Lo unico que interpretamos son los renglones en blanco (parrafos nuevos), los
 * renglones sueltos de las listas y las direcciones, que se vuelven enlaces
 * (ver guia_enlaces()).
 */
function guia_render($guia) {
    foreach (guia_bloques($guia) as $b) {
        $tipo = $b['t'] ?? '';
        $v = trim((string) ($b['v'] ?? ''));

        if ($tipo === 'titulo' && $v !== '') {
            echo '<h2 id="' . htmlspecialchars(guia_slug($v)) . '">' . htmlspecialchars($v) . "</h2>\n";

        } elseif ($tipo === 'texto' && $v !== '') {
            foreach (preg_split('/\n\s*\n/', $v) as $parrafo) {
                $parrafo = trim($parrafo);
                if ($parrafo !== '') echo '<p>' . nl2br(guia_enlaces(htmlspecialchars($parrafo))) . "</p>\n";
            }

        } elseif ($tipo === 'lista' && $v !== '') {
            echo "<ul>\n";
            foreach (preg_split('/\n/', $v) as $item) {
                $item = trim($item, " \t-•");
                if ($item !== '') echo '  <li>' . guia_enlaces(htmlspecialchars($item)) . "</li>\n";
            }
            echo "</ul>\n";

        } elseif ($tipo === 'destacado' && $v !== '') {
            echo '<div class="resumen">';
            foreach (preg_split('/\n\s*\n/', $v) as $parrafo) {
                $parrafo = trim($parrafo);
                if ($parrafo !== '') echo '<p>' . nl2br(guia_enlaces(htmlspecialchars($parrafo))) . '</p>';
            }
            echo "</div>\n";

        } elseif ($tipo === 'formula' && $v !== '') {
            echo '<div class="formula">' . htmlspecialchars($v) . "</div>\n";

        } elseif ($tipo === 'imagen' && !empty($b['ext'])) {
            $url = guia_img_url($guia['id'], $b['id'] ?? '', $b['ext']);
            $pie = trim((string) ($b['pie'] ?? ''));
            echo '<figure class="fig-guia">';
            echo '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars($pie ?: $guia['titulo'])
               . '" loading="lazy" decoding="async">';
            if ($pie !== '') echo '<figcaption>' . htmlspecialchars($pie) . '</figcaption>';
            echo "</figure>\n";

        } elseif ($tipo === 'video' && $v !== '') {
            $pie = trim((string) ($b['pie'] ?? ''));
            echo '<figure class="fig-guia">';
            echo '<div class="video-guia"><iframe src="https://www.youtube-nocookie.com/embed/'
               . htmlspecialchars($v) . '" title="' . htmlspecialchars($pie ?: $guia['titulo'])
               . '" loading="lazy" allowfullscreen'
               . ' allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"'
               . ' referrerpolicy="strict-origin-when-cross-origin"></iframe></div>';
            if ($pie !== '') echo '<figcaption>' . htmlspecialchars($pie) . '</figcaption>';
            echo "</figure>\n";
        }
    }
}

/** Texto plano de la guia, para la meta descripcion cuando no hay bajada. */
function guia_extracto($guia, $largo = 160) {
    foreach (guia_bloques($guia) as $b) {
        if (in_array($b['t'] ?? '', ['destacado', 'texto'], true) && trim($b['v'] ?? '') !== '') {
            $t = preg_replace('/\s+/u', ' ', trim($b['v']));
            return mb_strlen($t) > $largo ? mb_substr($t, 0, $largo - 1) . '…' : $t;
        }
    }
    return '';
}

/** Todas las guias publicadas, la mas nueva primero. */
function guias_publicadas() {
    if (!com_db_ok()) return [];
    try {
        return com_db()->query('SELECT * FROM guias WHERE publicado = 1
                                ORDER BY creado_en DESC, id DESC')->fetchAll();
    } catch (Throwable $e) {
        return [];   // la tabla todavia no existe: la portada de guias sigue andando
    }
}

/** Una guia por su direccion. Devuelve null si no existe o no esta publicada. */
function guia_por_slug($slug) {
    if (!com_db_ok()) return null;
    try {
        $stmt = com_db()->prepare('SELECT * FROM guias WHERE slug = ? AND publicado = 1 LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    } catch (Throwable $e) {
        return null;
    }
}
