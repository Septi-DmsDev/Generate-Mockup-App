<?php
require "db.php";
session_start();

// Validasi login
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// Cek apakah ada ID preset yang dikirim
if (isset($_GET['id'])) {
    $presetId = mysqli_real_escape_string($conn, $_GET['id']);

    // 1. Ambil semua daftar file gambar yang terkait dengan preset ini
    $queryFiles = mysqli_query($conn, "SELECT file_path FROM backgrounds WHERE preset_id = '$presetId'");

    // 2. Hapus file fisik dari folder backgrounds/
    while ($row = mysqli_fetch_assoc($queryFiles)) {
        $filePath = "backgrounds/" . $row['file_path'];
        
        // Cek apakah file benar-benar ada sebelum dihapus
        if (file_exists($filePath)) {
            unlink($filePath); // Menghapus file permanen dari server
        }
    }

    // 3. Hapus data dari tabel backgrounds (child) terlebih dahulu
    mysqli_query($conn, "DELETE FROM backgrounds WHERE preset_id = '$presetId'");

    // 4. Baru kemudian hapus data dari tabel presets (parent)
    mysqli_query($conn, "DELETE FROM presets WHERE id = '$presetId'");

    // Redirect kembali ke preset manager dengan status sukses
    header("Location: preset_manager.php?status=deleted");
    exit;
} else {
    // Jika tidak ada ID, kembalikan ke halaman utama
    header("Location: preset_manager.php");
    exit;
}
?>