<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UploadService
{
    protected $redis;
    protected $imagekitService;
    protected $encryptionService;

    public function __construct(
        ImagekitService $imagekitService,
        EncryptionService $encryptionService
    ) {
        $this->redis = Redis::connection();
        $this->imagekitService = $imagekitService;
        $this->encryptionService = $encryptionService;
    }

    public function storeChunk(string $uploadId, int $chunkNumber, string $chunkData, int $totalChunks)
    {
        $this->redis->hset(
            "upload:{$uploadId}:chunks",
            "chunk:{$chunkNumber}",
            $chunkData
        );

        // Guardar el total de chunks si aún no existe
        $this->redis->hsetnx(
            "upload:{$uploadId}:progress",
            'total_chunks',
            $totalChunks
        );

        $this->updateProgress($uploadId, $chunkNumber);
    }

    public function finalizeUpload(string $uploadId)
    {
        try {
            // Obtener todos los chunks
            $chunks = $this->redis->hgetall("upload:{$uploadId}:chunks");

            if (empty($chunks)) {
                throw new \Exception('No chunks found for this upload');
            }

            ksort($chunks); // Ordenar chunks por número

            // Combinar chunks
            $completeFile = '';
            foreach ($chunks as $chunk) {
                $completeFile .= $chunk;
            }

            if (empty($completeFile)) {
                throw new \Exception('Empty file content');
            }

            // Generar nombre único
            $fileName = uniqid('file_') . '.encrypted';

            // Subir a ImageKit
            $uploadedFile = $this->imagekitService->upload([
                'file' => $completeFile,
                'fileName' => $fileName,
            ]);


            if (
                !isset($uploadedFile->result) || !isset($uploadedFile->result->url)
            ) {
                throw new \Exception('Invalid response from ImageKit');
            }

            // Limpiar chunks de Redis
            $this->redis->del("upload:{$uploadId}:chunks");
            $this->redis->del("upload:{$uploadId}:progress");

            return [
                'path' => $uploadedFile->result->url,
                'size' => strlen($completeFile),
                'encryption_key' => config('app.encryption_key')
            ];
        } catch (\Exception $e) {
            // Puedes agregar logs aquí si lo deseas
            Log::error('Error in finalizeUpload: ' . $e->getMessage());
            throw $e;
        }
    }

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
}
