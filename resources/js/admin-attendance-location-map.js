/**
 * Admin attendance location map — Leaflet popup (rules: modular, reusable).
 */

import { addAttendanceRadiusLayer } from './attendance-radius-layer.js';
import { distanceMeters, formatDistance, isWithinRadius } from './geo-distance.js';

const TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const TILE_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

const MARKER_STYLES = {
    'Absen Masuk': { label: 'M' },
    'Absen Pulang': { label: 'P' },
};

const RADIUS_STATUS = {
    inside: {
        ring: 'bg-green-600',
        badge: 'inline-flex rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-800',
        text: 'Dalam radius',
    },
    outside: {
        ring: 'bg-red-600',
        badge: 'inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-800',
        text: 'Luar radius',
    },
    unknown: {
        ring: 'bg-gray-500',
        badge: 'inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-700',
        text: 'Radius tidak aktif',
    },
};

const AdminAttendanceLocationMap = {
    map: null,
    markers: [],
    radiusLayers: null,
    pendingPayload: null,

    el(id) {
        return document.getElementById(id);
    },

    modal() {
        return this.el('admin-attendance-location-map-modal');
    },

    geofenceConfig() {
        const node = this.el('admin-attendance-geofence-config');
        if (!node?.textContent) {
            return null;
        }

        try {
            return JSON.parse(node.textContent);
        } catch {
            return null;
        }
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

        document.querySelectorAll('[data-admin-location-map-close]').forEach((button) => {
            button.addEventListener('click', () => this.close());
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !this.modal()?.classList.contains('hidden')) {
                this.close();
            }
        });
    },

    enrichPoint(point, geofence) {
        if (!geofence?.enabled) {
            return { ...point, withinRadius: null, distanceMeters: null };
        }

        const dist = distanceMeters(
            point.lat,
            point.lng,
            geofence.officeLatitude,
            geofence.officeLongitude,
        );

        return {
            ...point,
            distanceMeters: dist,
            withinRadius: isWithinRadius(
                point.lat,
                point.lng,
                geofence.officeLatitude,
                geofence.officeLongitude,
                geofence.radiusMeters,
            ),
        };
    },

    buildPayload(employeeName, data) {
        const geofence = this.geofenceConfig();
        const points = [];

        if (data.clock_in_lat != null && data.clock_in_lng != null) {
            points.push(this.enrichPoint({
                label: 'Absen Masuk',
                lat: Number(data.clock_in_lat),
                lng: Number(data.clock_in_lng),
                address: data.clock_in_location || null,
            }, geofence));
        }

        if (data.clock_out_lat != null && data.clock_out_lng != null) {
            points.push(this.enrichPoint({
                label: 'Absen Pulang',
                lat: Number(data.clock_out_lat),
                lng: Number(data.clock_out_lng),
                address: data.clock_out_location || null,
            }, geofence));
        }

        return {
            title: employeeName,
            points,
            geofence,
        };
    },

    setPendingFromDetail(employeeName, data) {
        this.pendingPayload = this.buildPayload(employeeName, data);
        return this.pendingPayload.points.length > 0;
    },

    openFromPending() {
        if (!this.pendingPayload?.points?.length) {
            return;
        }
        this.open(this.pendingPayload);
    },

    open(payload) {
        const { title, points, geofence } = payload;
        if (!points?.length) {
            return;
        }

        const titleEl = this.el('admin-attendance-location-map-title');
        const subtitleEl = this.el('admin-attendance-location-map-subtitle');

        if (titleEl) {
            titleEl.textContent = 'Lokasi Absensi';
        }
        if (subtitleEl) {
            subtitleEl.textContent = title || '';
        }

        this.renderLegend(points, geofence);
        this.renderFooter(points, geofence);

        this.modal()?.classList.remove('hidden');
        this.modal()?.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        const loading = this.el('admin-attendance-location-map-loading');
        if (loading) {
            loading.textContent = 'Memuat peta...';
            loading.classList.remove('hidden');
        }

        if (typeof L === 'undefined') {
            this.showMapError('Peta belum siap. Muat ulang halaman.');
            return;
        }

        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => this.renderMap(points, geofence));
        });
    },

    showMapError(message) {
        const loading = this.el('admin-attendance-location-map-loading');
        if (loading) {
            loading.textContent = message;
            loading.classList.remove('hidden');
        }
    },

    close() {
        this.destroyMap();
        this.modal()?.classList.add('hidden');
        this.modal()?.classList.remove('flex');

        if (!this.isDetailModalOpen()) {
            document.body.classList.remove('overflow-hidden');
        }
    },

    isDetailModalOpen() {
        const detail = document.getElementById('admin-attendance-detail-modal');
        return detail && !detail.classList.contains('hidden');
    },

    destroyMap() {
        this.markers = [];
        this.radiusLayers = null;

        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    },

    radiusStatusFor(point) {
        if (point.withinRadius === true) {
            return RADIUS_STATUS.inside;
        }
        if (point.withinRadius === false) {
            return RADIUS_STATUS.outside;
        }
        return RADIUS_STATUS.unknown;
    },

    createMarkerIcon(point) {
        const base = MARKER_STYLES[point.label] || { label: '•' };
        const status = this.radiusStatusFor(point);

        return L.divIcon({
            className: 'bg-transparent border-0',
            html: `<span class="flex h-9 w-9 items-center justify-center rounded-full ${status.ring} text-xs font-bold text-white shadow-lg ring-2 ring-white">${base.label}</span>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -20],
        });
    },

    renderLegend(points, geofence) {
        const legend = this.el('admin-attendance-location-map-legend');
        if (!legend) {
            return;
        }

        const chips = points.map((point) => {
            const base = MARKER_STYLES[point.label] || { label: '•' };
            const status = this.radiusStatusFor(point);

            return `<span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                <span class="flex h-5 w-5 items-center justify-center rounded-full ${status.ring} text-[10px] font-bold text-white">${base.label}</span>
                ${point.label}
            </span>`;
        });

        if (geofence?.enabled) {
            chips.push(`<span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-200">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">K</span>
                Radius ${this.escapeHtml(geofence.radiusLabel || '')}
            </span>`);
            chips.push(`<span class="${RADIUS_STATUS.inside.badge}">${RADIUS_STATUS.inside.text}</span>`);
            chips.push(`<span class="${RADIUS_STATUS.outside.badge}">${RADIUS_STATUS.outside.text}</span>`);
        }

        legend.innerHTML = chips.join('');
    },

    renderFooter(points, geofence) {
        const footer = this.el('admin-attendance-location-map-footer');
        if (!footer) {
            return;
        }

        footer.innerHTML = points.map((point) => {
            const status = this.radiusStatusFor(point);
            const address = point.address
                ? `<p class="mt-0.5 text-gray-700 dark:text-gray-300">${this.escapeHtml(point.address)}</p>`
                : '<p class="mt-0.5 text-gray-400">Alamat tidak tersedia</p>';

            const coords = `<p class="mt-1 font-mono text-[11px] text-gray-500">${point.lat.toFixed(6)}, ${point.lng.toFixed(6)}</p>`;

            const radiusInfo = geofence?.enabled && point.distanceMeters != null
                ? `<p class="mt-1.5"><span class="${status.badge}">${status.text}</span>
                    <span class="ml-2 text-gray-500">Jarak dari kantor: ${formatDistance(point.distanceMeters)}</span></p>`
                : '';

            return `<div class="mb-2 last:mb-0">
                <p class="font-semibold text-gray-800 dark:text-gray-200">${point.label}</p>
                ${address}
                ${coords}
                ${radiusInfo}
            </div>`;
        }).join('');
    },

    renderMap(points, geofence) {
        const container = this.el('admin-attendance-location-map-canvas');
        const loading = this.el('admin-attendance-location-map-loading');

        if (!container) {
            return;
        }

        this.destroyMap();

        const first = points[0];
        this.map = L.map(container, { zoomControl: true }).setView([first.lat, first.lng], 16);

        L.tileLayer(TILE_URL, {
            attribution: TILE_ATTRIBUTION,
            maxZoom: 19,
        }).addTo(this.map);

        if (geofence?.enabled) {
            this.radiusLayers = addAttendanceRadiusLayer(this.map, geofence);
        }

        const bounds = [];

        points.forEach((point) => {
            const marker = L.marker([point.lat, point.lng], {
                icon: this.createMarkerIcon(point),
            }).addTo(this.map);

            const status = this.radiusStatusFor(point);
            const popupLines = [
                `<p class="font-semibold text-gray-900">${this.escapeHtml(point.label)}</p>`,
                point.address
                    ? `<p class="mt-1 text-sm text-gray-600">${this.escapeHtml(point.address)}</p>`
                    : '',
                `<p class="mt-1 font-mono text-xs text-gray-500">${point.lat.toFixed(6)}, ${point.lng.toFixed(6)}</p>`,
            ];

            if (geofence?.enabled && point.distanceMeters != null) {
                popupLines.push(
                    `<p class="mt-2"><span class="${status.badge}">${status.text}</span></p>`,
                    `<p class="mt-1 text-xs text-gray-500">Jarak kantor: ${formatDistance(point.distanceMeters)}</p>`,
                );
            }

            marker.bindPopup(`<div class="text-sm">${popupLines.join('')}</div>`);
            this.markers.push(marker);
            bounds.push([point.lat, point.lng]);
        });

        const fitTargets = [...this.markers];
        if (this.radiusLayers?.radiusCircle) {
            fitTargets.push(this.radiusLayers.radiusCircle);
        }

        if (fitTargets.length > 1) {
            const group = L.featureGroup(fitTargets);
            this.map.fitBounds(group.getBounds().pad(0.12), { maxZoom: 17 });
        } else if (this.radiusLayers?.radiusCircle) {
            this.map.fitBounds(this.radiusLayers.radiusCircle.getBounds().pad(0.08), { maxZoom: 16 });
        }

        loading?.classList.add('hidden');
        this.map.invalidateSize();
        
        if (window.ResizeObserver) {
            const observer = new ResizeObserver(() => this.map?.invalidateSize());
            observer.observe(container);
            this.map.on('remove', () => observer.disconnect());
        }
        setTimeout(() => this.map?.invalidateSize(), 300);
        setTimeout(() => this.map?.invalidateSize(), 1000);
    },

    escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    },
};

export default AdminAttendanceLocationMap;
