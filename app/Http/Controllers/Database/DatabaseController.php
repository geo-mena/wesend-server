<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;
use App\Models\TemporaryDatabase;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DatabaseController extends Controller
{
    public function create()
    {
        try {
            $payload = [
                'endpoints' => [
                    [
                        'type' => 'read_write'
                    ]
                ],
                'branch' => [
                    'parent_id' => config('services.neon.parent_id')
                ]
            ];

            // 2. Crear branch en Neon
            $neonResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.neon.key'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->timeout(15)
                ->post('https://console.neon.tech/api/v2/projects/' . config('services.neon.project') . '/branches', $payload);

            if ($neonResponse->failed()) {
                Log::error('Error Neon API', [
                    'status' => $neonResponse->status(),
                    'response' => $neonResponse->json()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error creating database'
                ], 500);
            }

            if ($neonResponse->failed()) {
                Log::error('Error Neon API', [
                    'status' => $neonResponse->status(),
                    'response' => $neonResponse->body()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error creating database'
                ], 500);
            }

            $branchData = $neonResponse->json()['branch'];

            // 3. Construir URL segura
            $connectionUrl = sprintf(
                "postgresql://%s:%s@%s/%s?sslmode=require",
                $branchData['role_name'] ?? 'default_user',
                $branchData['role_password'] ?? 'default_password',
                $branchData['host'] ?? 'default_host',
                $branchData['database_name'] ?? 'default_database'
            );

            // 4. Guardar en base de datos local
            $tempDb = TemporaryDatabase::create([
                'id' => Str::uuid(),
                'connection_url' => $connectionUrl,
                'branch_id' => $branchData['id'],
                'expires_at' => Carbon::now()->addHour(),
            ]);

            $dataResponse = [
                'id' => $tempDb->id,
                'connection_url' => $connectionUrl,
                'expires_at' => $tempDb->expires_at->toIso8601String(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Database will be automatically deleted in 1 hour',
                'data' => $dataResponse
            ]);
        } catch (Exception $e) {
            Log::error('Database creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $database = TemporaryDatabase::findOrFail($id);
        return response()->json($database);
    }
}
