<?php

namespace App\Http\Controllers\Database;

use App\Http\Controllers\Controller;
use App\Services\Database\DatabaseService;
use Illuminate\Support\Facades\Http;
use App\Models\TemporaryDatabase;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class DatabaseController extends Controller
{
    protected $databaseService;

    public function __construct(
        DatabaseService $databaseService
    ) {
        $this->databaseService = $databaseService;
    }

    /**
     * 🔥 Create a new database
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function create(Request $request)
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

            $neonResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.neon.key'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])
                ->timeout(15)
                ->post('https://console.neon.tech/api/v2/projects/' . config('services.neon.project') . '/branches', $payload);

            if ($neonResponse->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error creating database'
                ], 500);
            }

            if ($neonResponse->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Error creating database'
                ], 500);
            }

            $responseData = $neonResponse->json();
            $branchData = $responseData['branch'] ?? null;

            if (!$branchData || !isset($branchData['id'])) {
                throw new Exception("Branch ID was not received in response from Neon.");
            }

            $branchId = $branchData['id'];

            if (isset($responseData['connection_uris']) && count($responseData['connection_uris']) > 0) {
                $connectionUrl = $responseData['connection_uris'][0]['connection_uri'];
            } else {
                $connectionUrl = sprintf(
                    "postgresql://%s:%s@%s/%s?sslmode=require",
                    $branchData['role_name']     ?? 'default_user',
                    $branchData['role_password'] ?? 'default_password',
                    $branchData['host']          ?? 'default_host',
                    $branchData['database_name'] ?? 'default_database'
                );
            }

            $tempDb = TemporaryDatabase::create([
                'id' => Str::uuid(),
                'connection_url' => $connectionUrl,
                'branch_id' => $branchId,
                'expires_at' => Carbon::now()->addHour(),
            ]);

            $this->databaseService->registerDatabase($request->ip(), $tempDb->id);

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
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔥 Show a database
     *
     * @param $id
     * @return JsonResponse
     * @throws Exception
     */
    public function show($id)
    {
        try {
            $database = TemporaryDatabase::findOrFail($id);

            if (Carbon::now()->isAfter($database->expires_at)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Database has expired',
                    'expired_at' => $database->expires_at
                ], 410);
            }

            if (empty($database->connection_url)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Database connection URL is missing'
                ], 500);
            }

            return response()->json($database);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database not found ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * 🔥 Get user databases
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function getDatabases(Request $request)
    {
        try {
            $databases = $this->databaseService->getActiveDatabase($request->ip());

            return response()->json([
                'status' => 'success',
                'data' => $databases
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error retrieving databases: ' . $e->getMessage()
            ], 500);
        }
    }
}
