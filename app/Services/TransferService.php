<?php

namespace App\Services;

use App\Models\File;
use App\Models\Transfer;
use Exception;
use Illuminate\Support\Facades\Log;

class TransferService
{
    protected $r2Service;
    protected $encryptionService;

    public function __construct(
        R2Service $r2Service,
        EncryptionService $encryptionService
    ) {
        $this->r2Service = $r2Service;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Método para subir un archivo a R2
     *
     * @param File $file
     * @param string $content
     * @return string
     */
    public function getDecryptedFile(File $file)
    {
        try {
            // Obtener archivo encriptado de R2
            $encryptedContent = $this->r2Service->get($file->storage_path);

            // Desencriptar contenido
            $decryptedContent = $this->encryptionService->decrypt(
                $encryptedContent,
                $file->encryption_key
            );

            return $decryptedContent;
        } catch (Exception $e) {
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
        try {
            $expiredTransfers = Transfer::where('expires_at', '<', now())->get();

            foreach ($expiredTransfers as $transfer) {
                foreach ($transfer->files as $file) {
                    // Eliminar archivo de R2
                    $this->r2Service->delete($file->storage_path);
                }

                // Eliminar registros de la base de datos
                $transfer->files()->delete();
                $transfer->delete();
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}
