@php
    $fieldClass = 'block min-h-11 w-full rounded-2xl border-gray-200 bg-gray-50 text-sm shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
    $labelClass = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400';
@endphp

<div class="grid gap-x-5 gap-y-4 md:grid-cols-2">

    <div class="md:col-span-2 rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Foto Profil</p>

        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="shrink-0">
                <img id="{{ $mode === 'edit' ? 'edit_photo_preview' : 'photo_preview' }}" src=""
                    alt="Preview foto profil"
                    class="hidden h-24 w-24 rounded-2xl border border-gray-200 object-cover shadow-sm dark:border-gray-700">

                <div id="{{ $mode === 'edit' ? 'edit_photo_placeholder' : 'photo_placeholder' }}"
                    class="flex h-24 w-24 items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white text-xs text-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-500">
                    Foto
                </div>
            </div>

            <div class="flex-1">
                <label class="{{ $labelClass }}">Upload Foto</label>

                <input type="file" name="profile_photo"
                    id="{{ $mode === 'edit' ? 'edit_profile_photo' : 'profile_photo' }}"
                    accept="image/png,image/jpeg,image/jpg,image/webp" data-photo-preview="{{ $mode }}"
                    class="block w-full rounded-2xl border border-gray-200 bg-white text-sm text-gray-700 file:mr-4 file:rounded-xl file:border-0 file:bg-red-600 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-red-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-gray-700 dark:file:text-gray-100 dark:hover:file:bg-gray-600">

                <p class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                    Format JPG, JPEG, PNG, atau WEBP. Maksimal 2MB. Wajah harus terlihat jelas (digunakan untuk verifikasi absensi).
                </p>
                <input type="hidden" name="face_descriptor_json"
                    id="{{ $mode === 'edit' ? 'edit_face_descriptor_json' : 'face_descriptor_json' }}" value="">
                <p id="{{ $mode === 'edit' ? 'edit_face_descriptor_status' : 'face_descriptor_status' }}"
                    class="mt-2 hidden text-xs font-medium"></p>
            </div>
        </div>
    </div>

    <div class="md:col-span-2">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Data Utama</p>
    </div>

    <div>
        <label class="{{ $labelClass }}">Nama <span class="text-red-500">*</span></label>
        <input type="text" name="name" id="{{ $mode === 'edit' ? 'edit_name' : 'name' }}"
            placeholder="Masukkan nama karyawan" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Email</label>
        <input type="email" name="email" id="{{ $mode === 'edit' ? 'edit_email' : 'email' }}"
            placeholder="contoh@email.com" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Jabatan</label>
        <input type="text" name="position" id="{{ $mode === 'edit' ? 'edit_position' : 'position' }}"
            placeholder="Contoh: Software Engineer" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Staff</label>
        <input type="text" name="staff" id="{{ $mode === 'edit' ? 'edit_staff' : 'staff' }}"
            placeholder="Contoh: IT" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Jadwal Kerja</label>
        <select name="default_work_schedule_id"
            id="{{ $mode === 'edit' ? 'edit_default_work_schedule_id' : 'default_work_schedule_id' }}"
            class="{{ $fieldClass }} font-semibold text-gray-800 dark:text-gray-100">
            @foreach ($workSchedules ?? [] as $schedule)
                <option value="{{ $schedule->id }}" data-code="{{ $schedule->code }}"
                    @if ($schedule->code === 'regular') selected @endif>
                    {{ $schedule->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            Otomatis diisi ke OB, Security, atau Staff Engineering jika Staff/Jabatan sesuai.
        </p>
    </div>

    <div>
        <label class="{{ $labelClass }}">NIK</label>
        <input type="text" name="nik" id="{{ $mode === 'edit' ? 'edit_nik' : 'nik' }}" inputmode="numeric"
            placeholder="Masukkan NIK" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Pendidikan</label>
        <input type="text" name="education" id="{{ $mode === 'edit' ? 'edit_education' : 'education' }}"
            placeholder="Contoh: S1 Teknik Informatika" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Tempat Lahir</label>
        <input type="text" name="birth_place" id="{{ $mode === 'edit' ? 'edit_birth_place' : 'birth_place' }}"
            placeholder="Contoh: Jakarta" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Tanggal Lahir</label>
        <input type="date" name="birth_date" id="{{ $mode === 'edit' ? 'edit_birth_date' : 'birth_date' }}"
            class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Tanggal Masuk</label>
        <input type="date" name="join_date" id="{{ $mode === 'edit' ? 'edit_join_date' : 'join_date' }}"
            class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Gaji Pokok</label>
        <div class="flex rounded-2xl shadow-sm">
            <span class="inline-flex items-center rounded-l-2xl border border-r-0 border-gray-200 bg-white px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">Rp</span>
            <input type="text" name="basic_salary"
                id="{{ $mode === 'edit' ? 'edit_basic_salary' : 'basic_salary' }}" value="1000000" inputmode="numeric"
                placeholder="Masukkan gaji pokok" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                class="block min-h-11 w-full rounded-none rounded-r-2xl border-gray-200 bg-gray-50 text-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </div>

    <div>
        <label class="{{ $labelClass }}">Status</label>
        <select name="employment_status" id="{{ $mode === 'edit' ? 'edit_employment_status' : 'employment_status' }}"
            class="{{ $fieldClass }} font-semibold text-gray-800 dark:text-gray-100">
            <option value="active">Aktif</option>
            <option value="inactive">Tidak Aktif</option>
            <option value="resigned">Resign</option>
        </select>
    </div>

    <div class="md:col-span-2 mt-1 border-t border-gray-100 pt-4 dark:border-gray-700">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Informasi Bank</p>
    </div>

    <div>
        <label class="{{ $labelClass }}">Bank</label>
        <input type="text" name="bank_name" id="{{ $mode === 'edit' ? 'edit_bank_name' : 'bank_name' }}"
            placeholder="Contoh: BCA" class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">No Rekening</label>
        <input type="text" name="bank_account_number"
            id="{{ $mode === 'edit' ? 'edit_bank_account_number' : 'bank_account_number' }}" inputmode="numeric"
            placeholder="Masukkan nomor rekening" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            class="{{ $fieldClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">Atas Nama</label>
        <input type="text" name="bank_account_name"
            id="{{ $mode === 'edit' ? 'edit_bank_account_name' : 'bank_account_name' }}"
            placeholder="Nama pemilik rekening" class="{{ $fieldClass }}">
    </div>

    <div class="md:col-span-2">
        <label class="{{ $labelClass }}">Pengalaman Kerja</label>
        <textarea name="work_experience" id="{{ $mode === 'edit' ? 'edit_work_experience' : 'work_experience' }}"
            rows="2" placeholder="Masukkan pengalaman kerja"
            class="block w-full rounded-2xl border-gray-200 bg-gray-50 text-sm shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
    </div>

    <div class="md:col-span-2">
        <label class="{{ $labelClass }}">Alamat</label>
        <textarea name="address" id="{{ $mode === 'edit' ? 'edit_address' : 'address' }}" rows="2"
            placeholder="Masukkan alamat lengkap"
            class="block w-full rounded-2xl border-gray-200 bg-gray-50 text-sm shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
    </div>

    <div class="md:col-span-2 mt-1 rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Hak Akses</p>
        <div class="mt-3 flex flex-wrap gap-4">
            <label class="inline-flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="roles[]" value="hr"
                    id="{{ $mode === 'edit' ? 'edit_role_hr' : 'role_hr' }}"
                    class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">HR / Personalia</span>
                <span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/50">
                    Akses Dashboard &amp; Admin
                </span>
            </label>
        </div>
    </div>

    <div class="md:col-span-2 mt-1 rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Komponen Gaji</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tunjangan default karyawan.</p>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @php
                $excludedComponents = ['lembur', 'uang makan'];
            @endphp
            @foreach ($salaryComponents ?? [] as $component)
                @php
                    $componentNameLower = strtolower($component->name ?? '');
                    $isExcluded = collect($excludedComponents)->contains(
                        fn($ex) => str_contains($componentNameLower, $ex),
                    );
                @endphp
                @if ($isExcluded)
                    @continue
                @endif
                <div>
                    <label class="mb-1.5 flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ $component->name }}

                        @if ($component->type === 'allowance')
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold normal-case tracking-normal text-emerald-700 ring-1 ring-inset ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/50">
                                Tunjangan
                            </span>
                        @else
                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold normal-case tracking-normal text-rose-700 ring-1 ring-inset ring-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900/50">
                                Potongan
                            </span>
                        @endif
                    </label>

                    <div class="flex rounded-2xl shadow-sm">
                        <span class="inline-flex items-center rounded-l-2xl border border-r-0 border-gray-200 bg-white px-3 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">Rp</span>

                        <input type="text" name="salary_components[{{ $component->id }}]"
                            id="{{ $mode === 'edit' ? 'edit_sc_' . $component->id : 'sc_' . $component->id }}"
                            value="0" inputmode="numeric" placeholder="0"
                            onfocus="if(this.value === '0') this.value = ''"
                            onblur="if(this.value === '') this.value = '0'"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="block min-h-11 w-full rounded-none rounded-r-2xl border-gray-200 bg-white text-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
