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