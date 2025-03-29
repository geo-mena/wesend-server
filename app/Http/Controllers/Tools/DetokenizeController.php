<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Services\Base64\DetokenizeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class DetokenizeController extends Controller
{
    protected $detokenizeService;

    /**
     * Constructor con inyección de dependencias
     * 
     * @param DetokenizeService $detokenizeService
     * @return void
     */
    public function __construct(DetokenizeService $detokenizeService)
    {
        $this->detokenizeService = $detokenizeService;
    }

    /**
     * Detokenizar una imagen encriptada y devolver sus detalles
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function detokenize(Request $request)
    {
        try {
            $request->validate([
                'bestImageToken' => 'required|string',
                'transactionId' => 'nullable|string',
            ]);

            $bestImageToken = $request->input('bestImageToken');
            $transactionId = $request->input('transactionId');

            // Obtener el buffer de imagen en base64
            $imageBuffer = $this->detokenizeService->detokenizeImage($bestImageToken, $transactionId);

            if (!$imageBuffer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not detokenize the image'
                ], 400);
            }

            // Decodificar y guardar la imagen
            $imageInfo = $this->detokenizeService->decodeAndSaveImage($imageBuffer, 'public', 'detokenized');

            if (!$imageInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not process the detokenized image'
                ], 400);
            }

            $response = [
                'timestamp' => now()->toIso8601String(),
                'file_name' => $imageInfo['file_name'],
                'mime_type' => $imageInfo['mime_type'],
                'extension' => $imageInfo['extension'],
                'width' => $imageInfo['width'],
                'height' => $imageInfo['height'],
                'size' => $imageInfo['size'],
                'preview_url' => $imageInfo['url'],
                'download_url' => route('api.detokenize.download', $imageInfo['file_name']),
                'imageBuffer' => $imageBuffer,
            ];

            if ($transactionId !== null) {
                $response['transactionId'] = $transactionId;
            }

            if (isset($imageInfo['jpeg_quality'])) {
                $response['jpeg_quality'] = $imageInfo['jpeg_quality'];
            }

            if (isset($imageInfo['is_progressive'])) {
                $response['is_progressive_jpeg'] = $imageInfo['is_progressive'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Image detokenized successfully',
                'data' => $response
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error detokenizing the image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar una imagen detokenizada
     *
     * @param string $fileName
     * @return BinaryFileResponse|JsonResponse
     */
    public function download($fileName)
    {
        try {
            $filePath = 'detokenized/' . $fileName;

            if (file_exists(storage_path('app/public/' . $filePath))) {
                $fullPath = storage_path('app/public/' . $filePath);
                $mimeType = mime_content_type($fullPath);

                return response()->download($fullPath, $fileName, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ])->deleteFileAfterSend(true);
            }

            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error downloading the file: ' . $e->getMessage()
            ], 500);
        }
    }
}
