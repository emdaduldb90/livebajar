<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageWebpOptimizer
{
    public const TARGET_BYTES = 60_000;
    public const MAX_BYTES = 70_000;
    private const MAX_DIMENSION = 1280;
    private const MIN_DIMENSION = 480;

    /** @return array{path:string,size_bytes:int,mime_type:string,original_name:string} */
    public function storeUpload(UploadedFile $file, string $directory): array
    {
        $temporaryPath = $file->getRealPath();
        if ($temporaryPath === false) {
            throw new RuntimeException('The uploaded image could not be read.');
        }

        $path = $this->optimiseFile($temporaryPath, $directory, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        return [
            'path' => $path,
            'size_bytes' => Storage::disk('public')->size($path),
            'mime_type' => 'image/webp',
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
        ];
    }

    public function optimiseExisting(string $path): string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            throw new RuntimeException('Image file not found: '.$path);
        }

        $newPath = $this->optimiseFile($disk->path($path), dirname($path), pathinfo($path, PATHINFO_FILENAME));
        if ($newPath !== $path) {
            $disk->delete($path);
        }

        return $newPath;
    }

    public function supported(): bool
    {
        return function_exists('imagecreatefromstring') && function_exists('imagewebp');
    }

    private function optimiseFile(string $sourcePath, string $directory, string $name): string
    {
        if (! $this->supported()) {
            throw new RuntimeException('PHP GD with WebP support is required. Enable the GD extension in your hosting PHP selector.');
        }

        $metadata = @getimagesize($sourcePath);
        if ($metadata === false || ! in_array($metadata[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('Only valid JPG, PNG and WebP image files can be optimized.');
        }

        $contents = file_get_contents($sourcePath);
        $source = $contents === false ? false : @imagecreatefromstring($contents);
        if ($source === false) {
            throw new RuntimeException('The image could not be decoded for WebP optimization.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $ratio = min(1, self::MAX_DIMENSION / max($sourceWidth, $sourceHeight));
        $width = max(self::MIN_DIMENSION, (int) round($sourceWidth * $ratio));
        $height = max(self::MIN_DIMENSION, (int) round($sourceHeight * $ratio));
        $width = min($width, $sourceWidth);
        $height = min($height, $sourceHeight);

        $disk = Storage::disk('public');
        $directory = trim(str_replace('\\', '/', $directory), '/.');
        $directory = $directory === '' ? 'images' : $directory;
        $disk->makeDirectory($directory);
        $destination = $directory.'/'.Str::slug($name ?: 'image').'-'.Str::lower(Str::random(12)).'.webp';
        $destinationPath = $disk->path($destination);
        $destinationDirectory = dirname($destinationPath);
        if (! is_dir($destinationDirectory) || ! is_writable($destinationDirectory)) {
            imagedestroy($source);
            throw new RuntimeException('The image upload folder could not be created or is not writable.');
        }
        $bestPath = null;
        $bestSize = PHP_INT_MAX;

        while (true) {
            $canvas = imagecreatetruecolor($width, $height);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

            foreach ([82, 74, 66, 58, 50, 42] as $quality) {
                if (! @imagewebp($canvas, $destinationPath, $quality)) {
                    imagedestroy($canvas);
                    imagedestroy($source);
                    throw new RuntimeException('Unable to write the optimized WebP file.');
                }
                $size = filesize($destinationPath) ?: PHP_INT_MAX;
                if ($size < $bestSize) {
                    $bestSize = $size;
                    $bestPath = $destinationPath.'.best';
                    copy($destinationPath, $bestPath);
                }
                if ($size <= self::MAX_BYTES) {
                    imagedestroy($canvas);
                    imagedestroy($source);
                    @unlink($bestPath);
                    return $destination;
                }
            }
            imagedestroy($canvas);

            if ($width <= self::MIN_DIMENSION || $height <= self::MIN_DIMENSION) {
                break;
            }
            $width = max(self::MIN_DIMENSION, (int) round($width * 0.82));
            $height = max(self::MIN_DIMENSION, (int) round($height * 0.82));
        }

        imagedestroy($source);
        if ($bestPath === null || ! @rename($bestPath, $destinationPath)) {
            throw new RuntimeException('Unable to save the optimized WebP file.');
        }

        return $destination;
    }
}
