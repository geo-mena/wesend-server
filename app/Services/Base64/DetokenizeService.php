<?php

namespace App\Services\Base64;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class DetokenizeService
{
    /**
     * URL base del servicio de identidad
     * 
     * @var string
     */
    protected $identityApiBaseUrl;

    /**
     * API Key para el servicio de identidad
     * 
     * @var string
     */
    protected $apiKey;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->identityApiBaseUrl = Config::get('services.identity.base_url', env('IDENTITY_API_BASE_URL'));
        $this->apiKey = Config::get('services.identity.api_key', env('IDENTITY_API_KEY'));
    }

    /**
     * Detokenizar una imagen encriptada
     *
     * @param string $bestImageToken Token de la imagen encriptada
     * @param string|null $transactionId ID de la transacción (opcional)
     * @return string|false String Base64 de la imagen o false si hay error
     */
    public function detokenizeImage($bestImageToken, $transactionId = null)
    {
        try {
            $requestData = [
                'bestImageToken' => $bestImageToken
            ];

            if ($transactionId !== null) {
                $requestData['transactionId'] = $transactionId;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->identityApiBaseUrl . '/services/detokenize', $requestData);

            if ($response->successful()) {
                $data = $response->json();
                return $data['imageBuffer'] ?? false;
            }

            return false;
        } catch (Exception $e) {
            // Registrar el error
            Log::error('Error en detokenizeImage: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decodificar y guardar una imagen Base64
     *
     * @param string $base64String
     * @param string $storageDisk Disco de almacenamiento ('public', 's3', etc.)
     * @param string $storageFolder Carpeta donde guardar
     * @return array|false Información de la imagen o false si falla
     */
    public function decodeAndSaveImage($base64String, $storageDisk = 'public', $storageFolder = 'detokenized')
    {
        $imageData = null;
        $mimeType = null;

        if (Str::contains($base64String, ';base64,')) {
            list($type, $base64String) = explode(';', $base64String);
            list(, $base64String) = explode(',', $base64String);
            list(, $mimeType) = explode(':', $type);
        } else {
            $imageData = base64_decode($base64String);
            if (!$imageData) {
                return false;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);
        }

        if ($imageData === null) {
            $imageData = base64_decode($base64String);
            if (!$imageData) {
                return false;
            }
        }

        $extension = $this->getExtensionFromMimeType($mimeType);
        $fileName = Str::random(16) . '.' . $extension;

        $storagePath = $storageFolder ? trim($storageFolder, '/') . '/' : '';
        $storagePath .= $fileName;

        // Guardar en el disco
        Storage::disk($storageDisk)->put($storagePath, $imageData);

        if (!Storage::disk($storageDisk)->exists($storagePath)) {
            return false;
        }

        // Obtener información de la imagen
        $fullPath = storage_path('app/' . ($storageDisk === 'public' ? 'public/' : '') . $storagePath);
        try {
            list($width, $height) = getimagesize($fullPath);
            $size = Storage::disk($storageDisk)->size($storagePath) / 1024;

            $url = $storageDisk === 'public'
                ? asset('storage/' . $storagePath)
                : '';

            return [
                'file_name' => $fileName,
                'mime_type' => $mimeType,
                'extension' => $extension,
                'width' => $width,
                'height' => $height,
                'size' => round($size, 2),
                'path' => $storagePath,
                'full_path' => $fullPath,
                'url' => $url,
            ];
        } catch (Exception $e) {
            Storage::disk($storageDisk)->delete($storagePath);
            return false;
        }
    }

    /**
     * Obtener extensión de archivo a partir del tipo MIME
     *
     * @param string $mimeType
     * @return string
     */
    public function getExtensionFromMimeType($mimeType)
    {
        $mimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/tiff' => 'tiff',
            'image/x-icon' => 'ico',
        ];

        return $mimeTypes[$mimeType] ?? 'jpg';
    }

    /**
     * Eliminar un archivo del almacenamiento
     *
     * @param string $filePath
     * @param string $storageDisk
     * @return bool
     */
    public function deleteFile($filePath, $storageDisk = 'public')
    {
        if (Storage::disk($storageDisk)->exists($filePath)) {
            return Storage::disk($storageDisk)->delete($filePath);
        }

        return false;
    }
}