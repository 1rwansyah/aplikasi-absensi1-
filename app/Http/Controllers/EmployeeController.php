<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\ActivityLogService;
use App\Services\EmployeeFaceDescriptorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeFaceDescriptorService $faceDescriptors,
    ) {}

    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $staff = $request->query('staff');

        $employees = Employee::with('salaryComponents.salaryComponent')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('staff', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('employment_status', $status))
            ->when($staff, fn ($query) => $query->where('staff', $staff))
            ->orderBy('employee_code')
            ->paginate(15)
            ->withQueryString();

        $staffs = Employee::query()
            ->whereNotNull('staff')
            ->where('staff', '!=', '')
            ->distinct()
            ->orderBy('staff')
            ->pluck('staff');

        $statuses = Employee::query()
            ->whereNotNull('employment_status')
            ->where('employment_status', '!=', '')
            ->distinct()
            ->orderBy('employment_status')
            ->pluck('employment_status');

        $salaryComponents = SalaryComponent::where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $workSchedules = WorkSchedule::query()
            ->whereIn('code', ['regular', 'ob', 'security', 'engineering'])
            ->orderByRaw("FIELD(code, 'regular', 'ob', 'security', 'engineering')")
            ->get(['id', 'code', 'name']);

        $nextCode = Employee::generateCode();

        $allEmployees = Employee::with('salaryComponents.salaryComponent')->get();

        $summary = [
            'total' => $allEmployees->count(),
            'active' => $allEmployees->where('employment_status', 'active')->count(),
            'inactive' => $allEmployees->where('employment_status', 'inactive')->count(),
            'resigned' => $allEmployees->where('employment_status', 'resigned')->count(),
            'total_gross_salary' => $allEmployees->sum(fn ($e) => $e->gross_salary),
        ];

        return view('employees.index', compact(
            'employees',
            'staffs',
            'statuses',
            'search',
            'status',
            'staff',
            'nextCode',
            'salaryComponents',
            'workSchedules',
            'summary'
        ));
    }

    public function create()
    {
        return redirect()->route('employees.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'employee_code' => ['sometimes', 'string', 'max:255', 'unique:employees,employee_code'],
            'nik' => ['nullable', 'digits_between:1,20'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'education' => ['nullable', 'string', 'max:255'],
            'work_experience' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email', 'unique:employees,email'],
            'position' => ['nullable', 'string', 'max:255'],
            'staff' => ['nullable', 'string', 'max:255'],
            'join_date' => ['nullable', 'date'],
            'employment_status' => ['nullable', Rule::in(['active', 'inactive', 'resigned'])],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'digits_between:1,20'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'default_work_schedule_id' => ['nullable', 'integer', 'exists:work_schedules,id'],
        ]);

        $salaryComponents = $request->input('salary_components', []);
        // admin role must never be assignable from employee form — IT-only
        $selectedRoles = array_filter(
            $request->input('roles', []),
            fn ($r) => in_array($r, ['hr', 'employee'])
        );

        $roleNames = array_unique(array_merge(['employee'], $selectedRoles));

        unset($validated['profile_photo']);

        $validated['basic_salary'] = $validated['basic_salary'] ?? 1000000;
        $validated['employment_status'] = $validated['employment_status'] ?? 'active';
        $validated['employee_code'] = Employee::generateCode();
        $validated['default_work_schedule_id'] = $this->resolveDefaultWorkScheduleId($validated);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = $request
                ->file('profile_photo')
                ->store('employee-photos', 'public');
            $validated['face_descriptor'] = $this->resolveFaceDescriptorPayload($request);
        }

        DB::transaction(function () use ($validated, $salaryComponents, $roleNames) {
            if (! empty($validated['email'])) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make('ChangeMe123!'),
                    'role' => 'employee',
                ]);

                $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
                $user->roles()->sync($roleIds);

                $validated['user_id'] = $user->id;
            }

            $employee = Employee::create($validated);

            foreach ($salaryComponents as $componentId => $amount) {
                if ((int) $amount > 0) {
                    EmployeeSalaryComponent::create([
                        'employee_id' => $employee->id,
                        'salary_component_id' => $componentId,
                        'amount' => $amount,
                        'is_active' => true,
                    ]);
                }
            }
        });

        $employee = Employee::where('email', $validated['email'] ?? '')->orWhere('nik', $validated['nik'] ?? '')->latest()->first();
        ActivityLogService::log(
            auth()->user(),
            'create',
            "Menambahkan data karyawan baru: {$validated['name']} dengan role [".implode(', ', $roleNames).']',
            $employee
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Data karyawan berhasil ditambahkan.',
            ]);
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function show(Employee $employee)
    {
        $employee->load('salaryComponents.salaryComponent');

        if (request()->expectsJson()) {
            return response()->json($this->formatEmployeeResponse($employee));
        }

        return redirect()->route('employees.index');
    }

    public function profilePhoto(Employee $employee): StreamedResponse
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        if (! $user->isAdmin() && $user->id !== $employee->user_id) {
            abort(403);
        }

        if (! $employee->hasProfilePhoto()) {
            abort(404, 'Foto profil tidak ditemukan.');
        }

        return Storage::disk('public')->response($employee->profile_photo, basename($employee->profile_photo), [
            'Content-Disposition' => 'inline; filename="'.basename($employee->profile_photo).'"',
        ]);
    }

    public function edit(Employee $employee)
    {
        $employee->load('salaryComponents.salaryComponent');

        if (request()->expectsJson()) {
            return response()->json($this->formatEmployeeResponse($employee));
        }

        return redirect()->route('employees.index');
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'digits_between:1,20'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'education' => ['nullable', 'string', 'max:255'],
            'work_experience' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee->id),
                Rule::unique('users', 'email')->ignore($employee->user_id),
            ],
            'position' => ['nullable', 'string', 'max:255'],
            'staff' => ['nullable', 'string', 'max:255'],
            'join_date' => ['nullable', 'date'],
            'employment_status' => ['nullable', Rule::in(['active', 'inactive', 'resigned'])],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'digits_between:1,20'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'default_work_schedule_id' => ['nullable', 'integer', 'exists:work_schedules,id'],
        ]);

        $salaryComponents = $request->input('salary_components', []);
        $selectedRoles = array_filter(
            $request->input('roles', []),
            fn ($r) => in_array($r, ['hr', 'employee'])
        );

        unset($validated['profile_photo']);

        $validated['basic_salary'] = $validated['basic_salary'] ?? 0;
        $validated['employment_status'] = $validated['employment_status'] ?? 'active';
        $validated['default_work_schedule_id'] = $this->resolveDefaultWorkScheduleId($validated);

        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo) {
                Storage::disk('public')->delete($employee->profile_photo);
            }

            $validated['profile_photo'] = $request
                ->file('profile_photo')
                ->store('employee-photos', 'public');
            $validated['face_descriptor'] = $this->resolveFaceDescriptorPayload($request);
        }

        $oldRoles = $employee->user
            ? $employee->user->roles()->pluck('name')->sort()->values()->all()
            : [];

        DB::transaction(function () use ($employee, $validated, $salaryComponents, $selectedRoles) {
            $employee->update($validated);

            if ($employee->user) {
                $employee->user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? $employee->user->email,
                ]);
                $roleNames = array_unique(array_merge(['employee'], $selectedRoles));
                $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
                $employee->user->roles()->sync($roleIds);
            } elseif (! empty($validated['email'])) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make('ChangeMe123!'),
                    'role' => 'employee',
                ]);
                $roleNames = array_unique(array_merge(['employee'], $selectedRoles));
                $roleIds = Role::whereIn('name', $roleNames)->pluck('id');
                $user->roles()->sync($roleIds);
                $employee->update(['user_id' => $user->id]);
            }

            EmployeeSalaryComponent::where('employee_id', $employee->id)->delete();

            foreach ($salaryComponents as $componentId => $amount) {
                if ((int) $amount > 0) {
                    EmployeeSalaryComponent::create([
                        'employee_id' => $employee->id,
                        'salary_component_id' => $componentId,
                        'amount' => $amount,
                        'is_active' => true,
                    ]);
                }
            }
        });

        $employee->load('user.roles');

        $newRoles = $employee->user
            ? $employee->user->roles->pluck('name')->sort()->values()->all()
            : [];

        ActivityLogService::log(auth()->user(), 'update', "Memperbarui data karyawan: {$validated['name']}", $employee);
        if ($oldRoles !== $newRoles) {
            ActivityLogService::log(
                auth()->user(),
                'update',
                'Memperbarui akses role karyawan: '.$employee->name.
                ' dari ['.implode(', ', $oldRoles ?: ['-']).'] ke ['.implode(', ', $newRoles ?: ['-']).']',
                $employee
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Data karyawan berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            $userId = $employee->user_id;
            $photoPath = $employee->profile_photo;

            EmployeeSalaryComponent::where('employee_id', $employee->id)->delete();

            $employee->delete();

            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            if ($userId) {
                User::where('id', $userId)->delete();
            }
        });

        ActivityLogService::log(auth()->user(), 'delete', "Menghapus data karyawan: {$employee->name}", $employee);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Data karyawan berhasil dihapus.');
    }

    private function resolveFaceDescriptorPayload(Request $request): ?string
    {
        $descriptor = $this->faceDescriptors->decodeFromRequest(
            $request->input('face_descriptor_json')
        );

        if ($descriptor === null) {
            return null;
        }

        return json_encode($descriptor);
    }

    private function resolveDefaultWorkScheduleId(array $data): ?int
    {
        $staff = strtoupper(trim((string) ($data['staff'] ?? '')));
        $position = strtoupper(trim((string) ($data['position'] ?? '')));

        if ($staff === 'OB' || $position === 'OB') {
            return WorkSchedule::query()->where('code', 'ob')->value('id');
        }

        if ($staff === 'SECURITY' || $position === 'SECURITY') {
            return WorkSchedule::query()->where('code', 'security')->value('id');
        }

        if ($staff === 'ENGINEERING' || str_contains($position, 'ENGINEERING')) {
            return WorkSchedule::query()->where('code', 'engineering')->value('id');
        }

        if (! empty($data['default_work_schedule_id'])) {
            return (int) $data['default_work_schedule_id'];
        }

        return WorkSchedule::query()->where('code', 'regular')->value('id');
    }

    private function formatEmployeeResponse(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'user_id' => $employee->user_id,
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'nik' => $employee->nik,
            'birth_place' => $employee->birth_place,
            'birth_date' => $employee->birth_date?->format('Y-m-d'),
            'address' => $employee->address,
            'education' => $employee->education,
            'work_experience' => $employee->work_experience,
            'email' => $employee->email,
            'position' => $employee->position,
            'staff' => $employee->staff,
            'default_work_schedule_id' => $employee->default_work_schedule_id,
            'join_date' => $employee->join_date?->format('Y-m-d'),
            'employment_status' => $employee->employment_status,
            'basic_salary' => $employee->basic_salary,
            'bank_name' => $employee->bank_name,
            'bank_account_number' => $employee->bank_account_number,
            'bank_account_name' => $employee->bank_account_name,
            'profile_photo' => $employee->profile_photo,
            'profile_photo_url' => $employee->profilePhotoUrl(),
            'salary_components' => $employee->salaryComponents
                ->where('is_active', true)
                ->where('amount', '>', 0)
                ->pluck('amount', 'salary_component_id'),
            'salary_component_details' => $employee->salaryComponents
                ->where('is_active', true)
                ->where('amount', '>', 0)
                ->map(function ($item) {
                    return [
                        'id' => $item->salary_component_id,
                        'name' => $item->salaryComponent?->name,
                        'type' => $item->salaryComponent?->type,
                        'amount' => $item->amount,
                    ];
                })->values(),
            'gross_salary' => $employee->gross_salary,
            'roles' => $employee->user?->roles->pluck('name') ?? [],
        ];
    }
}
