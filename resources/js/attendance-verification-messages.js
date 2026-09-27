const notRecorded = 'Absensi belum tercatat.';

function notice(title, message, action = 'retry', type = 'error', steps = []) {
    return { title, message, action, type, steps };
}

export function shouldShowDelayedVerificationNotice({
    verificationInFlight,
    verified,
    activeNoticeType,
}) {
    return verificationInFlight && !verified && activeNoticeType === 'scanning';
}

export const VERIFICATION_NOTICES = {
    cameraUnsupported: notice(
        'Kamera tidak didukung',
        `Browser ini tidak dapat membuka kamera untuk verifikasi. ${notRecorded}`,
        'close',
        'error',
        [
            'Buka website SADAR menggunakan Chrome, Edge, atau Safari versi terbaru.',
            'Pastikan website dibuka melalui alamat HTTPS.',
            'Muat ulang halaman, lalu lakukan absensi kembali.',
        ],
    ),
    cameraNotReady: notice(
        'Kamera masih disiapkan',
        `Preview kamera belum siap digunakan. ${notRecorded}`,
        'retry',
        'warning',
        [
            'Tunggu beberapa detik sampai gambar kamera terlihat dan status berubah menjadi Siap.',
            'Izin kamera seharusnya sudah diizinkan saat halaman absensi dibuka. Jika ditolak, muat ulang halaman lalu tekan Izinkan, atau ubah izin Kamera di pengaturan situs.',
            'Jika preview tetap gelap, tekan Coba Lagi atau muat ulang halaman.',
        ],
    ),
    profileMissing: notice(
        'Foto profil belum tersedia',
        `Hubungi HR untuk menambahkan atau memperbarui foto profil sebelum melakukan absensi. ${notRecorded}`,
        'close',
        'error',
        [
            'Pastikan foto profil Anda sudah tampil pada akun SADAR.',
            'Jika foto belum tersedia, hubungi HR/Admin untuk mengunggah foto wajah yang jelas.',
            'Lakukan absensi kembali setelah foto profil berhasil diperbarui.',
        ],
    ),
    faceNotDetected: notice(
        'Wajah belum terdeteksi',
        `Sistem tidak menemukan wajah yang cukup jelas pada foto. Wajah mungkin terlalu gelap, terlalu jauh, buram, atau terpotong. ${notRecorded}`,
        'retake',
        'warning',
        [
            'Bersihkan lensa kamera dan pastikan cahaya datang dari arah depan, bukan dari belakang.',
            'Lepaskan masker, kacamata gelap, atau benda lain yang menutupi wajah.',
            'Posisikan seluruh wajah di tengah kamera dengan jarak sekitar 30–60 cm.',
            'Pastikan gambar tidak bergerak atau buram, lalu tekan Ambil Foto Ulang.',
        ],
    ),
    multipleFaces: notice(
        'Terdeteksi lebih dari satu wajah',
        `Sistem menemukan lebih dari satu wajah pada foto sehingga verifikasi tidak dapat dilanjutkan. ${notRecorded}`,
        'retake',
        'warning',
        [
            'Pastikan tidak ada orang lain di dalam area kamera.',
            'Hindari latar belakang yang memuat foto, poster, atau layar dengan gambar wajah.',
            'Arahkan kamera hanya ke wajah Anda, lalu tekan Ambil Foto Ulang.',
        ],
    ),
    faceMismatch: notice(
        'Wajah tidak cocok dengan foto profil',
        `Wajah pada foto belum mencapai tingkat kecocokan minimum dengan foto profil. ${notRecorded}`,
        'retake',
        'warning',
        [
            'Pastikan akun dan foto profil yang digunakan benar-benar milik Anda.',
            'Hadapkan wajah lurus ke kamera dengan ekspresi netral dan pencahayaan dari depan.',
            'Lepaskan masker, kacamata gelap, atau aksesori yang menutupi wajah.',
            'Tekan Ambil Foto Ulang. Jika selalu gagal, hubungi HR/Admin untuk memperbarui foto profil.',
        ],
    ),
    photoMissing: notice(
        'Foto belum tersedia',
        `Belum ada foto yang dapat diperiksa oleh sistem. ${notRecorded}`,
        'retake',
        'warning',
        [
            'Pastikan preview kamera sudah terlihat dan status menunjukkan Siap.',
            'Posisikan wajah di tengah kamera, lalu tekan Ambil Foto.',
            'Periksa hasil preview sebelum memilih Gunakan Foto.',
        ],
    ),
    gpsOutsideRadius: notice(
        'Lokasi di luar radius absensi',
        `Posisi yang dibaca GPS masih berada di luar radius kantor. ${notRecorded}`,
        'retry',
        'warning',
        [
            'Pastikan Anda benar-benar sudah berada di area kantor yang diizinkan untuk absensi.',
            'Buka Pengaturan HP, lalu pastikan tombol Lokasi atau GPS sudah aktif.',
            'Pastikan izin lokasi untuk website SADAR di browser diatur ke Izinkan atau Allow.',
            'Jika HP menyediakan pilihan Lokasi Presisi atau Akurat, aktifkan pilihan tersebut. Lewati langkah ini jika tidak tersedia.',
            'Nyalakan Wi-Fi atau data seluler, kembali ke halaman ini, lalu tunggu 10–20 detik sampai posisi diperbarui.',
            'Tekan Coba Lagi satu kali dan tunggu hasilnya. Jangan menekan tombol berulang kali.',
        ],
    ),
    gpsUnavailable: notice(
        'Lokasi belum ditemukan',
        `Perangkat belum berhasil mendapatkan koordinat lokasi Anda. Absensi pada percobaan ini belum dapat dikirim. Jika absensi sudah tercatat di sistem, muat ulang halaman.`,
        'retry',
        'error',
        [
            'Buka Pengaturan HP, lalu pastikan tombol Lokasi atau GPS sudah aktif.',
            'Kembali ke browser, lalu ketuk ikon pengaturan situs atau gembok di samping alamat website SADAR.',
            'Ubah izin Lokasi menjadi Izinkan atau Allow. Jika ikon tidak ada, buka menu browser, pilih Setelan atau Pengaturan Situs, lalu pilih Lokasi.',
            'Jika HP menyediakan pilihan Lokasi Presisi atau Akurat, aktifkan pilihan tersebut. Lewati langkah ini jika tidak tersedia.',
            'Nyalakan Wi-Fi atau data seluler agar HP dapat menentukan lokasi.',
            'Kembali ke halaman SADAR dan muat ulang halaman agar izin lokasi diterapkan.',
            'Tunggu 10–20 detik, lalu tekan Coba Lagi satu kali. Jangan menekan tombol berulang kali.',
        ],
    ),
    gpsAcquiring: notice(
        'Memakai lokasi dari halaman absensi',
        'Izin lokasi seharusnya sudah diberikan saat halaman absensi dibuka. Pastikan Lokasi/GPS HP sudah aktif. Jika popup belum muncul, muat ulang halaman lalu tekan Izinkan. Absensi belum tercatat sampai GPS dan verifikasi selesai.',
        'none',
        'scanning',
        [
            'Buka Pengaturan HP dan pastikan tombol Lokasi atau GPS sudah aktif.',
            'Izin diminta saat masuk halaman, bukan di modal ini. Jika ditolak, muat ulang halaman lalu tekan Izinkan atau Allow.',
            'Jika sudah diblokir, ubah izin Lokasi di pengaturan situs browser, lalu muat ulang halaman.',
        ],
    ),
    gpsTakingLonger: notice(
        'Lokasi masih dicari',
        'Pengambilan GPS membutuhkan waktu lebih lama dari biasanya. Pastikan Lokasi/GPS HP sudah aktif dan izin SADAR di browser sudah Izinkan. Absensi belum tercatat.',
        'none',
        'warning',
        [
            'Buka Pengaturan HP, lalu pastikan tombol Lokasi atau GPS sudah aktif.',
            'Ketuk ikon gembok/pengaturan situs di browser, lalu ubah izin Lokasi menjadi Izinkan atau Allow.',
            'Muat ulang halaman jika izin belum diberikan saat halaman absensi dibuka.',
            'Nyalakan Wi-Fi atau data seluler, lalu tunggu 10–20 detik tanpa menutup modal.',
            'Jika status tetap sama setelah 20 detik, tekan Coba Lagi satu kali.',
        ],
    ),
    aiUnavailable: notice(
        'Verifikasi wajah belum siap',
        `Model verifikasi wajah gagal dimuat. Periksa koneksi internet, muat ulang halaman, lalu coba kembali. ${notRecorded}`,
    ),
    networkFailure: notice(
        'Absensi gagal dikirim',
        `Terjadi gangguan koneksi atau server. Periksa koneksi internet lalu coba kembali. ${notRecorded}`,
    ),
    reportIncomplete: notice(
        'Laporan belum lengkap',
        `Tulis laporan minimal 15 karakter sebelum melanjutkan absensi. ${notRecorded}`,
        'close',
    ),
    verificationProcessing: notice(
        'Wajah sedang diverifikasi',
        `Mohon tunggu dan jangan menutup halaman. ${notRecorded} sampai proses verifikasi selesai.`,
        'none',
        'scanning',
    ),
    verificationTakingLonger: notice(
        'Verifikasi membutuhkan waktu lebih lama',
        `Koneksi atau proses pemeriksaan wajah sedang berjalan lebih lambat. Tetap di halaman ini dan jangan menekan tombol berulang. ${notRecorded} sampai muncul pemberitahuan berhasil.`,
        'none',
        'scanning',
    ),
    success: notice(
        'Absensi berhasil',
        'Data absensi telah berhasil tercatat.',
        'none',
        'success',
    ),
};

