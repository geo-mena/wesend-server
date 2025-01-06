<?php

namespace App\Services;

class CompressionService
{
    private const COMPRESSION_HEADER = 'COMPRESSED_V1:';

    /**
     * Comprime datos si es beneficioso hacerlo
     */
    public function compressIfBeneficial(string $content, string $mimeType): array
    {
        if (!$this->shouldCompress($mimeType)) {
            return [
                'content' => $content,
                'compressed' => false
            ];
        }

        $compressed = gzencode($content, 9); // Máxima compresión

        // Solo comprimir si reduce al menos 10%
        if (strlen($compressed) < strlen($content) * 0.9) {
            return [
                'content' => self::COMPRESSION_HEADER . $compressed,
                'compressed' => true
            ];
        }

        return [
            'content' => $content,
            'compressed' => false
        ];
    }

    /**
     * Descomprime datos si están comprimidos
     */
    public function decompress(string $content): string
    {
        if (str_starts_with($content, self::COMPRESSION_HEADER)) {
            $compressed = substr($content, strlen(self::COMPRESSION_HEADER));
            return gzdecode($compressed);
        }
        return $content;
    }

    /**
     * Determina si un tipo de archivo debería comprimirse
     */
    private function shouldCompress(string $mimeType): bool
    {
        $skipTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/gzip',
            'video/mp4',
            'video/mpeg',
            'audio/mpeg',
            'audio/mp4'
        ];

        return !in_array($mimeType, $skipTypes);
    }
}
