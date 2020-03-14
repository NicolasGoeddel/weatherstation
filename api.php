<?php
include_once('station.php');
include_once('type.php');
include_once('misc.php');

// error handler function
function myErrorHandler($errno, $errstr, $errfile, $errline) {
    if ($errno == E_USER_ERROR) {
        jsonError($errstr);
    }
    return FALSE;
}

set_error_handler("myErrorHandler");


if (!isset($_GET['module'])) {
    jsonError("module missing.");
}
$module = $_GET['module'];

if (!isset($_GET['action'])) {
    jsonError("action missing.");
}
$action = $_GET['action'];

if ($module == "station") {
    if ($action == "list") {
        jsonOut(Station::dictAll());
    }

} elseif ($module == "type") {
    if ($action == "list") {
        $name = get($_GET['name'], null);
        if ($name === null) {
            jsonOut(Type::dictAll());
        } else {
            jsonOut(Type::getType($name)->dict());
        }
    }

} elseif ($module == "data") {
    if (!isset($_GET['station'])) {
        jsonError("station missing.");
    }
    $stationId = intval($_GET['station']);
    $station = Station::getStation($stationId);
    if ($station === null) {
        jsonError("station unknown.");
    }
    if ($action == "add") {
        $jsonBody = file_get_contents('php://input');
  
        $data = json_decode($jsonBody, TRUE);

        usort($data, function($a, $b) {
            return $a['reltime'] <=> $b['reltime'];
        });

        $offsetTime = $data[0]['reltime'];

        $missing = array();
        
        foreach($data as $d) {
            $diff = $d['reltime'] - $offsetTime;
            $dataTime = new DateTime();
            $dataTime->add(new DateInterval("PT${diff}S"));
            $missed = $station->addData($dataTime, $d['data']);
            if (count($missed) > 0) {
                $missing[] = array('reltime' => $d['reltime'],
                                   'data' => $missed);
            }
        }

        jsonOut(array('success' => TRUE,
                      'missing' => $missing));

    } else if ($action == 'get') {
        $startTime = parseDate($_GET['start']);
        $endTime = parseDate($_GET['end']);
        $types = get($_GET['types']);
        jsonOut($station->getData($types, $startTime, $endTime));
        exit();
    }
} else {
    jsonError("Unknown module.");
}

jsonError("Unknown action.");

?>