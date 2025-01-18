<?php

namespace App\Http\Controllers\CronJob;

use App\Http\Controllers\Controller;
use App\Services\UploadService;
use App\Services\TransferService;
use Exception;
use Illuminate\Http\JsonResponse;

class CronController extends Controller
{
    protected $uploadService;
    protected $transferService;

    public function __construct(
        UploadService $uploadService,
        TransferService $transferService
    ) {
        $this->uploadService = $uploadService;
        $this->transferService = $transferService;
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
}
