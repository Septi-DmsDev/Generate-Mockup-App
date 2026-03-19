<?php
require "db.php";

if (!isset($_GET['id']) || !isset($_GET['file'])) {
    die("Data tidak lengkap.");
}

$bgId = intval($_GET['id']);
$file = $_GET['file'];

$path = "backgrounds/" . $file;
if (file_exists($path)) {
    unlink($path);
}

mysqli_query($conn, "DELETE FROM backgrounds WHERE id='$bgId'");

header("Location: preset_manager.php?deleted=1");
exit;
?>
