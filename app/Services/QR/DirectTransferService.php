<?php

namespace App\Services\QR;

use App\Models\DirectTransfer;
use App\Services\EncryptionService;
use App\Services\R2Service;
use Exception;
use Illuminate\Support\Facades\Hash;

class DirectTransferService
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
     * 🔒️ Crea una nueva transferencia directa
     * 
     * @param array $data
     * @return DirectTransfer
     * @throws Exception
     */
    public function create(array $data)
    {
        try {
            $transfer = DirectTransfer::create([
                'token' => $data['token'],
                'pin' => Hash::make($data['pin']),
                'expires_at' => $data['expires_at'],
                'used' => false
            ]);

            $transfer->files()->attach($data['files']);

            return $transfer;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🔒️ Valida el PIN de una transferencia
     * 
     * @param string $token
     * @param string $pin
     * @return DirectTransfer
     * @throws Exception
     */
    public function validatePin($token, $pin)
    {
        try {
            $transfer = DirectTransfer::where('token', $token)
                ->where('expires_at', '>', now())
                // ->where('used', false) // Descomentar si se desea marcar como usado despues de las pruebas
                ->with('files')
                ->firstOrFail();

            if (!Hash::check($pin, $transfer->pin)) {
                throw new Exception('Invalid PIN');
            }

            // Marcar como usado ? Descomentar si se desea marcar como usado despues de las pruebas
            // $transfer->used = true;
            // $transfer->save();

            return $transfer;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🔒️ Encuentra una transferencia por token
     * 
     * @param string $token
     * @return DirectTransfer
     * @throws Exception
     */
    public function findTransfer($token)
    {
        try {
            $transfer = DirectTransfer::where('token', $token)
                ->where('expires_at', '>', now())
                ->with('files')
                ->firstOrFail();

            return $transfer;
        } catch (Exception $e) {
            throw new Exception('Transfer not found or expired');
        }
    }

    /**
     * Limpia los recursos de una transferencia usada
     * 
     * @param DirectTransfer $transfer
     * @return void
     * @throws Exception
     */
    public function cleanupUsedTransfer(DirectTransfer $transfer)
    {
        try {
            if ($transfer->used) {
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

    /**
     * Elimina transferencias expiradas
     * 
     * @return void
     * @throws Exception
     */
    public function deleteExpiredTransfers()
    {
        try {
            $expiredTransfers = DirectTransfer::where('expires_at', '<', now())->get();

            foreach ($expiredTransfers as $transfer) {
                foreach ($transfer->files as $file) {
                    $this->r2Service->delete($file->storage_path);
                }

                $transfer->files()->delete();
                $transfer->delete();
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}