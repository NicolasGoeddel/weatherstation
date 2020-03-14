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

    public function getData($types = null, $startTime = null, $endTime = null, $stepSize = null) {
        global $conn;
        if (is_string($types)) {
            $types = explode(',', $types);
        } else if (!is_array($types)) {
            $types = null;
        }

        $filterQuery = array("station_id = " . strval($this->id));

        if ($startTime instanceof DateTime) {
            $startTime = $startTime->format("Y-m-d H:i:s");
            $filterQuery[] = "`timestamp` >= '" . $startTime . "'";
        } else {
            $startTime = null;
        }

        if ($endTime instanceof DateTime) {
            $endTime = $endTime->format("Y-m-d H:i:s");
            $filterQuery[] = "`timestamp` <= '" . $endTime . "'";
        } else {
            $endTime = null;
        }

        $filterQuerySql = implode(" AND ", $filterQuery);

        $data = array('timestamp' => array());
        $allTypes = array();

        if ($result = $conn->query("Select DISTINCT name, type.id AS id, unit FROM data JOIN type ON data.type_id = type.id WHERE " . $filterQuerySql . " ORDER BY type_id ASC")) {
            while ($row = $result->fetch_object()) {
                if ($types === null or in_array($row->name, $types)) {
                    $allTypes[] = array(
                        'name' => $row->name,
                        'id' => $row->id
                    );
                    $data[$row->name] = array(
                        'data' => array(),
                        'unit' => $row->unit
                    );
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
               
        $counter = 0;
        if ($result = $conn->query("SELECT `timestamp`, name, AVG(value) * conversion_factor AS value FROM data JOIN type ON data.type_id = type.id WHERE " . $filterQuerySql . " GROUP BY `timestamp`, type_id ORDER BY `timestamp` ASC, type_id ASC;")) {
            $lastTimestamp = null;
            while ($row = $result->fetch_object()) {
                $timestamp = new DateTime($row->timestamp);

                if ($timestamp != $lastTimestamp) {
                    foreach($allTypes as $typeInfo) {
                        $typeName = $typeInfo['name'];
                        if (count($data[$typeName]['data']) < $counter) {
                            $data[$typeName]['data'][] = null;
                        }
                    }
                    $data['timestamp'][] = $timestamp->format(DateTime::ISO8601);
                    $counter++;
                }

                $data[$row->name]['data'][] = $row->value;
                
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