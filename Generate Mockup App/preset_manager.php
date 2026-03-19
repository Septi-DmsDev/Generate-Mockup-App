<?php
require "db.php";
session_start();

// Query mengambil data preset dan menghitung jumlah produk di dalamnya
$queryPresets = "SELECT p.*, COUNT(b.id) as jml_produk 
                 FROM presets p 
                 LEFT JOIN backgrounds b ON p.id = b.preset_id 
                 GROUP BY p.id 
                 ORDER BY p.id DESC";
$presets = mysqli_query($conn, $queryPresets);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Preset Manager</title>
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
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-add { background: #22c55e; color: white; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 15px; box-shadow: 0 4px 10px rgba(34, 197, 94, 0.3); }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        table th, table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        table th { background: #f8fafc; color: #64748b; font-size: 13px; text-transform: uppercase; }
        
        .btn-action { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 600; color: white; margin-right: 5px; }
        .bg-blue { background: #0ea5e9; }
        .bg-green { background: #16a34a; }
        .bg-red { background: #ef4444; }
    </style>
</head>
<body style="background: #f1f5f9; font-family: 'inter', sans-serif;">

<header>
    <div class="header-left">
        Dashboard Preset Produk
    </div>
    <nav class="header-right">
        <a href="user_index.php" class="nav-btn">Dashboard</a>
        <a href="preset_manager.php" class="nav-btn">Preset Manager</a>
        <a href="logo_manager.php" class="nav-btn">Logo Manager</a>
        <a href="logout.php" class="nav-btn nav-btn-logout">Logout</a>
    </nav>
</header>

<div class="container">
    <div class="top-bar">
        <h2 style="color: #1e293b;">Daftar Preset Mockup</h2>
        <a href="add_preset.php" class="btn-add">+ TAMBAH PRESET</a>
    </div>

    <table>
        <thead>
            <tr>
                <th width="50">No</th>
                <th>Nama Preset</th>
                <th>Jumlah Produk</th>
                <th width="120">Download</th>
                <th width="180">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while ($p = mysqli_fetch_assoc($presets)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                <td><span style="color: #64748b;"><?= $p['jml_produk'] ?> Gambar</span></td>
                <td>
                    <a href="download_preset_zip.php?id=<?= $p['id'] ?>" class="btn-action bg-green" title="Download ZIP">
                        📦 ZIP
                    </a>
                </td>
                <td>
                    <a href="edit_preset.php?id=<?= $p['id'] ?>" class="btn-action bg-blue">EDIT</a>
                    <a href="delete_preset_all.php?id=<?= $p['id'] ?>" 
                    class="btn-action bg-red" 
                    onclick="return confirm('PERHATIAN! Menghapus preset ini akan menghapus semua gambar produk di dalamnya secara permanen. Lanjutkan?')">
                    HAPUS
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>