<?php
require "db.php";
require "image.php";
session_start();

// 1. Validasi Login & ID
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: preset_manager.php");
    exit;
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// 2. Ambil Data Nama Preset
$qPreset = mysqli_query($conn, "SELECT name FROM presets WHERE id = '$id'");
$preset = mysqli_fetch_assoc($qPreset);

if (!$preset) {
    die("Preset tidak ditemukan.");
}

// 3. Logika Upload Multiple Produk
if (isset($_POST['upload_produk'])) {
    if (isset($_FILES['bg_file']['tmp_name']) && is_array($_FILES['bg_file']['tmp_name'])) {
        foreach ($_FILES['bg_file']['tmp_name'] as $key => $tmpName) {
            if ($tmpName == "" || !file_exists($tmpName)) continue;

            // Generate nama file unik (.png untuk transparansi)
            $newFileName = time() . "_" . $key . "_" . rand(100, 999) . ".png";
            $savePath = "backgrounds/" . $newFileName;

            // Buat folder jika belum ada
            if (!file_exists("backgrounds")) {
                mkdir("backgrounds", 0777, true);
            }

            if (move_uploaded_file($tmpName, $savePath)) {
                // Proses Resize & Optimasi (via image.php)
                $img = prepareBackground($savePath); 
                imagepng($img, $savePath, 9); // Simpan ulang sebagai PNG teroptimasi
                imagedestroy($img);

                // Simpan ke database
                mysqli_query($conn, "INSERT INTO backgrounds (preset_id, file_path) VALUES ('$id', '$newFileName')");
            }
        }
        header("Location: edit_preset.php?id=$id&status=uploaded");
        exit;
    }
}

// 4. Ambil Daftar Gambar dalam Preset ini
$bgList = mysqli_query($conn, "SELECT * FROM backgrounds WHERE preset_id = '$id' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Preset - <?= htmlspecialchars($preset['name']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f1f5f9; font-family: sans-serif; margin: 0; }
        header { background: #ff2828; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .nav-btn { background: rgba(255, 255, 255, 0.25); color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; }
        
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 25px; }
        
        .top-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        h2 { margin: 0; color: #1e293b; }
        
        .upload-area { background: #f8fafc; border: 2px dashed #e2e8f0; padding: 20px; border-radius: 8px; text-align: center; }
        input[type="file"] { margin: 15px 0; }
        
        .btn { padding: 10px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .btn-upload { background: #2563eb; color: white; }
        .btn-upload:hover { background: #1d4ed8; }
        
        /* Gallery System */
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; margin-top: 10px; }
        .item-card { background: white; border: 1px solid #e2e8f0; padding: 10px; border-radius: 8px; text-align: center; transition: transform 0.2s; }
        .item-card:hover { transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .img-box { width: 100%; height: 140px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 10px; }
        .img-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
        
        .btn-del { background: #ef4444; color: white; text-decoration: none; font-size: 11px; padding: 5px 10px; border-radius: 4px; display: block; }
        .btn-del:hover { background: #dc2626; }

        .status-msg { background: #dcfce7; color: #166534; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border-left: 4px solid #22c55e; }
    </style>
</head>
<body>

<header>
    <div class="header-left" style="font-weight: bold; font-size: 18px;">✏️ Edit Produk Preset</div>
    <div class="header-right">
        <a href="preset_manager.php" class="nav-btn">🔙 Kembali ke List</a>
    </div>
</header>

<div class="container">
    
    <?php if (isset($_GET['status']) && $_GET['status'] == 'uploaded'): ?>
        <div class="status-msg">✅ Produk berhasil ditambahkan ke dalam preset.</div>
    <?php endif; ?>

    <div class="card">
        <div class="top-section">
            <h2>Preset: <?= htmlspecialchars($preset['name']) ?></h2>
            <span style="color: #64748b; font-size: 14px;">Tambah gambar produk (Overlay)</span>
        </div>

        <div class="upload-area">
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="bg_file[]" multiple required accept="image/*">
                <div style="margin-top: 5px; color: #94a3b8; font-size: 12px;">Pilih banyak file sekaligus (JPG/PNG) | Size Recommended 1800×1800px</div>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">
                <button type="submit" name="upload_produk" class="btn btn-upload">MULAI UPLOAD</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="top-section">
            <h2>Daftar Gambar Produk</h2>
            <span style="color: #64748b; font-size: 14px;"><?= mysqli_num_rows($bgList) ?> Item</span>
        </div>

        <div class="gallery-grid">
            <?php if (mysqli_num_rows($bgList) > 0): ?>
                <?php while ($bg = mysqli_fetch_assoc($bgList)): ?>
                    <div class="item-card">
                        <div class="img-box">
                            <img src="backgrounds/<?= $bg['file_path'] ?>?t=<?= time() ?>" alt="Produk">
                        </div>
                        <a href="delete_bg.php?id=<?= $bg['id'] ?>&file=<?= $bg['file_path'] ?>&ref=<?= $id ?>" 
                           class="btn-del" 
                           onclick="return confirm('Hapus gambar ini dari preset?')">HAPUS</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; color: #94a3b8; padding: 40px;">
                    Belum ada gambar produk di preset ini.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>