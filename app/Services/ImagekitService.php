<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use ImageKit\ImageKit;

class ImagekitService
{
    protected $imagekit;

    public function __construct()
    {
        $this->imagekit = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.endpoint')
        );
    }

    public function upload(array $params)
    {
        try {
            $tempFile = tempnam(sys_get_temp_dir(), 'upload_');
            file_put_contents($tempFile, $params['file']);

            $response = $this->imagekit->upload([
                'file' => fopen($tempFile, 'r'),
                'fileName' => $params['fileName'],
                'useUniqueFileName' => true
            ]);

            unlink($tempFile);

            // Verificar que el tamaño subido coincida con el original
            if (($response->result->size ?? 0) !== strlen($params['file'])) {
                throw new Exception('Upload size mismatch');
            }

            return $response;
        } catch (Exception $e) {
            Log::error('ImageKit upload error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getFile(string $path)
    {
        try {
            $opts = ([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Accept: application/octet-stream',
                        'Content-Type: application/octet-stream'
                    ],
                    'ignore_errors' => true
                ]
            ]);

            $context = stream_context_create($opts);
            $content = file_get_contents($path, false, $context);

            if ($content === false) {
                throw new Exception('Failed to fetch file content');
            }

            return $content;
        } catch (Exception $e) {
            Log::error('ImageKit getFile error', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteFile(string $fileId)
    {
        return $this->imagekit->deleteFile($fileId);
    }
}
