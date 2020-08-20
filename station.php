<?php
include_once('database.php');
include_once('type.php');

class Station {
    private $id;
    private $name;
    private static $stations = null;

    private function __construct($id, $name) {
        $this->id = intval($id);
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function addData($datetime, $data) {
        global $conn;

        $timestamp = $datetime->format("Y-m-d H:i:s");
        $missing = array();

        if ($prepped = $conn->prepare("INSERT INTO data (station_id, timestamp, type_id, value) VALUES (?, ?, ?, ?);")) {
            $prepped->bind_param("isid", $this->id, $timestamp, $typeId, $value);

            foreach($data as $typeName => $value) {
                $type = Type::getType($typeName);
                if ($type === null) {
                    $missing[] = array($typeName => $value);
                } else {
                    $typeId = $type->getId();
                    $prepped->execute();
                }
            }

            $prepped->close();
        }
        return $missing;
    }

    public function getData($types = null, $startTime = null, $endTime = null, $stepSize = null, $subTypes = null) {
        global $conn;
        if (is_string($types)) {
            $types = explode(',', $types);
        } else if (!is_array($types)) {
            $types = null;
        }

        if (startsWith($stepSize, 'auto')) {
            $t = explode(':', $stepSize);
            if (count($t) == 1) {
                $autoStepSize = 100;
            } else {
                $autoStepSize = intval($t[1]);
                if ($autoStepSize < 1) {
                    $autoStepSize = 1;
                }
            }
        } else {
            $autoStepSize = null;
            $stepSize = parseTimeDelta($stepSize);
            if ($stepSize <= 1) {
                $stepSize = null;
            }
        }

        if (is_string($subTypes)) {
            $subTypes = explode(',', $subTypes);
        } else if (!is_array($subTypes)) {
            $subTypes = null;
        }
        if ($subTypes === null || count($subTypes) == 0) {
            $subTypes = array('data');
            $stepSize = null;
        }

        $subTypesMap = array(
            'data' => "AVG(value) * conversion_factor",
            'min'  => "MIN(value) * conversion_factor",
            'max'  => "MAX(value) * conversion_factor",
            'stddev'  => "STDDEV_POP(value) * conversion_factor",
            'variance'  => "VAR_POP(value) * conversion_factor"
        );

        $subTypesSql = array();
        foreach ($subTypes as $subType) {
            $subTypesSql[] = $subTypesMap[$subType] . " AS v_" . $subType;
        }

        $filterQuery = array("station_id = " . strval($this->id));

        if ($startTime instanceof DateTime) {
            if (is_int($stepSize)) {
                $startTime->sub(new DateInterval("PT${stepSize}S"));
            }
            $startTime = $startTime->format("Y-m-d H:i:s");
            $filterQuery[] = "`timestamp` >= '" . $startTime . "'";
        } else {
            $startTime = null;
        }

        if ($endTime instanceof DateTime) {
            if (is_int($stepSize)) {
                $endTime->add(new DateInterval("PT${stepSize}S"));
            }
            $endTime = $endTime->format("Y-m-d H:i:s");
            $filterQuery[] = "`timestamp` <= '" . $endTime . "'";
        } else {
            $endTime = null;
        }

        $filterQuerySql = implode(" AND ", $filterQuery);

        $data = array('timestamp' => array());
        $allTypes = array();

        $timeRange = array(
            'min' => null,
            'max' => null
        );

        if ($result = $conn->query("
            SELECT
                name,
                type.id AS id,
                unit,
                typical_min,
                typical_max,
                conversion_factor,
                COUNT(data.id) AS count,
                MIN(`timestamp`) As time_min,
                MAX(`timestamp`) AS time_max
            FROM
                data JOIN type ON data.type_id = type.id
            WHERE " . $filterQuerySql . "
            GROUP BY
                type.id
            ORDER BY
                type_id ASC")) {
            while ($row = $result->fetch_object()) {
                if ($types === null or in_array($row->name, $types)) {
                    $allTypes[] = array(
                        'name' => $row->name,
                        'id' => $row->id
                    );

                    $data[$row->name] = array(
                        'unit' => $row->unit
                    );
                    if ($row->typical_min !== null) {
                        $data[$row->name]['typical_min'] = $row->typical_min * $row->conversion_factor;
                    } else {
                        $data[$row->name]['typical_min'] = null;
                    };
                    if ($row->typical_max !== null) {
                        $data[$row->name]['typical_max'] = $row->typical_max * $row->conversion_factor;
                    } else {
                        $data[$row->name]['typical_max'] = null;
                    }
                    foreach ($subTypes as $subType) {
                        $data[$row->name][$subType] = array();
                    }

                    $time_min = new DateTime($row->time_min);
                    if ($timeRange['min'] === null || $time_min->getTimestamp() < $timeRange['min']->getTimestamp()) {
                        $timeRange['min'] = $time_min;
                    }
                    $time_max = new DateTime($row->time_max);
                    if ($timeRange['max'] === null || $time_max->getTimestamp() > $timeRange['max']->getTimestamp()) {
                        $timeRange['max'] = $time_max;
                    }
                }
            }
            $result->close();
        } else {
            //TODO Add Exception here
            return null;
        }

        if (count($allTypes) === 0) {
            return $data;
        }

        if (!($types === null)) {
            $filterQuery[] = "data.type_id IN (" . implode(',', array_column($allTypes, 'id')) . ")";
        }
        
        $filterQuerySql = implode(" AND ", $filterQuery);

        if ($stepSize === null) {
            $timegrouping = "`timestamp`";
            $timeselect   = "MIN(`timestamp`) AS `timestamp`";
        } else {
            $halfStep = strval(floor($stepSize / 2));
            $timegrouping = "(UNIX_TIMESTAMP(`timestamp`) - " . $halfStep . ") DIV " . $stepSize;
            $timeselect   = "ADDTIME(MIN(`timestamp`), " . $halfStep . ") AS `timestamp`";
        }
               
        $counter = 0;
        $sqlQuery = "
                    SELECT
                        " . $timeselect . ",
                        name,
                        " . implode(", ", $subTypesSql) . "
                    FROM
                        data JOIN type ON data.type_id = type.id
                    WHERE
                        " . $filterQuerySql . " 
                    GROUP BY
                        " . $timegrouping . ",
                        type_id
                    ORDER BY
                        `timestamp` ASC, type_id ASC;";
        if ($result = $conn->query($sqlQuery)) {
            $lastTimestamp = null;
            while ($row = $result->fetch_assoc()) {
                $timestamp = new DateTime($row['timestamp']);

                if ($timestamp != $lastTimestamp) {
                    foreach($allTypes as $typeInfo) {
                        $typeName = $typeInfo['name'];
                        foreach ($subTypes as $subType) {
                            if (count($data[$typeName][$subType]) < $counter) {
                                $data[$typeName][$subType][] = null;
                            }
                        }
                    }
                    $data['timestamp'][] = $timestamp->format(DateTime::ISO8601);
                    $counter++;
                }

                foreach ($subTypes as $subType) {
                    $data[$row['name']][$subType][] = $row["v_" . $subType];
                }
                
                $lastTimestamp = $timestamp;
            }
            $result->close();
        } else {
            echo $conn->error;
        }
        return $data;
    }

    public static function getStation($id) {
        global $conn;
        if (self::$stations !== null && isset(self::$stations[$id])) {
            return self::$stations[$id];
        } else {
            if (self::$stations === null) {
                self::$stations = array();
            }
            $station = null;
            if ($prepped = $conn->prepare("SELECT id, name FROM station WHERE id = ?;")) {
                $prepped->bind_param('i', $id);
                $prepped->execute();
                $result = $prepped->get_result();
                if ($result->num_rows == 0) {
                    $result->close();
                    $prepped->close();
                    return null;
                }
                $row = $result->fetch_object();
                $station = new Station($row->id, $row->name);
                self::$stations[$id] = $station;
                $result->close();
                $prepped->close();
            }
            
            return $station;
        }
    }

    public static function getStations() {
        global $conn;
        if (self::$stations === null) {
            self::$stations = array();
            if ($result = $conn->query("SELECT id, name FROM station;")) {
                while ($row = $result->fetch_object()) {
                    self::$stations[$row->id] = new Station($row->id, $row->name);
                }
                $result->close();
            }
            ksort(self::$stations);
        }
        return self::$stations;
    }

    public static function htmlTable() {
        echo "<table><tr><th></th><th>ID</th><th>Name</th></tr>";
        foreach(self::getStations() as $station) {
            $name = htmlspecialchars($station->name);
            $id = $station->id;
            echo "<tr><td></td><td>${id}</td><td>${name}</td></tr>";
        }
        echo "</table>";
    }

    public function dict() {
        return array('id' => $this->id,
                     'name' => $this->name);
    }

    public static function dictAll() {
        $result = array();

        foreach(self::getStations() as $station) {
            $result[$station->id] = $station->dict();
        }

        return $result;
    }
}

?>