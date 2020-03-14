<?php
include_once('config.php');

$conn = new mysqli($db['hostname'], $db['username'], $db['password']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->select_db($db['database']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>