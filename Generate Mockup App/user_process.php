<?php
session_start();
require "db.php";
require "image.php"; // Pastikan file ini berisi fungsi prepareUserImage & prepareBackground yang sudah kita perbaiki sebelumnya

$session = session_id();
$folder = "temp_results/" . $session . "/";

// 1. Bersihkan folder hasil lama
if (!is_dir($folder)) mkdir($folder, 0777, true);
$files = glob($folder . "*");
foreach($files as $file){
    if(is_file($file)) unlink($file);
}

// 2. Validasi Input dari user_index.php
// Perhatikan: name di form adalah 'user_bg', bukan 'user_img'
if (!isset($_POST['preset_id']) || !isset($_FILES['user_bg'])) {
    die("Error: Data tidak lengkap. Pilih preset dan upload gambar.");
}

$preset_id = intval($_POST['preset_id']);

// ---------------------------------------------------------
// PERSIAPAN LOGO (LAYER 3)
// ---------------------------------------------------------
$logoId  = isset($_POST['logo_id']) ? intval($_POST['logo_id']) : 0;
$logoPos = isset($_POST['logo_pos']) ? $_POST['logo_pos'] : 'top-right';

$logoImg = null;
$logoW = 0; 
$logoH = 0;

if ($logoId > 0) {
    // Ambil path logo dari database
    $qLogo = mysqli_query($conn, "SELECT * FROM logos WHERE id=$logoId");
    if ($qLogo && mysqli_num_rows($qLogo) > 0) {
        $logoData = mysqli_fetch_assoc($qLogo);
        // Pastikan path sesuai dengan script upload logo kamu
        // Di logo_upload.php path disimpan full (termasuk folder uploads/logos/)
        $path = $logoData['file_path']; 
        
        if (file_exists($path)) {
            $logoImg = imagecreatefrompng($path);
            if($logoImg) {
                imagealphablending($logoImg, true);
                imagesavealpha($logoImg, true);
                $logoW = imagesx($logoImg);
                $logoH = imagesy($logoImg);
            }
        }
    }
}

// Fungsi Hitung Posisi Logo
function getLogoCoords($canvasSize, $w, $h, $pos) {
    $pad = 50; // Jarak padding dari pinggir
    if ($pos == 'top-left')     return [$pad, $pad];
    if ($pos == 'top-right')    return [$canvasSize - $w - $pad, $pad];
    if ($pos == 'bottom-left')  return [$pad, $canvasSize - $h - $pad];
    if ($pos == 'bottom-right') return [$canvasSize - $w - $pad, $canvasSize - $h - $pad];
    return [$pad, $pad]; // Default top-left
}

// ---------------------------------------------------------
// PROSES GENERATE (LOOPING USER IMAGE)
// ---------------------------------------------------------
// Karena input form pakai user_bg[] (multiple), kita harus loop
$countFiles = count($_FILES['user_bg']['name']);

// Ambil data background preset sekali saja agar hemat query
$bgRows = [];
$qBg = mysqli_query($conn, "SELECT * FROM backgrounds WHERE preset_id=$preset_id");
while($row = mysqli_fetch_assoc($qBg)) {
    $bgRows[] = $row;
}

// Loop setiap gambar user yang diupload
for($i = 0; $i < $countFiles; $i++) {

    // Cek error upload
    if ($_FILES['user_bg']['error'][$i] !== UPLOAD_ERR_OK) continue;

    $tmpName = $_FILES['user_bg']['tmp_name'][$i];
    $fileName = "user_temp_" . $i . ".png";
    $userPath = $folder . $fileName;

    // Pindahkan file user
    if (!move_uploaded_file($tmpName, $userPath)) continue;

    // Prepare User Image (LAYER 1 - BAWAH)
    $userImage = prepareUserImage($userPath);
    if (!$userImage) continue;

    // Loop setiap angle background (LAYER 2 - TENGAH)
    foreach ($bgRows as $b) {
        $bgPath = "backgrounds/" . $b['file_path'];
        
        // Cek file background ada/tidak
        if(!file_exists($bgPath)) continue;

        $bgImage = prepareBackground($bgPath); 

        if ($bgImage) {
            // Nama file output: result_{id_background}_{urutan_upload}.png
            $outName = "result_" . $b['id'] . "_" . $i . ".png";
            $outPath = $folder . $outName;

            // --- KOMPOSISI GAMBAR MANUAL (3 LAYER) ---
            
            // A. Buat Canvas Transparan
            $final = imagecreatetruecolor(1080, 1080);
            imagealphablending($final, false);
            imagesavealpha($final, true);
            $trans = imagecolorallocatealpha($final, 255, 255, 255, 127);
            imagefill($final, 0, 0, $trans);
            imagealphablending($final, true);

            // B. Tempel User Pattern (Paling Bawah)
            imagecopy($final, $userImage, 0, 0, 0, 0, 1080, 1080);

            // C. Tempel Admin Overlay (Tengah - Menimpa user tapi bolong di tengah)
            imagecopy($final, $bgImage, 0, 0, 0, 0, 1080, 1080);

            // D. Tempel Logo (Paling Atas) - Jika ada
            if ($logoImg) {
                list($lx, $ly) = getLogoCoords(1080, $logoW, $logoH, $logoPos);
                imagecopy($final, $logoImg, $lx, $ly, 0, 0, $logoW, $logoH);
            }

            // E. Simpan Hasil
            imagepng($final, $outPath, 9);

            imagedestroy($final);
            imagedestroy($bgImage);
        }
    }
    imagedestroy($userImage);
}

// Bersihkan Logo dari memori
if ($logoImg) imagedestroy($logoImg);

// Redirect ke halaman preview
header("Location: preview.php");
exit;
?>