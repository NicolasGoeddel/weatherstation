<?php
include_once('database.php');
include_once('misc.php');

class Type {
    private $id;
    private $name;
    private $unit;
    private $conversionFactor;

    private static $types = null;

    private function __construct($id, $name, $unit, $conversionFactor) {
        $this->id = intval($id);
        $this->name = $name;
        $this->unit = $unit;
        $this->conversionFactor = $conversionFactor;
    }

    public static function getType($name) {
        if (self::$types === null) {
            return get(self::getTypes()[$name], null);
        }
        return get(self::$types[$name], null);
    }

    public function getId() {
        return $this->id;
    }

    public static function getTypes() {
        global $conn;
        if (self::$types === null) {
            self::$types = array();
            if ($result = $conn->query("SELECT id, name, unit, conversion_factor FROM type;")) {
                while ($row = $result->fetch_object()) {
                    self::$types[$row->name] = new Type($row->id, $row->name, $row->unit, $row->conversion_factor);
                }
                $result->close();
            }
            ksort(self::$types);
        }
        return self::$types;
    }
    
    public static function htmlTable() {
        echo "<table><tr><th></th><th>Name</th><th>ID</th><th>Unit</th><th>Factor</th></tr>";
        foreach(self::getTypes() as $type) {
            $name = htmlspecialchars($type->name);
            $id = $type->id;
            $unit = htmlspecialchars($type->unit);
            $factor = $type->conversionFactor;
            echo "<tr><td></td><td>${name}</td><td>${id}</td><td>${unit}</td><td>${factor}</td></tr>";
        }
        echo "</table>";
    }

    public function dict() {
        return array('name' => $this->name,
                     'id' => $this->id,
                     'unit' => $this->unit,
                     'conversion_factor' => $this->conversionFactor); 
    }

    public static function dictAll() {
        $result = array();

        foreach(self::getTypes() as $type) {
            $result[$type->name] = $type->dict();
        }

        return $result;
    }
}
?>