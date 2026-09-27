<?php

namespace App\Http\Controllers;

use App\Http\Requests\Face\SyncFaceDescriptorRequest;
use App\Http\Requests\Face\VerifyFaceRequest;
use App\Services\EmployeeFaceDescriptorService;
use App\Services\FaceVerificationService;
use Illuminate\Http\JsonResponse;

class FaceVerificationController extends Controller
{
    public function __construct(
        private readonly FaceVerificationService $faceVerification,
        private readonly EmployeeFaceDescriptorService $faceDescriptors,
    ) {}

    public function verify(VerifyFaceRequest $request): JsonResponse
    {
        $result = $this->faceVerification->verifyForUser(
            $request->user(),
            $request->descriptor(),
            $request->facesDetected(),
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function syncDescriptor(SyncFaceDescriptorRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if ($employee === null) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan.',
            ], 422);
        }

        if (! $employee->hasProfilePhoto()) {
            return response()->json([
                'success' => false,
                'message' => 'Foto profil belum tersedia.',
            ], 422);
        }

        if ($request->facesDetected() !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Foto profil harus menampilkan satu wajah dengan jelas.',
            ], 422);
        }

        try {
            $this->faceDescriptors->save($employee, $request->descriptor());
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data wajah dari foto profil berhasil disiapkan.',
        ]);
    }
}
