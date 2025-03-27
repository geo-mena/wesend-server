<?php

namespace App\Http\Controllers\Base64;

use App\Http\Controllers\Controller;
use App\Services\Base64\Base64ImageService;
use Illuminate\Http\Request;
use Exception;

class Base64ImageController extends Controller
{
    protected $base64Service;

    /**
     * Constructor con inyección de dependencias
     * 
     * @param Base64ImageService $base64Service
     * @return void
     */
    public function __construct(Base64ImageService $base64Service)
    {
        $this->base64Service = $base64Service;
    }

    /**
     * Decodificar un string Base64 y guardar la imagen en el sistema de archivos
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function decode(Request $request)
    {
        try {
            $request->validate([
                'base64_code' => 'required|string',
            ]);

            $base64String = $request->input('base64_code');

            $imageInfo = $this->base64Service->decodeAndSave($base64String, 'public', 'temp');

            if (!$imageInfo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El string Base64 no es válido o no se pudo procesar la imagen'
                ], 400);
            }

            $response = [
                'file_name' => $imageInfo['file_name'],
                'mime_type' => $imageInfo['mime_type'],
                'extension' => $imageInfo['extension'],
                'width' => $imageInfo['width'],
                'height' => $imageInfo['height'],
                'size' => $imageInfo['size'],
                'preview_url' => $imageInfo['url'],
                'download_url' => route('api.base64.download', $imageInfo['file_name']),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Image processed successfully',
                'data' => $response
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la imagen'
            ], 500);
        }
    }

    /**
     * Descargar un archivo del sistema de archivos
     *
     * @param string $fileName
     * @return BinaryFileResponse|JsonResponse
     * @throws Exception
     */
    public function download($fileName)
    {
        try {
            $filePath = 'temp/' . $fileName;

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
                'message' => 'Archivo no encontrado'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo'
            ], 500);
        }
    }
}
