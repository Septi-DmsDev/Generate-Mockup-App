<?php

// Ukuran standar baru untuk sistem High-Res
$TARGET_SIZE = 1800; 

function prepareUserImage($srcPath)
{
    global $TARGET_SIZE;
    
    // Coba baca file
    $fileData = @file_get_contents($srcPath);
    if (!$fileData) return false;
    
    $src = @imagecreatefromstring($fileData);
    if (!$src) return false;

    $sw = imagesx($src);
    $sh = imagesy($src);

    // Buat kanvas dasar 2160x2160 (Upgrade dari 1080)
    $final = imagecreatetruecolor($TARGET_SIZE, $TARGET_SIZE);
    
    imagealphablending($final, false);
    imagesavealpha($final, true);
    
    // Isi dengan Transparan
    $transparent = imagecolorallocatealpha($final, 255, 255, 255, 127);
    imagefill($final, 0, 0, $transparent);
    
    // Logic Resize (Maintain Aspect Ratio)
    $ratio_src = $sw / $sh;
    $ratio_dest = 1; 

    if ($ratio_src > $ratio_dest) {
        $new_h = $TARGET_SIZE;
        $new_w = $TARGET_SIZE * $ratio_src;
        $x = ($TARGET_SIZE - $new_w) / 2;
        $y = 0;
    } else {
        $new_w = $TARGET_SIZE;
        $new_h = $TARGET_SIZE / $ratio_src;
        $x = 0;
        $y = ($TARGET_SIZE - $new_h) / 2;
    }

    // Gunakan imagecopyresampled untuk kualitas interpolasi terbaik (tidak blur)
    imagecopyresampled($final, $src, $x, $y, 0, 0, $new_w, $new_h, $sw, $sh);

    imagedestroy($src);
    return $final;
}

function prepareBackground($srcPath)
{
    global $TARGET_SIZE;
    if (!file_exists($srcPath)) return false;

    $data = @file_get_contents($srcPath);
    if (!$data) return false;

    $src = @imagecreatefromstring($data);
    if (!$src) return false; 

    // Upgrade dari imagescale biasa ke imagecopyresampled manual untuk ketajaman ekstra
    $final = imagecreatetruecolor($TARGET_SIZE, $TARGET_SIZE);
    imagealphablending($final, false);
    imagesavealpha($final, true);
    
    $transparent = imagecolorallocatealpha($final, 255, 255, 255, 127);
    imagefill($final, 0, 0, $transparent);

    // Scaling ke 2160x2160
    imagecopyresampled($final, $src, 0, 0, 0, 0, $TARGET_SIZE, $TARGET_SIZE, imagesx($src), imagesy($src));

    imagedestroy($src);
    return $final;
}

function overlayFull($bg, $user, $outputPath)
{
    global $TARGET_SIZE;
    // 1. Buat Kanvas Kosong 2160px
    $canvas = imagecreatetruecolor($TARGET_SIZE, $TARGET_SIZE);

    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
    imagefill($canvas, 0, 0, $transparent);
    
    imagealphablending($canvas, true);

    // 2. Tempel GAMBAR USER (Layer Bawah)
    imagecopy($canvas, $user, 0, 0, 0, 0, $TARGET_SIZE, $TARGET_SIZE);

    // 3. Tempel GAMBAR ADMIN (Layer Atas)
    imagecopy($canvas, $bg, 0, 0, 0, 0, $TARGET_SIZE, $TARGET_SIZE);

    // 4. Simpan hasilnya sebagai PNG dengan kompresi sedang agar tetap tajam
    // PNG level 6 adalah titik seimbang antara kualitas dan kecepatan
    imagepng($canvas, $outputPath, 6); 

    imagedestroy($canvas);
}
?>