let employeeFaceModelsReady = false;
let employeeFaceDetectorOptions = null;

async function ensureEmployeeFaceModels() {
    if (employeeFaceModelsReady || typeof faceapi === 'undefined') {
        return employeeFaceModelsReady;
    }

    const modelPath = document.querySelector('meta[name="face-model-path"]')?.content;

    if (!modelPath) {
        return false;
    }

    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
        faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
        faceapi.nets.faceRecognitionNet.loadFromUri(modelPath),
    ]);

    employeeFaceDetectorOptions = new faceapi.TinyFaceDetectorOptions({
        inputSize: 224,
        scoreThreshold: 0.5,
    });

    employeeFaceModelsReady = true;
    return true;
}

function descriptorFieldForMode(mode) {
    return document.getElementById(mode === 'edit' ? 'edit_face_descriptor_json' : 'face_descriptor_json');
}

function descriptorStatusForMode(mode) {
    return document.getElementById(mode === 'edit' ? 'edit_face_descriptor_status' : 'face_descriptor_status');
}

function setDescriptorStatus(mode, message, isError = false) {
    const el = descriptorStatusForMode(mode);

    if (!el) {
        return;
    }

    el.textContent = message;
    el.classList.remove('hidden', 'text-green-600', 'text-red-600', 'dark:text-green-400', 'dark:text-red-400');
    el.classList.add(isError ? 'text-red-600' : 'text-green-600', 'dark:' + (isError ? 'text-red-400' : 'text-green-400'));
}

export async function extractFaceDescriptorFromFile(file, mode) {
    const field = descriptorFieldForMode(mode);

    if (!field) {
        return;
    }

    field.value = '';
    setDescriptorStatus(mode, 'Memproses wajah dari foto...', false);

    if (!file || typeof faceapi === 'undefined') {
        setDescriptorStatus(mode, '', false);
        return;
    }

    const ready = await ensureEmployeeFaceModels();

    if (!ready) {
        setDescriptorStatus(mode, 'Model AI belum siap. Simpan foto lalu coba lagi dari halaman absensi.', true);
        return;
    }

    const image = await new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error('Gagal membaca gambar.'));
        img.src = URL.createObjectURL(file);
    });

    const detections = await faceapi
        .detectAllFaces(image, employeeFaceDetectorOptions)
        .withFaceLandmarks()
        .withFaceDescriptors();

    URL.revokeObjectURL(image.src);

    if (detections.length !== 1) {
        setDescriptorStatus(
            mode,
            detections.length < 1
                ? 'Wajah tidak terdeteksi. Gunakan foto dengan wajah yang jelas.'
                : 'Hanya satu wajah yang diperbolehkan pada foto profil.',
            true,
        );
        return;
    }

    field.value = JSON.stringify(Array.from(detections[0].descriptor));
    setDescriptorStatus(mode, 'Wajah berhasil diproses dari foto profil.', false);
}
