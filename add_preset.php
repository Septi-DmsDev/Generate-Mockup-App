<?php
require "db.php";
session_start();

// Cek validasi login jika diperlukan
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// Logika Simpan Preset
if (isset($_POST['save_preset'])) {
    $name = mysqli_real_escape_string($conn, $_POST['preset_name']);
    
    if (!empty($name)) {
        $query = "INSERT INTO presets (name) VALUES ('$name')";
        if (mysqli_query($conn, $query)) {
            header("Location: preset_manager.php?status=success");
            exit;
        } else {
            $error = "Gagal menyimpan preset ke database.";
        }
    } else {
        $error = "Nama preset tidak boleh kosong.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Preset Baru</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        header { background: #ff2828; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .brand { font-size: 20px; font-weight: bold; }
        
        .container-form { max-width: 500px; margin: 50px auto; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #64748b; font-weight: 600; font-size: 14px; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; font-size: 15px; transition: border 0.2s; }
        input[type="text"]:focus { border-color: #ff2828; outline: none; }
        
        .btn-group { display: flex; gap: 10px; margin-top: 30px; }
        .btn { flex: 1; padding: 12px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; font-size: 14px; transition: opacity 0.2s; }
        .btn-save { background: #16a34a; color: white; }
        .btn-cancel { background: #94a3b8; color: white; }
        .btn:hover { opacity: 0.9; }
        
        .alert { background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
    </style>
</head>
<body>

<header>
    <div class="brand">Preset Manager</div>
</header>

<div class="container-form">
    <div class="card">
        <h2>+ Tambah Preset Baru</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="preset_name">Nama Preset (Contoh: Green Seri 01)</label>
                <input type="text" name="preset_name" id="preset_name" placeholder="Ketik nama preset di sini..." required autofocus>
            </div>

            <div class="btn-group">
                <a href="preset_manager.php" class="btn btn-cancel">BATAL</a>
                <button type="submit" name="save_preset" class="btn btn-save">SIMPAN PRESET</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>