/**
 * Attendance report fields — CKEditor 5 (local bundle via Vite alias).
 */

import {
    Bold,
    ClassicEditor,
    Essentials,
    Italic,
    List,
    Paragraph,
} from 'ckeditor5';

import '../../public/ckeditor/ckeditor5/ckeditor5.css';

const editors = new WeakMap();

export const ATTENDANCE_EDITOR_CONFIG = {
    licenseKey: 'GPL',
    plugins: [
        Essentials,
        Paragraph,
        Bold,
        Italic,
        List,
    ],
    toolbar: {
        items: [
            'undo',
            'redo',
            '|',
            'bold',
            'italic',
            '|',
            'bulletedList',
            'numberedList',
        ],
        shouldNotGroupWhenFull: true,
    },
};

function setEditorPlaceholder(editor, placeholder) {
    if (!editor || !placeholder) {
        return;
    }

    editor.editing.view.change((writer) => {
        writer.setAttribute('placeholder', placeholder, editor.editing.view.document.getRoot());
    });

    const editable = editor.ui?.view?.editable?.element;

    if (editable) {
        editable.dataset.placeholder = placeholder;
    }

    if (editor.sourceElement) {
        editor.sourceElement.placeholder = placeholder;
    }
}

function reportFieldName(form) {
    return form.dataset.type === 'clock-in' ? 'clock_in_report' : 'clock_out_report';
}

function reportTextarea(form) {
    if (form.dataset.dualAction === '1') {
        return form.querySelector('[data-attendance-ckeditor] textarea, textarea[data-attendance-report]');
    }

    const name = reportFieldName(form);

    return form.querySelector(`[name="${name}"]`);
}

function plainTextLength(html) {
    const div = document.createElement('div');
    div.innerHTML = html;

    return (div.textContent || '').replace(/\s+/g, ' ').trim().length;
}

export function getEditorHtml(textarea) {
    if (!textarea) {
        return '';
    }

    const editor = editors.get(textarea);

    if (editor) {
        return editor.getData().trim();
    }

    return textarea.value?.trim() || '';
}

export function setEditorHtml(textarea, html) {
    if (!textarea) {
        return;
    }

    const editor = editors.get(textarea);

    if (editor) {
        editor.setData(html ?? '');
        textarea.value = editor.getData();

        return;
    }

    textarea.value = html ?? '';
}

export function syncEditorTextarea(textarea) {
    if (!textarea) {
        return;
    }

    const editor = editors.get(textarea);

    if (editor) {
        textarea.value = editor.getData();
    }
}

export function syncAllEditors(root = document) {
    root.querySelectorAll('[data-attendance-ckeditor] textarea').forEach((textarea) => {
        syncEditorTextarea(textarea);
    });
}

export function getAttendanceReportHtml(form) {
    return getEditorHtml(reportTextarea(form));
}

export function getAttendanceReportPlainLength(form) {
    return plainTextLength(getAttendanceReportHtml(form));
}

async function createEditor(textarea) {
    if (textarea.dataset.ckeditorInitialized === '1') {
        return editors.get(textarea) ?? null;
    }

    textarea.dataset.ckeditorInitialized = '1';

    const placeholder = textarea.getAttribute('placeholder') || '';

    const editor = await ClassicEditor.create(textarea, {
        ...ATTENDANCE_EDITOR_CONFIG,
        placeholder,
    });

    editors.set(textarea, editor);

    editor.model.document.on('change:data', () => {
        textarea.value = editor.getData();
        textarea.dispatchEvent(new CustomEvent('attendance-report-change', {
            bubbles: true,
            detail: {
                plainLength: plainTextLength(textarea.value),
            },
        }));
    });

    setEditorPlaceholder(editor, placeholder);

    return editor;
}

export function initAttendanceEditors(root = document) {
    root.querySelectorAll('[data-attendance-ckeditor] textarea').forEach((textarea) => {
        createEditor(textarea).catch((error) => {
            console.error('CKEditor init failed:', error);
            textarea.dataset.ckeditorInitialized = '0';
        });
    });
}

if (typeof window !== 'undefined') {
    window.getAttendanceReportHtml = getAttendanceReportHtml;
    window.getAttendanceReportPlainLength = getAttendanceReportPlainLength;
    window.initAttendanceEditors = initAttendanceEditors;
    window.setAttendanceEditorHtml = setEditorHtml;
    window.syncAttendanceEditors = syncAllEditors;
}
