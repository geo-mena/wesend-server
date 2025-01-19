<?php

namespace App\Services\QR;

use App\Models\DirectTransfer;
use Illuminate\Support\Facades\Hash;
use App\Services\EncryptionService;
use App\Services\R2Service;
use Exception;

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
                ->where('used', false)
                ->with('files')
                ->firstOrFail();

            if (!Hash::check($pin, $transfer->pin)) {
                throw new Exception('Invalid PIN');
            }

            //! Marcar como usado
            $transfer->used = true;
            $transfer->save();

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
            throw $e;
        }
    }

    /**
     * 🔒️ Limpia los recursos de una transferencia usada
     * 
     * @param DirectTransfer $transfer
     * @return void
     * @throws Exception
     */
    public function cleanupUsedTransfer(DirectTransfer $transfer)
    {
        try {
            if ($transfer->used) {
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

    /**
     * 🔒️ Elimina transferencias expiradas
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

    /**
     * 🔒️ Ejecuta todas las tareas de limpieza de transferencias
     * 
     * @return void
     * @throws Exception
     */
    public function cleanDirectTransfers()
    {
        try {
            $usedTransfers = DirectTransfer::where('used', true)->get();
            foreach ($usedTransfers as $transfer) {
                $this->cleanupUsedTransfer($transfer);
            }

            $this->deleteExpiredTransfers();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
