<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfilePictureService
{
    private const MAX_DIMENSION = 400;

    private const JPEG_QUALITY = 80;

    /**
     * Compress and store a profile picture. Returns the storage path.
     */
    public function store(UploadedFile $file, User $user): string
    {
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('Unable to process the uploaded image.');
        }

        $compressed = $this->compress($source);
        imagedestroy($source);

        $path = "profile-pictures/{$user->id}.jpg";

        $this->deleteFile($user->profile_picture);

        ob_start();
        imagejpeg($compressed, null, self::JPEG_QUALITY);
        $contents = ob_get_clean();
        imagedestroy($compressed);

        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    public function delete(User $user): void
    {
        $this->deleteFile($user->profile_picture);
        $user->update(['profile_picture' => null]);
    }

    private function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  \GdImage  $source
     */
    private function compress(\GdImage $source): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        [$targetWidth, $targetHeight] = $this->scaledDimensions($width, $height);

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

    /**
     * @return array{0: int, 1: int}
     */
    private function scaledDimensions(int $width, int $height): array
    {
        if ($width <= self::MAX_DIMENSION && $height <= self::MAX_DIMENSION) {
            return [$width, $height];
        }

        $ratio = min(self::MAX_DIMENSION / $width, self::MAX_DIMENSION / $height);

        return [
            (int) round($width * $ratio),
            (int) round($height * $ratio),
        ];
    }
}
