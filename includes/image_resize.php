<?php
/**
 * Auto-resize uploaded images to fit within MAX dimensions.
 * Always saves as JPEG to keep file size small for 300MB hosting.
 */
function resizeAndSaveImage(
    string $sourcePath,
    string $destPath,
    int $maxW = 800,
    int $maxH = 600
): bool {
    $info = getimagesize($sourcePath);
    if ($info === false) return false;

    [$origW, $origH] = $info;
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($sourcePath); break;
        case 'image/png':  $src = imagecreatefrompng($sourcePath);  break;
        case 'image/gif':  $src = imagecreatefromgif($sourcePath);  break;
        case 'image/webp': $src = imagecreatefromwebp($sourcePath); break;
        default: return false;
    }
    if (!$src) return false;

    $ratio = min($maxW / $origW, $maxH / $origH, 1);
    $newW  = (int)round($origW * $ratio);
    $newH  = (int)round($origH * $ratio);

    $dst = imagecreatetruecolor($newW, $newH);

    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $t = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $t);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    $ok = imagejpeg($dst, $destPath, 80);

    imagedestroy($src);
    imagedestroy($dst);
    return $ok;
}