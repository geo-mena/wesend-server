<?php

namespace App\Services;

use App\Models\File;
use App\Models\Transfer;
use Exception;

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
     * 🔒️ Método para subir un archivo a R2
     *
     * @param File $file
     * @return string
     * @throws Exception
     */
    public function getDecryptedFile(File $file)
    {
        try {
            $encryptedContent = $this->r2Service->get($file->storage_path);

            $content = is_array($encryptedContent) ? $encryptedContent['content'] : $encryptedContent;

            //! Desencriptar contenido
            $decryptedContent = $this->encryptionService->decrypt(
                $content,
                $file->encryption_key
            );

            return $decryptedContent;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🔒️ Método para eliminar transferencias expiradas
     *
     * @return void
     * @throws Exception
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

    /**
     * 🔒️ Método para limpiar archivos después de una descarga única
     *
     * @param Transfer $transfer
     * @return void
     * @throws Exception
     */
    public function cleanupSingleDownload(Transfer $transfer)
    {
        try {
            if ($transfer->single_download && $transfer->downloaded) {
                // Eliminar archivos de R2
                foreach ($transfer->files as $file) {
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
