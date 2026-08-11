<?php
session_start();
require "db.php";

header("Content-Type: application/json");

$session = session_id();
$folder = "temp_editor/$session/";

// pastikan folder ada
if (!is_dir($folder)) mkdir($folder, 0777, true);

// ambil preset id
if (!isset($_POST['preset_id'])) {
    echo json_encode(["error" => "Preset ID tidak ditemukan."]);
    exit;
}

$preset = intval($_POST['preset_id']);

// ambil background pertama
$first = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT file_path FROM backgrounds WHERE preset_id=$preset ORDER BY id ASC LIMIT 1"
));

if (!$first) {
    echo json_encode(["error" => "Preset tidak memiliki background."]);
    exit;
}

// pastikan file upload ada
if (!isset($_FILES['user_img']['tmp_name'])) {
    echo json_encode(["error" => "Gambar user tidak ditemukan."]);
    exit;
}

// BACA GAMBAR USER + CONVERT SELALU KE JPEG
$tmp = $_FILES['user_img']['tmp_name'];
$imageData = file_get_contents($tmp);

$img = imagecreatefromstring($imageData);
if (!$img) {
    echo json_encode(["error" => "Gagal membaca gambar user."]);
    exit;
}

$userFile = "user.png";
$userPath = $folder . $userFile;

// simpan sebagai JPEG untuk konsistensi
imagejpeg($img, $userPath, 100);
imagedestroy($img);

// simpan ke SESSION
$_SESSION['user_file'] = $userFile;
$_SESSION['preset_id'] = $preset;

// KIRIM JSON RESPONSE
echo json_encode([
    "bg" => "backgrounds/" . $first['file_path'],
    "user" => $userPath
]);
