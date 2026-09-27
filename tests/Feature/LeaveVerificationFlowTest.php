<?php

namespace Tests\Feature;

use App\Enums\AttendanceReportStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Notifications\LeaveRejectedNotification;
use App\Services\AttendanceService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\HtmlString;
use Tests\TestCase;

class LeaveVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    private User $hrUser;

    private AttendanceService $attendanceService;

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

        $this->hrUser = User::factory()->create();
        $this->hrUser->roles()->attach(Role::where('name', 'hr')->firstOrFail());

        $this->attendanceService = app(AttendanceService::class);

        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
    }

    public function test_rejected_sick_stays_sick_in_report_status(): void
    {
        $attendance = $this->submitSickLeave();

        $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'no_done',
            'rejection_reason' => 'Dokumen tidak lengkap atau tidak jelas',
        ]);

        $attendance->refresh();

        $this->assertSame(VerificationStatus::NoDone, $attendance->verification_status);
        $this->assertSame(AttendanceReportStatus::Sick, AttendanceReportStatus::fromAttendance($attendance));
        $this->assertSame('Sakit (Ditolak)', AttendanceReportStatus::Sick->labelFor($attendance));
        $this->assertSame('Sakit (Ditolak)', $attendance->statusLabel());

        $this->createRegularSchedule();

        $response = $this->actingAs($this->employeeUser)->get(route('attendance.index'));
        $response->assertOk();
        $response->assertSee('Pengajuan sakit Anda telah ditolak');
        $response->assertSee('Lihat detail pengajuan');
        $response->assertSee('Alasan Penolakan HR');
        $response->assertSee('Dokumen tidak lengkap atau tidak jelas');
    }

    public function test_rejected_permission_counts_as_alpha_in_report_status(): void
    {
        $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'permission',
            'leave_note' => 'Keperluan keluarga mendesak hari ini',
            'doctor_note' => UploadedFile::fake()->create('bukti-izin.pdf', 100, 'application/pdf'),
        ]);

        $attendance = Attendance::firstOrFail();

        $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'no_done',
            'rejection_reason' => 'Dokumen tidak lengkap atau tidak jelas',
        ]);

        $attendance->refresh();

        $this->assertSame(AttendanceReportStatus::Alpha, AttendanceReportStatus::fromAttendance($attendance));
    }

    public function test_employee_can_resubmit_after_rejection(): void
    {
        $attendance = $this->submitSickLeave();

        $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'no_done',
            'rejection_reason' => 'Dokumen tidak lengkap atau tidak jelas',
        ]);

        $response = $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'permission',
            'leave_note' => 'Urusan keluarga mendesak hari ini',
            'doctor_note' => UploadedFile::fake()->create('bukti-izin.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('attendances', 1);

        $attendance = Attendance::first();
        $this->assertSame(AttendanceType::Permission, $attendance->type);
        $this->assertSame(VerificationStatus::Pending, $attendance->verification_status);
    }

    public function test_verify_rejects_already_verified_submission(): void
    {
        $attendance = $this->submitSickLeave();

        $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'done',
        ]);

        $response = $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'no_done',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_rejected_sick_increments_monthly_sick_statistics(): void
    {
        $attendance = $this->submitSickLeave();

        $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'no_done',
            'rejection_reason' => 'Dokumen tidak lengkap atau tidak jelas',
        ]);

        $statistics = $this->attendanceService->monthlyStatisticsFor($this->employeeUser, '2026-06');

        $this->assertSame(1, $statistics['sakit']);
        $this->assertSame(0, $statistics['alpha']);
    }

    public function test_reject_without_reason_fails_validation(): void
    {
        $attendance = $this->submitSickLeave();

        $response = $this->actingAs($this->hrUser)->from(route('leave-verification.index'))->patch(
            route('leave-verification.verify', $attendance),
            ['verification_status' => 'no_done'],
        );

        $response->assertRedirect(route('leave-verification.index'));
        $response->assertSessionHasErrors('rejection_reason');
        $this->assertSame(VerificationStatus::Pending, $attendance->fresh()->verification_status);
    }

    public function test_reject_with_short_reason_fails_validation(): void
    {
        $attendance = $this->submitSickLeave();

        $response = $this->actingAs($this->hrUser)->from(route('leave-verification.index'))->patch(
            route('leave-verification.verify', $attendance),
            [
                'verification_status' => 'no_done',
                'rejection_reason' => 'Terlalu',
            ],
        );

        $response->assertRedirect(route('leave-verification.index'));
        $response->assertSessionHasErrors('rejection_reason');
        $this->assertSame(VerificationStatus::Pending, $attendance->fresh()->verification_status);
    }

    public function test_reject_with_reason_stores_reason_and_sends_email(): void
    {
        Notification::fake();

        $attendance = $this->submitSickLeave();
        $reason = 'Surat dokter tidak terbaca dengan jelas, mohon unggah ulang.';
        $resubmissionMessage = 'Lengkapi keterangan dan bukti pendukung, lalu ajukan kembali melalui aplikasi atau website SADAR sebelum pukul 23.00 hari ini. Pengajuan ulang tetap memerlukan persetujuan HR.';

        $response = $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'no_done',
            'rejection_reason' => $reason,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertSame(VerificationStatus::NoDone, $attendance->verification_status);
        $this->assertSame($reason, $attendance->rejection_reason);

        Notification::assertSentTo(
            $this->employeeUser,
            LeaveRejectedNotification::class,
            function (LeaveRejectedNotification $notification) use ($attendance, $reason, $resubmissionMessage) {
                $mail = $notification->toMail($this->employeeUser);

                return $notification->attendance->is($attendance)
                    && $notification->attendance->rejection_reason === $reason
                    && in_array($resubmissionMessage, $mail->introLines, true)
                    && in_array('Status absensi: sakit (ditolak). Uang makan untuk hari tersebut tidak diberikan.', $mail->introLines, true)
                    && $mail->salutation instanceof HtmlString
                    && $mail->salutation->toHtml() === 'Regards,<br>Tim HR';
            },
        );

        $page = $this->actingAs($this->employeeUser)->get(route('leave-verification.my-submissions'));
        $page->assertOk();
        $page->assertSeeText('Pengajuan sakit Anda telah ditolak.');
        $page->assertSeeText('Anda dapat mengajukan ulang dengan bukti yang lebih lengkap sebelum pukul 23.00 hari ini.');
        $page->assertSee('Alasan Penolakan HR');
        $page->assertSee($reason);
        $page->assertSee('data-is-image="1"', false);
        $page->assertSee('id="evidence-modal-title"', false);
        $page->assertSee('submissionEvidenceViewer', false);
    }

    public function test_rejected_permission_shows_alpha_and_resubmission_message_before_cutoff(): void
    {
        $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'permission',
            'leave_note' => 'Keperluan keluarga mendesak hari ini',
            'doctor_note' => UploadedFile::fake()->create('bukti-izin.pdf', 100, 'application/pdf'),
        ]);

        $attendance = Attendance::firstOrFail();

        $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'no_done',
            'rejection_reason' => 'Bukti izin belum dapat diverifikasi dengan jelas.',
        ]);

        $page = $this->actingAs($this->employeeUser)->get(route('leave-verification.my-submissions'));

        $page->assertOk();
        $page->assertSeeText('Pengajuan izin Anda telah ditolak dan tercatat sebagai alfa.');
        $page->assertSeeText('Anda dapat mengajukan ulang dengan bukti yang lebih lengkap sebelum pukul 23.00 hari ini.');
        $page->assertSee('data-is-image="0"', false);

        $mail = (new LeaveRejectedNotification($attendance->fresh()))->toMail($this->employeeUser);
        $this->assertContains('Status absensi: tercatat sebagai alfa untuk hari tersebut.', $mail->introLines);
    }

    public function test_rejected_submission_does_not_offer_same_day_resubmission_after_cutoff_or_on_later_day(): void
    {
        $attendance = $this->submitSickLeave();

        $this->actingAs($this->hrUser)->patch(route('leave-verification.verify', $attendance), [
            'verification_status' => 'no_done',
            'rejection_reason' => 'Surat dokter belum dapat diverifikasi dengan jelas.',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-06 23:00:00', 'Asia/Jakarta'));

        $cutoffPage = $this->actingAs($this->employeeUser)->get(route('leave-verification.my-submissions'));
        $cutoffPage->assertOk();
        $cutoffPage->assertSeeText('Pengajuan sakit Anda telah ditolak.');
        $cutoffPage->assertDontSeeText('Anda dapat mengajukan ulang dengan bukti yang lebih lengkap sebelum pukul 23.00 hari ini.');

        $cutoffMail = (new LeaveRejectedNotification($attendance->fresh()))->toMail($this->employeeUser);
        $this->assertNotContains(
            'Lengkapi keterangan dan bukti pendukung, lalu ajukan kembali melalui aplikasi atau website SADAR sebelum pukul 23.00 hari ini. Pengajuan ulang tetap memerlukan persetujuan HR.',
            $cutoffMail->introLines,
        );

        Carbon::setTestNow(Carbon::parse('2026-06-07 08:00:00', 'Asia/Jakarta'));

        $laterPage = $this->actingAs($this->employeeUser)->get(route('leave-verification.my-submissions'));
        $laterPage->assertOk();
        $laterPage->assertDontSeeText('Anda dapat mengajukan ulang dengan bukti yang lebih lengkap sebelum pukul 23.00 hari ini.');
    }

    public function test_employee_can_access_my_submissions_page(): void
    {
        $this->submitSickLeave();

        $response = $this->actingAs($this->employeeUser)->get(route('leave-verification.my-submissions'));

        $response->assertOk();
        $response->assertSee('Status Pengajuan');
        $response->assertSee('Demam tinggi sejak pagi hari ini');
    }

    public function test_employee_attendance_history_uses_modal_for_leave_evidence(): void
    {
        $attendance = $this->submitSickLeave();

        $response = $this->actingAs($this->employeeUser)->get(route('attendance.history'));

        $response->assertOk();
        $response->assertSee('x-data="attendanceEvidenceViewer"', false);
        $response->assertSee('id="evidence-modal-title"', false);
        $response->assertSee('data-is-image="1"', false);
        $response->assertSee($attendance->doctorNoteViewUrl(), false);
    }

    public function test_employee_attendance_page_uses_modal_for_current_leave_evidence(): void
    {
        $this->createRegularSchedule();

        $attendance = $this->submitSickLeave();

        $response = $this->actingAs($this->employeeUser)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee('Pengajuan Sakit');
        $response->assertSee('x-data="attendanceEvidenceViewer"', false);
        $response->assertSee('id="evidence-modal-title"', false);
        $response->assertSee('data-is-image="1"', false);
        $response->assertSee($attendance->doctorNoteViewUrl(), false);
        $response->assertDontSee('Buka di tab baru');
    }

    public function test_sidebar_shows_pending_verification_badge_for_hr(): void
    {
        $this->submitSickLeave();

        $response = $this->actingAs($this->hrUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('title="1 pengajuan menunggu verifikasi"', false);
        $response->assertSee(route('leave-verification.index', ['status' => 'pending']), false);
    }

    public function test_pending_filter_shows_all_pending_when_date_is_not_selected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
        $this->submitSickLeave();

        Carbon::setTestNow(Carbon::parse('2026-06-07 08:00:00', 'Asia/Jakarta'));

        $todayResponse = $this->actingAs($this->hrUser)->get(route('leave-verification.index'));
        $todayResponse->assertOk();
        $todayResponse->assertDontSee('Demam tinggi sejak pagi hari ini');
        $todayResponse->assertSee('title="1 pengajuan menunggu verifikasi"', false);

        $pendingResponse = $this->actingAs($this->hrUser)->get(route('leave-verification.index', [
            'status' => 'pending',
        ]));
        $pendingResponse->assertOk();
        $pendingResponse->assertSee('Demam tinggi sejak pagi hari ini');
        $pendingResponse->assertSee('title="1 pengajuan menunggu verifikasi"', false);
        $pendingResponse->assertSee('value="pending"', false);
    }

    public function test_pending_filter_with_explicit_date_still_filters_by_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
        $this->submitSickLeave();

        Carbon::setTestNow(Carbon::parse('2026-06-07 08:00:00', 'Asia/Jakarta'));

        $todayPending = $this->actingAs($this->hrUser)->get(route('leave-verification.index', [
            'status' => 'pending',
            'date' => '2026-06-07',
        ]));
        $todayPending->assertOk();
        $todayPending->assertDontSee('Demam tinggi sejak pagi hari ini');

        $originalDatePending = $this->actingAs($this->hrUser)->get(route('leave-verification.index', [
            'status' => 'pending',
            'date' => '2026-06-06',
        ]));
        $originalDatePending->assertOk();
        $originalDatePending->assertSee('Demam tinggi sejak pagi hari ini');
    }

    public function test_leave_verification_index_can_search_by_employee_name(): void
    {
        $this->submitSickLeave();

        $otherUser = User::factory()->create(['name' => 'Andi Lain']);
        $otherUser->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $otherUser->id,
            'employee_code' => 'EMP-002',
            'name' => 'Andi Lain',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        $this->actingAs($otherUser)->post(route('attendance.leave'), [
            'type' => 'permission',
            'leave_note' => 'Keperluan pribadi',
            'doctor_note' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ]);

        $response = $this->actingAs($this->hrUser)->get(route('leave-verification.index', [
            'date' => '2026-06-06',
            'search' => 'Budi',
        ]));

        $response->assertOk();
        $response->assertSee('Budi Karyawan');
        $response->assertDontSee('Andi Lain');
    }

    public function test_leave_verification_defaults_to_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
        $this->submitSickLeave();

        $todayResponse = $this->actingAs($this->hrUser)->get(route('leave-verification.index'));
        $todayResponse->assertOk();
        $todayResponse->assertSee('Demam tinggi sejak pagi hari ini');
        $todayResponse->assertSee('value="2026-06-06"', false);

        Carbon::setTestNow(Carbon::parse('2026-06-07 08:00:00', 'Asia/Jakarta'));

        $nextDayResponse = $this->actingAs($this->hrUser)->get(route('leave-verification.index'));
        $nextDayResponse->assertOk();
        $nextDayResponse->assertDontSee('Demam tinggi sejak pagi hari ini');
        $nextDayResponse->assertSee('value="2026-06-07"', false);

        $previousDateResponse = $this->actingAs($this->hrUser)->get(route('leave-verification.index', [
            'date' => '2026-06-06',
        ]));
        $previousDateResponse->assertOk();
        $previousDateResponse->assertSee('Demam tinggi sejak pagi hari ini');
    }

    private function submitSickLeave(): Attendance
    {
        $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'sick',
            'leave_note' => 'Demam tinggi sejak pagi hari ini',
            'doctor_note' => UploadedFile::fake()->image('surat-dokter.jpg'),
        ]);

        return Attendance::firstOrFail();
    }

    private function createRegularSchedule(): WorkSchedule
    {
        return WorkSchedule::create([
            'name' => 'Reguler',
            'code' => 'regular',
            'clock_in_start' => '09:00:00',
            'late_limit' => '09:15:00',
            'clock_out_start' => '18:00:00',
            'clock_out_limit' => '18:15:00',
        ]);
    }
}
