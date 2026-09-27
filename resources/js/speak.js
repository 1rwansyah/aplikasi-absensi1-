/**
 * Browser Text-to-Speech helper (Speech Synthesis API).
 * Queues utterances safely: stops previous speech, waits for completion, works around Chrome pauses.
 */

export const VOICE_MESSAGES = {
    ATTENDANCE_SUCCESS: 'Absensi Anda berhasil',
    VERIFY_SUCCESS: 'Verifikasi berhasil',
    FACE_NOT_MATCH: 'Wajah tidak cocok',
    FACE_MATCH_TOO_LOW: 'Kecocokan wajah terlalu rendah. Silakan absen ulang',
    LOW_LIGHT: 'Pencahayaan Anda kurang, silakan cari tempat yang lebih terang',
    FACE_NOT_DETECTED: 'Wajah tidak terdeteksi',
    MULTIPLE_FACE: 'Hanya satu wajah diperbolehkan',
    GPS_INVALID: 'Lokasi Anda berada di luar radius absensi',
};

/** Minimum time success UI stays visible while speech plays (ms). */
export const SPEECH_SUCCESS_MIN_DISPLAY_MS = 1200;

/** Buffer after speech ends before navigation (ms). */
export const SPEECH_END_BUFFER_MS = 400;

let speechGeneration = 0;
let resumeKeepAliveId = null;
let voicesReady = false;

function synthesis() {
    return typeof window !== 'undefined' ? window.speechSynthesis : null;
}

function clearResumeKeepAlive() {
    if (resumeKeepAliveId !== null) {
        clearInterval(resumeKeepAliveId);
        resumeKeepAliveId = null;
    }
}

/** Prevents Chrome from pausing long utterances mid-speech. */
function startResumeKeepAlive() {
    clearResumeKeepAlive();
    const synth = synthesis();
    if (!synth) return;

    resumeKeepAliveId = window.setInterval(() => {
        if (!synth.speaking) {
            clearResumeKeepAlive();
            return;
        }
        synth.pause();
        synth.resume();
    }, 8000);
}

function assignIndonesianVoice(utterance) {
    const voices = synthesis()?.getVoices() ?? [];
    const idVoice = voices.find((v) => v.lang === 'id-ID')
        || voices.find((v) => v.lang.startsWith('id'));
    if (idVoice) {
        utterance.voice = idVoice;
    }
}

function waitForVoices(timeoutMs = 2500) {
    const synth = synthesis();
    if (!synth) {
        return Promise.resolve(false);
    }

    if (voicesReady && synth.getVoices().length > 0) {
        return Promise.resolve(true);
    }

    return new Promise((resolve) => {
        const finish = () => {
            voicesReady = synth.getVoices().length > 0;
            resolve(voicesReady);
        };

        if (synth.getVoices().length > 0) {
            finish();
            return;
        }

        const timer = window.setTimeout(() => {
            synth.removeEventListener('voiceschanged', onVoices);
            finish();
        }, timeoutMs);

        const onVoices = () => {
            window.clearTimeout(timer);
            synth.removeEventListener('voiceschanged', onVoices);
            finish();
        };

        synth.addEventListener('voiceschanged', onVoices);
    });
}

/**
 * Stops any in-progress or pending speech immediately.
 */
export function stopSpeech() {
    const synth = synthesis();
    if (!synth) return;

    speechGeneration += 1;
    synth.cancel();
    clearResumeKeepAlive();
}

/**
 * Speaks text and resolves when playback finishes (or is cancelled).
 * @param {string} text
 * @returns {Promise<void>}
 */
export function speak(text) {
    return new Promise((resolve) => {
        const synth = synthesis();
        if (!synth || !text) {
            resolve();
            return;
        }

        const generation = ++speechGeneration;
        synth.cancel();
        clearResumeKeepAlive();

        const settle = () => {
            if (generation === speechGeneration) {
                clearResumeKeepAlive();
            }
            resolve();
        };

        const play = () => {
            if (generation !== speechGeneration) {
                resolve();
                return;
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.95;
            assignIndonesianVoice(utterance);

            utterance.onend = settle;
            utterance.onerror = settle;

            startResumeKeepAlive();

            window.setTimeout(() => {
                if (generation !== speechGeneration) {
                    resolve();
                    return;
                }
                synth.speak(utterance);
            }, 100);
        };

        waitForVoices().then(play);
    });
}

/**
 * Waits for speech to finish and enforces a minimum visible success duration.
 * @param {string} text
 * @param {number} [minDisplayMs]
 * @returns {Promise<void>}
 */
export function speakWithMinDuration(text, minDisplayMs = SPEECH_SUCCESS_MIN_DISPLAY_MS) {
    const started = Date.now();

    return speak(text).then(() => {
        const remaining = minDisplayMs - (Date.now() - started);
        if (remaining <= 0) {
            return;
        }
        return new Promise((resolve) => window.setTimeout(resolve, remaining));
    });
}

export function isFaceMismatchMessage(message) {
    if (!message) {
        return false;
    }

    const normalized = message.toLowerCase();

    return normalized.includes('tidak cocok')
        || normalized.includes('foto profil')
        || normalized.includes('kecocokan wajah')
        || normalized.includes('absen ulang');
}

if (typeof window !== 'undefined' && synthesis()) {
    synthesis().getVoices();
    synthesis().addEventListener('voiceschanged', () => {
        voicesReady = synthesis().getVoices().length > 0;
    });
}
