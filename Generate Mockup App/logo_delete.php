<?php
require "db.php";
session_start();

// 1. Validasi Login
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// 2. Ambil ID Logo yang akan dihapus
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);

    // 3. Ambil path file sebelum data di database dihapus
    $query = mysqli_query($conn, "SELECT file_path FROM logos WHERE id = '$id'");
    
    if ($row = mysqli_fetch_assoc($query)) {
        $filePath = $row['file_path'];

        // 4. Hapus File Fisik di Server
        // Memastikan file benar-benar ada sebelum dihapus menggunakan unlink()
        if (file_exists($filePath)) {
            @unlink($filePath); 
        }

        // 5. Hapus Data dari Database
        $delete = mysqli_query($conn, "DELETE FROM logos WHERE id = '$id'");

        if ($delete) {
            // Berhasil dihapus, kembali ke Logo Manager
            header("Location: logo_manager.php?status=deleted");
            exit;
        } else {
            echo "Gagal menghapus data dari database.";
        }
    } else {
        echo "Data logo tidak ditemukan.";
    }
} else {
    header("Location: logo_manager.php");
    exit;
}