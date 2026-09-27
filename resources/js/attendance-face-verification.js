/**
 * Attendance Face Verification Modal — smile liveness + face match.
 */

import {
    getAttendanceReportHtml,
    getAttendanceReportPlainLength,
} from './attendance-report.js';
import { csrfFetch } from './csrf-fetch.js';
import { showToast } from './toast.js';
import { resolveAttendanceLocation } from './attendance-geolocation.js';
import {
    cameraErrorNotice,
    serverErrorNotice,
    shouldShowDelayedVerificationNotice,
    VERIFICATION_NOTICES,
} from './attendance-verification-messages.js';
import {
    createDetectorOptions,
    ensureDetectionReady as loadDetectionModels,
    ensureRecognitionReady as loadRecognitionModel,
    isDetectionReady,
    preloadFaceModels,
} from './face-api-runtime.js';
import {
    distanceToMatchPercent,
    formatMatchPercent,
    matchPercentBadgeClasses,
    meetsMinMatchPercent,
} from './face-match.js';
import {
    isFaceMismatchMessage,
    speak,
    speakWithMinDuration,
    stopSpeech,
    SPEECH_END_BUFFER_MS,
    VOICE_MESSAGES,
} from './speak.js';

const MIN_REPORT_LENGTH = 15;

const FaceVerificationModal = {
    detectorOptions: null,
    stream: null,
    currentForm: null,
    profileDescriptor: null,
    verified: false,
    confirmTimer: null,
    voiceCooldownMs: 1800,
    voiceEvents: new Map(),
    warmUpPromise: null,
    cachedLocation: null,
    keepCameraOnClose: true,
    capturedPhotoDataUrl: null,
    successAlertShown: false,
    verificationInFlight: false,
    verificationDelayTimer: null,
    locationInFlight: false,
    locationDelayTimer: null,
    activeNotice: null,
    reportListenerBound: false,

    el: (id) => document.getElementById(id),

    modal() { return this.el('face-verification-modal'); },
    videoEl() { return this.el('face-video'); },
    canvasEl() { return this.el('face-canvas'); },
    previewImgEl() { return this.el('face-preview-image'); },
    statusEl() { return this.el('face-status'); },
    badgeEl() { return this.el('face-status-badge'); },
    retryBtn() { return this.el('face-retry-button'); },
    cancelBtn() { return this.el('face-cancel-button'); },
    verifyBtn() { return this.el('face-verify-button'); },
    captureBtn() { return this.el('face-capture-button'); },
    retakeBtn() { return this.el('face-retake-button'); },
    usePhotoBtn() { return this.el('face-use-photo-button'); },
    profileImg() { return this.el('face-modal-profile-photo'); },
    titleEl() { return this.el('face-modal-title'); },
    challengeLabelEl() { return this.el('face-challenge-label'); },
    challengeHintEl() { return this.el('face-challenge-hint'); },
    matchPercentEl() { return this.el('face-match-percent'); },
    loadingPhaseEl() { return this.el('face-loading-phase'); },
    previewLabelEl() { return this.el('face-preview-label'); },
    liveIndicatorEl() { return this.el('face-live-indicator'); },
    cameraPanelEl() { return this.el('face-camera-panel'); },
    readyPanelEl() { return this.el('face-ready-panel'); },
    readyTitleEl() { return this.el('face-ready-title'); },
    readyHintEl() { return this.el('face-ready-hint'); },
    readyStepsHeadingEl() { return this.el('face-ready-steps-heading'); },
    readyStepsEl() { return this.el('face-ready-steps'); },
    loadingGuidanceEl() { return this.el('face-loading-guidance'); },
    panelLiveBadgeEl() { return this.el('face-panel-live-badge'); },

    config() {
        return window.attendanceFaceConfig || {};
    },

    threshold() {
        const fromForm = parseFloat(this.currentForm?.dataset.faceThreshold);
        if (!Number.isNaN(fromForm)) {
            return fromForm;
        }
        return parseFloat(this.config().threshold) || 0.5;
    },

    minMatchPercent() {
        const fromForm = parseInt(this.currentForm?.dataset.minMatchPercent, 10);
        if (!Number.isNaN(fromForm)) {
            return fromForm;
        }
        const fromConfig = parseInt(this.config().minMatchPercent, 10);
        return Number.isNaN(fromConfig) ? 74 : fromConfig;
    },

    async ensureDetectionReady() {
        if (isDetectionReady() && this.detectorOptions) {
            this.setPipelineStep('ai', true);
            return true;
        }

        const ok = await loadDetectionModels();
        if (ok) {
            this.detectorOptions = createDetectorOptions();
            this.setPipelineStep('ai', true);
        }
        return ok;
    },

    async ensureRecognitionReady() {
        return loadRecognitionModel();
    },

    setLoadingPhase(phase, message, badgeType = 'loading') {
        const labels = {
            gps: 'Mengambil lokasi GPS...',
            camera: 'Menyiapkan kamera & AI...',
            ai: 'Memuat model AI...',
            verify: 'Menyiapkan verifikasi...',
            ready: 'Kamera aktif — siap ambil foto',
            scanning: 'Memindai wajah...',
        };

        const phaseEl = this.loadingPhaseEl();
        if (phaseEl) {
            phaseEl.textContent = labels[phase] || message;
        }

        if (message) {
            this.setStatus(message, badgeType);
        } else if (labels[phase]) {
            this.setStatus(labels[phase], badgeType);
        }

        if (phase === 'ready') {
            this.revealCamera();
        } else if (['gps', 'camera', 'ai', 'verify'].includes(phase)) {
            this.revealLoadingStatus();
        }
    },

    isGpsReady() {
        if (!this.cachedLocation) {
            return false;
        }

        if (!this.currentForm) {
            return true;
        }

        return this.isLocationWithinGeofence(this.currentForm, this.cachedLocation);
    },

    isHardwareReady() {
        return this.isCameraActive() && this.isGpsReady();
    },

    setCaptureEnabled(enabled) {
        const btn = this.captureBtn();
        if (btn) {
            btn.disabled = !enabled;
        }
    },

    syncReadinessUi(statusType = 'loading') {
        const hardwareReady = this.isHardwareReady();
        const cameraLive = this.isCameraActive();
        const hasCapturedPhoto = Boolean(this.capturedPhotoDataUrl);
        const isError = statusType === 'error' || statusType === 'warning';
        const isWarning = statusType === 'warning';
        const isSuccess = statusType === 'success';

        const previewLabel = this.previewLabelEl();
        if (previewLabel) {
            if (hasCapturedPhoto) {
                previewLabel.textContent = 'Preview foto';
                previewLabel.classList.remove('hidden');
            } else if (cameraLive) {
                previewLabel.textContent = 'Preview mirror aktif';
                previewLabel.classList.remove('hidden');
            } else {
                previewLabel.textContent = 'Menyiapkan preview...';
                previewLabel.classList.remove('hidden');
            }
        }

        const liveIndicator = this.liveIndicatorEl();
        if (liveIndicator) {
            liveIndicator.classList.toggle('hidden', !hardwareReady || hasCapturedPhoto || isError);
        }

        const panelLive = this.panelLiveBadgeEl();
        if (panelLive) {
            panelLive.classList.toggle('hidden', !hardwareReady || isError);
        }

        const loadingGuidance = this.loadingGuidanceEl();
        if (loadingGuidance) {
            loadingGuidance.classList.toggle(
                'hidden',
                hardwareReady || hasCapturedPhoto || isError || isSuccess,
            );
        }

        const title = this.readyTitleEl();
        const hint = this.readyHintEl();
        const panel = this.readyPanelEl();

        if (title && hint) {
            if (isSuccess) {
                title.textContent = this.activeNotice?.title || 'Absensi berhasil';
                hint.textContent = this.activeNotice?.message || 'Data absensi telah berhasil tercatat.';
                title.className = 'mt-1 text-base font-bold text-emerald-800 dark:text-emerald-200 sm:text-lg';
                hint.className = 'mt-1 text-xs text-emerald-700/90 dark:text-emerald-300/90';
            } else if (isError) {
                title.textContent = this.activeNotice?.title || 'Verifikasi belum siap';
                hint.textContent = this.activeNotice?.message || 'Perbaiki masalah di bawah, lalu tekan Coba Lagi. Absensi belum tercatat.';
                title.className = isWarning
                    ? 'mt-1 text-base font-bold text-amber-800 dark:text-amber-200 sm:text-lg'
                    : 'mt-1 text-base font-bold text-red-800 dark:text-red-200 sm:text-lg';
                hint.className = isWarning
                    ? 'mt-1 text-xs text-amber-700/90 dark:text-amber-300/90'
                    : 'mt-1 text-xs text-red-700/90 dark:text-red-300/90';
            } else if (statusType === 'scanning' && this.activeNotice) {
                title.textContent = this.activeNotice.title;
                hint.textContent = this.activeNotice.message;
                title.className = 'mt-1 text-base font-bold text-blue-800 dark:text-blue-200 sm:text-lg';
                hint.className = 'mt-1 text-xs text-blue-700/90 dark:text-blue-300/90';
            } else if (hasCapturedPhoto) {
                title.textContent = 'Foto siap diperiksa';
                hint.textContent = 'Periksa hasil foto, lalu gunakan atau ulangi.';
                title.className = 'mt-1 text-base font-bold text-blue-800 dark:text-blue-200 sm:text-lg';
                hint.className = 'mt-1 text-xs text-blue-700/90 dark:text-blue-300/90';
            } else if (hardwareReady) {
                title.textContent = 'Verifikasi wajah realtime aktif';
                hint.textContent = 'Pastikan hanya satu wajah terlihat jelas di kamera.';
                title.className = 'mt-1 text-base font-bold text-blue-800 dark:text-blue-200 sm:text-lg';
                hint.className = 'mt-1 text-xs text-blue-700/90 dark:text-blue-300/90';
            } else if (!this.isGpsReady()) {
                title.textContent = 'Memakai izin lokasi dari halaman absensi';
                hint.textContent = 'Izin lokasi seharusnya sudah diizinkan saat halaman dibuka. Jika belum, muat ulang halaman lalu tekan Izinkan.';
                title.className = 'mt-1 text-base font-bold text-blue-800 dark:text-blue-200 sm:text-lg';
                hint.className = 'mt-1 text-xs text-blue-700/90 dark:text-blue-300/90';
            } else if (!cameraLive) {
                title.textContent = 'Memakai kamera yang sudah disiapkan';
                hint.textContent = 'Izin kamera seharusnya sudah diizinkan saat masuk halaman. Hadapkan wajah, lalu ambil foto. Jika kamera belum tampil, tekan Coba Lagi.';
                title.className = 'mt-1 text-base font-bold text-blue-800 dark:text-blue-200 sm:text-lg';
                hint.className = 'mt-1 text-xs text-blue-700/90 dark:text-blue-300/90';
            } else {
                title.textContent = 'Menyiapkan verifikasi...';
                hint.textContent = 'Tunggu lokasi GPS dan kamera siap sebelum mengambil foto.';
                title.className = 'mt-1 text-base font-bold text-blue-800 dark:text-blue-200 sm:text-lg';
                hint.className = 'mt-1 text-xs text-blue-700/90 dark:text-blue-300/90';
            }
        }

        if (panel) {
            panel.className = isSuccess
                ? 'rounded-xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 dark:border-emerald-900/50 dark:bg-emerald-950/30'
                : isWarning
                ? 'rounded-xl border border-amber-200 bg-amber-50/90 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-950/30'
                : isError
                    ? 'rounded-xl border border-red-200 bg-red-50/90 px-4 py-3 dark:border-red-900/50 dark:bg-red-950/30'
                    : 'rounded-xl border border-blue-100 bg-blue-50/90 px-4 py-3 dark:border-blue-900/50 dark:bg-blue-950/30';
        }

        this.setCaptureEnabled(hardwareReady && !hasCapturedPhoto && !isError);
    },

    async startCamera({ prewarm = false } = {}) {
        if (!navigator.mediaDevices?.getUserMedia) {
            if (!prewarm) {
                this.setNotice(VERIFICATION_NOTICES.cameraUnsupported);
            }
            return false;
        }

        if (this.isCameraActive()) {
            return this.attachStreamToVideo();
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
                audio: false,
            });
            const attached = await this.attachStreamToVideo();
            if (!attached) {
                if (!prewarm) {
                    this.setNotice(cameraErrorNotice());
                }
                return false;
            }
            if (!prewarm) {
                this.setLoadingPhase('ready', 'Kamera aktif — siap ambil foto', 'ready');
            }
            this.setPipelineStep('camera', true);
            return true;
        } catch (error) {
            console.error('Gagal membuka kamera:', error);
            if (!prewarm) {
                this.setNotice(cameraErrorNotice(error));
            }
            return false;
        }
    },

    isCameraActive() {
        return Boolean(this.stream?.active && this.stream.getVideoTracks().some((track) => track.readyState === 'live'));
    },

    async attachStreamToVideo() {
        const video = this.videoEl();
        if (!video || !this.stream) {
            return false;
        }

        if (video.srcObject !== this.stream) {
            video.srcObject = this.stream;
        }

        try {
            await video.play();
            return true;
        } catch {
            return false;
        }
    },

    stopCamera() {
        this.releaseCamera();
    },

    releaseCamera() {
        this.stream?.getTracks().forEach((t) => t.stop());
        this.stream = null;
        const video = this.videoEl();
        if (video) {
            video.srcObject = null;
        }
        this.setPipelineStep('camera', false);
    },

    onVisibilityChange() {
        if (document.visibilityState !== 'visible' || !this.isCameraActive()) {
            return;
        }
        this.attachStreamToVideo().catch(() => { });
    },

    canWarmUp() {
        const cfg = this.config();
        return Boolean(cfg.profilePhotoUrl || cfg.hasFaceRegistered);
    },

    async preloadProfileDescriptor(photoUrl) {
        if (this.profileDescriptor || !photoUrl) {
            return;
        }

        const recognitionOk = await this.ensureRecognitionReady();
        if (!recognitionOk || !this.detectorOptions) {
            return;
        }

        const img = await new Promise((resolve, reject) => {
            const el = new Image();
            el.crossOrigin = 'anonymous';
            el.onload = () => resolve(el);
            el.onerror = () => reject(new Error('Foto profil tidak dapat dimuat.'));
            el.src = photoUrl;
        });

        const detection = await faceapi
            .detectSingleFace(img, this.detectorOptions)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (detection) {
            this.profileDescriptor = detection.descriptor;
        }
    },

    warmUp() {
        if (!this.canWarmUp()) {
            return Promise.resolve(false);
        }

        if (this.warmUpPromise) {
            return this.warmUpPromise;
        }

        this.warmUpPromise = (async () => {
            preloadFaceModels();
            await this.ensureDetectionReady();
            await loadRecognitionModel();

            const cfg = this.config();
            if (cfg.profilePhotoUrl && cfg.hasFaceRegistered) {
                await this.preloadProfileDescriptor(cfg.profilePhotoUrl);
            }

            await this.startCamera({ prewarm: true });
            return true;
        })().catch(() => false);

        return this.warmUpPromise;
    },

    setNotice(notice) {
        if (['error', 'warning', 'success'].includes(notice.type)) {
            this.clearVerificationDelayNotice();
        }

        this.activeNotice = notice;
        this.setStatus(notice.message, notice.type, notice);
        this.renderNoticeSteps(notice.steps, notice.type);
        this.revealNotice(notice.type);

        const retry = this.retryBtn();
        if (retry) {
            retry.classList.toggle('hidden', notice.action !== 'retry');
            retry.querySelector('[data-button-label]')?.replaceChildren('Coba Lagi');
        }

        const retake = this.retakeBtn();
        if (retake) {
            retake.classList.toggle('hidden', notice.action !== 'retake');
        }

        if (notice.action === 'retake') {
            this.usePhotoBtn()?.classList.add('hidden');
        }
    },

    revealNotice(type) {
        if (type !== 'error' && type !== 'warning') {
            return;
        }

        this.scrollModalTo(this.readyPanelEl(), true);
    },

    revealLoadingStatus() {
        this.scrollModalTo(this.readyPanelEl());
    },

    revealCamera() {
        this.scrollModalTo(this.cameraPanelEl());
    },

    scrollModalTo(element, focus = false) {
        window.requestAnimationFrame(() => {
            if (!element) {
                return;
            }

            element.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'center',
            });
            if (focus) {
                element.focus({ preventScroll: true });
            }
        });
    },

    renderNoticeSteps(steps = [], type = 'error') {
        const heading = this.readyStepsHeadingEl();
        const list = this.readyStepsEl();
        if (!list) {
            return;
        }

        list.replaceChildren();
        heading?.classList.toggle('hidden', steps.length === 0);
        list.classList.toggle('hidden', steps.length === 0);

        const warning = type === 'warning';
        if (heading) {
            heading.className = steps.length === 0
                ? 'hidden'
                : `mt-3 border-t pt-3 text-xs font-bold ${
                    warning
                        ? 'border-amber-200/70 text-amber-800 dark:border-amber-800/60 dark:text-amber-200'
                        : 'border-red-200/70 text-red-800 dark:border-red-800/60 dark:text-red-200'
                }`;
        }
        list.className = steps.length === 0
            ? 'hidden'
            : `mt-2 list-decimal space-y-2 pl-5 text-xs leading-relaxed ${
                warning
                    ? 'text-amber-800 dark:text-amber-200'
                    : 'text-red-800 dark:text-red-200'
            }`;

        steps.forEach((step) => {
            const item = document.createElement('li');
            item.textContent = step;
            list.appendChild(item);
        });
    },

    setStatus(message, type = 'loading', notice = null) {
        const status = this.statusEl();
        const badge = this.badgeEl();

        if (notice) {
            this.activeNotice = notice;
        } else if (type !== 'error' && type !== 'warning') {
            this.activeNotice = null;
            this.renderNoticeSteps();
        }

        if (status) {
            status.textContent = notice?.title || message;
            status.setAttribute('aria-live', 'polite');
        }
        if (!badge) {
            this.syncReadinessUi(type);
            return;
        }

        const map = {
            loading: 'inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            ready: 'inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/50 dark:text-green-200',
            error: 'inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-200',
            warning: 'inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
            scanning: 'inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',
            success: 'inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/50 dark:text-green-200',
        };
        const label = {
            loading: 'Memuat...',
            ready: 'Siap',
            error: 'Gagal',
            warning: 'Perhatian',
            scanning: 'Memindai...',
            success: 'Berhasil',
        };
        badge.className = map[type] || map.loading;
        badge.textContent = label[type] || 'Memuat...';
        this.syncReadinessUi(type);
    },

    setPipelineStep(step, active) {
        const el = this.el(`face-pipeline-${step}`);
        if (!el) {
            return;
        }

        const dot = el.querySelector('[data-pipeline-dot]');
        const base = 'flex items-center gap-2 rounded-lg border px-2.5 py-2 text-[11px] font-medium transition-colors';
        if (active) {
            el.className = `${base} border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-200`;
            if (dot) {
                dot.className = 'h-2 w-2 shrink-0 rounded-full bg-emerald-500';
            }
            return;
        }

        el.className = `${base} border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-600 dark:bg-gray-900/40 dark:text-gray-400`;
        if (dot) {
            dot.className = 'h-2 w-2 shrink-0 rounded-full bg-gray-300 dark:bg-gray-600';
        }
    },

    resetPipeline() {
        ['camera', 'ai', 'face', 'verify', 'match'].forEach((step) => {
            this.setPipelineStep(step, false);
        });
        const matchEl = this.matchPercentEl();
        if (matchEl) {
            matchEl.classList.add('hidden');
            matchEl.textContent = '—';
            matchEl.className = 'hidden inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold';
        }
    },

    showMatchPercent(percent) {
        const el = this.matchPercentEl();
        if (!el || percent == null) {
            return;
        }
        el.textContent = formatMatchPercent(percent);
        el.className = matchPercentBadgeClasses(percent);
        el.classList.remove('hidden');
        this.setPipelineStep('match', true);
    },

    clearConfirmTimer() {
        if (this.confirmTimer) {
            clearTimeout(this.confirmTimer);
            this.confirmTimer = null;
        }
    },

    startVerificationDelayNotice() {
        this.clearVerificationDelayNotice();
        this.verificationDelayTimer = window.setTimeout(() => {
            if (shouldShowDelayedVerificationNotice({
                verificationInFlight: this.verificationInFlight,
                verified: this.verified,
                activeNoticeType: this.activeNotice?.type,
            })) {
                this.setNotice(VERIFICATION_NOTICES.verificationTakingLonger);
            }
        }, 4000);
    },

    clearVerificationDelayNotice() {
        if (this.verificationDelayTimer) {
            window.clearTimeout(this.verificationDelayTimer);
            this.verificationDelayTimer = null;
        }
    },

    startLocationDelayNotice() {
        this.clearLocationDelayNotice();
        this.locationDelayTimer = window.setTimeout(() => {
            if (this.locationInFlight && !this.isGpsReady()) {
                this.setNotice(VERIFICATION_NOTICES.gpsTakingLonger);
            }
        }, 4000);
    },

    clearLocationDelayNotice() {
        if (this.locationDelayTimer) {
            window.clearTimeout(this.locationDelayTimer);
            this.locationDelayTimer = null;
        }
    },

    async playVoice(text) {
        try {
            await speak(text);
        } catch {
            // ignore speech errors
        }
    },

    playSuccessSound() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                return;
            }

            const context = new AudioContext();
            const playTone = (frequency, startTime, duration) => {
                const oscillator = context.createOscillator();
                const gain = context.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, startTime);
                gain.gain.exponentialRampToValueAtTime(0.12, startTime + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);

                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start(startTime);
                oscillator.stop(startTime + duration);
            };

            const now = context.currentTime;
            playTone(880, now, 0.12);
            playTone(1175, now + 0.12, 0.18);

            window.setTimeout(() => {
                context.close().catch(() => {});
            }, 500);
        } catch {
            // ignore audio errors
        }
    },

    showSuccessAlert() {
        if (this.successAlertShown) {
            return;
        }
        this.successAlertShown = true;

        const modal = this.modal();
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        showToast('success', 'Absensi berhasil. Data telah tercatat.', 2500);

        this.playSuccessSound();
    },

    async announce(key, text, cooldownMs = this.voiceCooldownMs) {
        if (!text) {
            return;
        }
        const now = Date.now();
        const last = this.voiceEvents.get(key) || 0;
        if (now - last < cooldownMs) {
            return;
        }
        this.voiceEvents.set(key, now);
        await this.playVoice(text);
    },

    scheduleConfirmAfterSuccess() {
        this.clearConfirmTimer();
        speakWithMinDuration(VOICE_MESSAGES.VERIFY_SUCCESS).then(() => {
            this.confirmTimer = setTimeout(() => {
                this.confirmTimer = null;
                if (this.verified) {
                    this.confirm();
                }
            }, SPEECH_END_BUFFER_MS);
        });
    },

    captureFrame() {
        const video = this.videoEl();
        const canvas = this.canvasEl();
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.save();
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0);
        ctx.restore();

        return canvas.toDataURL('image/jpeg', 0.85);
    },

    capturePhoto() {
        if (!this.isHardwareReady()) {
            this.setNotice(
                this.isGpsReady()
                    ? VERIFICATION_NOTICES.cameraNotReady
                    : VERIFICATION_NOTICES.gpsUnavailable,
            );
            return;
        }

        const video = this.videoEl();
        const previewImg = this.previewImgEl();
        const canvas = this.canvasEl();
        
        if (!video || !previewImg || !canvas) {
            return;
        }

        // Capture frame using existing captureFrame method (preserves existing mirror behavior)
        this.capturedPhotoDataUrl = this.captureFrame();
        previewImg.src = this.capturedPhotoDataUrl;

        // Show preview, hide video
        video.classList.add('hidden');
        previewImg.classList.remove('hidden');

        // Update buttons
        this.captureBtn()?.classList.add('hidden');
        this.retakeBtn()?.classList.remove('hidden');
        this.usePhotoBtn()?.classList.remove('hidden');
        this.usePhotoBtn() && (this.usePhotoBtn().disabled = !this.isGpsReady());

        this.setStatus('Foto berhasil diambil. Silakan periksa hasilnya.', 'ready');
    },

    retakePhoto() {
        const video = this.videoEl();
        const previewImg = this.previewImgEl();

        if (!video || !previewImg) {
            return;
        }

        // Clear captured photo
        this.capturedPhotoDataUrl = null;
        previewImg.src = '';

        // Show video, hide preview
        video.classList.remove('hidden');
        previewImg.classList.add('hidden');

        // Update buttons
        this.captureBtn()?.classList.remove('hidden');
        this.retakeBtn()?.classList.add('hidden');
        this.usePhotoBtn()?.classList.add('hidden');

        if (this.isHardwareReady()) {
            this.setStatus('Kamera aktif — siap ambil foto', 'ready');
        } else {
            this.setStatus('Menyiapkan kamera dan lokasi GPS...', 'loading');
            this.setCaptureEnabled(false);
        }
    },

    async detectFromVideo() {
        return faceapi
            .detectAllFaces(this.videoEl(), this.detectorOptions)
            .withFaceLandmarks()
            .withFaceDescriptors();
    },

    distance(a, b) {
        return Math.hypot(a.x - b.x, a.y - b.y);
    },

    averagePoint(points) {
        const total = points.reduce((acc, point) => ({
            x: acc.x + point.x,
            y: acc.y + point.y,
        }), { x: 0, y: 0 });
        return { x: total.x / points.length, y: total.y / points.length };
    },

    attendanceForm() {
        return document.querySelector('[data-attendance-face-form]');
    },

    async fetchLocation(form) {
        return resolveAttendanceLocation({
            mapId: form.dataset.geofenceMapId || '',
            requiresGeofence: form.dataset.requiresGeofence === '1',
        });
    },

    async preloadPageLocation() {
        const form = this.attendanceForm();

        if (!form) {
            return;
        }

        try {
            this.cachedLocation = await this.fetchLocation(form);
        } catch {
            this.cachedLocation = null;
        }
    },

    isLocationWithinGeofence(form, geo) {
        return form.dataset.requiresGeofence !== '1' || geo.withinRadius;
    },

    async acquireLocation(form) {
        this.locationInFlight = true;
        this.setLoadingPhase('gps', 'Mengambil lokasi GPS...', 'scanning');
        this.setNotice(VERIFICATION_NOTICES.gpsAcquiring);
        this.startLocationDelayNotice();
        this.revealLoadingStatus();

        try {
            const geo = await this.fetchLocation(form);

            if (!this.isLocationWithinGeofence(form, geo)) {
                this.cachedLocation = null;
                this.setNotice(VERIFICATION_NOTICES.gpsOutsideRadius);
                await this.announce('gps-invalid', VOICE_MESSAGES.GPS_INVALID);
                return false;
            }

            this.cachedLocation = geo;
            return true;
        } catch (err) {
            console.error('Gagal mengambil lokasi absensi:', err);
            this.cachedLocation = null;
            this.setNotice(err.verificationNotice || VERIFICATION_NOTICES.gpsUnavailable);
            return false;
        } finally {
            this.locationInFlight = false;
            this.clearLocationDelayNotice();
        }
    },

    async ensureLocationReady(form) {
        if (this.cachedLocation) {
            if (!this.isLocationWithinGeofence(form, this.cachedLocation)) {
                this.cachedLocation = null;
                this.setNotice(VERIFICATION_NOTICES.gpsOutsideRadius);
                await this.announce('gps-invalid', VOICE_MESSAGES.GPS_INVALID);
                return false;
            }

            return true;
        }

        return this.acquireLocation(form);
    },

    async syncProfileDescriptor(form) {
        if (form.dataset.needsSync !== '1' || !form.dataset.profilePhotoUrl) {
            return true;
        }

        this.setStatus('Menyiapkan data wajah dari foto profil...', 'loading');

        const img = await new Promise((resolve, reject) => {
            const el = new Image();
            el.crossOrigin = 'anonymous';
            el.onload = () => resolve(el);
            el.onerror = () => reject(new Error('Foto profil tidak dapat dimuat.'));
            el.src = form.dataset.profilePhotoUrl;
        });

        const detections = await faceapi
            .detectAllFaces(img, this.detectorOptions)
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (detections.length !== 1) {
            this.setNotice({
                ...VERIFICATION_NOTICES.profileMissing,
                title: 'Foto profil belum dapat diverifikasi',
                message: detections.length < 1
                    ? 'Wajah tidak terdeteksi pada foto profil. Hubungi HR untuk memperbarui foto profil. Absensi belum tercatat.'
                    : 'Foto profil menampilkan lebih dari satu wajah. Hubungi HR untuk memperbarui foto profil. Absensi belum tercatat.',
            });
            return false;
        }

        this.profileDescriptor = detections[0].descriptor;

        const res = await csrfFetch(form.dataset.syncUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                face_descriptor: Array.from(detections[0].descriptor),
                faces_detected: 1,
            }),
        });

        if (!res) {
            return false;
        }

        const data = await res.json();
        if (!res.ok || !data.success) {
            this.setNotice(serverErrorNotice(data.message || 'Data wajah dari foto profil gagal disiapkan.'));
            return false;
        }

        form.dataset.needsSync = '0';
        return true;
    },

    async ensureProfileDescriptor(form) {
        if (this.profileDescriptor) {
            return true;
        }
        if (!form.dataset.profilePhotoUrl) {
            return false;
        }

        const img = await new Promise((resolve, reject) => {
            const el = new Image();
            el.crossOrigin = 'anonymous';
            el.onload = () => resolve(el);
            el.onerror = () => reject(new Error('Foto profil tidak dapat dimuat.'));
            el.src = form.dataset.profilePhotoUrl;
        });

        const detection = await faceapi
            .detectSingleFace(img, this.detectorOptions)
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            return false;
        }

        this.profileDescriptor = detection.descriptor;
        return true;
    },

    async runVerification(form) {
        this.retryBtn()?.classList.add('hidden');
        this.verified = false;
        this.setPipelineStep('verify', false);
        this.setPipelineStep('match', false);
        this.setNotice(VERIFICATION_NOTICES.verificationProcessing);

        const detectionOk = await this.ensureDetectionReady();
        if (!detectionOk) {
            this.setNotice(VERIFICATION_NOTICES.aiUnavailable);
            return;
        }

        this.setLoadingPhase('verify', 'Memverifikasi wajah...', 'scanning');
        this.setNotice(VERIFICATION_NOTICES.verificationProcessing);
        this.setPipelineStep('verify', true);

        const recognitionOk = await this.ensureRecognitionReady();
        if (!recognitionOk) {
            this.setNotice(VERIFICATION_NOTICES.aiUnavailable);
            return;
        }

        if (form.dataset.needsSync === '1') {
            const synced = await this.syncProfileDescriptor(form);
            if (!synced) {
                return;
            }
        } else {
            await this.ensureProfileDescriptor(form);
        }

        // Use captured photo for verification instead of live video
        const previewImg = this.previewImgEl();
        if (!previewImg || !this.capturedPhotoDataUrl) {
            this.setNotice(VERIFICATION_NOTICES.photoMissing);
            this.retakePhoto();
            return;
        }

        // Load image for face detection
        const img = await new Promise((resolve, reject) => {
            const el = new Image();
            el.crossOrigin = 'anonymous';
            el.onload = () => resolve(el);
            el.onerror = () => reject(new Error('Gagal memuat foto.'));
            el.src = this.capturedPhotoDataUrl;
        });

        const faces = await faceapi
            .detectAllFaces(img, this.detectorOptions)
            .withFaceLandmarks()
            .withFaceDescriptors();

        if (faces.length === 0) {
            this.setNotice(VERIFICATION_NOTICES.faceNotDetected);
            await this.playVoice(VOICE_MESSAGES.FACE_NOT_DETECTED);
            return;
        }
        if (faces.length > 1) {
            this.setNotice(VERIFICATION_NOTICES.multipleFaces);
            await this.announce('multiple-face-verify', VOICE_MESSAGES.MULTIPLE_FACE);
            return;
        }

        const result = faces[0];

        if (!result?.descriptor) {
            this.setNotice(VERIFICATION_NOTICES.faceNotDetected);
            await this.announce('face-not-detected-verify', VOICE_MESSAGES.FACE_NOT_DETECTED);
            return;
        }

        const liveDescriptor = result.descriptor;

        if (this.profileDescriptor) {
            const distance = faceapi.euclideanDistance(liveDescriptor, this.profileDescriptor);
            const percent = distanceToMatchPercent(distance);
            const minPercent = this.minMatchPercent();
            this.showMatchPercent(percent);

            if (!meetsMinMatchPercent(percent, minPercent)) {
                this.setNotice({
                    ...VERIFICATION_NOTICES.faceMismatch,
                    message: `Kecocokan wajah hanya ${percent ?? 0}%, sedangkan batas minimal adalah ${minPercent}%. Ikuti langkah perbaikan di bawah lalu ambil foto ulang. Absensi belum tercatat.`,
                });
                await this.playVoice(VOICE_MESSAGES.FACE_MATCH_TOO_LOW);
                return;
            }
        }

        const photo = this.capturedPhotoDataUrl;
        const reportName = form.dataset.type === 'clock-in' ? 'clock_in_report' : 'clock_out_report';
        const report = getAttendanceReportHtml(form);

        const geo = this.cachedLocation;

        if (!geo) {
            this.setNotice(VERIFICATION_NOTICES.gpsUnavailable);
            return;
        }

        if (!this.isLocationWithinGeofence(form, geo)) {
            this.cachedLocation = null;
            this.setNotice(VERIFICATION_NOTICES.gpsOutsideRadius);
            await this.announce('gps-invalid', VOICE_MESSAGES.GPS_INVALID);
            return;
        }

        const payload = {
            [reportName]: report,
            face_descriptor: Array.from(liveDescriptor),
            faces_detected: 1,
            verification_photo: photo,
            latitude: geo.latitude,
            longitude: geo.longitude,
        };

        if (geo.location) {
            payload.attendance_location = geo.location;
        }

        const res = await csrfFetch(form.dataset.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (!res) {
            return;
        }

        const data = await res.json();

        if (!res.ok || !data.success) {
            if (isFaceMismatchMessage(data.message)) {
                this.setNotice(VERIFICATION_NOTICES.faceMismatch);
                await this.playVoice(VOICE_MESSAGES.FACE_NOT_MATCH);
            } else {
                this.setNotice(serverErrorNotice(data.message));
            }
            return;
        }

        this.verified = true;
        this.usePhotoBtn()?.classList.add('hidden');
        this.markFormAttendanceCompleted(form);
        this.setNotice(VERIFICATION_NOTICES.success);
        this.showSuccessAlert();
        this.scheduleConfirmAfterSuccess();
    },

    /**
     * Persist success on the form immediately so a GPS failure on a second open
     * (before page reload) cannot claim attendance was never recorded.
     */
    markFormAttendanceCompleted(form) {
        if (!form) {
            return;
        }

        const actionType = form.dataset.type;
        if (actionType === 'clock-in') {
            form.dataset.hasClockIn = '1';
        }
        if (actionType === 'clock-out') {
            form.dataset.hasClockOut = '1';
        }

        form.querySelectorAll(`[data-attendance-action="${actionType}"]`).forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });
    },

    async open(form) {
        const cfg = this.config();

        if (!cfg.hasFaceRegistered && !cfg.profilePhotoUrl) {
            this.showFormAlert(
                form,
                VERIFICATION_NOTICES.profileMissing.title,
                VERIFICATION_NOTICES.profileMissing.message,
                'error',
            );
            return;
        }

        if (form.dataset.type === 'clock-in' && form.dataset.hasClockIn === '1') {
            showToast('warning', 'Anda sudah absen masuk', 2000);
            return;
        }

        if (form.dataset.type === 'clock-out' && form.dataset.hasClockOut === '1') {
            showToast('warning', 'Anda sudah absen pulang', 2000);
            return;
        }

        this.currentForm = form;
        this.verified = false;
        this.verificationInFlight = false;
        this.clearVerificationDelayNotice();
        this.clearLocationDelayNotice();
        this.locationInFlight = false;
        this.successAlertShown = false;
        this.voiceEvents.clear();
        stopSpeech();
        this.clearConfirmTimer();
        this.resetPipeline();

        // Reset preview state
        this.capturedPhotoDataUrl = null;
        const previewImg = this.previewImgEl();
        if (previewImg) {
            previewImg.src = '';
            previewImg.classList.add('hidden');
        }
        const video = this.videoEl();
        if (video) {
            video.classList.remove('hidden');
        }

        // Reset buttons
        this.captureBtn()?.classList.remove('hidden');
        this.setCaptureEnabled(false);
        this.retakeBtn()?.classList.add('hidden');
        this.usePhotoBtn()?.classList.add('hidden');

        const label = form.dataset.type === 'clock-in' ? 'Absen Masuk' : 'Absen Pulang';
        if (this.titleEl()) {
            this.titleEl().textContent = `Verifikasi Wajah — ${label}`;
        }

        const photoUrl = form.dataset.profilePhotoUrl || cfg.profilePhotoUrl || '';
        const profileImg = this.profileImg();
        if (profileImg && photoUrl) {
            profileImg.src = photoUrl;
            profileImg.classList.remove('hidden');
        }

        const modal = this.modal();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';

        this.retryBtn()?.classList.add('hidden');
        this.setLoadingPhase('gps', 'Mengambil lokasi GPS...', 'scanning');

        try {
            const locationOk = await this.ensureLocationReady(form);
            if (!locationOk) {
                this.setCaptureEnabled(false);
                return;
            }

            this.setLoadingPhase(
                'camera',
                'Kamera dan model AI sedang dimuat. Mohon tunggu...',
                'loading',
            );

            await this.warmUp();

            if (!this.isCameraActive()) {
                const camOk = await this.startCamera();
                if (!camOk) {
                    this.setCaptureEnabled(false);
                    return;
                }
            } else {
                await this.attachStreamToVideo();
                this.setPipelineStep('camera', true);
                this.setLoadingPhase('ready', 'Kamera aktif — siap ambil foto', 'ready');
            }

            if (!isDetectionReady()) {
                this.setLoadingPhase('ai', 'Model AI sedang dimuat. Mohon tunggu...', 'loading');
            }

            const aiOk = await this.ensureDetectionReady();
            if (!aiOk) {
                this.setNotice(VERIFICATION_NOTICES.aiUnavailable);
                this.setCaptureEnabled(false);
                return;
            }

            this.setLoadingPhase('ready', 'Kamera aktif — siap ambil foto', 'ready');
        } catch (err) {
            console.error('Gagal menyiapkan verifikasi absensi:', err);
            this.setNotice(err.verificationNotice || VERIFICATION_NOTICES.networkFailure);
            this.setCaptureEnabled(false);
        }
    },

    close() {
        if (this.verificationInFlight) {
            this.setNotice(VERIFICATION_NOTICES.verificationTakingLonger);
            return;
        }

        stopSpeech();
        this.clearConfirmTimer();
        if (!this.keepCameraOnClose) {
            this.releaseCamera();
        }
        this.verified = false;
        this.verificationInFlight = false;
        this.locationInFlight = false;
        this.clearVerificationDelayNotice();
        this.clearLocationDelayNotice();
        if (!this.keepCameraOnClose) {
            this.profileDescriptor = null;
        }
        this.currentForm = null;
        this.resetPipeline();

        // Reset preview state
        this.capturedPhotoDataUrl = null;
        const previewImg = this.previewImgEl();
        if (previewImg) {
            previewImg.src = '';
            previewImg.classList.add('hidden');
        }
        const video = this.videoEl();
        if (video) {
            video.classList.remove('hidden');
        }

        // Reset buttons
        this.captureBtn()?.classList.remove('hidden');
        this.setCaptureEnabled(false);
        this.retakeBtn()?.classList.add('hidden');
        this.usePhotoBtn()?.classList.add('hidden');

        if (this.challengeLabelEl()) {
            this.challengeLabelEl().textContent = 'Verifikasi Wajah';
        }
        if (this.challengeHintEl()) {
            this.challengeHintEl().textContent = 'Hadapkan wajah ke kamera.';
        }
        const modal = this.modal();
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
        this.syncReadinessUi('loading');
    },

    confirm() {
        if (!this.verified || !this.currentForm) {
            return;
        }
        this.clearConfirmTimer();
        stopSpeech();
        this.close();
        window.location.reload();
    },

    setFormAction(form, actionType) {
        form.dataset.type = actionType;
        form.dataset.action = actionType === 'clock-in'
            ? form.dataset.actionClockIn
            : form.dataset.actionClockOut;
    },

    beginAttendanceAction(form, actionType) {
        if (form.dataset.dualAction !== '1') {
            return;
        }

        if (actionType === 'clock-in' && form.dataset.hasClockIn === '1') {
            showToast('warning', 'Anda sudah absen masuk', 2000);
            return;
        }

        if (actionType === 'clock-out' && form.dataset.hasClockOut === '1') {
            showToast('warning', 'Anda sudah absen pulang', 2000);
            return;
        }

        this.setFormAction(form, actionType);

        const reportPlainLength = getAttendanceReportPlainLength(form);

        if (reportPlainLength < MIN_REPORT_LENGTH) {
            this.showFormAlert(
                form,
                VERIFICATION_NOTICES.reportIncomplete.title,
                VERIFICATION_NOTICES.reportIncomplete.message,
                'error',
                'report-incomplete',
            );
            return;
        }

        this.open(form);
    },

    showFormAlert(form, title, message, type, code = '') {
        const alert = form.querySelector('[id$="-alert"]');
        if (!alert) {
            return;
        }

        alert.classList.remove('hidden');
        alert.setAttribute('role', 'alert');
        alert.dataset.alertCode = code;
        alert.replaceChildren();

        const titleElement = document.createElement('p');
        titleElement.className = 'font-semibold';
        titleElement.textContent = title;

        const messageElement = document.createElement('p');
        messageElement.className = 'mt-1 text-xs font-normal leading-relaxed';
        messageElement.textContent = message;

        alert.append(titleElement, messageElement);
        alert.className = type === 'error'
            ? 'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200'
            : 'rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/40 dark:text-green-200';
    },

    clearFormAlert(form, code = '') {
        const alert = form?.querySelector('[id$="-alert"]');
        if (!alert || (code && alert.dataset.alertCode !== code)) {
            return;
        }

        alert.classList.add('hidden');
        alert.removeAttribute('role');
        alert.removeAttribute('data-alert-code');
        alert.replaceChildren();
    },

    boot() {
        const modal = this.modal();
        if (!modal) {
            return;
        }

        this.cancelBtn()?.addEventListener('click', () => this.close());

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.close();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                this.close();
            }
        });

        if (!this.reportListenerBound) {
            this.reportListenerBound = true;

            document.addEventListener('attendance-report-change', (event) => {
                const form = event.target.closest?.('[data-attendance-face-form]');
                if (form && (event.detail?.plainLength ?? 0) >= MIN_REPORT_LENGTH) {
                    this.clearFormAlert(form, 'report-incomplete');
                }
            });

            document.addEventListener('input', (event) => {
                const form = event.target.closest?.('[data-attendance-face-form]');
                if (form && getAttendanceReportPlainLength(form) >= MIN_REPORT_LENGTH) {
                    this.clearFormAlert(form, 'report-incomplete');
                }
            });
        }

        this.captureBtn()?.addEventListener('click', () => {
            this.capturePhoto();
        });

        this.retakeBtn()?.addEventListener('click', () => {
            this.retakePhoto();
        });

        this.usePhotoBtn()?.addEventListener('click', async () => {
            if (!this.currentForm || this.verificationInFlight || this.verified) return;

            if (!this.isGpsReady()) {
                this.setNotice(VERIFICATION_NOTICES.gpsUnavailable);
                return;
            }

            if (!this.capturedPhotoDataUrl) {
                this.setNotice(VERIFICATION_NOTICES.photoMissing);
                return;
            }

            const btn = this.usePhotoBtn();
            if (btn) {
                this.verificationInFlight = true;
                this.startVerificationDelayNotice();
                btn.disabled = true;
                if (this.cancelBtn()) {
                    this.cancelBtn().disabled = true;
                }
                const originalText = btn.textContent;
                btn.textContent = 'Memverifikasi...';

                try {
                    await this.runVerification(this.currentForm);
                } catch (error) {
                    console.error('Gagal mengirim verifikasi absensi:', error);
                    this.setNotice(VERIFICATION_NOTICES.networkFailure);
                } finally {
                    this.clearVerificationDelayNotice();
                    this.verificationInFlight = false;
                    if (this.cancelBtn()) {
                        this.cancelBtn().disabled = false;
                    }
                    if (!this.verified) {
                        btn.disabled = !this.isGpsReady();
                        btn.textContent = originalText;
                    }
                }
            }
        });

        this.retryBtn()?.addEventListener('click', async () => {
            if (!this.currentForm) {
                return;
            }
            stopSpeech();
            this.clearConfirmTimer();
            this.retryBtn().classList.add('hidden');
            this.setCaptureEnabled(false);

            if (!this.cachedLocation) {
                this.setLoadingPhase('gps', 'Mengambil lokasi GPS...', 'scanning');
                const locationOk = await this.ensureLocationReady(this.currentForm);
                if (!locationOk) {
                    this.setCaptureEnabled(false);
                    return;
                }
            }

            // Reset preview state
            this.capturedPhotoDataUrl = null;
            const previewImg = this.previewImgEl();
            if (previewImg) {
                previewImg.src = '';
                previewImg.classList.add('hidden');
            }
            const video = this.videoEl();
            if (video) {
                video.classList.remove('hidden');
            }

            // Reset buttons
            this.captureBtn()?.classList.remove('hidden');
            this.setCaptureEnabled(false);
            this.retakeBtn()?.classList.add('hidden');
            this.usePhotoBtn()?.classList.add('hidden');

            this.resetPipeline();
            this.setPipelineStep('ai', isDetectionReady());
            this.setPipelineStep('camera', this.isCameraActive());
            if (!this.isCameraActive()) {
                this.setLoadingPhase(
                    'camera',
                    'Kamera dan model AI sedang dimuat. Mohon tunggu...',
                    'loading',
                );
            }
            if (!isDetectionReady()) {
                this.setLoadingPhase('ai', 'Model AI sedang dimuat. Mohon tunggu...', 'loading');
            }
            const aiPromise = this.ensureDetectionReady();
            const ok = this.isCameraActive() ? true : await this.startCamera();
            if (!ok) {
                this.setCaptureEnabled(false);
                return;
            }
            const aiOk = await aiPromise;
            if (aiOk) {
                this.setLoadingPhase('ready', 'Kamera aktif — siap ambil foto', 'ready');
            } else {
                this.setNotice(VERIFICATION_NOTICES.aiUnavailable);
                this.setCaptureEnabled(false);
            }
        });

        if (!this.submitListenerBound) {
            this.submitListenerBound = true;

            document.addEventListener('submit', (e) => {
                const form = e.target;

                if (!(form instanceof HTMLFormElement) || !form.matches('[data-attendance-face-form]')) {
                    return;
                }

                if (form.dataset.dualAction === '1') {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();

                const reportPlainLength = getAttendanceReportPlainLength(form);

                if (reportPlainLength < MIN_REPORT_LENGTH) {
                    this.showFormAlert(
                        form,
                        VERIFICATION_NOTICES.reportIncomplete.title,
                        VERIFICATION_NOTICES.reportIncomplete.message,
                        'error',
                        'report-incomplete',
                    );
                    return;
                }

                this.open(form);
            });
        }

        if (!this.dualActionListenerBound) {
            this.dualActionListenerBound = true;

            document.addEventListener('click', (e) => {
                const button = e.target.closest('[data-attendance-action]');

                if (!button || button.disabled) {
                    return;
                }

                const form = button.closest('[data-attendance-face-form]');

                if (!form || form.dataset.dualAction !== '1') {
                    return;
                }

                e.preventDefault();
                this.beginAttendanceAction(form, button.dataset.attendanceAction);
            });
        }

        this.preloadPageLocation().catch(() => { });
        this.warmUp().catch(() => { });
        window.addEventListener('pagehide', () => this.releaseCamera());
        document.addEventListener('visibilitychange', () => this.onVisibilityChange());
    },
};

function bootModal() {
    if (!document.getElementById('face-verification-modal')) {
        return;
    }

    FaceVerificationModal.boot();

    if (window.GeofenceMap && !window.GeofenceMap.booted) {
        window.GeofenceMap.initAll();
        window.GeofenceMap.booted = true;
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootModal);
} else {
    bootModal();
}

export default FaceVerificationModal;
