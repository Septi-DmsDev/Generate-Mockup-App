<?php
session_start();
// 1. Atur memori agar bisa memproses upload gambar besar
ini_set('memory_limit', '-1'); 
header('Content-Type: application/json');

// 2. Cek Session & Folder
$sessionId = session_id();
$folder = "temp_results/" . $sessionId . "/";

if (!file_exists($folder)) {
    // Buat folder jika belum ada (safety)
    mkdir($folder, 0777, true);
}

// 3. Terima Data JSON
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['layout_data'])) {
    
    $jsonRaw = $_POST['layout_data'];
    $data = json_decode($jsonRaw, true);

    if ($data === null) {
        echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
        exit;
    }

    // ======================================================
    // 4. PROSES PEMBERSIHAN DATA (Handling Base64 Image)
    // ======================================================
    // Kita loop setiap layer. Jika ada gambar Base64 (upload manual/stiker),
    // kita simpan jadi file fisik agar layout.json ringan & tidak rusak.
    
    foreach ($data as $key => &$layer) {
        // Cek apakah layer ini punya properti 'src' dan isinya adalah data:image (Base64)
        if (isset($layer['src']) && strpos($layer['src'], 'data:image') === 0) {
            
            // Generate nama file unik untuk aset ini
            // Menggunakan timestamp + index array agar unik
            $imgName = "asset_" . time() . "_" . $key . ".png";
            $imgPath = $folder . $imgName;
            
            // Proses Decode Base64
            // Format: data:image/png;base64,ASDFGHJKL...
            $parts = explode(',', $layer['src']);
            
            if (count($parts) >= 2) {
                $base64 = $parts[1];
                $decoded = base64_decode($base64);
                
                // Simpan sebagai file PNG fisik di folder user
                if (file_put_contents($imgPath, $decoded)) {
                    
                    // BERHASIL: Ganti 'src' di JSON menjadi path file fisik
                    // Kita simpan nama filenya saja agar relatif terhadap folder session
                    // Tapi agar process_generate mudah membacanya, kita simpan full path relatif
                    // Ingat: process_generate ada di root, folder ada di temp_results/...
                    $layer['src'] = $imgPath; 
                }
            }
        }
    }
    // (Penting: putuskan referensi variabel setelah loop)
    unset($layer);

    // 5. Simpan ke file 'layout.json'
    // File ini sekarang bersih (berisi link file), bukan kode Base64 panjang
    $savePath = $folder . "layout.json";
    
    // Encode kembali array ke JSON dengan format rapi
    $cleanJson = json_encode($data, JSON_PRETTY_PRINT);
    
    if (file_put_contents($savePath, $cleanJson)) {
        echo json_encode(["status" => "success", "message" => "Master Layout saved successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to write layout file"]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "No data received"]);
}
?>