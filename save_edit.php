<?php
session_start();

$session = session_id();
$folder = "temp_results/" . $session . "/";

$file = $_POST['file'];
$data = $_POST['data']; // Base64 image

$path = $folder . $file;

// decode base64
$imgData = str_replace("data:image/jpeg;base64,", "", $data);
$imgData = base64_decode($imgData);

// save file
file_put_contents($path, $imgData);

echo "OK";
?>
