<?php
session_start();
require "db.php";

$presetId = $_POST['preset_id'];

// ambil background pertama
$bg = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT file_path FROM backgrounds WHERE preset_id=$presetId ORDER BY id ASC LIMIT 1"
));
$backgroundPath = "backgrounds/" . $bg['file_path'];

// simpan user image sementara
$session = session_id();
$folder = "temp_editor/" . $session . "/";

if (!is_dir($folder)) mkdir($folder, 0777, true);

$userName = "user.jpg";
move_uploaded_file($_FILES['user_img']['tmp_name'], $folder.$userName);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Preview Editor</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>Preview Penempatan Objek</header>

<div class="container">

<h3>Atur Posisi, Zoom, Rotasi</h3>

<canvas id="editorCanvas" width="1080" height="1080" style="border:1px solid #aaa;max-width:100%;"></canvas>

<!-- zoom -->
<div style="margin-top:20px;">
    Zoom:
    <input type="range" id="zoom" min="0.2" max="3" value="1" step="0.01">
</div>

<!-- rotate -->
<div style="margin-top:10px;">
    Rotasi:
    <input type="range" id="rotate" min="-180" max="180" value="0" step="1">
</div>

<button id="processBtn" class="download-btn" style="margin-top:20px;">
    Proses Semua Gambar
</button>

<script>
let canvas = document.getElementById('editorCanvas');
let ctx = canvas.getContext('2d');

let bg = new Image();
let user = new Image();

bg.src = "<?= $backgroundPath ?>";
user.src = "<?= $folder.$userName ?>";

let posX = 0, posY = 0;
let scale = 1;
let rotateDeg = 0;

// draw function
function draw() {
    ctx.clearRect(0, 0, 1080, 1080);

    ctx.drawImage(bg, 0, 0, 1080, 1080);

    let w = user.width * scale;
    let h = user.height * scale;

    let centerX = posX + w/2;
    let centerY = posY + h/2;

    ctx.save();
    ctx.translate(centerX, centerY);
    ctx.rotate(rotateDeg * Math.PI/180);
    ctx.drawImage(user, -w/2, -h/2, w, h);
    ctx.restore();
}

// load check
let loaded = 0;
bg.onload = user.onload = () => {
    loaded++;
    if (loaded === 2) draw();
};

// drag move
let dragging = false;
let sx=0, sy=0;

canvas.onmousedown = e => {
    dragging = true;
    sx = e.offsetX - posX;
    sy = e.offsetY - posY;
};
canvas.onmouseup = () => dragging = false;
canvas.onmousemove = e => {
    if (!dragging) return;
    posX = e.offsetX - sx;
    posY = e.offsetY - sy;
    draw();
};

// zoom
document.getElementById("zoom").oninput = e => {
    scale = parseFloat(e.target.value);
    draw();
};

// rotate
document.getElementById("rotate").oninput = e => {
    rotateDeg = parseFloat(e.target.value);
    draw();
};

// PROSES
document.getElementById("processBtn").onclick = () => {

    fetch("store_transform.php", {
        method: "POST",
        body: new URLSearchParams({
            posX, posY, scale, rotateDeg,
            preset_id: "<?= $presetId ?>"
        })
    }).then(() => {
        window.location = "user_process.php";
    });
};
</script>

</div>
</body>
</html>
