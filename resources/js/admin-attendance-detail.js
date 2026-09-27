/**
 * Admin attendance detail modal + lokasi absensi trigger (rules: clean, modular).
 */

import AdminAttendanceLocationMap from './admin-attendance-location-map.js';
import { faceMatchRowHtml } from './face-match.js';
import { distanceMeters, formatDistance, isWithinRadius } from './geo-distance.js';

const AdminAttendanceDetail = {
    el(id) {
        return document.getElementById(id);
    },

    modal() {
        return this.el('admin-attendance-detail-modal');
    },

    checkInMap: null,
    checkOutMap: null,

    destroyInlineMaps() {
        if (this.checkInMap) {
            this.checkInMap.remove();
            this.checkInMap = null;
        }
        if (this.checkOutMap) {
            this.checkOutMap.remove();
            this.checkOutMap = null;
        }
    },

    initInlineMaps(data) {
        this.destroyInlineMaps();
        const geofence = this.geofenceConfig();
        
        // Wait for modal transition to finish so map size is accurate
        setTimeout(() => {
            if (data.clock_in_lat != null && data.clock_in_lng != null) {
                this.checkInMap = this.renderInlineMap('attendanceCheckInMap', data.clock_in_lat, data.clock_in_lng, data.clock_in_location, 'Absen Masuk', geofence);
            }
            if (data.clock_out_lat != null && data.clock_out_lng != null) {
                this.checkOutMap = this.renderInlineMap('attendanceCheckOutMap', data.clock_out_lat, data.clock_out_lng, data.clock_out_location, 'Absen Pulang', geofence);
            }
        }, 150);
    },

    renderInlineMap(containerId, lat, lng, address, label, geofence) {
        const container = document.getElementById(containerId);
        if (!container) return null;

        const map = L.map(container, { zoomControl: true }).setView([lat, lng], 16);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        const pointGroup = [];

        const markerIcon = L.divIcon({
            className: 'bg-transparent border-0',
            html: `<span class="flex h-9 w-9 items-center justify-center rounded-full ${label === 'Absen Masuk' ? 'bg-blue-600' : 'bg-amber-600'} text-xs font-bold text-white shadow-lg ring-2 ring-white">${label === 'Absen Masuk' ? 'M' : 'P'}</span>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -20],
        });
        const marker = L.marker([lat, lng], { icon: markerIcon }).addTo(map);
        
        let popupHtml = `<div class="text-sm"><p class="font-semibold text-gray-900">${this.escape(label)}</p>`;
        if (address) {
            popupHtml += `<p class="mt-1 text-sm text-gray-600">${this.escape(address)}</p>`;
        }
        if (geofence?.enabled) {
            const dist = distanceMeters(lat, lng, geofence.officeLatitude, geofence.officeLongitude);
            popupHtml += `<p class="mt-1 text-xs text-gray-500">Jarak kantor: ${formatDistance(dist)}</p>`;
        }
        popupHtml += `</div>`;
        
        marker.bindPopup(popupHtml);
        pointGroup.push(marker);

        if (geofence?.enabled) {
            const circle = L.circle([geofence.officeLatitude, geofence.officeLongitude], {
                color: '#3b82f6',
                fillColor: '#3b82f6',
                fillOpacity: 0.1,
                radius: geofence.radiusMeters,
                weight: 2,
            }).addTo(map);
            pointGroup.push(circle);
        }

        if (pointGroup.length > 1) {
            const group = L.featureGroup(pointGroup);
            map.fitBounds(group.getBounds().pad(0.12), { maxZoom: 17 });
        }
        
        map.invalidateSize();
        
        if (window.ResizeObserver) {
            const observer = new ResizeObserver(() => map.invalidateSize());
            observer.observe(container);
            map.on('remove', () => observer.disconnect());
        }
        setTimeout(() => map.invalidateSize(), 300);
        setTimeout(() => map.invalidateSize(), 1000);
        
        return map;
    },

    init() {
        if (!this.modal()) {
            return;
        }

        this.modal()?.addEventListener('click', (event) => {
            if (event.target === this.modal()) {
                this.close();
            }
        });

        document.querySelectorAll('[data-admin-detail-close]').forEach((button) => {
            button.addEventListener('click', () => this.close());
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !this.modal()?.classList.contains('hidden')) {
                const mapModal = document.getElementById('admin-attendance-location-map-modal');
                if (mapModal && !mapModal.classList.contains('hidden')) {
                    AdminAttendanceLocationMap.close();
                    return;
                }
                this.close();
            }
        });

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-open-attendance-location-map]');
            if (trigger) {
                event.preventDefault();
                AdminAttendanceLocationMap.openFromPending();
            }
        });
    },

    open(name, data) {
        const body = this.el('admin-attendance-detail-body');
        const hasMap = AdminAttendanceLocationMap.setPendingFromDetail(name, data);

        if (body) {
            body.innerHTML = this.buildBodyHtml(data, hasMap);
        }

        this.el('admin-attendance-detail-name').textContent = name;
        this.el('admin-attendance-detail-status').textContent = 'Status: ' + (data.status || '—');

        const modal = this.modal();
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        this.initInlineMaps(data);
    },

    close() {
        this.destroyInlineMaps();
        const modal = this.modal();
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');

        const mapModal = document.getElementById('admin-attendance-location-map-modal');
        if (mapModal?.classList.contains('hidden')) {
            document.body.classList.remove('overflow-hidden');
        }
    },

    buildBodyHtml(data, hasMap) {
        const rows = [];

        if (data.clock_in || data.clock_out) {
            rows.push(this.section('Waktu', `
                <p>Masuk: ${this.escape(data.clock_in || '—')}</p>
                <p>Pulang: ${this.escape(data.clock_out || '—')}</p>
            `));
        }

        if (data.leave_note || data.doctor_note_url) {
            let html = '';
            if (data.leave_note) {
                html += `<div class="prose prose-sm max-w-none dark:prose-invert mb-2">${data.leave_note}</div>`;
            }
            if (data.doctor_note_url) {
                html += `<a href="${this.escapeAttr(data.doctor_note_url)}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    Lihat Surat Sakit/Izin
                </a>`;
            }
            rows.push(this.section('Keterangan Izin/Sakit', html));
        }

        if (
            data.clock_in_face_distance != null
            || data.clock_out_face_distance != null
            || data.clock_in_face_match_percent != null
            || data.clock_out_face_match_percent != null
        ) {
            const faceHtml = [
                faceMatchRowHtml('Masuk', data.clock_in_face_distance, data.clock_in_face_match_percent),
                faceMatchRowHtml('Pulang', data.clock_out_face_distance, data.clock_out_face_match_percent),
            ].join('');
            rows.push(this.section('Kecocokan Wajah', `<div class="space-y-2">${faceHtml}</div>`));
        }

        const hasMasuk = data.clock_in_report || data.clock_in_photo || data.clock_in_location || data.clock_in_lat != null;
        if (hasMasuk) {
            rows.push(this.buildReportSection('Laporan Masuk', data.clock_in_report, data.clock_in_photo, data.clock_in_lat, data.clock_in_lng, data.clock_in_location, 'attendanceCheckInMap'));
        }

        const hasPulang = data.clock_out_report || data.clock_out_photo || data.clock_out_location || data.clock_out_lat != null;
        if (hasPulang) {
            rows.push(this.buildReportSection('Laporan Pulang', data.clock_out_report, data.clock_out_photo, data.clock_out_lat, data.clock_out_lng, data.clock_out_location, 'attendanceCheckOutMap'));
        }

        return rows.join('') || '<p class="text-gray-500 dark:text-gray-400">Tidak ada detail.</p>';
    },

    buildReportSection(title, report, photo, lat, lng, location, mapId) {
        let html = '';
        if (report) {
            html += `
                <div class="prose prose-sm max-w-none dark:prose-invert mb-3">
                    ${report}
                </div>
            `;
        }
        if (photo) {
            html += `
                <a href="${this.escapeAttr(photo)}" target="_blank" rel="noopener" class="block mb-4">
                    <img src="${this.escapeAttr(photo)}" alt="Foto verifikasi"
                        class="h-28 w-full md:w-1/2 lg:w-1/3 rounded-xl border border-gray-200 object-cover dark:border-gray-600">
                </a>
            `;
        }
        
        const geofence = this.geofenceConfig();
        if (lat != null && lng != null) {
            const radiusStatus = this.inlineRadiusStatusHtml(lat, lng, geofence);
            html += `
                <div class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 md:p-6 mb-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">LOKASI ABSEN ${title === 'Laporan Masuk' ? 'MASUK' : 'PULANG'}</p>
                        <div class="flex items-center gap-2">
                            ${radiusStatus}
                            <button type="button" data-open-attendance-location-map class="inline-flex items-center justify-center rounded-lg bg-gray-100 p-1.5 text-gray-500 hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300" title="Perbesar Peta">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
                            </button>
                        </div>
                    </div>
                    <div id="${mapId}" class="h-72 md:h-80 w-full rounded-2xl border border-slate-200 overflow-hidden z-0"></div>
                </div>
            `;
        } else if (location) {
            html += `<p class="text-gray-600 dark:text-gray-300 text-sm mb-4">Lokasi: ${this.escape(location)}</p>`;
        }

        return this.section(title, html);
    },

    geofenceConfig() {
        const node = document.getElementById('admin-attendance-geofence-config');
        if (!node?.textContent) {
            return null;
        }

        try {
            return JSON.parse(node.textContent);
        } catch {
            return null;
        }
    },

    inlineRadiusStatusHtml(lat, lng, geofence) {
        if (!geofence?.enabled || lat == null || lng == null) {
            return '';
        }
        const within = isWithinRadius(
            Number(lat),
            Number(lng),
            geofence.officeLatitude,
            geofence.officeLongitude,
            geofence.radiusMeters,
        );
        if (within) {
            return `<span class="bg-green-50 text-green-700 border border-green-200 rounded-full px-4 py-2 text-xs font-bold shadow-sm whitespace-nowrap">Dalam Radius Kantor</span>`;
        } else {
            return `<span class="bg-red-50 text-red-700 border border-red-200 rounded-full px-4 py-2 text-xs font-bold shadow-sm whitespace-nowrap">Luar Radius Kantor</span>`;
        }
    },

    section(title, content) {
        return `<div><p class="font-semibold text-gray-900 dark:text-gray-100">${title}</p>${content}</div>`;
    },

    escape(value) {
        return AdminAttendanceLocationMap.escapeHtml(value);
    },

    escapeAttr(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },
};

function bootAdminAttendanceMonitoring() {
    if (!document.getElementById('admin-attendance-detail-modal')) {
        return;
    }

    AdminAttendanceLocationMap.init();
    AdminAttendanceDetail.init();

    window.openAdminAttendanceDetail = (name, data) => AdminAttendanceDetail.open(name, data);
    window.closeAdminAttendanceDetail = () => AdminAttendanceDetail.close();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootAdminAttendanceMonitoring);
} else {
    bootAdminAttendanceMonitoring();
}

export { AdminAttendanceDetail, AdminAttendanceLocationMap };
