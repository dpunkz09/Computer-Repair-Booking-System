<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class HomepageImageService
{
    private const MAX_DIMENSION = 1920;

    private const JPEG_QUALITY = 85;

    public function storeHero(UploadedFile $file): string
    {
        return $this->store($file, 'homepage/hero');
    }

    public function storeSection(UploadedFile $file, int $index): string
    {
        return $this->store($file, 'homepage/sections/'.$index);
    }

    public function deletePath(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function store(UploadedFile $file, string $directory): string
    {
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('Unable to process the uploaded homepage image.');
        }

        $processed = $this->resize($source);
        imagedestroy($source);

        $filename = Str::uuid().'.jpg';
        $path = $directory.'/'.$filename;

        ob_start();
        imagejpeg($processed, null, self::JPEG_QUALITY);
        $contents = ob_get_clean();
        imagedestroy($processed);

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * @param  \GdImage  $source
     */
    private function resize(\GdImage $source): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $ratio = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height, 1);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        return $canvas;
    }
}
