<?php
session_start();
$sessionId = session_id();
$folder = "temp_results/" . $sessionId . "/";

if(isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filePath = $folder . $file;
    $jsonPath = $folder . pathinfo($file, PATHINFO_FILENAME) . ".json";
    
    // Validasi agar cuma bisa hapus file di folder session sendiri
    if(file_exists($filePath) && strpos($file, 'final_result_') === 0) {
        unlink($filePath);
        if(file_exists($jsonPath)) unlink($jsonPath);
    }
}

// Redirect kembali
header("Location: process_generate.php");
exit;
?>