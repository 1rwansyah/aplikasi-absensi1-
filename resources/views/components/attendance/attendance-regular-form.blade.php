@props([
    'settings',
    'hasFaceRegistered' => false,
    'needsFaceDescriptorSync' => false,
    'profilePhotoUrl' => null,
    'faceMatchThreshold' => 0.5,
    'faceMinMatchPercent' => 74,
    'showClockOutWaiting' => false,
    'clockOutOpensAt' => null,
    'clockOutWindow' => null,
    'hasClockIn' => false,
    'hasClockOut' => false,
    'reportMode' => 'rencana-kerja',
    'disableClockIn' => false,
    'pendingClockOutDate' => null,
])

<x-attendance.attendance-face-form
    {{ $attributes }}
    dual-action
    type="attendance-regular"
    :settings="$settings"
    :has-face-registered="$hasFaceRegistered"
    :needs-face-descriptor-sync="$needsFaceDescriptorSync"
    :profile-photo-url="$profilePhotoUrl"
    :face-match-threshold="$faceMatchThreshold"
    :face-min-match-percent="$faceMinMatchPercent"
    title="Absensi Reguler"
    subtitle="Lengkapi laporan kerja, lalu pilih absen masuk atau pulang."
    :show-clock-out-waiting="$showClockOutWaiting"
    :clock-out-opens-at="$clockOutOpensAt"
    :clock-out-window="$clockOutWindow"
    :report-mode="$reportMode"
    :disable-clock-in="$disableClockIn"
    :pending-clock-out-date="$pendingClockOutDate"
    :action-url="route('attendance.clock-in')"
    :has-clock-in="$hasClockIn"
    :has-clock-out="$hasClockOut"
/>
