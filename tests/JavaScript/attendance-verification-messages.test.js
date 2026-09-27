import assert from 'node:assert/strict';
import test from 'node:test';

import {
    cameraErrorNotice,
    geolocationErrorNotice,
    serverErrorNotice,
    shouldShowDelayedVerificationNotice,
    VERIFICATION_NOTICES,
} from '../../resources/js/attendance-verification-messages.js';

test('camera errors provide specific actionable messages', () => {
    const errors = [
        cameraErrorNotice({ name: 'NotAllowedError' }),
        cameraErrorNotice({ name: 'NotFoundError' }),
        cameraErrorNotice({ name: 'NotReadableError' }),
        cameraErrorNotice({ name: 'OverconstrainedError' }),
    ];

    assert.deepEqual(errors.map((error) => error.title), [
        'Akses kamera belum diizinkan',
        'Kamera tidak ditemukan',
        'Kamera sedang tidak tersedia',
        'Kamera tidak sesuai pengaturan',
    ]);
    errors.forEach((error) => {
        assert.ok(error.steps.length >= 3);
        assert.match(error.steps.join(' '), /(Coba Lagi|Muat ulang)/);
    });
});

test('geolocation errors provide specific actionable messages', () => {
    const permissionDenied = geolocationErrorNotice({ code: 1 });
    const unavailable = geolocationErrorNotice({ code: 2 });
    const timeout = geolocationErrorNotice({ code: 3 });
    const outsideRadius = VERIFICATION_NOTICES.gpsOutsideRadius;

    assert.equal(permissionDenied.title, 'Akses lokasi belum diizinkan');
    assert.equal(unavailable.title, 'Lokasi belum ditemukan');
    assert.equal(timeout.title, 'Pengambilan lokasi terlalu lama');

    [permissionDenied, unavailable, timeout, outsideRadius].forEach((error) => {
        assert.ok(error.steps.length >= 4);
        assert.match(error.steps.join(' '), /GPS|Lokasi/);
        assert.match(error.steps.join(' '), /Coba Lagi/);
        assert.match(error.message, /Absensi (belum tercatat\.|pada percobaan ini belum dapat dikirim)/);
    });
});

test('face verification failures provide clear recovery steps', () => {
    const failures = [
        VERIFICATION_NOTICES.faceNotDetected,
        VERIFICATION_NOTICES.multipleFaces,
        VERIFICATION_NOTICES.faceMismatch,
        VERIFICATION_NOTICES.photoMissing,
    ];

    failures.forEach((failure) => {
        assert.equal(failure.action, 'retake');
        assert.ok(failure.steps.length >= 3);
        assert.match(failure.steps.join(' '), /Ambil Foto|foto ulang/i);
        assert.match(failure.message, /Absensi belum tercatat\./);
    });
});

test('all failure notices clearly say attendance is not recorded', () => {
    const failures = [
        VERIFICATION_NOTICES.cameraUnsupported,
        VERIFICATION_NOTICES.cameraNotReady,
        VERIFICATION_NOTICES.profileMissing,
        VERIFICATION_NOTICES.faceNotDetected,
        VERIFICATION_NOTICES.multipleFaces,
        VERIFICATION_NOTICES.faceMismatch,
        VERIFICATION_NOTICES.photoMissing,
        VERIFICATION_NOTICES.gpsOutsideRadius,
        VERIFICATION_NOTICES.gpsUnavailable,
        VERIFICATION_NOTICES.aiUnavailable,
        VERIFICATION_NOTICES.networkFailure,
        VERIFICATION_NOTICES.reportIncomplete,
        cameraErrorNotice({ name: 'NotAllowedError' }),
        geolocationErrorNotice({ code: 3 }),
        serverErrorNotice('Wajah tidak cocok'),
    ];

    failures.forEach((failure) => {
        assert.match(
            failure.message,
            /Absensi belum tercatat\.|Absensi pada percobaan ini belum dapat dikirim/,
        );
    });

    assert.match(VERIFICATION_NOTICES.reportIncomplete.message, /minimal 15 karakter/);
});

test('server message is preserved without exposing raw objects', () => {
    const mapped = serverErrorNotice('Jadwal absensi belum dibuka');

    assert.equal(mapped.title, 'Absensi gagal dikirim');
    assert.equal(mapped.message, 'Jadwal absensi belum dibuka. Absensi belum tercatat.');
    assert.equal(serverErrorNotice({ error: 'internal' }).message, VERIFICATION_NOTICES.networkFailure.message);
});

test('slow verification notice prevents users from assuming attendance is complete', () => {
    const processing = VERIFICATION_NOTICES.verificationProcessing;
    const delayed = VERIFICATION_NOTICES.verificationTakingLonger;

    assert.equal(processing.type, 'scanning');
    assert.match(processing.message, /jangan menutup halaman/i);
    assert.match(delayed.message, /jangan menekan tombol berulang/i);
    assert.match(delayed.message, /Absensi belum tercatat\./);
});

test('face verification failure is not replaced by delayed scanning status', () => {
    assert.equal(shouldShowDelayedVerificationNotice({
        verificationInFlight: true,
        verified: false,
        activeNoticeType: VERIFICATION_NOTICES.verificationProcessing.type,
    }), true);

    [
        VERIFICATION_NOTICES.faceMismatch,
        VERIFICATION_NOTICES.faceNotDetected,
        VERIFICATION_NOTICES.multipleFaces,
        VERIFICATION_NOTICES.cameraNotReady,
        VERIFICATION_NOTICES.networkFailure,
    ].forEach((failure) => {
        assert.equal(shouldShowDelayedVerificationNotice({
            verificationInFlight: true,
            verified: false,
            activeNoticeType: failure.type,
        }), false);
    });

    assert.equal(shouldShowDelayedVerificationNotice({
        verificationInFlight: true,
        verified: true,
        activeNoticeType: VERIFICATION_NOTICES.verificationProcessing.type,
    }), false);
});

test('gps acquiring guidance uses the page-level permission and still explains recovery', () => {
    const acquiring = VERIFICATION_NOTICES.gpsAcquiring;
    const delayed = VERIFICATION_NOTICES.gpsTakingLonger;

    assert.equal(acquiring.type, 'scanning');
    assert.match(acquiring.message, /halaman absensi dibuka/i);
    assert.match(acquiring.message, /Lokasi\/GPS HP sudah aktif/i);
    assert.match(acquiring.message, /Izinkan/i);
    assert.match(acquiring.steps.join(' '), /Lokasi atau GPS sudah aktif/);
    assert.match(acquiring.steps.join(' '), /Izinkan|Allow/);
    assert.match(acquiring.steps.join(' '), /pengaturan situs/i);

    assert.equal(delayed.type, 'warning');
    assert.match(delayed.message, /Lokasi\/GPS HP sudah aktif/i);
    assert.match(delayed.message, /Izinkan/i);
    assert.ok(delayed.steps.length >= 3);
    assert.match(delayed.message, /Absensi belum tercatat\./);
});
