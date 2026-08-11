<?php
require "db.php";
session_start();

// 1. Validasi Login
if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$sessionId = session_id();
$folder = "temp_results/" . $sessionId . "/";

// 2. Ambil Parameter File (Contoh: final_result_0.jpg)
$targetResultFile = isset($_GET["file"]) ? $_GET["file"] : "";

if (empty($targetResultFile) || !file_exists($folder . $targetResultFile)) {
    echo "<script>alert('File tidak ditemukan!'); window.location='process_generate.php';</script>";
    exit;
}

// 3. LOAD METADATA JSON
// Kita perlu tahu komponen apa saja yg membentuk gambar ini (BG asli, Preset, Logo)
// Metadata ini dibuat oleh process_generate.php
$jsonMetaFile = $folder . pathinfo($targetResultFile, PATHINFO_FILENAME) . ".json";

// Default values (jika json hilang)
$backgroundPath = $folder . $targetResultFile; // Fallback ke gambar jadi
$productPath = "";
$logoPath = "";
$savedLayout = null;

if (file_exists($jsonMetaFile)) {
    $meta = json_decode(file_get_contents($jsonMetaFile), true);
    
    // Ambil path aset asli dari metadata
    // Path harus lengkap relatif terhadap root folder
    if(isset($meta['user_bg_file'])) {
        $backgroundPath = $folder . $meta['user_bg_file'];
    }
    if(isset($meta['preset_file'])) {
        $productPath = $meta['preset_file'];
    }
    if(isset($meta['logo_file'])) {
        $logoPath = $meta['logo_file'];
    }
    // Jika ada layout posisi tersimpan
    if(isset($meta['layout_used'])) {
        $savedLayout = $meta['layout_used'];
    }
}

// 4. Load Font
$fontFiles = glob("fonts/*.ttf");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Hasil Satuan</title>
    <link rel="stylesheet" href="editor.css">
    
    <style>
        /* Override Header Warna Biru Muda untuk membedakan dengan Editor Master */
        header { background-color: #0ea5e9 !important; }
        
        <?php foreach ($fontFiles as $f): 
            $name = pathinfo($f, PATHINFO_FILENAME); ?>
            @font-face { font-family: '<?= $name ?>'; src: url('<?= $f ?>'); }
        <?php endforeach; ?>
    </style>
</head>
<body>

<header>
    <div class="brand">✏️ Edit Hasil Satuan</div>
    <div class="header-actions">
        <button onclick="saveLayoutJSON()" class="btn-save">
             💾 Simpan Perubahan
        </button>
        
        <a href="process_generate.php" class="btn-close" style="
            background: rgba(255,255,255,0.2); 
            color: white; 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600;
            display: inline-block;
        ">Batal</a>
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
        bg: "<?= $backgroundPath ?>", 
        productPath: "<?= $productPath ?>", 
        logoPath: "<?= $logoPath ?>",
        sessionId: "<?= $sessionId ?>",
        targetFilename: "<?= $targetResultFile ?>",
        // Load layout spesifik gambar ini (posisi terakhir)
        savedLayout: <?= $savedLayout ? json_encode($savedLayout) : 'null' ?>
    };
</script>

<script src="editor.js"></script> 

<script>
// --- FUNGSI INIT KHUSUS EDIT HASIL ---
async function initResultEditor() {
    try {
        console.log("Initializing Result Editor...", appConfig);
        
        // [SOLUSI DOUBLE LAYER]
        // 1. Kosongkan array layers sepenuhnya
        layers = []; 
        activeLayerId = null;
        
        // 2. Render canvas kosong dulu untuk menghapus sisa-sisa gambar sebelumnya
        render(); 
        updateLayerList();

        // 3. Baru muat ulang dari JSON yang benar
        if (appConfig.savedLayout && Array.isArray(appConfig.savedLayout) && appConfig.savedLayout.length > 0) {
            
            for (const layerData of appConfig.savedLayout) {
                const props = { ...layerData };
                delete props.id; // Buat ID baru agar fresh

                // Teks
                if (layerData.type === 'text') {
                    await addLayer('text', layerData.text, layerData.name || "Teks", props);
                } 
                // Gambar (Background, Preset, Logo, Stiker)
                else if (layerData.src) {
                    // PENTING: Cek apakah src masih valid (tidak null/undefined)
                    await addLayer(layerData.type, layerData.src, layerData.name || layerData.type, props);
                }
            }

        } else {
            // Fallback (Hanya jika belum pernah diedit)
            if(appConfig.bg) await addLayer('background', appConfig.bg, "Background User", { locked: true, x: 900, y: 900 });
            if(appConfig.productPath) await addLayer('product', appConfig.productPath, "Preset Overlay", { x: 900, y: 900 });
            if(appConfig.logoPath) await addLayer('logo', appConfig.logoPath, "Logo", { x: 900, y: 150 });
        }
        
        // Final Render
        render();
        updateLayerList();
        saveHistory();

    } catch (e) {
        console.error("Gagal init editor:", e);
        alert("Gagal memuat layout gambar. Cek console.");
    }
}

// --- FUNGSI SIMPAN KHUSUS ---
function saveSingleResult() {
    const exportData = layers.map(L => ({
        type: L.type,
        // Ambil src asli (bisa berupa path URL atau Base64 jika baru upload)
        src: (L.type !== 'text' && L.img) ? L.img.src : null, 
        text: L.text,
        font: L.font, fontSize: L.fontSize, color: L.color,
        x: L.x, y: L.y, scale: L.scale, rotation: L.rotation,
        width: L.width, height: L.height,
        opacity: L.opacity, flipX: L.flipX, flipY: L.flipY,
        name: L.name 
    }));

    const btnSimpan = document.querySelector('.btn-save');
    btnSimpan.innerText = "⏳ Menyimpan...";
    btnSimpan.disabled = true;

    const formData = new FormData();
    formData.append('layout_data', JSON.stringify(exportData));
    formData.append('target_file', appConfig.targetFilename);

    fetch('save_single_result.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            window.location = "process_generate.php?t=" + new Date().getTime(); 
        } else {
            alert("Gagal menyimpan: " + (data.message || "Error tidak diketahui"));
        }
    })
    .catch(err => {
        console.error(err);
        alert("Kesalahan koneksi server.");
    })
    .finally(() => {
        btnSimpan.innerText = "💾 Simpan Perubahan";
        btnSimpan.disabled = false;
    });
}

// JALANKAN INIT MANUAL (Beri jeda sedikit agar editor.js selesai loading)
setTimeout(initResultEditor, 100);
</script>
</body>
</html>