export function cameraErrorNotice(error) {
    switch (error?.name) {
        case 'NotAllowedError':
        case 'SecurityError':
            return notice(
                'Akses kamera belum diizinkan',
                `Izin kamera ditolak atau diblokir. Izin seharusnya diberikan saat halaman absensi dibuka. ${notRecorded}`,
                'retry',
                'error',
                [
                    'Muat ulang halaman absensi, lalu tekan Izinkan saat browser meminta kamera.',
                    'Jika popup tidak muncul, ketuk ikon gembok atau pengaturan situs di samping alamat website.',
                    'Ubah izin Kamera menjadi Izinkan atau Allow, lalu tekan Coba Lagi.',
                ],
            );
        case 'NotFoundError':
        case 'DevicesNotFoundError':
            return notice(
                'Kamera tidak ditemukan',
                `Perangkat tidak menemukan kamera yang dapat digunakan. ${notRecorded}`,
                'retry',
                'error',
                [
                    'Pastikan kamera perangkat aktif, tidak tertutup, dan tidak dinonaktifkan.',
                    'Jika memakai kamera eksternal, lepas lalu hubungkan kembali.',
                    'Muat ulang halaman, kemudian tekan Coba Lagi.',
                ],
            );
        case 'NotReadableError':
        case 'TrackStartError':
            return notice(
                'Kamera sedang tidak tersedia',
                `Kamera sedang digunakan atau dikunci oleh aplikasi lain. ${notRecorded}`,
                'retry',
                'error',
                [
                    'Tutup Kamera, WhatsApp, Zoom, Google Meet, atau aplikasi lain yang memakai kamera.',
                    'Kembali ke browser dan pastikan tab SADAR tetap aktif.',
                    'Tekan Coba Lagi. Jika masih gagal, tutup dan buka kembali browser.',
                ],
            );
        case 'OverconstrainedError':
        case 'ConstraintNotSatisfiedError':
            return notice(
                'Kamera tidak sesuai pengaturan',
                `Kamera tidak mendukung pengaturan video yang diperlukan. ${notRecorded}`,
                'retry',
                'error',
                [
                    'Muat ulang halaman untuk mengatur ulang kamera.',
                    'Jika perangkat memiliki beberapa kamera, pilih kamera depan atau kamera lain.',
                    'Gunakan Chrome atau Edge versi terbaru, lalu tekan Coba Lagi.',
                ],
            );
        default:
            return notice(
                'Kamera gagal dibuka',
                `Kamera tidak dapat dimulai karena izin, perangkat, atau browser bermasalah. ${notRecorded}`,
                'retry',
                'error',
                [
                    'Pastikan izin Kamera untuk website SADAR sudah diaktifkan.',
                    'Tutup aplikasi lain yang sedang menggunakan kamera.',
                    'Muat ulang halaman, lalu tekan Coba Lagi.',
                ],
            );
    }
}

