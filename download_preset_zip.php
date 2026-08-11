<?php
require "db.php";
session_start();

if (isset($_GET['id'])) {
    $presetId = $_GET['id'];
    
    // Ambil info preset
    $qPreset = mysqli_query($conn, "SELECT name FROM presets WHERE id='$presetId'");
    $preset = mysqli_fetch_assoc($qPreset);
    $presetName = str_replace(' ', '_', $preset['name']);

    // Ambil semua gambar background dalam preset ini
    $qFiles = mysqli_query($conn, "SELECT file_path FROM backgrounds WHERE preset_id='$presetId'");
    
    if (mysqli_num_rows($qFiles) > 0) {
        $zip = new ZipArchive();
        $zipName = "Produk_" . $presetName . ".zip";

        if ($zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            while ($file = mysqli_fetch_assoc($qFiles)) {
                $filePath = "backgrounds/" . $file['file_path'];
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $file['file_path']);
                }
            }
            $zip->close();

            // Kirim ke browser untuk download
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename=' . $zipName);
            header('Content-Length: ' . filesize($zipName));
            readfile($zipName);
            
            // Hapus file zip sementara setelah download selesai
            unlink($zipName);
            exit;
        }
    } else {
        echo "<script>alert('Tidak ada gambar di preset ini.'); window.location='preset_manager.php';</script>";
    }
}