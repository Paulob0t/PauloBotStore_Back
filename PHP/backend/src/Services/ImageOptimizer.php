<?php

namespace App\Services;

class ImageOptimizer
{
    private static string $cacheDir = __DIR__ . '/../../storage/cache/images';

    public static function serveOptimized(string $imageData, int $maxWidth = 400, int $quality = 82): void
    {
        if (!is_dir(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }

        // Si es una URL externa válida
        if (filter_var($imageData, FILTER_VALIDATE_URL)) {
            header("Location: " . $imageData);
            exit;
        }

        $cacheKey = md5($imageData . '_' . $maxWidth . '_' . $quality);
        $cacheFile = self::$cacheDir . '/' . $cacheKey . '.webp';

        // Si ya está en cache de disco, servir directamente
        if (file_exists($cacheFile)) {
            self::sendHeaders(filemtime($cacheFile), $cacheKey);
            readfile($cacheFile);
            exit;
        }

        // Decodificar Base64 o binario directo
        $rawBinary = null;
        if (str_starts_with($imageData, 'data:image/')) {
            $parts = explode(',', $imageData, 2);
            $rawBinary = base64_decode($parts[1] ?? '');
        } else {
            $rawBinary = $imageData;
        }

        if (empty($rawBinary)) {
            http_response_code(404);
            exit;
        }

        $sourceImg = @imagecreatefromstring($rawBinary);
        if (!$sourceImg) {
            http_response_code(404);
            exit;
        }

        $origW = imagesx($sourceImg);
        $origH = imagesy($sourceImg);

        // Calcular ratio para redimensionar manteniendo proporción
        $ratio = min($maxWidth / max($origW, 1), $maxWidth / max($origH, 1), 1.0);
        $newW = max(1, (int)($origW * $ratio));
        $newH = max(1, (int)($origH * $ratio));

        $targetImg = imagecreatetruecolor($newW, $newH);

        // Preservar transparencia para PNG/WebP
        imagealphablending($targetImg, false);
        imagesavealpha($targetImg, true);
        $transparent = imagecolorallocatealpha($targetImg, 0, 0, 0, 127);
        imagefilledrectangle($targetImg, 0, 0, $newW, $newH, $transparent);

        imagecopyresampled($targetImg, $sourceImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($sourceImg);

        // Guardar en cache como WebP
        if (function_exists('imagewebp')) {
            imagewebp($targetImg, $cacheFile, $quality);
            imagedestroy($targetImg);

            self::sendHeaders(time(), $cacheKey);
            readfile($cacheFile);
            exit;
        }

        // Fallback a JPEG si WebP no estuviera soportado
        $fallbackFile = self::$cacheDir . '/' . $cacheKey . '.jpg';
        imagejpeg($targetImg, $fallbackFile, $quality);
        imagedestroy($targetImg);

        header("Content-Type: image/jpeg");
        header("Cache-Control: public, max-age=31536000, immutable");
        readfile($fallbackFile);
        exit;
    }

    private static function sendHeaders(int $mtime, string $etag): void
    {
        header("Content-Type: image/webp");
        header("Cache-Control: public, max-age=31536000, immutable");
        header("ETag: \"" . $etag . "\"");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag) {
            http_response_code(304);
            exit;
        }
    }
}
