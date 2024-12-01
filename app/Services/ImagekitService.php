<?php

namespace App\Services;

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
        return $this->imagekit->upload([
            'file' => $params['file'],
            'fileName' => $params['fileName'],
            'useUniqueFileName' => true
        ]);
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
