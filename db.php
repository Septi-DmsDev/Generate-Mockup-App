<?php
// Prioritaskan Environment Variables (untuk deploy di Coolify)
// Jika tidak ada (misal di localhost XAMPP), gunakan nilai default (fallback)
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USERNAME') ?: "root";
$pass = getenv('DB_PASSWORD') ?: "";
$db   = getenv('DB_DATABASE') ?: "overlay_app";
$port = getenv('DB_PORT') ?: 3306;

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Database error: " . mysqli_connect_error());
}
?>
