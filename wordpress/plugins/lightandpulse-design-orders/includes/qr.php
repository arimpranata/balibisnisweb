<?php
/**
 * QR codes, generated on this server (no third-party service): inline SVG for
 * pages, and PNG/SVG downloads for print.
 */
defined('ABSPATH') || exit;

use Lightandpulse\DesignOrders\QR\QRCode;
use const Lightandpulse\DesignOrders\QR\QR_ERROR_CORRECT_LEVEL_M;

/** Matrix of booleans (true = dark module). */
function lpdo_qr_matrix(string $text): array
{
    $qr = QRCode::getMinimumQRCode($text, QR_ERROR_CORRECT_LEVEL_M);
    $n = $qr->getModuleCount();
    $m = [];
    for ($r = 0; $r < $n; $r++) {
        for ($c = 0; $c < $n; $c++) {
            $m[$r][$c] = (bool) $qr->isDark($r, $c);
        }
    }
    return $m;
}

/** SVG with a 4-module quiet zone. One path, so it stays small and crisp at any size. */
function lpdo_qr_svg(string $text, int $size = 160, string $dark = '#000000', string $light = '#ffffff', string $label = ''): string
{
    $m = lpdo_qr_matrix($text);
    $n = count($m);
    $q = 4;
    $d = '';
    foreach ($m as $r => $row) {
        foreach ($row as $c => $on) {
            if ($on) {
                $d .= 'M' . ($c + $q) . ' ' . ($r + $q) . 'h1v1h-1z';
            }
        }
    }
    $view = $n + 2 * $q;
    return '<svg xmlns="http://www.w3.org/2000/svg" class="lpdo-qr" viewBox="0 0 ' . $view . ' ' . $view . '" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges" role="img" aria-label="' . esc_attr($label ?: __('QR code', 'lightandpulse-design-orders')) . '">'
        . '<rect width="100%" height="100%" fill="' . esc_attr($light) . '"/><path fill="' . esc_attr($dark) . '" d="' . $d . '"/></svg>';
}

/** Send a QR code as a PNG (GD) or SVG download, then exit. */
function lpdo_qr_send(string $text, string $filename, string $format = 'png'): void
{
    nocache_headers();
    if ($format === 'png' && function_exists('imagecreatetruecolor')) {
        $m = lpdo_qr_matrix($text);
        $n = count($m);
        $scale = 16;
        $px = ($n + 8) * $scale;
        $im = imagecreatetruecolor($px, $px);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefill($im, 0, 0, $white);
        foreach ($m as $r => $row) {
            foreach ($row as $c => $on) {
                if ($on) {
                    imagefilledrectangle($im, ($c + 4) * $scale, ($r + 4) * $scale, ($c + 5) * $scale - 1, ($r + 5) * $scale - 1, $black);
                }
            }
        }
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '.png"');
        imagepng($im);
        imagedestroy($im);
        exit;
    }
    header('Content-Type: image/svg+xml');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '.svg"');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . lpdo_qr_svg($text, 512); // phpcs:ignore WordPress.Security.EscapeOutput -- generated SVG.
    exit;
}
