<?php

namespace App\Services\Database;

use Illuminate\Support\Facades\Redis;
use App\Models\TemporaryDatabase;
use Carbon\Carbon;
use Exception;

class DatabaseService
{
    protected $redis;
    protected const TTL = 3600;

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Registra una nueva base de datos para una IP
     * 
     * @param string $ip
     * @param string $databaseId
     * @return void
     * @throws Exception
     */
    public function registerDatabase(string $ip, string $databaseId): void
    {
        try {
            $key = "temp_db:{$ip}";

            $this->redis->multi();
            $this->redis->set($key, $databaseId);
            $this->redis->expire($key, self::TTL);
            $this->redis->exec();
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Obtiene la base de datos activa para una IP
     * 
     * @param string $ip
     * @return array
     * @throws Exception
     */
    public function getActiveDatabase(string $ip): array
    {
        try {
            $key = "temp_db:{$ip}";
            $databaseId = $this->redis->get($key);

            if (!$databaseId) {
                return [
                    'status' => 'error',
                    'message' => 'No active database found',
                    'database' => null
                ];
            }

            $database = TemporaryDatabase::find($databaseId);

            if (!$database || Carbon::now()->isAfter($database->expires_at)) {
                $this->redis->del($key);
                return [
                    'status' => 'error',
                    'message' => 'Database has expired',
                    'database' => null
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Active database found',
                'database' => $database,
                'ttl' => $this->redis->ttl($key)
            ];
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Invalida la base de datos para una IP
     * 
     * @param string $ip
     * @return void
     */
    public function invalidateDatabase(string $ip): void
    {
        try {
            $key = "temp_db:{$ip}";
            $this->redis->del($key);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Limpia registros expirados
     * 
     * @return void
     */
    public function cleanExpiredDatabases(): void
    {
        try {
            $pattern = 'temp_db:*';
            $keys = $this->redis->keys($pattern);

            foreach ($keys as $key) {
                $databaseId = $this->redis->get($key);
                if ($databaseId) {
                    $database = TemporaryDatabase::find($databaseId);
                    if (!$database || Carbon::now()->isAfter($database->expires_at)) {
                        $this->redis->del($key);
                    }
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}
