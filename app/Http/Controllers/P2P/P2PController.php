<?php

namespace App\Http\Controllers\P2P;

use App\Http\Controllers\Controller;
use App\Services\EncryptionService;
use App\Services\P2P\P2PService;
use Exception;
use Illuminate\Http\Request;

class P2PController extends Controller
{
    protected $p2pService;
    protected $encryptionService;

    public function __construct(
        P2PService $p2pService,
        EncryptionService $encryptionService
    ) {
        $this->p2pService = $p2pService;
        $this->encryptionService = $encryptionService;
    }

    /**
     * Iniciar una sesión P2P
     */
    public function createSession(Request $request)
    {
        try {
            $session = $this->p2pService->createSession();

            return response()->json([
                'success' => true,
                'session_id' => $session->id,
                'offer' => $session->offer
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating P2P session'
            ], 500);
        }
    }

    /**
     * Responder a una oferta P2P
     */
    public function answerOffer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'answer' => 'required|string'
        ]);

        try {
            $this->p2pService->handleAnswer(
                $request->input('session_id'),
                $request->input('answer')
            );

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error handling P2P answer'
            ], 500);
        }
    }

    /**
     * Intercambiar candidatos ICE
     */
    public function exchangeICE(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'candidate' => 'required|string'
        ]);

        try {
            $this->p2pService->exchangeICECandidate(
                $request->input('session_id'),
                $request->input('candidate')
            );

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exchanging ICE candidates'
            ], 500);
        }
    }
}
