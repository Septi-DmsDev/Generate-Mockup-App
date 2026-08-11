<?php
require "db.php"; // Pastikan file koneksi database di-include
session_start();

// 1. Validasi Login & Parameter
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

// Ambil Session ID untuk folder
$sessionId = session_id();
$folder = "temp_results/" . $sessionId . "/";
$targetFile = isset($_GET["file"]) ? $_GET["file"] : "";

// Validasi file user...
if (empty($targetFile) || !file_exists($folder . $targetFile)) {
    echo "<script>alert('File user tidak ditemukan!'); window.location='user_index.php';</script>";
    exit;
}
$backgroundPath = $folder . $targetFile;


// ==========================================
// 2. Ambil Data BACKGROUNDS (Bukan Presets)
// ==========================================
$productPath = ""; // Default kosong

if (isset($_SESSION['selected_preset']) && $_SESSION['selected_preset'] != "") {
    $presetId = $_SESSION['selected_preset'];
    
    // Kita ambil dari tabel 'backgrounds' dimana 'preset_id' sesuai pilihan user.
    // Kita pakai LIMIT 1 untuk mengambil gambar pertama saja sebagai sampel layout.
    $qPreset = mysqli_query($conn, "SELECT file_path FROM backgrounds WHERE preset_id = '$presetId' LIMIT 1");
    
    if ($row = mysqli_fetch_assoc($qPreset)) {
        $productPath = "backgrounds/" . $row['file_path']; 
    }
}

// ==========================================
// 3. Ambil Data LOGO
// ==========================================
$logoPath = ""; 
if (isset($_SESSION['selected_logo']) && $_SESSION['selected_logo'] != "0") {
    $logoId = $_SESSION['selected_logo'];
    
    $qLogo = mysqli_query($conn, "SELECT file_path FROM logos WHERE id = '$logoId'");
    
    if ($row = mysqli_fetch_assoc($qLogo)) {
        $logoPath = $row['file_path']; 
    }
}

$logoPos = isset($_SESSION['logo_pos']) ? $_SESSION['logo_pos'] : 'top-right';

// 4. Load Font
$fontFiles = glob("fonts/*.ttf");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Layout Editor</title>
    <link rel="stylesheet" href="editor.css">
    
    <style>
        <?php foreach ($fontFiles as $f): 
            $name = pathinfo($f, PATHINFO_FILENAME); ?>
            @font-face { font-family: '<?= $name ?>'; src: url('<?= $f ?>'); }
        <?php endforeach; ?>
    </style>
</head>
<body>

<header>
    <div class="brand">✨ Editor Layout Master</div>
    <div class="header-actions">
        <button onclick="saveLayoutJSON()" class="btn-save">
            💾 Simpan Master Layout
        </button>
        
        <a href="user_index.php" class="btn-close" style="
            background: #cbd5e1; 
            color: #334155; 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600;
            display: inline-block;
        ">Tutup</a>
    </div>
</header>

<div class="main-wrapper">
    
    <aside class="left-sidebar">
        <div id="propertiesPanel" style="display:none;">
            <hr>
            <h4>Properti Layer</h4>
            
            <div id="textTools" style="display:none;">
                <label>Isi Teks</label>
                <input type="text" id="inpText" class="inp-full">
                
                <label>Font</label>
                <select id="inpFont" class="inp-full">
                    <option value="Arial">Arial</option>
                    <?php foreach ($fontFiles as $f): 
                        $name = pathinfo($f, PATHINFO_FILENAME); ?>
                        <option value="<?= $name ?>"><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label>Warna</label>
                <input type="color" id="inpColor" value="#000000" style="width:100%; height:30px;">
            </div>

            <div class="control-group">
                <label>Scale (Zoom)</label>
                <div class="row">
                    <input type="range" id="rngScale" min="0.1" max="3" step="0.01">
                    <input type="number" id="valScale" step="0.01">
                </div>
            </div>

            <div class="control-group">
                <label>Rotate (°)</label>
                <div class="row">
                    <input type="range" id="rngRotate" min="-180" max="180" step="1">
                    <input type="number" id="valRotate">
                </div>
            </div>

            <div class="row" style="margin-top:10px;">
                <button id="btnMirrorX" class="btn-small">↔ Mirror X</button>
                <button id="btnMirrorY" class="btn-small">↕ Mirror Y</button>
            </div>

            <hr>
            <h4>Efek & Filter</h4>
            
            <div class="control-group">
                <label>Opacity</label>
                <input type="range" id="rngOpacity" min="0" max="1" step="0.01">
            </div>
            
            <div class="control-group">
                <label>Brightness</label>
                <input type="range" id="rngBrightness" min="0" max="200" value="100">
            </div>

            <div class="control-group">
                <label>Contrast</label>
                <input type="range" id="rngContrast" min="0" max="200" value="100">
            </div>
        </div>

        <div id="noSelectionMsg" style="text-align:center; color:#888; margin-top:20px;">
            Pilih objek di canvas atau layer untuk mengedit.
        </div>
    </aside>

    <main class="canvas-area">
        <canvas id="editorCanvas" width="1800" height="1800"></canvas>
    </main>

    <aside class="right-sidebar">
        <div class="panel-header">Layer</div>        
        <div class="layer-controls">
            <button onclick="moveLayer('up')" title="Naikkan Layer">🔼</button>
            <button onclick="fitToPage()" title="Fit ke Layar (Tengah)">🖼️ Fit</button>
            <button onclick="moveLayer('down')" title="Turunkan Layer">🔽</button>
        </div>

        <ul id="layerList" class="layer-list">
            </ul>

        <div class="history-panel">
            <button onclick="editorUndo()" id="btnUndo">⟲ Undo</button>
            <button onclick="editorRedo()" id="btnRedo">⟳ Redo</button>
        </div>

        <div class="tool-group">
            <h4>Tambah Objek</h4>
            <button onclick="addTextLayer()" class="btn-tool">🔤 Tambah Teks</button>
            <button onclick="document.getElementById('imgUpload').click()" class="btn-tool">🖼️ Tambah Gambar</button>
            <input type="file" id="imgUpload" hidden accept="image/*">
        </div>
    </aside>

</div>

<script>
    const appConfig = {
        // Background adalah foto yang diupload user
        bg: "<?= $backgroundPath ?>", 
        
        // Product adalah gambar dari Preset yang dipilih
        productPath: "<?= $productPath ?>", 
        
        // Logo yang dipilih user
        logoPath: "<?= $logoPath ?>",   
        logoPos: "<?= $logoPos ?>", 
        
        sessionId: "<?= $sessionId ?>"
    };
</script>

<script src="editor.js"></script>

<script>
    // Kita panggil init() secara manual karena di editor.js sudah dihapus
    // agar tidak bentrok dengan halaman edit_result.php
    init();
</script>

</body>
</html>