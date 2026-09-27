@props([
    'settings',
    'hasFaceRegistered' => false,
    'needsFaceDescriptorSync' => false,
    'profilePhotoUrl' => null,
    'faceMatchThreshold' => 0.5,
    'faceMinMatchPercent' => 74,
])

<x-attendance.attendance-face-form
    {{ $attributes }}
    type="clock-in"
    :settings="$settings"
    :has-face-registered="$hasFaceRegistered"
    :needs-face-descriptor-sync="$needsFaceDescriptorSync"
    :profile-photo-url="$profilePhotoUrl"
    :face-match-threshold="$faceMatchThreshold"
    :face-min-match-percent="$faceMinMatchPercent"
    title="Absensi Reguler"
    subtitle="Absen masuk dengan verifikasi wajah & lokasi GPS."
    report-mode="rencana-kerja"
    report-name="clock_in_report"
    submit-label="Absen Masuk"
    :action-url="route('attendance.clock-in')"
/>
