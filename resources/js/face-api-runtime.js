/**
 * Singleton loader for face-api.js (local script) and AI models.
 * The library and its models load only when face verification starts.
 */

const state = {
    detectionReady: false,
    recognitionReady: false,
    scriptPromise: null,
    detectionPromise: null,
    recognitionPromise: null,
};

export function modelPath() {
    return document.querySelector('meta[name="face-model-path"]')?.content || '/models';
}

export function createDetectorOptions() {
    return new faceapi.TinyFaceDetectorOptions({
        inputSize: 160,
        scoreThreshold: 0.5,
    });
}

export function isDetectionReady() {
    return state.detectionReady;
}

export function isRecognitionReady() {
    return state.recognitionReady;
}

export function faceApiScriptUrl() {
    return document.querySelector('meta[name="face-api-script-url"]')?.content
        || '/face-api/face-api.min.js';
}

export function waitForFaceApi() {
    if (typeof faceapi !== 'undefined') {
        return Promise.resolve(true);
    }

    if (state.scriptPromise) {
        return state.scriptPromise;
    }

    const loadPromise = new Promise((resolve) => {
        let settled = false;
        let script = document.getElementById('face-api-script');

        const finish = (ok) => {
            if (settled) {
                return;
            }
            settled = true;
            script?.removeEventListener('load', onLoad);
            script?.removeEventListener('error', onError);
            resolve(ok);
        };

        const onLoad = () => {
            const ready = typeof faceapi !== 'undefined';
            if (ready) {
                window.dispatchEvent(new Event('face-api:ready'));
            }
            finish(ready);
        };

        const onError = () => {
            script?.remove();
            finish(false);
        };

        const shouldAppend = !script;
        if (shouldAppend) {
            script = document.createElement('script');
            script.id = 'face-api-script';
            script.src = faceApiScriptUrl();
            script.async = true;
        }

        script.addEventListener('load', onLoad, { once: true });
        script.addEventListener('error', onError, { once: true });

        if (shouldAppend) {
            document.head.appendChild(script);
        }
    });

    state.scriptPromise = loadPromise.then((ready) => {
        if (!ready) {
            state.scriptPromise = null;
        }
        return ready;
    });

    return state.scriptPromise;
}

export async function loadDetectionModels() {
    if (state.detectionReady) {
        return true;
    }

    if (state.detectionPromise) {
        return state.detectionPromise;
    }

    const path = modelPath();

    state.detectionPromise = Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(path),
        faceapi.nets.faceLandmark68Net.loadFromUri(path),
    ])
        .then(() => {
            state.detectionReady = true;
            return true;
        })
        .catch((err) => {
            state.detectionPromise = null;
            console.error('face-api detection models failed:', err);
            throw err;
        });

    return state.detectionPromise;
}

export async function loadRecognitionModel() {
    if (state.recognitionReady) {
        return true;
    }

    if (state.recognitionPromise) {
        return state.recognitionPromise;
    }

    await loadDetectionModels();

    const path = modelPath();

    state.recognitionPromise = faceapi.nets.faceRecognitionNet
        .loadFromUri(path)
        .then(() => {
            state.recognitionReady = true;
            return true;
        })
        .catch((err) => {
            state.recognitionPromise = null;
            console.error('face-api recognition model failed:', err);
            throw err;
        });

    return state.recognitionPromise;
}

export async function ensureDetectionReady() {
    const apiReady = await waitForFaceApi();
    if (!apiReady) {
        return false;
    }

    try {
        await loadDetectionModels();
        return true;
    } catch {
        return false;
    }
}

export async function ensureRecognitionReady() {
    const apiReady = await waitForFaceApi();
    if (!apiReady) {
        return false;
    }

    try {
        await loadRecognitionModel();
        return true;
    } catch {
        return false;
    }
}

export function preloadFaceModels() {
    waitForFaceApi().then((ready) => {
        if (ready) {
            loadDetectionModels().catch(() => {});
        }
    });
}
