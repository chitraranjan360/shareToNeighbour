<?php
/**
 * Image resizing helper used during uploads.
 *
 * It reads an uploaded image, scales it down to fit within the requested
 * bounds, and writes the resized result as a compressed JPEG file.
 */

// Resize an image from sourcePath and save the resized result to destPath.
function resizeAndSaveImage(
    string $sourcePath,
    string $destPath,
    int $maxW = 800,
    int $maxH = 600
): bool {
    // Read image metadata so the original size and format are known.
    $info = getimagesize($sourcePath);
    if ($info === false) return false;

    // Capture original dimensions and MIME type.
    [$origW, $origH] = $info;
    $mime = $info['mime'];

    // Create a GD image resource for the detected source format.
    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($sourcePath); break;
        case 'image/png':  $src = imagecreatefrompng($sourcePath);  break;
        case 'image/gif':  $src = imagecreatefromgif($sourcePath);  break;
        case 'image/webp': $src = imagecreatefromwebp($sourcePath); break;
        default: return false;
    }
    if (!$src) return false;

    // Preserve aspect ratio while keeping the image inside the max bounds.
    $ratio = min($maxW / $origW, $maxH / $origH, 1);
    $newW  = (int)round($origW * $ratio);
    $newH  = (int)round($origH * $ratio);

    // Create the destination canvas at the resized dimensions.
    $dst = imagecreatetruecolor($newW, $newH);

    // Preserve transparency for PNG and GIF sources.
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $t = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $t);
    }

    // Copy and resample the original image into the new canvas.
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
    // Save the final image as a compressed JPEG.
    $ok = imagejpeg($dst, $destPath, 80);

    // Release GD memory before returning.
    imagedestroy($src);
    imagedestroy($dst);
    return $ok;
}