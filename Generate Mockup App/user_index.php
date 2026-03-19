<?php
require "db.php";
session_start();

// 1. Cek Login
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// 2. LOGIKA PENANGANAN FORM (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. Siapkan Folder
    $session = session_id();
    $targetDir = "temp_results/" . $session . "/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    // B. Simpan Pilihan User ke Session 
    // (Agar saat halaman reload, pilihan tidak hilang)
    $_SESSION['selected_preset'] = $_POST['preset_id'];
    $_SESSION['selected_logo'] = $_POST['logo_id'];
    $_SESSION['logo_pos'] = $_POST['logo_pos'];

    // C. Upload File Background
    $uploadedFiles = [];
    // Cek apakah ada file yang diupload
    if(isset($_FILES['user_bg']['name']) && is_array($_FILES['user_bg']['name'])) {
        $total = count($_FILES['user_bg']['name']); 
        for( $i=0 ; $i < $total ; $i++ ) {
            $rawName = basename($_FILES['user_bg']['name'][$i]);
            // Skip jika nama file kosong (tidak ada file dipilih)
            if(empty($rawName)) continue;

            $fileName = preg_replace("/[^a-zA-Z0-9.]/", "_", $rawName);
            $targetFile = $targetDir . $fileName;
            
            if(move_uploaded_file($_FILES['user_bg']['tmp_name'][$i], $targetFile)) {
                $uploadedFiles[] = $fileName;
            }
        }
    }

    // D. Cek Aksi Tombol
    $action = isset($_POST['action']) ? $_POST['action'] : 'process';

    if ($action === 'edit') {
        // --- TOMBOL: ATUR LAYOUT ---
        if (count($uploadedFiles) > 0) {
            $firstFile = $uploadedFiles[0];
            header("Location: editor.php?file=" . urlencode($firstFile));
            exit();
        } else {
            echo "<script>alert('Harap pilih minimal satu gambar untuk diedit!');</script>";
        }

    } else {
        // --- TOMBOL: PROSES GAMBAR ---
        
        // [PERBAIKAN PENTING] 
        // Saya MENGHAPUS kode 'unlink' di sini.
        // Agar layout.json dari editor TIDAK TERHAPUS saat user klik proses.
        
        if (count($uploadedFiles) > 0) {
            header("Location: process_generate.php"); 
            exit();
        } else {
            echo "<script>alert('Tidak ada gambar yang diproses. Silakan upload gambar dulu.');</script>";
        }
    }
}

// Ambil data DB
$presets = mysqli_query($conn, "SELECT * FROM presets ORDER BY id DESC");
$logos = mysqli_query($conn, "SELECT * FROM logos ORDER BY name ASC");

// --- HELPER: AMBIL PILIHAN TERAKHIR DARI SESSION ---
// Ini yang membuat inputan tidak hilang setelah kembali dari editor
$lastPreset = isset($_SESSION['selected_preset']) ? $_SESSION['selected_preset'] : '';
$lastLogo   = isset($_SESSION['selected_logo']) ? $_SESSION['selected_logo'] : '';
$lastPos    = isset($_SESSION['logo_pos']) ? $_SESSION['logo_pos'] : 'top-right';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Generate Mockup</title>
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

        /* Styling Tombol Aksi */
        .form-actions { display: flex; gap: 15px; margin-top: 20px; }
        .btn-action {
            flex: 1; padding: 12px; border: none; border-radius: 6px;
            font-size: 16px; font-weight: bold; cursor: pointer; color: white;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-edit { background-color: #f59e0b; }
        .btn-edit:hover { background-color: #d97706; }
        .btn-process { background-color: #2563eb; }
        .btn-process:hover { background-color: #1d4ed8; }
        
        /* Alert Box */
        .alert-success {
            background: #dcfce7; color: #166534; 
            padding: 15px; border-radius: 6px; margin-bottom: 20px; 
            border: 1px solid #bbf7d0; display: flex; align-items: center; gap: 10px;
        }
    </style>
</head>

<body>

<header>
    <div class="header-left">
        Mockup Generator
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

    <?php 
    $sessId = session_id();
    if(file_exists("temp_results/" . $sessId . "/layout.json")) { 
    ?>
        <div class="alert-success">
            <span style="font-size: 24px;">✅</span>
            <div>
                <strong>Master Layout Tersimpan!</strong><br>
                <small>Layout yang Anda edit sudah siap. Silakan upload gambar baru (banyak sekaligus) lalu klik tombol <b>"Proses Gambar"</b>.</small>
            </div>
        </div>
    <?php } ?>

    <form action="" method="POST" enctype="multipart/form-data">

      <h3>Pilih Preset</h3>
      <select name="preset_id" required style="width: 100%; padding: 10px; margin-bottom: 20px;">
        <option value="">-- pilih preset --</option>
        <?php while ($p = mysqli_fetch_assoc($presets)) { 
            // Logic agar pilihan tidak hilang
            $selected = ($p['id'] == $lastPreset) ? 'selected' : ''; 
        ?>
          <option value="<?= $p['id'] ?>" <?= $selected ?>>
            <?= $p['name'] ?>
          </option>
        <?php } ?>
      </select>

      <div style="display: flex; gap: 20px; margin-bottom: 20px;">
        
        <div style="flex: 1;">
            <h3 style="margin-top: 0;">Pilih Logo</h3>
            <select name="logo_id" style="width: 100%; padding: 10px;">
                <option value="0">-- Tidak Pakai Logo --</option>
                <?php while ($l = mysqli_fetch_assoc($logos)) { 
                    $selected = ($l['id'] == $lastLogo) ? 'selected' : '';
                ?>
                    <option value="<?= $l['id'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($l['name']) ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div style="flex: 1;">
            <h3 style="margin-top: 0;">Posisi Logo</h3>
            <select name="logo_pos" style="width: 100%; padding: 10px;">
                <?php 
                $positions = ['top-right'=>'Top-Right', 'top-left'=>'Top-Left', 'bottom-right'=>'Bottom-Right', 'bottom-left'=>'Bottom-Left', 'center'=>'Center'];
                foreach($positions as $val => $label) {
                    $selected = ($val == $lastPos) ? 'selected' : '';
                    echo "<option value='$val' $selected>$label</option>";
                }
                ?>
            </select>
        </div>

      </div>

      <h3>Upload Background</h3>
      <input type="file" name="user_bg[]" id="bgInput" accept="image/*" multiple required style="width: 100%; padding: 10px; margin-bottom: 20px;">
      
      <div class="form-actions">
        <button type="submit" name="action" value="edit" class="btn-action btn-edit">
           ⚙️ Atur Layout
        </button>

        <button type="submit" name="action" value="process" class="btn-action btn-process">
           🚀 Proses Gambar
        </button>
      </div>

    </form>
  </div>
</div>

</body>
</html>