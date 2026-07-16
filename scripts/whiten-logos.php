<?php

declare(strict_types=1);

/**
 * One-time script: convert dark gray logo text to white.
 * Run: php scripts/whiten-logos.php
 */

$logos = [
    __DIR__ . '/../poomconnect_images/websites-logo/poom-logo-200x50.png',
    __DIR__ . '/../poomconnect_images/websites-logo/poom-logo-240x60.png',
    __DIR__ . '/../poomconnect_images/websites-logo/poom-logo-320x80.png',
];

function shouldWhiten(int $r, int $g, int $b, int $a): bool
{
    if ($a < 20) {
        return false;
    }

    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $saturation = $max === 0 ? 0 : ($max - $min) / $max;

    // Keep vibrant gradient pixels (purple / pink)
    if ($saturation > 0.25 && $max > 80) {
        return false;
    }

    // Dark or gray text → white
    if ($max < 200 && $saturation < 0.35) {
        return true;
    }

    // Muted mid-gray text
    if ($max < 140 && abs($r - $g) < 30 && abs($g - $b) < 30) {
        return true;
    }

    return false;
}

foreach ($logos as $path) {
    if (!is_file($path)) {
        echo "Skip missing: $path\n";
        continue;
    }

    $img = imagecreatefrompng($path);
    if ($img === false) {
        echo "Failed to load: $path\n";
        continue;
    }

    imagesavealpha($img, true);
    $w = imagesx($img);
    $h = imagesy($img);
    $white = imagecolorallocatealpha($img, 255, 255, 255, 0);
    $changed = 0;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $rgba = imagecolorat($img, $x, $y);
            $a = ($rgba >> 24) & 0x7F;
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;

            if (shouldWhiten($r, $g, $b, 127 - $a)) {
                imagesetpixel($img, $x, $y, $white);
                $changed++;
            }
        }
    }

    imagepng($img, $path);
    imagedestroy($img);
    echo "Updated: $path ($changed pixels)\n";
}

echo "Done.\n";
