<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeFaceProfileService
{
    public function store(Employee $employee, UploadedFile $photo, int $actorId): void
    {
        [$path, $hash] = $this->storeSanitizedPhoto($photo);

        try {
            $employee->faceProfiles()->create([
                'reference_photo_path' => $path,
                'reference_photo_hash' => $hash,
                'uploaded_by_user_id' => $actorId,
                'uploaded_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }

    /** @return array{string, string} */
    private function storeSanitizedPhoto(UploadedFile $photo): array
    {
        if (! extension_loaded('gd') || ! extension_loaded('exif')) {
            throw ValidationException::withMessages(['photo' => 'Pemrosesan foto belum tersedia. Hubungi administrator.']);
        }

        $jpeg = $photo->getMimeType() === 'image/jpeg';
        $source = $photo->getPathname();
        $image = $jpeg ? @imagecreatefromjpeg($source) : @imagecreatefrompng($source);

        if ($image === false) {
            throw ValidationException::withMessages(['photo' => 'Foto tidak dapat dibaca. Pilih file JPG atau PNG lain.']);
        }

        if ($jpeg) {
            $metadata = @exif_read_data($source);
            $image = $this->orientJpeg($image, (int) ($metadata['Orientation'] ?? 1));
        } else {
            imagesavealpha($image, true);
        }

        $file = tmpfile();
        if ($file === false) {
            throw ValidationException::withMessages(['photo' => 'Foto gagal disimpan. Coba unggah kembali.']);
        }

        try {
            $encoded = $jpeg ? imagejpeg($image, $file, 95) : imagepng($image, $file);
            $path = 'employee-face-profiles/'.Str::uuid().($jpeg ? '.jpg' : '.png');
            $hash = $encoded ? hash_file('sha256', stream_get_meta_data($file)['uri']) : false;

            if ($hash === false || ! rewind($file) || ! Storage::disk('local')->put($path, $file)) {
                throw ValidationException::withMessages(['photo' => 'Foto gagal disimpan. Coba unggah kembali.']);
            }

            return [$path, $hash];
        } finally {
            fclose($file);
        }
    }

    private function orientJpeg(GdImage $image, int $orientation): GdImage
    {
        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        $angle = match ($orientation) {
            3 => 180,
            5, 8 => 90,
            6, 7 => -90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        return imagerotate($image, $angle, 0)
            ?: throw ValidationException::withMessages(['photo' => 'Orientasi foto tidak dapat diproses.']);
    }
}
