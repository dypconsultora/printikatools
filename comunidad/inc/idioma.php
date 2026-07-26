<?php
/**
 * Idioma de la portada.
 *
 * La landing existe en dos direcciones distintas para que Google pueda
 * indexar cada idioma por separado:
 *
 *   https://printikatools.com/     -> castellano
 *   https://printikatools.com/en/  -> ingles
 *
 * Las dos salen del MISMO index.php: en/index.php define LANDING_EN y lo
 * incluye. El texto se escribe una sola vez, en castellano, y se traduce al
 * vuelo con el diccionario de assets/lang/landing-en.json. Asi no hay dos
 * copias de la landing que mantener en paralelo.
 */

/** 'es' o 'en', segun por que direccion entraron. */
function landing_idioma() {
    return defined('LANDING_EN') ? 'en' : 'es';
}

/** Diccionario castellano -> ingles (vacio si estamos en castellano). */
function landing_dic() {
    static $dic = null;
    if ($dic !== null) return $dic;
    $dic = [];
    if (landing_idioma() === 'en') {
        $json = @file_get_contents(dirname(__DIR__, 2) . '/assets/lang/landing-en.json');
        if ($json !== false) {
            $d = json_decode($json, true);
            if (is_array($d)) $dic = $d;
        }
    }
    return $dic;
}

/** Traduce una frase suelta (la devuelve igual si no esta en el diccionario). */
function t($texto) {
    $dic = landing_dic();
    return $dic[$texto] ?? $texto;
}

/**
 * Traduce el HTML ya armado: reemplaza el texto que hay entre etiquetas y
 * los atributos que el visitante llega a ver. Es el mismo criterio que usaba
 * el traductor de JavaScript, pero hecho en el servidor, que es lo unico que
 * Google indexa.
 *
 * Lo que queda adentro de <script> y <style> no se toca.
 */
function landing_traducir($html) {
    $dic = landing_dic();
    if (!$dic) return $html;

    // 1) Guardar aparte scripts y estilos
    $guardado = [];
    $html = preg_replace_callback('#<(script|style)\b[^>]*>.*?</\1\s*>#is', function ($m) use (&$guardado) {
        $guardado[] = $m[0];
        return "\x02" . (count($guardado) - 1) . "\x03";
    }, $html);

    // 2) Texto entre etiquetas
    $html = preg_replace_callback('#>([^<]+)<#', function ($m) use ($dic) {
        $limpio = trim(preg_replace('/\s+/u', ' ', $m[1]));
        if ($limpio === '' || !isset($dic[$limpio])) return $m[0];
        return '>' . $dic[$limpio] . '<';
    }, $html);

    // 3) Atributos visibles (incluye los data- que usa el selector de moneda)
    $attrs = ['alt', 'title', 'aria-label', 'placeholder', 'data-ars', 'data-usd'];
    $html = preg_replace_callback(
        '#\b(' . implode('|', $attrs) . ')="([^"]*)"#i',
        function ($m) use ($dic) {
            $limpio = trim($m[2]);
            return isset($dic[$limpio]) ? $m[1] . '="' . htmlspecialchars($dic[$limpio], ENT_QUOTES) . '"' : $m[0];
        },
        $html
    );

    // 4) Devolver scripts y estilos a su lugar
    return preg_replace_callback('#\x02(\d+)\x03#', function ($m) use ($guardado) {
        return $guardado[(int) $m[1]] ?? '';
    }, $html);
}

/**
 * Titulo animado del hero, palabra por palabra (cada una entra con su propio
 * retraso). Se arma en el servidor para que Google lea el titular completo.
 * El segundo grupo es el que va con el degradado (<em>).
 */
function landing_hero_h1() {
    $partes = landing_idioma() === 'en'
        ? [['Run', 'your', '3D', 'printing', 'workshop'], ['like', 'a', 'business']]
        : [['Manejá', 'tu', 'taller', 'de', 'impresión', '3D'], ['como', 'un', 'negocio']];

    $retraso = 0;
    $palabra = function ($p) use (&$retraso) {
        $html = '<span class="palabra" data-delay="' . $retraso . '">' . $p . '</span>';
        $retraso += 120;
        return $html;
    };
    $normal = implode(' ', array_map($palabra, $partes[0]));
    $retraso += 60; // respiro antes del remate
    $destacado = implode(' ', array_map($palabra, $partes[1]));

    return $normal . ' <em>' . $destacado . '</em>';
}

/** Direccion de la portada en el otro idioma (para el selector y el hreflang). */
function landing_url($idioma) {
    return $idioma === 'en' ? 'https://printikatools.com/en/' : 'https://printikatools.com/';
}
