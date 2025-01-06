<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Transfer;
use App\Services\R2Service;
use App\Services\UploadService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class NuclearCleanup extends Command
{
    protected $signature = 'cleanup:nuclear';
    protected $description = '🚨 WARNING: Elimina todos los archivos, transferencias y datos temporales sin condiciones';

    protected $r2Service;
    protected $uploadService;

    public function __construct(R2Service $r2Service, UploadService $uploadService)
    {
        parent::__construct();
        $this->r2Service = $r2Service;
        $this->uploadService = $uploadService;
    }

    public function handle()
    {
        if (!$this->confirm('🚧 ADVERTENCIA: Esta operación eliminará TODOS los datos sin posibilidad de recuperación. ¿Está seguro?')) {
            return;
        }

        try {
            $this->info('Iniciando limpieza nuclear...');

            // 1. Limpiar todos los archivos en R2
            File::chunk(100, function ($files) {
                foreach ($files as $file) {
                    try {
                        $this->r2Service->delete($file->storage_path);
                        $file->delete();
                    } catch (Exception $e) {
                        Log::error("Error eliminando archivo {$file->storage_path}: " . $e->getMessage());
                    }
                }
            });

            // 2. Eliminar transferencias
            Transfer::chunk(100, function ($transfers) {
                foreach ($transfers as $transfer) {
                    $transfer->delete();
                }
            });

            // 3. Limpiar Redis
            $redis = Redis::connection();
            $keys = $redis->keys('upload:*');
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $redis->del($key);
                }
            }

            $tempUploads = $redis->hgetall('temp_uploads');
            if (!empty($tempUploads)) {
                foreach ($tempUploads as $path => $expiry) {
                    try {
                        $this->r2Service->delete($path);
                    } catch (Exception $e) {
                        Log::error("Error eliminando archivo temporal {$path}: " . $e->getMessage());
                    }
                }
                $redis->del('temp_uploads');
            }

            $this->info('✅ Limpieza nuclear completada.');
        } catch (Exception $e) {
            $this->error('Error durante la limpieza nuclear: ' . $e->getMessage());
            Log::error('Error durante la limpieza nuclear: ' . $e->getMessage());
            throw $e;
        }
    }
}
