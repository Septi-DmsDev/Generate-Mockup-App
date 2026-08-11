<?php
require "db.php";
session_start();

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$logos = mysqli_query($conn, "SELECT * FROM logos ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo Manager - Dashboard</title>
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

        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        
        .card { 
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
            margin-bottom: 25px; 
            border: 1px solid #e2e8f0;
        }

        h3 { margin-top: 0; font-size: 18px; color: #1e293b; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }

        /* REVISI TOTAL FORM MENGGUNAKAN GRID (Solusi Cacat Layout) */
        .grid-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto; 
            gap: 20px;
            align-items: end; /* Tetap di bawah, tapi kita angkat tombolnya nanti */
        }

        .input-box { display: flex; flex-direction: column; gap: 8px; }

        .input-box label { font-size: 13px; font-weight: 600; color: #64748b; }

        .input-control {
            height: 42px; /* Tinggi seragam untuk semua */
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            transition: all 0.2s;
        }

        /* Khusus input file agar teks di dalamnya tidak berantakan */
        input[type="file"].input-control {
            padding: 6px 10px;
            line-height: 28px;
        }

        .input-control:focus { border-color: #26a742; outline: none; box-shadow: 0 0 0 3px rgba(38, 167, 66, 0.1); }

        .btn-submit {
            height: 42px;
            padding: 0 24px;
            background: #26a742;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            font-size: 14px;
            
            /* TAMBAHKAN DUA BARIS INI */
            margin-bottom: 2px; /* Menaikkan tombol sedikit agar sejajar secara optik */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #1e8535;
            transform: translateY(-1px); /* Efek sedikit naik saat hover */
        }

        /* STYLING TABEL */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th { background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; padding: 15px; text-align: left; }
        table td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

        .logo-preview {
            width: 120px;
            height: 55px;
            background: #f1f5f9;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border: 1px solid #e2e8f0;
        }

        .logo-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }

        .badge-size {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            font-family: 'Courier New', Courier, monospace;
        }

        .btn-hapus {
            color: #ef4444;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            background: #fee2e2;
            padding: 6px 14px;
            border-radius: 6px;
            transition: 0.2s;
        }

        .btn-hapus:hover { background: #ef4444; color: white; }

        /* RESPONSIVE */
        @media (max-width: 800px) {
            .grid-form { grid-template-columns: 1fr; }
            .btn-submit { width: 100%; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-left">
        Logo Manager 
    </div>
    <nav class="header-right">
        <a href="user_index.php" class="nav-btn">Dashboard</a>
        <a href="preset_manager.php" class="nav-btn">Preset Manager</a>
        <a href="logo_manager.php" class="nav-btn">Logo Manager</a>
        <a href="logout.php" class="nav-btn nav-btn-logout">Logout</a>
    </nav>
</header>

<div class="container">

    <div class="card">
        <h3>Upload Logo Baru</h3>
        <form action="logo_upload.php" method="POST" enctype="multipart/form-data">
            <div class="grid-form">
                
                <div class="input-box">
                    <label>Nama Toko / Logo</label>
                    <input type="text" name="name" class="input-control" placeholder="Contoh: Classy Souvenir" required>
                </div>

                <div class="input-box">
                    <label>File Logo (PNG Transparan)</label>
                    <input type="file" name="logo" class="input-control" accept="image/png" required>
                </div>
            </div>
            <div>
                <button type="submit" class="btn-submit">
                    🚀 Upload Logo
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Daftar Logo Terdaftar</h3>
        <table>
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th width="150">Preview</th>
                    <th>Nama Toko / Logo</th>
                    <th width="150">Ukuran (px)</th>
                    <th width="100">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while ($l = mysqli_fetch_assoc($logos)): 
                    $px = "Unknown";
                    if (file_exists($l['file_path'])) {
                        $s = getimagesize($l['file_path']);
                        if ($s) $px = $s[0] . " x " . $s[1] . " px";
                    }
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <div class="logo-preview">
                            <img src="<?= $l['file_path'] ?>?t=<?= time() ?>" alt="Logo">
                        </div>
                    </td>
                    <td>
                        <strong style="color: #1e293b;"><?= htmlspecialchars($l['name']) ?></strong>
                    </td>
                    <td><span class="badge-size"><?= $px ?></span></td>
                    <td>
                        <a href="logo_delete.php?id=<?= $l['id'] ?>" 
                           class="btn-hapus" 
                           onclick="return confirm('Hapus logo ini?')">Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>