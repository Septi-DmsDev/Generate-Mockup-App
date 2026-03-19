<?php
// 1. Start buffering untuk mencegah kebocoran karakter yang bisa merusak ZIP
ob_start();

session_start();

// 2. Setting Keamanan & Performa
error_reporting(0);
ini_set('display_errors', 0);
ini_set('max_execution_time', 600);
ini_set('memory_limit', '512M');

// Pastikan skrip tetap berjalan sampai selesai hapus file meskipun user menutup browser
ignore_user_abort(true);

$session = session_id();
$folder = "temp_results/" . $session . "/";

if (!is_dir($folder)) {
    ob_end_clean(); 
    die("Error: Folder hasil tidak ditemukan.");
}

// 4. Ambil semua file gambar untuk dibungkus
$files = array_merge(glob($folder . "*.jpg"), glob($folder . "*.png"));

if (empty($files)) {
    ob_end_clean();
    die("Error: Tidak ada file gambar untuk di-download.");
}

// 5. Setup ZIP
$zipName = "mockup_results_" . date("Ymd_His") . ".zip";
$zipPath = $folder . $zipName;

if (file_exists($zipPath)) {
    unlink($zipPath);
}

$zip = new ZipArchive;
if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
    foreach ($files as $file) {
        $zip->addFile($file, basename($file));
    }
    $zip->close();
} else {
    ob_end_clean();
    die("Error: Gagal membuat file ZIP.");
}

// 6. Pengiriman File ke Browser
if (file_exists($zipPath)) {
    
    // Bersihkan semua buffer sebelum kirim header
    if (ob_get_length()) ob_end_clean();
    
    header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
    header("Cache-Control: public"); 
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: Binary");
    header("Content-Length: " . filesize($zipPath));
    header("Content-Disposition: attachment; filename=\"" . $zipName . "\"");
    
    // Kirim file ke browser
    readfile($zipPath);
    
    // ============================================================
    // 7. LOGIKA PEMBERSIHAN (SETELAH DOWNLOAD SELESAI)
    // ============================================================
    
    // Hapus semua file di dalam folder session ini
    // Termasuk JPG hasil edit, JSON metadata, dan file ZIP barusan
    $cleanupFiles = glob($folder . "*");
    foreach ($cleanupFiles as $f) {
        if (is_file($f)) {
            @unlink($f); 
        }
    }

    // Kami tidak menghapus folder-nya agar tidak error saat pengecekan session selanjutnya
    // Kami juga tidak melakukan session_destroy() agar login user tetap aktif

    exit;
} else {
    die("Error: File ZIP tidak ditemukan.");
}
?>