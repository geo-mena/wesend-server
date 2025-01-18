<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Redis;

class RateLimitService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * 🔒️ Verifica si una IP puede subir más archivos
     * 
     * @param string $ip
     * @param int $fileSize Tamaño en bytes
     * @return array
     * @throws Exception
     */
    public function canUpload(string $ip, int $fileSize): array
    {
        try {
            $key = "upload_limit:{$ip}";
            $period = 86400; // 24 horas en segundos
            $limit = 1073741824; // 1 GB en bytes

            //! Obtener uso actual
            $currentUsage = (int) $this->redis->get($key) ?? 0;
            $remaining = $limit - $currentUsage;

            //! Verificar si excedería el límite
            if ($fileSize > $remaining) {
                $remainingTime = $this->redis->ttl($key);

                return [
                    'allowed' => false,
                    'remaining_bytes' => $remaining,
                    'reset_in' => $remainingTime,
                    'message' => 'El archivo excede el espacio disponible. Tienes ' . round($remaining / 1024 / 1024, 2) . ' MB disponibles'
                ];
            }

            return [
                'allowed' => true,
                'remaining_bytes' => $remaining,
                'reset_in' => $this->redis->ttl($key),
                'message' => null
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🔒️ Registra el uso de bytes para una IP
     * 
     * @param string $ip
     * @param int $bytes
     * @return void
     * @throws Exception
     */
    public function trackUsage(string $ip, int $bytes): void
    {
        try {
            $key = "upload_limit:{$ip}";
            $period = 86400; // 24 horas

            $this->redis->multi();

            //! Incrementar contador
            $this->redis->incrby($key, $bytes);

            //! Establecer TTL si es nueva key
            $this->redis->expire($key, $period);

            $this->redis->exec();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * 🚩 Limpia registros huérfanos
     * 
     * @return void
     * @throws Exception
     */
    public function cleanOrphanedRecords(): void
    {
        try {
            $pattern = 'upload_limit:*';
            $keys = $this->redis->keys($pattern);

            foreach ($keys as $key) {
                if ($this->redis->ttl($key) <= 0) {
                    $this->redis->del($key);
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}
