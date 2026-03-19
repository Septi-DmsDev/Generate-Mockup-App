<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "overlay_app";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database error: " . mysqli_connect_error());
}
?>
