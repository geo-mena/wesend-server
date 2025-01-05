<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UploadService
{
    protected $redis;
    protected $r2Service;
    protected $encryptionService;

    public function __construct(
        R2Service $r2Service,
        EncryptionService $encryptionService
    ) {
        $this->redis = Redis::connection();
        $this->r2Service = $r2Service;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Método para almacenar un chunk de un archivo
     *
     * @param string $uploadId
     * @param int $chunkNumber
     * @param string $chunkData
     * @param int $totalChunks
     */
    public function storeChunk(string $uploadId, int $chunkNumber, string $chunkData, int $totalChunks)
    {
        try {
            $this->redis->multi();

            // Guardar chunk en Redis
            $this->redis->hset(
                "upload:{$uploadId}:chunks",
                "chunk:{$chunkNumber}",
                $chunkData
            );

            // Expirar chunks en 1 hora
            $this->redis->expire("upload:{$uploadId}:chunks", 3600);

            // Guardar el total de chunks si aún no existe
            $this->redis->hset(
                "upload:{$uploadId}:progress",
                'total_chunks',
                $totalChunks
            );

            $this->redis->expire("upload:{$uploadId}:progress", 3600);
            $this->redis->exec();

            $this->updateProgress($uploadId, $chunkNumber);
        } catch (Exception $e) {
            $this->deleteChunks($uploadId);
            throw $e;
        }
    }

    /**
     * Método para finalizar un upload
     *
     * @param string $uploadId
     * @return array
     */
    public function finalizeUpload(string $uploadId)
    {
        try {
            // Obtener chunks
            $chunks = $this->redis->hgetall("upload:{$uploadId}:chunks");

            if (empty($chunks)) {
                throw new Exception('No chunks found for this upload');
            }

            ksort($chunks);

            // Iniciar transacción en Redis
            $this->redis->multi();

            // Combinar chunks
            $completeFile = '';
            foreach ($chunks as $chunk) {
                $completeFile .= $chunk;
            }

            // Crear estructura de directorios por fecha
            $dateFolder = now()->format('Y/m/d');
            $fileName = uniqid('file_') . '.encrypted';
            $fullPath = "{$dateFolder}/{$fileName}";

            $this->redis->hset(
                'temp_uploads',
                $fullPath,
                now()->addHours(1)->timestamp
            );

            // Subir a R2
            $this->r2Service->upload($completeFile, $fullPath);

            // Si llegamos aquí, el archivo se subió correctamente. Eliminar de temporales
            $this->redis->hdel('temp_uploads', $fullPath);

            // Limpiar chunks de Redis
            $this->redis->del("upload:{$uploadId}:chunks");
            $this->redis->del("upload:{$uploadId}:progress");

            $this->redis->exec();

            return [
                'path' => $fullPath,
                'size' => strlen($completeFile),
                'encryption_key' => config('app.encryption_key')
            ];
        } catch (Exception $e) {

            $this->deleteChunks($uploadId);
            if (isset($fullPath)) {
                $this->r2Service->delete($fullPath);
            }

            throw $e;
        }
    }

    /**
     * Método para actualizar el progreso de un upload
     *
     * @param string $uploadId
     * @param int $currentChunk
     */
    private function updateProgress(string $uploadId, int $currentChunk)
    {
        $totalChunks = $this->redis->hget("upload:{$uploadId}:progress", 'total_chunks');
        $progress = ($currentChunk / $totalChunks) * 100;

        $this->redis->hset(
            "upload:{$uploadId}:progress",
            'progress',
            $progress
        );
    }

    /**
     * Método para eliminar los chunks de un upload
     *
     * @param string $uploadId
     * @return bool
     */
    public function deleteChunks(string $uploadId)
    {
        try {
            // Eliminar chunks
            $this->redis->del("upload:{$uploadId}:chunks");

            // Eliminar progreso
            $this->redis->del("upload:{$uploadId}:progress");

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Método para eliminar un archivo de R2
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFileFromStorage(string $filePath)
    {
        try {
            $this->r2Service->delete($filePath);
            
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Método para obtener el progreso de un upload
     *
     * @param string $uploadId
     * @return array
     */
    public function getUploadProgress(string $uploadId): array
    {
        $progress = $this->redis->hgetall("upload:{$uploadId}:progress");

        return [
            'current_progress' => $progress['progress'] ?? 0,
            'total_chunks' => $progress['total_chunks'] ?? 0
        ];
    }

    /**
     * Método para limpiar archivos temporales expirados
     */
    public function cleanOrphanedFiles()
    {
        try {
            // Obtener archivos temporales expirados
            $tempFiles = $this->redis->hgetall('temp_uploads');
            $now = now()->timestamp;

            foreach ($tempFiles as $path => $expiresAt) {
                if ($expiresAt < $now) {
                    // Eliminar archivo de R2
                    $this->r2Service->delete($path);
                    // Eliminar registro
                    $this->redis->hdel('temp_uploads', $path);
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}
