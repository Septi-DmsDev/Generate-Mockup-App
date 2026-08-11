<?php
// 1. SETTING WAJIB
ini_set('memory_limit', '-1'); 
set_time_limit(0); 
error_reporting(E_ALL & ~E_DEPRECATED); 

session_start();
require "db.php"; 

$session = session_id();
$folder = "temp_results/" . $session . "/";

if (!file_exists($folder)) {
    die("Folder session tidak ditemukan. Silakan upload ulang.");
}

// ==========================================
// BAGIAN 1: IDENTIFIKASI FILE & CEK HASIL
// ==========================================
$allFiles = glob($folder . "*");
$userImages = [];
$existingResults = [];

foreach ($allFiles as $f) {
    $base = basename($f);
    // Abaikan file sistem
    if ($base == 'layout.json' || $base == 'logo.png' || strpos($base, 'asset_') === 0) continue; 
    
    // Identifikasi gambar hasil yang sudah ada
    if (strpos($base, 'final_result_') === 0 && pathinfo($base, PATHINFO_EXTENSION) == 'jpg') {
        $existingResults[] = $base;
        continue;
    }
    
    // Identifikasi gambar asli dari user
    $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png'])) { 
        $userImages[] = $base; 
    }
}

// ==========================================
// BAGIAN 2: LOAD DATA MASTER
// ==========================================
$layoutFile = $folder . "layout.json";
$layoutData = [];
if (file_exists($layoutFile)) {
    $layoutData = json_decode(file_get_contents($layoutFile), true);
}

$logoPath = "";
if (isset($_SESSION['selected_logo']) && $_SESSION['selected_logo'] != 0) {
    $lid = $_SESSION['selected_logo'];
    $q = mysqli_query($conn, "SELECT file_path FROM logos WHERE id='$lid'");
    if ($row = mysqli_fetch_assoc($q)) $logoPath = $row['file_path'];
}

$presetImages = [];
if (isset($_SESSION['selected_preset'])) {
    $pid = $_SESSION['selected_preset'];
    $q = mysqli_query($conn, "SELECT file_path FROM backgrounds WHERE preset_id='$pid'");
    while ($row = mysqli_fetch_assoc($q)) { 
        $presetImages[] = "backgrounds/" . $row['file_path']; 
    }
}
if (count($presetImages) == 0) $presetImages[] = null; 

// ==========================================
// BAGIAN 3: HELPER
// ==========================================
function resizeIfTooBig($source, $maxW = 4000) {
    if (!$source) return false;
    $w = imagesx($source); $h = imagesy($source);
    if ($w > $maxW || $h > $maxW) {
        $ratio = $w / $h;
        $newW = (int)(($w > $h) ? $maxW : $maxW * $ratio);
        $newH = (int)(($w > $h) ? $maxW / $ratio : $maxW);
        $temp = imagecreatetruecolor($newW, $newH);
        imagealphablending($temp, false); imagesavealpha($temp, true);
        imagecopyresampled($temp, $source, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($source); return $temp;
    }
    return $source;
}

function smartLoadImage($path) {
    $path = parse_url($path, PHP_URL_PATH);
    $path = str_replace('/overlay-app/', '', $path); 
    $path = ltrim($path, '/');
    $path = urldecode($path);
    if (!file_exists($path)) return null;
    $data = file_get_contents($path);
    return ($data) ? @imagecreatefromstring($data) : null;
}

function hex2rgb($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
    }
    return array($r, $g, $b);
}

