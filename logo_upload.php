<?php
require "db.php";
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$folder = "uploads/logos/";
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$name = mysqli_real_escape_string($conn, $_POST['name']);
$file = $_FILES['logo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    die("Upload gagal");
}

$srcImg = imagecreatefrompng($file['tmp_name']);
if (!$srcImg) {
    die("File bukan PNG valid");
}

$srcW = imagesx($srcImg);
$srcH = imagesy($srcImg);

$maxSize = 150;

// hitung rasio
$ratio = min($maxSize / $srcW, $maxSize / $srcH, 1);
$newW = (int)($srcW * $ratio);
$newH = (int)($srcH * $ratio);

// canvas baru
$dstImg = imagecreatetruecolor($newW, $newH);
imagealphablending($dstImg, false);
imagesavealpha($dstImg, true);

$transparent = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
imagefill($dstImg, 0, 0, $transparent);

// resize
imagecopyresampled(
    $dstImg,
    $srcImg,
    0, 0, 0, 0,
    $newW, $newH,
    $srcW, $srcH
);

$filename = uniqid("logo_") . ".png";
$path = $folder . $filename;

imagepng($dstImg, $path);
imagedestroy($srcImg);
imagedestroy($dstImg);

// simpan DB
mysqli_query($conn, "
    INSERT INTO logos (name, file_path)
    VALUES ('$name', '$path')
");

header("Location: logo_manager.php");
exit;
