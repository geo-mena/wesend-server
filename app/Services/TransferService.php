<?php

namespace App\Services;

use App\Models\File;
use App\Models\Transfer;
use Exception;
use Illuminate\Support\Facades\Log;

class TransferService
{
    protected $imagekitService;
    protected $encryptionService;

    public function __construct(
        ImagekitService $imagekitService,
        EncryptionService $encryptionService
    ) {
        $this->imagekitService = $imagekitService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Método para subir un archivo a ImageKit
     *
     * @param File $file
     * @param string $content
     * @return string
     */
    public function getDecryptedFile(File $file)
    {
        try {
            // Obtener archivo encriptado de ImageKit
            $encryptedContent = $this->imagekitService->getFile($file->storage_path);

            // Desencriptar contenido
            $decryptedContent = $this->encryptionService->decrypt(
                $encryptedContent,
                $file->encryption_key
            );

            return $decryptedContent;
        } catch (Exception $e) {
            Log::error('Error in getDecryptedFile', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Método para eliminar transferencias expiradas
     *
     * @return void
     */
    public function deleteExpiredTransfers()
    {
        $expiredTransfers = Transfer::where('expires_at', '<', now())->get();

        foreach ($expiredTransfers as $transfer) {
            foreach ($transfer->files as $file) {
                // Eliminar archivo de ImageKit
                $this->imagekitService->deleteFile($file->storage_path);
            }

            // Eliminar registros de la base de datos
            $transfer->files()->delete();
            $transfer->delete();
        }
    }
}
