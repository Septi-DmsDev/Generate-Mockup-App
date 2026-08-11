<?php
function createZip($files, $zipPath)
{
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);

    foreach ($files as $f) {
        $zip->addFile($f, basename($f));
    }

    $zip->close();
}
?>
