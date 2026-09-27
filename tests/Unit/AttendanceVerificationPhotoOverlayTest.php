<?php

namespace Tests\Unit;

use App\DTOs\VerificationPhotoOverlay;
use App\Services\AttendanceVerificationPhotoOverlayService;
use App\Services\AttendanceVerificationPhotoService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceVerificationPhotoOverlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            $this->markTestSkipped('GD atau Imagick diperlukan untuk test overlay foto.');
        }

        if (! is_file(resource_path('fonts/LiberationSans-Bold.ttf'))) {
            $this->markTestSkipped('Font overlay tidak tersedia.');
        }
    }

    public function test_apply_returns_valid_jpeg_with_same_dimensions(): void
    {
        $binary = $this->sampleJpegBinary(480, 640);
        $originalSize = getimagesizefromstring($binary);

        $service = app(AttendanceVerificationPhotoOverlayService::class);

        $result = $service->apply($binary, new VerificationPhotoOverlay(
            time: '08:15',
            dateLabel: '06 Juni 2026',
            dayLabel: 'Sabtu',
            address: 'Jl. Contoh No. 1, Jakarta Pusat',
        ));

        $resultSize = getimagesizefromstring($result);

        $this->assertSame('image/jpeg', $resultSize['mime']);
        $this->assertSame($originalSize[0], $resultSize[0]);
        $this->assertSame($originalSize[1], $resultSize[1]);
        $this->assertNotSame($binary, $result);
    }

    public function test_overlay_does_not_cover_center_of_image(): void
    {
        $binary = $this->sampleJpegBinary(480, 640);

        $before = imagecreatefromstring($binary);
        $centerBefore = imagecolorat($before, 240, 320);

        $result = app(AttendanceVerificationPhotoOverlayService::class)->apply($binary, new VerificationPhotoOverlay(
            time: '20:57',
            dateLabel: '07 Juni 2026',
            dayLabel: 'Minggu',
            address: 'Ciputat Timur, Tangerang Selatan',
        ));

        $after = imagecreatefromstring($result);
        $centerAfter = imagecolorat($after, 240, 320);
        $bottomBefore = imagecolorat($before, 30, 610);
        $bottomAfter = imagecolorat($after, 30, 610);

        imagedestroy($before);
        imagedestroy($after);

        $this->assertSame($centerBefore, $centerAfter);
        $this->assertNotSame($bottomBefore, $bottomAfter);
    }

    public function test_store_base64_with_overlay_persists_file(): void
    {
        Storage::fake('public');

        $binary = $this->sampleJpegBinary(320, 240);
        $base64 = 'data:image/jpeg;base64,'.base64_encode($binary);

        $path = app(AttendanceVerificationPhotoService::class)->storeBase64WithOverlay(
            $base64,
            'clock-in-1',
            new VerificationPhotoOverlay(
                time: '17:30',
                dateLabel: '06 Juni 2026',
                dayLabel: 'Sabtu',
                address: 'Kantor Pusat',
            ),
        );

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $stored = Storage::disk('public')->get($path);
        $size = getimagesizefromstring($stored);

        $this->assertSame('image/jpeg', $size['mime']);
    }

    public function test_store_base64_with_overlay_falls_back_to_original_when_overlay_fails(): void
    {
        Storage::fake('public');

        $binary = $this->sampleJpegBinary(320, 240);
        $base64 = 'data:image/jpeg;base64,'.base64_encode($binary);

        $this->mock(AttendanceVerificationPhotoOverlayService::class, function ($mock): void {
            $mock->shouldReceive('apply')
                ->once()
                ->andThrow(new \RuntimeException('overlay failed'));
        });

        $path = app(AttendanceVerificationPhotoService::class)->storeBase64WithOverlay(
            $base64,
            'clock-out-9',
            new VerificationPhotoOverlay(
                time: '07:00',
                dateLabel: '06 Juni 2026',
                dayLabel: 'Sabtu',
                address: 'Lokasi fallback',
            ),
        );

        $this->assertNotNull($path);
        $this->assertSame($binary, Storage::disk('public')->get($path));
    }

    private function sampleJpegBinary(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 180, 200, 220);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);

        ob_start();
        imagejpeg($image, quality: 90);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
