<?php

namespace App\Services;

use App\Support\SiteSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BrandingImageService
{
    private const MAX_WIDTH = 320;

    private const MAX_HEIGHT = 120;

    private const PNG_COMPRESSION = 8;

    public function storeLogo(UploadedFile $file): string
    {
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('Unable to process the logo image.');
        }

        $resized = $this->resize($source);
        imagedestroy($source);

        $path = 'branding/site-logo.png';

        $this->deleteExistingLogo();

        ob_start();
        imagepng($resized, null, self::PNG_COMPRESSION);
        $contents = ob_get_clean();
        imagedestroy($resized);

        Storage::disk('public')->put($path, $contents);

        SiteSettings::set('logo_path', $path);

        return $path;
    }

    public function deleteLogo(): void
    {
        $this->deleteExistingLogo();
        SiteSettings::set('logo_path', '');
    }

    private function deleteExistingLogo(): void
    {
        $path = SiteSettings::get('logo_path');

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  \GdImage  $source
     */
    private function resize(\GdImage $source): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $ratio = min(self::MAX_WIDTH / $width, self::MAX_HEIGHT / $height, 1);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

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
