<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\DoctorNoteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorNoteController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly DoctorNoteService $doctorNotes,
    ) {}

    public function show(Attendance $attendance): StreamedResponse
    {
        $this->authorize('viewDoctorNote', $attendance);

        return $this->doctorNotes->stream($attendance->doctor_note_path);
    }
}
