<?php

namespace App\Services\P2P;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Exception;

class P2PService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = Redis::connection();
    }

    /**
     * Crear una nueva sesión P2P
     */
    public function createSession()
    {
        $sessionId = Str::uuid();

        $session = [
            'id' => $sessionId,
            'created_at' => now()->timestamp,
            'status' => 'waiting'
        ];

        // Guardar sesión en Redis con TTL de 5 minutos
        $this->redis->setex(
            "p2p:session:{$sessionId}",
            300,
            json_encode($session)
        );

        return (object) $session;
    }

    /**
     * Manejar respuesta a una oferta P2P
     */
    public function handleAnswer(string $sessionId, string $answer)
    {
        $session = $this->redis->get("p2p:session:{$sessionId}");

        if (!$session) {
            throw new Exception('Session not found or expired');
        }

        $session = json_decode($session);
        $session->answer = $answer;
        $session->status = 'connected';

        $this->redis->setex(
            "p2p:session:{$sessionId}",
            300,
            json_encode($session)
        );

        return $session;
    }

    /**
     * Intercambiar candidatos ICE
     */
    public function exchangeICECandidate(string $sessionId, string $candidate)
    {
        $candidates = $this->redis->lrange("p2p:ice:{$sessionId}", 0, -1);
        $this->redis->rpush("p2p:ice:{$sessionId}", $candidate);
        $this->redis->expire("p2p:ice:{$sessionId}", 300);

        return $candidates;
    }
}
