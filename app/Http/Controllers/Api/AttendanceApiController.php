<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExternalAttendanceApiService;
use App\Support\AppTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    public function __construct(
        private readonly ExternalAttendanceApiService $attendanceApi,
    ) {}

    public function today(): JsonResponse
    {
        $date = AppTime::today();
        $data = $this->attendanceApi->listForDate($date);

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'count' => $data->count(),
            'data' => $data->values(),
            'source' => 'sadar',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $date = $this->attendanceApi->parseDate($request->query('date'));
        $data = $this->attendanceApi->listForDate($date);

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'count' => $data->count(),
            'data' => $data->values(),
            'source' => 'sadar',
        ]);
    }

    public function employee(Request $request, string $identifier): JsonResponse
    {
        $date = $this->attendanceApi->parseDate($request->query('date'));
        $result = $this->attendanceApi->employeeStatus($identifier, $date);

        return response()->json([
            'success' => true,
            'data' => $result['payload'],
            'source' => 'sadar',
        ]);
    }
}
