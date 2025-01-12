<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RateLimitService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Verifica si una IP puede subir más archivos
     * 
     * @param string $ip
     * @param int $fileSize Tamaño en bytes
     * @return array
     */
    public function canUpload(string $ip, int $fileSize): array
    {
        $key = "upload_limit:{$ip}";
        $period = 86400; // 24 horas en segundos
        $limit = 1073741824; // 1 GB en bytes

        // Obtener uso actual
        $currentUsage = (int) $this->redis->get($key) ?? 0;

        // Verificar si excedería el límite
        if (($currentUsage + $fileSize) > $limit) {
            $remainingTime = $this->redis->ttl($key);

            return [
                'allowed' => false,
                'remaining_bytes' => $limit - $currentUsage,
                'reset_in' => $remainingTime,
                'message' => 'Has excedido el límite de subida de 1 GB por 24 horas'
            ];
        }

        return [
            'allowed' => true,
            'remaining_bytes' => $limit - $currentUsage,
            'reset_in' => $this->redis->ttl($key)
        ];
    }

    /**
     * Registra el uso de bytes para una IP
     * 
     * @param string $ip
     * @param int $bytes
     */
    public function trackUsage(string $ip, int $bytes): void
    {
        $key = "upload_limit:{$ip}";
        $period = 86400; // 24 horas

        $this->redis->multi();

        // Incrementar contador
        $this->redis->incrby($key, $bytes);

        // Establecer TTL si es nueva key
        $this->redis->expire($key, $period);

        $this->redis->exec();
    }
}
