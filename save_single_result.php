<?php
session_start();
require "db.php";

// 1. KONFIGURASI MEMORI & SERVER
// Mengatur agar PHP kuat memproses gambar resolusi tinggi (1800px)
ini_set('memory_limit', '-1'); 
set_time_limit(0); 
header('Content-Type: application/json');

$sessionId = session_id();
$folder = "temp_results/" . $sessionId . "/";
$canvasSize = 1800; // Sesuai dengan spesifikasi Editor Anda

// 2. HELPER: LOAD GAMBAR DARI PATH LOCALHOST
function loadLayerImage($pathOrBase64) {
    if (empty($pathOrBase64)) return null;

    // A. Jika data berupa Base64 (Upload baru dari editor)
    if (strpos($pathOrBase64, 'data:image') === 0) {
        $parts = explode(',', $pathOrBase64);
        if (count($parts) >= 2) {
            $data = base64_decode($parts[1]);
            return ($data) ? @imagecreatefromstring($data) : null;
        }
        return null;
    }

    // B. Pembersihan Path untuk Localhost (htdocs/overlay-app/)
    $cleanPath = parse_url($pathOrBase64, PHP_URL_PATH);
    // Menghapus nama folder project agar merujuk langsung ke folder relatif
    $cleanPath = str_replace('/overlay-app/', '', $cleanPath); 
    $cleanPath = ltrim($cleanPath, '/'); 
    $cleanPath = urldecode($cleanPath);

    if (!file_exists($cleanPath)) {
        return null;
    }

    // Mengambil konten file dan mendeteksi tipe gambar secara otomatis
    $fileData = file_get_contents($cleanPath);
    return ($fileData) ? @imagecreatefromstring($fileData) : null;
}

// 3. HELPER: WARNA HEX KE RGB
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

// 4. LOGIKA UTAMA RENDER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['layout_data'])) {
    
    $layoutData = json_decode($_POST['layout_data'], true);
    $targetFile = $_POST['target_file'] ?? 'result.jpg';

    if (!$layoutData) {
        echo json_encode(["status" => "error", "message" => "JSON Layout tidak valid"]);
        exit;
    }

    // Inisialisasi Canvas Kosong 1800x1800
    $canvas = imagecreatetruecolor($canvasSize, $canvasSize);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);

    foreach ($layoutData as $layer) {
        // --- PROSES LAYER TEKS ---
        if ($layer['type'] == 'text') {
            if (!empty($layer['text'])) {
                list($r, $g, $b) = hex2rgb($layer['color'] ?? "#000000");
                $color = imagecolorallocate($canvas, $r, $g, $b);
                $fontPath = 'fonts/' . ($layer['font'] ?? 'Arial') . '.ttf';
                if (!file_exists($fontPath)) $fontPath = 'fonts/Arial.ttf';
                
                // Gunakan koordinat asli dari editor (Tanpa Multiplier)
                imagettftext($canvas, (int)$layer['fontSize'], -$layer['rotation'], (int)$layer['x'], (int)$layer['y'], $color, $fontPath, $layer['text']);
            }
            continue;
        }

        // --- PROSES LAYER GAMBAR (BG, Logo, Preset, Stiker) ---
        $imgRes = loadLayerImage($layer['src'] ?? null);
        if ($imgRes) {
            $origW = imagesx($imgRes); $origH = imagesy($imgRes);
            $newW = (int)($origW * $layer['scale']); $newH = (int)($origH * $layer['scale']);
            
            // Pencegahan ukuran nol agar tidak error
            if ($newW < 1) $newW = 1; if ($newH < 1) $newH = 1;

            $tempImg = imagecreatetruecolor($newW, $newH);
            imagealphablending($tempImg, false); imagesavealpha($tempImg, true);
            $trans = imagecolorallocatealpha($tempImg, 0, 0, 0, 127);
            imagefill($tempImg, 0, 0, $trans);
            
            // Scaling dengan kualitas tinggi (imagecopyresampled)
            imagecopyresampled($tempImg, $imgRes, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            
            // Rotasi jika ada
            if (isset($layer['rotation']) && $layer['rotation'] != 0) {
                $tempImg = imagerotate($tempImg, -$layer['rotation'], $trans);
                imagesavealpha($tempImg, true);
            }
            
            // Penempelan ke Canvas Utama berdasarkan Titik Tengah
            $drawX = (int)($layer['x'] - (imagesx($tempImg) / 2));
            $drawY = (int)($layer['y'] - (imagesy($tempImg) / 2));
            
            imagecopy($canvas, $tempImg, $drawX, $drawY, 0, 0, imagesx($tempImg), imagesy($tempImg));
            
            imagedestroy($tempImg); imagedestroy($imgRes);
        }
    }

    // 5. SIMPAN HASIL DAN UPDATE METADATA
    // Simpan gambar JPEG dengan kualitas maksimal (100)
    if (imagejpeg($canvas, $folder . $targetFile, 100)) {
        
        // Simpan ulang Metadata JSON agar jika diedit lagi, posisinya tetap benar
        $jsonPath = $folder . pathinfo($targetFile, PATHINFO_FILENAME) . ".json";
        $meta = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];
        $meta['layout_used'] = $layoutData; 
        
        file_put_contents($jsonPath, json_encode($meta, JSON_PRETTY_PRINT));
        
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menulis file ke disk"]);
    }
    imagedestroy($canvas);
} else {
    echo json_encode(["status" => "error", "message" => "Permintaan tidak valid"]);
}