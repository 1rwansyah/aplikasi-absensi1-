<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeaveWhatsappNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->employeeUser = User::factory()->create(['name' => 'Budi Karyawan']);
        $this->employeeUser->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-001',
            'name' => 'Budi Karyawan',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-06 08:15:00', 'Asia/Jakarta'));
    }

    public function test_sick_leave_with_image_sends_whatsapp_group_image(): void
    {
        Http::fake([
            'http://wa-bot.test/*' => Http::response(['success' => true]),
        ]);

        // Submit leave - no WhatsApp sent yet
        $response = $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'sick',
            'leave_note' => 'Demam tinggi sejak pagi hari ini',
            'doctor_note' => UploadedFile::fake()->image('surat-dokter.jpg'),
            'latitude' => -6.2,
            'longitude' => 106.816666,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // No WhatsApp sent on submission (only check wa-bot requests)
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'wa-bot.test'));

        // Create HR user and approve
        $hrUser = User::factory()->create();
        $hrUser->roles()->attach(Role::where('name', 'hr')->firstOrFail());

        $attendance = $this->employeeUser->attendances()->latest()->first();

        // HR approves - WhatsApp should be sent
        $this->actingAs($hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'done',
        ]);

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/send-group-image')) {
                return false;
            }

            $caption = $request['caption'] ?? '';

            return str_contains($caption, 'LAPORAN SAKIT')
                && str_contains($caption, 'Budi Karyawan')
                && str_contains($caption, 'Demam tinggi sejak pagi hari ini')
                && str_contains($caption, 'Jam: 08:15')
                && str_contains((string) ($request['imageUrl'] ?? ''), 'storage/doctor-notes/');
        });

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/send-group')
            && ! str_contains($request->url(), '/send-group-image'));
    }

    public function test_permission_leave_with_pdf_sends_whatsapp_text_only(): void
    {
        Http::fake([
            'http://wa-bot.test/*' => Http::response(['success' => true]),
        ]);

        // Submit leave - no WhatsApp sent yet
        $response = $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'permission',
            'leave_note' => 'Urusan keluarga yang mendesak hari ini',
            'doctor_note' => UploadedFile::fake()->create('bukti-izin.pdf', 100, 'application/pdf'),
            'latitude' => -6.2,
            'longitude' => 106.816666,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // No WhatsApp sent on submission (only check wa-bot requests)
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'wa-bot.test'));

        // Create HR user and approve
        $hrUser = User::factory()->create();
        $hrUser->roles()->attach(Role::where('name', 'hr')->firstOrFail());

        $attendance = $this->employeeUser->attendances()->latest()->first();

        // HR approves - WhatsApp should be sent
        $this->actingAs($hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'done',
        ]);

        Http::assertSent(function (Request $request) {
            if (! str_ends_with($request->url(), '/send-group')) {
                return false;
            }

            $message = $request['message'] ?? '';

            return str_contains($message, 'LAPORAN IZIN')
                && str_contains($message, 'Urusan keluarga yang mendesak hari ini')
                && str_contains($message, 'PDF tersimpan di sistem HRIS');
        });

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/send-group-image'));
    }

    public function test_submit_leave_succeeds_when_whatsapp_send_fails(): void
    {
        Http::fake([
            'http://wa-bot.test/*' => Http::response(['error' => 'bot down'], 500),
        ]);

        // Submit leave - no WhatsApp sent on submission
        $response = $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'sick',
            'leave_note' => 'Demam tinggi sejak pagi hari ini',
            'doctor_note' => UploadedFile::fake()->image('surat-dokter.png'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionMissing('error');

        // No WhatsApp sent on submission (only check wa-bot requests)
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'wa-bot.test'));

        // Create HR user and approve - WhatsApp fails but verification succeeds
        $hrUser = User::factory()->create();
        $hrUser->roles()->attach(Role::where('name', 'hr')->firstOrFail());

        $attendance = $this->employeeUser->attendances()->latest()->first();

        $verifyResponse = $this->actingAs($hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'done',
        ]);

        $verifyResponse->assertRedirect();
        $verifyResponse->assertSessionHas('success');

        // WhatsApp was attempted but failed
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/send-group-image'));
    }
}
