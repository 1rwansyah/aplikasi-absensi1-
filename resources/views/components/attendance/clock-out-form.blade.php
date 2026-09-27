@props([
    'settings',
    'clockOutWindow',
    'hasFaceRegistered' => false,
    'needsFaceDescriptorSync' => false,
    'profilePhotoUrl' => null,
    'faceMatchThreshold' => 0.5,
    'faceMinMatchPercent' => 74,
])

<x-attendance.attendance-face-form
    {{ $attributes }}
    type="clock-out"
    :settings="$settings"
    :has-face-registered="$hasFaceRegistered"
    :needs-face-descriptor-sync="$needsFaceDescriptorSync"
    :profile-photo-url="$profilePhotoUrl"
    :face-match-threshold="$faceMatchThreshold"
    :face-min-match-percent="$faceMinMatchPercent"
    title="Absen Pulang"
    :subtitle="'Rentang pulang: '.$clockOutWindow"
    report-mode="hasil-pekerjaan"
    report-name="clock_out_report"
    submit-label="Absen Pulang"
    :action-url="route('attendance.clock-out')"
/>