// ==========================================
// BAGIAN 4: EKSEKUSI (HANYA JIKA PERLU)
// ==========================================
// Mencegah overwrite jika sudah ada hasil (setelah edit satuan)
if (empty($existingResults) || isset($_GET['force'])) {
    
    // Bersihkan hasil lama jika paksa (force)
    if (isset($_GET['force'])) {
        foreach ($allFiles as $f) {
            if (strpos(basename($f), 'final_result_') === 0) { @unlink($f); }
        }
    }

    $globalIndex = 0;
    $canvasSize = 1800; 

    foreach ($userImages as $imgName) {
        foreach ($presetImages as $currentPresetPath) {
            $canvas = imagecreatetruecolor($canvasSize, $canvasSize);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefill($canvas, 0, 0, $white);

            if (!empty($layoutData)) {
                foreach ($layoutData as $layer) {
                    $imgRes = null;
                    if ($layer['type'] == 'background') {
                        $imgRes = smartLoadImage($folder.$imgName);
                        if($imgRes) $imgRes = resizeIfTooBig($imgRes, 4000);
                    } elseif ($layer['type'] == 'product') {
                        if ($currentPresetPath) $imgRes = smartLoadImage($currentPresetPath);
                    } elseif ($layer['type'] == 'image') {
                        if (isset($layer['src'])) $imgRes = smartLoadImage($layer['src']);
                    } elseif ($layer['type'] == 'logo') {
                        if (!empty($logoPath)) $imgRes = smartLoadImage($logoPath);
                        if (!$imgRes && isset($layer['src'])) $imgRes = smartLoadImage($layer['src']);
                    } elseif ($layer['type'] == 'text') {
                        if (!empty($layer['text'])) {
                            list($r, $g, $b) = hex2rgb($layer['color'] ?? "#000000");
                            $textColor = imagecolorallocate($canvas, $r, $g, $b);
                            $fontPath = 'fonts/' . ($layer['font'] ?? 'Arial') . '.ttf';
                            if (!file_exists($fontPath)) $fontPath = 'fonts/Arial.ttf'; 
                            imagettftext($canvas, (int)$layer['fontSize'], -(int)$layer['rotation'], (int)$layer['x'], (int)$layer['y'], $textColor, $fontPath, $layer['text']);
                        }
                        continue; 
                    }

                    if ($imgRes) {
                        $origW = imagesx($imgRes); $origH = imagesy($imgRes);
                        $newW = (int)($origW * $layer['scale']); $newH = (int)($origH * $layer['scale']);
                        $tempImg = imagecreatetruecolor($newW, $newH);
                        imagealphablending($tempImg, false); imagesavealpha($tempImg, true);
                        imagefill($tempImg, 0, 0, imagecolorallocatealpha($tempImg, 0, 0, 0, 127));
                        imagecopyresampled($tempImg, $imgRes, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
                        if ($layer['rotation'] != 0) { $tempImg = imagerotate($tempImg, -$layer['rotation'], imagecolorallocatealpha($tempImg, 0, 0, 0, 127)); }

                        imagecopy($canvas, $tempImg, (int)($layer['x'] - (imagesx($tempImg) / 2)), (int)($layer['y'] - (imagesy($tempImg) / 2)), 0, 0, imagesx($tempImg), imagesy($tempImg));
                        imagedestroy($tempImg); imagedestroy($imgRes);
                    }
                }
            }

            $filenameBase = "final_result_" . $globalIndex;
            imagejpeg($canvas, $folder . $filenameBase . ".jpg", 100); 
            file_put_contents($folder . $filenameBase . ".json", json_encode(['user_bg_file'=>$imgName, 'preset_file'=>$currentPresetPath, 'logo_file'=>$logoPath, 'layout_used'=>$layoutData]));
            imagedestroy($canvas);
            $globalIndex++;
        }
    }
}

// ==========================================
// BAGIAN 5: TAMPILAN GALLERY
// ==========================================
$finalFiles = glob($folder . "final_result_*.jpg");
natsort($finalFiles);
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        * { box-sizing: border-box; }
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; margin: 0; color: #334155; }
        header {
            /* Membuat Header Tetap di Atas */
            position: sticky;
            top: 0;
            z-index: 1000;

            /* Gaya Glassmorphism */
            background: rgba(255, 255, 255, 0.8); 
            backdrop-filter: blur(12px); /* Efek blur di belakang header */
            -webkit-backdrop-filter: blur(12px);
            
            /* Layout */
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 5%;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .header-left {
            font-size: 20px;
            font-weight: 800;
            background: linear-gradient(135deg, #2879ff, #00d2ff); /* Warna Gradasi */
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        .header-right {
            display: flex;
            gap: 8px;
        }

        /* Tombol Navigasi Modern */
        .header-right a.nav-btn {
            color: #475569;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .header-right a.nav-btn:hover {
            background: #f1f5f9;
            color: #2879ff;
        }

        /* Tombol Logout Spesial */
        .header-right a.nav-btn-logout {
            background: #fff1f2;
            color: #e11d48;
        }

        .header-right a.nav-btn-logout:hover {
            background: #e11d48;
            color: white;
        }

        /* Tambahkan jarak pada container agar tidak tertutup header */
        .container {
            margin-top: 20px;
        }
    .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
    .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; padding: 20px 0; }
    .item-card { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #e2e8f0; display: flex; flex-direction: column; }
    .img-wrap { width: 100%; padding-top: 100%; position: relative; background: #f8fafc; border-bottom: 1px solid #eee; }
    .img-wrap img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; }
    .card-body { padding: 15px; text-align: center; }
    .btn-group { display: flex; gap: 10px; justify-content: center; }
    .btn-card { flex: 1; padding: 10px 0; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-decoration: none; color: white; font-size: 14px; text-transform: uppercase; }
    .btn-card-edit { background-color: #0ea5e9; } 
    .btn-card-edit:hover { background-color: #0284c7; }
    .btn-card-delete { background-color: #ef4444; } 
    .btn-card-delete:hover { background-color: #dc2626; }
    .download-all-btn { background: #22c55e; color: white; padding: 15px 40px; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; display: block; margin: 40px auto; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4); }
    </style>
</head>

<body>
<header>
    <div class="header-left">
        Mockup Generator
    </div>
    <nav class="header-right">
        <a href="user_index.php" class="nav-btn">Dashboard</a>
        <a href="preset_manager.php" class="nav-btn">Preset Manager</a>
        <a href="logo_manager.php" class="nav-btn">Logo Manager</a>
        <a href="logout.php" class="nav-btn nav-btn-logout">Logout</a>
    </nav>
</header>

<div class="container">
    <h3 style="text-align:center; color:#334155;">Berhasil Memproses <?= count($finalFiles) ?> Gambar</h3>
    
    <div class="gallery">
    <?php foreach ($finalFiles as $file) { 
        $baseName = basename($file);
    ?>
        <div class="item-card">
            <div class="img-wrap">
                <img src="<?= $file ?>?t=<?= time() ?>" alt="Hasil">
            </div>
            <div class="card-body">                
                <div class="btn-group">
                    <a href="edit_result.php?file=<?= $baseName ?>" class="btn-card btn-card-edit">EDIT</a>
                    <a href="delete_result.php?file=<?= $baseName ?>" class="btn-card btn-card-delete" onclick="return confirm('Yakin ingin menghapus gambar ini?');">HAPUS</a>
                </div>
            </div>
        </div>
    <?php } ?>
    </div>

    <?php if(count($finalFiles) > 0): ?>
    <form method="POST" action="download_zip.php" style="text-align:center;">
        <button class="download-all-btn">📦 Download Semua (.ZIP)</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>