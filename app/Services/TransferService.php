<?php

namespace App\Services;

use App\Models\File;
use App\Models\Transfer;

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

    public function getDecryptedFile(File $file)
    {
        // Obtener archivo encriptado de ImageKit
        $encryptedContent = $this->imagekitService->getFile($file->storage_path);

        // Desencriptar contenido
        return $this->encryptionService->decrypt(
            $encryptedContent,
            $file->encryption_key
        );
    }

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
