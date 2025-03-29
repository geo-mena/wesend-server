<?php

namespace App\Services\Base64;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Imagick;

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

            // Información de JPEG adicional para imágenes JPEG
            $jpegQuality = null;
            $isProgressive = false;

            if (strtolower($extension) === 'jpg' || strtolower($extension) === 'jpeg') {
                $imageInfo = $this->analyzeJpegWithImagick($fullPath);
                $jpegQuality = $imageInfo['quality'];
                $isProgressive = $imageInfo['progressive'];
            }

            $result = [
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

            if ($jpegQuality !== null) {
                $result['jpeg_quality'] = $jpegQuality;
            }

            if ($isProgressive !== null) {
                $result['is_progressive'] = $isProgressive;
            }

            return $result;
        } catch (Exception $e) {
            Storage::disk($storageDisk)->delete($storagePath);
            return false;
        }
    }

    /**
     * Analizar una imagen JPEG usando ImageMagick
     * Esta es una implementación más confiable usando una librería profesional
     *
     * @param string $imagePath Ruta de la imagen
     * @return array Información de calidad y formato
     */
    protected function analyzeJpegWithImagick($imagePath)
    {
        $result = [
            'quality' => null,
            'progressive' => false
        ];

        try {
            // Verificar si ImageMagick está disponible
            if (!extension_loaded('imagick')) {
                // Fallback a valores aproximados
                $result['quality'] = 85; // Valor por defecto común
                $imageData = file_get_contents($imagePath);
                $result['progressive'] = (strpos($imageData, chr(0xFF) . chr(0xC2)) !== false);
                return $result;
            }

            // Crear instancia de Imagick
            $imagick = new Imagick($imagePath);

            // Verificar si es progresivo
            $interlace = $imagick->getInterlaceScheme();
            $result['progressive'] = ($interlace == Imagick::INTERLACE_JPEG ||
                $interlace == Imagick::INTERLACE_PLANE ||
                $interlace == Imagick::INTERLACE_PARTITION);

            // Obtener calidad
            // ImageMagick no puede determinar directamente la calidad original de un JPEG
            // Usamos un método híbrido con herramientas externas si están disponibles

            // Intentar con JpegOptim si está instalado
            $jpegOptimAvailable = $this->isCommandAvailable('jpegoptim --version');
            if ($jpegOptimAvailable) {
                $tempFile = tempnam(sys_get_temp_dir(), 'jpeg_quality_') . '.jpg';
                copy($imagePath, $tempFile);
                $output = shell_exec("jpegoptim --all-normal --strip-none -T $tempFile 2>&1");

                // jpegoptim da una estimación de calidad en su salida
                if (preg_match('/quality\s*=\s*(\d+)/', $output, $matches)) {
                    $result['quality'] = intval($matches[1]);
                    @unlink($tempFile);
                    return $result;
                }
                @unlink($tempFile);
            }

            // Si no podemos determinar con exactitud, estimamos basados en la compresión,
            // pero con valores más razonables y típicos de la web

            // Detectar compresión usando las propiedades DCT (Discrete Cosine Transform)
            $compression = $imagick->getImageCompressionQuality();

            // Si imagick reporta 0, podemos intentar una aproximación más precisa
            if ($compression <= 0) {
                // Verificar artifactos de compresión
                // Menor cantidad de artifactos = mayor calidad
                $imagick->setImageFormat('jpg');
                $imagick->setImageCompressionQuality(100);
                $perfectImage = clone $imagick;

                // Comparar con versiones recomprimidas
                $testQualities = [100, 90, 85, 75, 60];
                $bestDiff = PHP_FLOAT_MAX;
                $bestQuality = 85; // valor predeterminado

                foreach ($testQualities as $testQuality) {
                    $testImage = clone $imagick;
                    $testImage->setImageCompressionQuality($testQuality);
                    $diff = $this->compareImages($perfectImage, $testImage);

                    if ($diff < $bestDiff) {
                        $bestDiff = $diff;
                        $bestQuality = $testQuality;
                    }
                }

                $result['quality'] = $bestQuality;
            } else {
                // Imagick reportó un valor, pero a menudo subestima
                // Ajustamos basado en prácticas comunes de compresión web
                $result['quality'] = min(100, max($compression, 60));

                // Si la imagen parece muy pequeña para su tamaño, probablemente sea de alta calidad
                $filesize = filesize($imagePath);
                $pixelCount = $imagick->getImageWidth() * $imagick->getImageHeight();
                $bytesPerPixel = $filesize / max(1, $pixelCount);

                if ($bytesPerPixel > 0.5 && $result['quality'] < 85) {
                    $result['quality'] = min(100, $result['quality'] + 10);
                } else if ($bytesPerPixel < 0.1 && $result['quality'] > 70) {
                    $result['quality'] = max(60, $result['quality'] - 10);
                }
            }

            // Cerrar imagick
            $imagick->clear();

            return $result;
        } catch (Exception $e) {
            Log::error('Error analizando JPEG con ImageMagick: ' . $e->getMessage());

            // Valores por defecto razonables en caso de error
            $result['quality'] = 85;
            return $result;
        }
    }

    /**
     * Compara dos imágenes Imagick para determinar su diferencia
     *
     * @param Imagick $img1 Primera imagen
     * @param Imagick $img2 Segunda imagen
     * @return float Valor de diferencia normalizado
     */
    protected function compareImages(Imagick $img1, Imagick $img2)
    {
        try {
            // Usa el método de comparación de ImageMagick
            $result = $img1->compareImages($img2, Imagick::METRIC_MEANSQUAREERROR);
            return $result[1];
        } catch (Exception $e) {
            // Si falla, devolvemos un valor alto de diferencia
            return 1.0;
        }
    }

    /**
     * Verifica si un comando de consola está disponible en el sistema
     *
     * @param string $command Comando a verificar
     * @return bool True si el comando está disponible
     */
    protected function isCommandAvailable($command)
    {
        $whereIsCommand = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';

        $process = proc_open(
            "$whereIsCommand " . escapeshellarg($command),
            [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"],
            ],
            $pipes
        );

        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            proc_close($process);

            return !empty($stdout);
        }

        return false;
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