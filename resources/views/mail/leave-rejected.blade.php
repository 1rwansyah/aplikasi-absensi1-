<x-mail::message>
<div style="text-align: center; margin-bottom: 28px;">
    <div style="display: inline-block; width: 52px; height: 52px; line-height: 52px; border-radius: 50%; background-color: #fee2e2; color: #dc2626; font-size: 26px; font-weight: 700;">×</div>
    <h1 style="margin: 14px 0 6px; color: #991b1b; font-size: 22px; line-height: 1.3;">Pengajuan {{ $typeLabel }} Ditolak</h1>
    <p style="margin: 0; color: #71717a; font-size: 14px;">{{ $dateLabel }}</p>
</div>

Halo, **{{ $employeeName }}**.

Pengajuan {{ strtolower($typeLabel) }} Anda telah diperiksa dan ditolak oleh **{{ $verifier }}**.

@if ($reason !== '')
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 24px 0; border-collapse: separate; border-spacing: 0;">
<tr>
<td style="border-left: 4px solid #dc2626; border-radius: 8px; background-color: #fef2f2; padding: 18px 20px;">
    <p style="margin: 0 0 8px; color: #991b1b; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;">Alasan penolakan</p>
    <p style="margin: 0; color: #450a0a; font-size: 15px; font-weight: 600; line-height: 1.6;">{!! nl2br(e($reason)) !!}</p>
</td>
</tr>
</table>
@endif

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 20px 0; border-collapse: separate; border-spacing: 0;">
<tr>
<td style="border-radius: 8px; background-color: #f4f4f5; padding: 16px 18px; color: #3f3f46; font-size: 14px; line-height: 1.6;">
    <strong style="color: #27272a;">Status absensi</strong><br>
    {{ $statusMessage }}
</td>
</tr>
</table>

@if ($canResubmitToday)
<div style="margin: 22px 0;">
    <p style="margin: 0 0 7px; color: #27272a; font-size: 15px; font-weight: 700;">Masih dapat mengajukan ulang</p>
    <p style="margin: 0; color: #52525b; font-size: 14px; line-height: 1.65;">Lengkapi keterangan dan bukti pendukung, lalu ajukan kembali melalui aplikasi atau website <strong>SADAR</strong> sebelum pukul <strong>23.00 hari ini</strong>. Pengajuan ulang tetap memerlukan persetujuan HR.</p>
</div>
@endif

<x-mail::button :url="$statusUrl" color="primary">
Lihat Status Pengajuan
</x-mail::button>

<div style="margin-top: 28px; color: #52525b; font-size: 14px; line-height: 1.6;">
Regards,<br>
<strong style="color: #27272a;">Tim HR</strong>
</div>
</x-mail::message>
