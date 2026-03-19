<?php
session_start();

$_SESSION['transform'] = [
    "x" => $_POST['posX'],
    "y" => $_POST['posY'],
    "scale" => $_POST['scale'],
    "rotate" => $_POST['rotateDeg'],
    "preset_id" => $_POST['preset_id']
];

echo "OK";
?>
