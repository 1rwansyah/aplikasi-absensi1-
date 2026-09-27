<?php

namespace App\Services;

use App\DTOs\VerificationPhotoOverlay;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class AttendanceVerificationPhotoOverlayService
{
    private const FONT_RELATIVE_PATH = 'fonts/LiberationSans-Bold.ttf';

    private const REFERENCE_MIN_DIMENSION = 640;

    private const ADDRESS_MAX_LINES = 2;

    /**
     * @var list<array{int, int}>
     */
    private const SHADOW_OFFSETS = [
        [1, 1],
    ];

    public function apply(string $binary, VerificationPhotoOverlay $overlay): string
    {
        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            throw new \RuntimeException('Ekstensi GD atau Imagick tidak tersedia.');
        }

        $fontPath = $this->fontPath();

        if ($fontPath === null) {
            throw new \RuntimeException('Font overlay foto verifikasi tidak ditemukan.');
        }

        $manager = new ImageManager(
            extension_loaded('gd') ? new GdDriver : new ImagickDriver,
        );

        $image = $manager->read($binary);
        $width = $image->width();
        $height = $image->height();
        $sizes = $this->responsiveSizes($width, $height);

        $addressLines = $this->wrapAddress($overlay->address, $sizes['addressMaxChars']);
        $layout = $this->layoutMetrics($overlay, $addressLines, $sizes, $width, $height);

        // $this->drawSubtleBackdrop($image, $layout);

        $this->drawShadowedText(
            $image,
            $overlay->time,
            $layout['timeX'],
            $layout['timeY'],
            $sizes['time'],
            $fontPath,
        );

        $image->drawLine(function ($line) use ($layout, $sizes): void {
            $line->from($layout['separatorX'], $layout['separatorTop']);
            $line->to($layout['separatorX'], $layout['separatorBottom']);
            $line->color('rgba(255, 220, 80, 0.92)');
            $line->width(max(2, (int) round(2 * $sizes['scale'])));
        });

        $this->drawShadowedText(
            $image,
            $overlay->dayLabel,
            $layout['metaX'],
            $layout['dayY'],
            $sizes['day'],
            $fontPath,
        );

        $this->drawShadowedText(
            $image,
            $overlay->dateLabel,
            $layout['metaX'],
            $layout['dateY'],
            $sizes['date'],
            $fontPath,
        );

        $addressY = $layout['addressY'];
        foreach ($addressLines as $line) {
            $this->drawShadowedText(
                $image,
                $line,
                $layout['addressX'],
                $addressY,
                $sizes['address'],
                $fontPath,
            );

            $addressY += $sizes['address'] + $sizes['lineGap'];
        }

        return (string) $image->toJpeg(85);
    }

    /**
     * @param  list<string>  $addressLines
     * @return array<string, int>
     */
    private function layoutMetrics(
        VerificationPhotoOverlay $overlay,
        array $addressLines,
        array $sizes,
        int $width,
        int $height,
    ): array {
        $paddingX = $sizes['paddingX'];
        $paddingBottom = $sizes['paddingBottom'];

        $metaBlockHeight = $sizes['day'] + $sizes['lineGap'] + $sizes['date'];
        $timeWidth = $this->estimateTextWidth($overlay->time, $sizes['time']);
        $metaWidth = max(
            $this->estimateTextWidth($overlay->dayLabel, $sizes['day']),
            $this->estimateTextWidth($overlay->dateLabel, $sizes['date']),
        );

        $mainRowHeight = max($sizes['time'], $metaBlockHeight);
        $addressBlockHeight = count($addressLines) > 0
            ? (count($addressLines) * $sizes['address']) + ((count($addressLines) - 1) * $sizes['lineGap'])
            : 0;

        $totalHeight = $mainRowHeight + $sizes['blockGap'] + $addressBlockHeight;
        $blockBottom = $height - $paddingBottom;
        $blockTop = max($sizes['paddingTop'], $blockBottom - $totalHeight);

        $timeX = $paddingX;
        $timeY = $blockTop + (int) round(($mainRowHeight - $sizes['time']) / 2);

        $separatorX = $timeX + $timeWidth + $sizes['separatorGap'];
        $metaX = $separatorX + $sizes['separatorGap'];

        $dayY = $blockTop;
        $dateY = $blockTop + $sizes['day'] + $sizes['lineGap'];

        $clusterRight = $metaX + $metaWidth + $paddingX;
        $clusterWidth = min($width - $paddingX, $clusterRight - $paddingX);

        return [
            'blockTop' => $blockTop,
            'blockBottom' => $blockBottom,
            'clusterLeft' => $paddingX,
            'clusterWidth' => max($clusterWidth, $width - ($paddingX * 2)),
            'timeX' => $timeX,
            'timeY' => $timeY,
            'separatorX' => $separatorX,
            'separatorTop' => $blockTop,
            'separatorBottom' => $blockTop + $metaBlockHeight,
            'metaX' => $metaX,
            'dayY' => $dayY,
            'dateY' => $dateY,
            'addressX' => $paddingX,
            'addressY' => $blockTop + $mainRowHeight + $sizes['blockGap'],
        ];
    }

    /**
     * @param  array<string, int>  $layout
     */
    private function drawSubtleBackdrop(ImageInterface $image, array $layout): void
    {
        $pad = 8;
        $backdropTop = max(0, $layout['blockTop'] - $pad);
        $backdropHeight = ($layout['blockBottom'] - $backdropTop) + $pad;

        $image->drawRectangle($layout['clusterLeft'], $backdropTop, function ($rectangle) use ($layout, $backdropHeight): void {
            $rectangle->size($layout['clusterWidth'], $backdropHeight);
            $rectangle->background('rgba(0, 0, 0, 0.18)');
        });
    }

    private function drawShadowedText(
        ImageInterface $image,
        string $text,
        int $x,
        int $y,
        int $size,
        string $fontPath,
    ): void {
        foreach (self::SHADOW_OFFSETS as [$offsetX, $offsetY]) {
            $this->drawPlainText(
                $image,
                $text,
                $x + $offsetX,
                $y + $offsetY,
                $size,
                $fontPath,
                'rgba(0, 0, 0, 0.35)',
            );
        }

        $this->drawPlainText($image, $text, $x, $y, $size, $fontPath, 'ffffff');
    }

    private function drawPlainText(
        ImageInterface $image,
        string $text,
        int $x,
        int $y,
        int $size,
        string $fontPath,
        string $color,
    ): void {
        $image->text($text, $x, $y, function ($font) use ($fontPath, $size, $color): void {
            $font->file($fontPath);
            $font->size($size);
            $font->color($color);
            $font->align('left');
            $font->valign('top');
        });
    }

    /**
     * @return array{
     *     scale: float,
     *     time: int,
     *     day: int,
     *     date: int,
     *     address: int,
     *     paddingX: int,
     *     paddingBottom: int,
     *     paddingTop: int,
     *     lineGap: int,
     *     blockGap: int,
     *     separatorGap: int,
     *     addressMaxChars: int
     * }
     */
    private function responsiveSizes(int $width, int $height): array
    {
        $scale = min($width, $height) / self::REFERENCE_MIN_DIMENSION;
        $scale = max(0.72, min(1.35, $scale));

        return [
            'scale' => $scale,
            'time' => max(34, (int) round(56 * $scale)),
            'day' => max(15, (int) round(21 * $scale)),
            'date' => max(14, (int) round(19 * $scale)),
            'address' => max(12, (int) round(16 * $scale)),
            'paddingX' => max(12, (int) round(18 * $scale)),
            'paddingBottom' => max(14, (int) round(22 * $scale)),
            'paddingTop' => max(8, (int) round(12 * $scale)),
            'lineGap' => max(3, (int) round(5 * $scale)),
            'blockGap' => max(6, (int) round(10 * $scale)),
            'separatorGap' => max(8, (int) round(12 * $scale)),
            'addressMaxChars' => max(28, (int) round($width / 11)),
        ];
    }

    private function estimateTextWidth(string $text, int $fontSize): int
    {
        return (int) round(mb_strlen($text) * $fontSize * 0.56);
    }

    /**
     * @return list<string>
     */
    private function wrapAddress(string $address, int $maxChars): array
    {
        $address = trim($address);

        if ($address === '') {
            return ['Lokasi tidak tersedia'];
        }

        $wrapped = wordwrap($address, $maxChars, "\n", true);
        $lines = explode("\n", $wrapped);

        if (count($lines) <= self::ADDRESS_MAX_LINES) {
            return $lines;
        }

        $lines = array_slice($lines, 0, self::ADDRESS_MAX_LINES);
        $lastIndex = self::ADDRESS_MAX_LINES - 1;
        $lines[$lastIndex] = mb_strimwidth($lines[$lastIndex], 0, $maxChars - 3).'...';

        return $lines;
    }

    private function fontPath(): ?string
    {
        $path = resource_path(self::FONT_RELATIVE_PATH);

        return is_file($path) ? $path : null;
    }
}
