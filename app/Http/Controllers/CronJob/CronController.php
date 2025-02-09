<?php

namespace App\Http\Controllers\CronJob;

use App\Http\Controllers\Controller;
use App\Services\Database\DatabaseService;
use App\Services\QR\DirectTransferService;
use App\Services\RateLimitService;
use App\Services\UploadService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Exception;

class CronController extends Controller
{
    protected $uploadService;
    protected $transferService;
    protected $rateLimitService;
    protected $directTransferService;
    protected $databaseService;

    public function __construct(
        UploadService $uploadService,
        TransferService $transferService,
        RateLimitService $rateLimitService,
        DirectTransferService $directTransferService,
        DatabaseService $databaseService
    ) {
        $this->uploadService = $uploadService;
        $this->transferService = $transferService;
        $this->rateLimitService = $rateLimitService;
        $this->directTransferService = $directTransferService;
        $this->databaseService = $databaseService;
    }

    /**
     * ⏰ Clean orphaned files
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function cleanOrphanedFiles(): JsonResponse
    {
        try {
            $this->uploadService->cleanOrphanedFiles();

            return response()->json([
                'success' => true,
                'message' => 'Orphaned files cleaned successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup process failed' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⏰ Clean expired transfers of R2 and DB
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function cleanExpiredTransfers(): JsonResponse
    {
        try {
            $this->transferService->deleteExpiredTransfers();

            return response()->json([
                'success' => true,
                'message' => 'Expired transfers cleaned successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup process failed' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⏰ Clean orphaned rate limit records
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function cleanOrphanedRecords(): JsonResponse
    {
        try {
            $this->rateLimitService->cleanOrphanedRecords();

            return response()->json([
                'success' => true,
                'message' => 'Orphaned rate limit records cleaned successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup process failed' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⏰ Clean orphaned rate limit records
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function cleanDirectTransfers(): JsonResponse
    {
        try {
            $this->directTransferService->cleanDirectTransfers();

            return response()->json([
                'success' => true,
                'message' => 'Direct transfers cleaned successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup process failed' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ⏰ Clean expired databases
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function cleanExpiredDatabases(): JsonResponse
    {
        try {
            $this->databaseService->cleanExpiredDatabases();

            return response()->json([
                'success' => true,
                'message' => 'Expired databases cleaned successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cleanup process failed' . $e->getMessage()
            ], 500);
        }
    }
}