export function geolocationErrorNotice(error) {
    if (!error) {
        return VERIFICATION_NOTICES.gpsUnavailable;
    }

    if (error.code === 1 || error.code === error.PERMISSION_DENIED) {
        return notice(
            'Akses lokasi belum diizinkan',
            `Browser atau perangkat menolak akses lokasi untuk website SADAR. ${notRecorded}`,
            'retry',
            'error',
            [
                'Buka Pengaturan HP, lalu aktifkan Lokasi atau GPS.',
                'Kembali ke browser, lalu ketuk ikon pengaturan situs atau gembok di samping alamat website SADAR.',
                'Pilih Izin Situs dan ubah Lokasi menjadi Izinkan atau Allow. Jika ikon tidak ada, buka menu browser, pilih Setelan atau Pengaturan Situs, lalu pilih Lokasi.',
                'Jika HP menyediakan pilihan Lokasi Presisi atau Akurat, aktifkan pilihan tersebut. Lewati langkah ini jika tidak tersedia.',
                'Pastikan Wi-Fi atau data seluler dalam keadaan aktif.',
                'Kembali ke halaman SADAR dan muat ulang halaman agar perubahan izin diterapkan.',
                'Tunggu 10–20 detik, lalu tekan Coba Lagi satu kali dan tunggu hasilnya.',
            ],
        );
    }

    if (error.code === 2 || error.code === error.POSITION_UNAVAILABLE) {
        return VERIFICATION_NOTICES.gpsUnavailable;
    }

    if (error.code === 3 || error.code === error.TIMEOUT) {
        return notice(
            'Pengambilan lokasi terlalu lama',
            `GPS belum memberikan posisi dalam batas waktu yang tersedia. ${notRecorded}`,
            'retry',
            'warning',
            [
                'Buka pengaturan HP, lalu pastikan tombol Lokasi atau GPS sudah aktif.',
                'Pastikan izin Lokasi untuk website SADAR di browser sudah diatur ke Izinkan atau Allow.',
                'Jika HP menyediakan pilihan Lokasi Presisi atau Akurat, aktifkan pilihan tersebut. Lewati langkah ini jika tidak tersedia.',
                'Nyalakan Wi-Fi atau data seluler agar proses pencarian lokasi berjalan lancar.',
                'Matikan lalu nyalakan kembali GPS, kembali ke halaman ini, dan tunggu 10–20 detik.',
                'Tekan Coba Lagi satu kali dan tunggu hasilnya. Jangan menekan tombol berulang kali.',
            ],
        );
    }

    return VERIFICATION_NOTICES.gpsUnavailable;
}

export function serverErrorNotice(message) {
    const safeMessage = typeof message === 'string' ? message.trim() : '';

    return notice(
        'Absensi gagal dikirim',
        safeMessage
            ? `${safeMessage}${/[.!?]$/.test(safeMessage) ? '' : '.'} ${notRecorded}`
            : VERIFICATION_NOTICES.networkFailure.message,
    );
}
