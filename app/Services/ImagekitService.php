<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use ImageKit\ImageKit;

class ImagekitService
{
    protected $imagekit;

    public function __construct()
    {
        $this->imagekit = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.endpoint')
        );
    }

    public function upload(array $params)
    {
        try {
            $response = $this->imagekit->upload([
                'file' => $params['file'],
                'fileName' => $params['fileName'],
                'useUniqueFileName' => true
            ]);

            Log::debug('ImageKit response:', ['response' => json_encode($response)]);
        
            return $response;
        } catch (Exception $e) {
            Log::error('ImageKit upload error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getFile(string $path)
    {
        return file_get_contents($path);
    }

    public function deleteFile(string $fileId)
    {
        return $this->imagekit->deleteFile($fileId);
    }
}
