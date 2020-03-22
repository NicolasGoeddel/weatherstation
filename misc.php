<?php

function get(&$var, $default=null) {
    return isset($var) ? $var : $default;
}

function parseDate(&$var) {
    $value = get($var, null);
    if ($value === null) {
        return null;
    }
    // DateTime::ISO8601 kann keine Millisekunden parsen, also manuell!
    $converted = DateTime::createFromFormat("Y-m-d\TH:i:s", $value);
    if ($converted === FALSE) {
        return null;
    }
    return $converted;
}

// https://stackoverflow.com/a/834355/4239139
function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function parseTimedelta($delta) {
    if (is_int($delta)) {
        return $delta;
    }
    if ($delta === null || !is_string($delta)) {
        return null;
    }
    $unitMap = array(
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
        'w' => 604800,
        'y' => 220752000
    );
    $unit = substr($delta, -1);
    $factor = 1;

    if (array_key_exists($unit, $unitMap)) {
        $factor = $unitMap[$unit];
    }

    return intval($delta) * $factor;
}

function jsonOut($data, $exitCode = 0) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit($exitCode);
}

function jsonError($error) {
    jsonOut(array('error' => $error,
                  'get' => $_GET),
            1);
}

?>