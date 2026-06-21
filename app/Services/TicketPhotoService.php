<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

class TicketPhotoService
{
    private const MAX_DIMENSION = 1600;

    private const JPEG_QUALITY = 82;

    private const MAX_PHOTOS_PER_TICKET = 8;

    /**
     * @param  array<int, UploadedFile>  $files
     * @return array{photos: array<int, TicketPhoto>, stored: int, skipped: int}
     */
    public function storeMany(Ticket $ticket, array $files): array
    {
        $existing = $ticket->photos()->count();
        $remaining = self::MAX_PHOTOS_PER_TICKET - $existing;

        if ($remaining <= 0) {
            return ['photos' => [], 'stored' => 0, 'skipped' => count($files)];
        }

        $requested = count($files);
        $files = array_slice($files, 0, $remaining);
        $photos = [];
        $sortOrder = $existing;

        foreach ($files as $file) {
            $photos[] = $this->store($ticket, $file, $sortOrder++);
        }

        return [
            'photos' => $photos,
            'stored' => count($photos),
            'skipped' => max(0, $requested - count($photos)),
        ];
    }

    public function store(Ticket $ticket, UploadedFile $file, int $sortOrder = 0): TicketPhoto
    {
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($source === false) {
            \Illuminate\Support\Facades\Log::warning('Ticket photo processing failed', [
                'ticket_id' => $ticket->id,
                'original_name' => $file->getClientOriginalName(),
            ]);

            throw new RuntimeException('Unable to process the uploaded image.');
        }

        $processed = $this->resize($source);
        imagedestroy($source);

        $filename = Str::uuid() . '.jpg';
        $path = "ticket-photos/{$ticket->id}/{$filename}";

        ob_start();
        imagejpeg($processed, null, self::JPEG_QUALITY);
        $contents = ob_get_clean();
        imagedestroy($processed);

        \Illuminate\Support\Facades\Storage::disk('public')->put($path, $contents);

        return $ticket->photos()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'sort_order' => $sortOrder,
        ]);
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
