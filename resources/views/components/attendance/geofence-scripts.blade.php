@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
        <script>
            window.GeofenceMap = {
                instances: {},

                init(container) {
                    if (typeof L === 'undefined' || container.dataset.initialized === '1') {
                        return;
                    }

                    container.dataset.initialized = '1';

                    const officeLat = parseFloat(container.dataset.officeLat);
                    const officeLng = parseFloat(container.dataset.officeLng);
                    let radius = parseInt(container.dataset.radius, 10);
                    const editable = container.dataset.editable === '1';
                    const trackUser = container.dataset.trackUser === '1';
                    const statusTarget = container.dataset.statusTarget
                        ? document.getElementById(container.dataset.statusTarget)
                        : null;

                    const map = L.map(container).setView([officeLat, officeLng], 16);

                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap',
                        maxZoom: 19,
                    }).addTo(map);

                    const officeMarker = L.marker([officeLat, officeLng], {
                        draggable: editable,
                    })
                        .addTo(map)
                        .bindPopup('Lokasi Kantor');

                    const radiusCircle = L.circle([officeLat, officeLng], {
                        color: '#2563eb',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.2,
                        radius,
                    }).addTo(map);

                    let userMarker = null;
                    let userAccuracyCircle = null;

                    const instance = {
                        map,
                        officeMarker,
                        radiusCircle,
                        userMarker,
                        officeLat,
                        officeLng,
                        radius,
                        statusTarget,
                        setRadius(newRadius) {
                            this.radius = newRadius;
                            this.radiusCircle.setRadius(newRadius);
                        },
                        setOffice(lat, lng) {
                            this.officeLat = lat;
                            this.officeLng = lng;
                            this.officeMarker.setLatLng([lat, lng]);
                            this.radiusCircle.setLatLng([lat, lng]);
                            this.map.panTo([lat, lng]);
                        },
                        updateStatus(distance, withinRadius, accuracy = null) {
                            if (!this.statusTarget) {
                                return;
                            }

                            const formatted = distance >= 1000
                                ? (distance / 1000).toFixed(2) + ' KM'
                                : Math.round(distance) + ' meter';

                            let accuracyText = '';
                            if (accuracy !== null) {
                                const accuracyFormatted = accuracy >= 1000
                                    ? (accuracy / 1000).toFixed(2) + ' KM'
                                    : Math.round(accuracy) + ' meter';
                                accuracyText = ` (Akurasi GPS: ${accuracyFormatted})`;
                            }

                            if (withinRadius) {
                                this.statusTarget.className = 'mt-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700';
                                this.statusTarget.textContent = 'Anda berada dalam radius absensi (' + formatted + ' dari kantor).' + accuracyText;
                            } else {
                                this.statusTarget.className = 'mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700';
                                this.statusTarget.textContent = 'Anda berada di luar radius absensi (' + formatted + ' dari kantor).' + accuracyText;
                            }
                        },
                        trackUserLocation(maxRetries = 2) {
                            if (!navigator.geolocation) {
                                if (this.statusTarget) {
                                    this.statusTarget.className = 'mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700';
                                    this.statusTarget.textContent = 'Peramban tidak mendukung geolokasi.';
                                }
                                return Promise.reject(new Error('Geolocation unsupported'));
                            }

                            let retryCount = 0;

                            const attemptLocation = (resolve, reject) => {
                                navigator.geolocation.getCurrentPosition(
                                    (position) => {
                                        const userLat = position.coords.latitude;
                                        const userLng = position.coords.longitude;
                                        const accuracy = position.coords.accuracy;

                                        // Retry jika accuracy terlalu buruk (>50 meter) dan masih ada retry
                                        if (accuracy > 50 && retryCount < maxRetries) {
                                            retryCount++;
                                            if (this.statusTarget) {
                                                this.statusTarget.className = 'mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700';
                                                this.statusTarget.textContent = 'Akurasi GPS rendah, mencoba lagi... (' + retryCount + '/' + maxRetries + ')';
                                            }
                                            setTimeout(() => attemptLocation(resolve, reject), 1000);
                                            return;
                                        }

                                        if (this.userMarker) {
                                            this.userMarker.setLatLng([userLat, userLng]);
                                        } else {
                                            this.userMarker = L.marker([userLat, userLng])
                                                .addTo(this.map)
                                                .bindPopup('Lokasi Anda');
                                        }

                                        // Update or create accuracy circle
                                        if (this.userAccuracyCircle) {
                                            this.userAccuracyCircle.setLatLng([userLat, userLng]);
                                            this.userAccuracyCircle.setRadius(accuracy);
                                        } else {
                                            this.userAccuracyCircle = L.circle([userLat, userLng], {
                                                color: '#ef4444',
                                                fillColor: '#f87171',
                                                fillOpacity: 0.15,
                                                radius: accuracy,
                                                weight: 1,
                                            }).addTo(this.map);
                                        }

                                        // Pan map to user location
                                        this.map.panTo([userLat, userLng]);

                                        const distance = this.map.distance(
                                            [userLat, userLng],
                                            [this.officeLat, this.officeLng],
                                        );

                                        const withinRadius = distance <= this.radius;
                                        this.updateStatus(distance, withinRadius, accuracy);

                                        resolve({ latitude: userLat, longitude: userLng, accuracy, distance, withinRadius });
                                    },
                                    (error) => {
                                        if (this.statusTarget) {
                                            this.statusTarget.className = 'mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700';
                                            this.statusTarget.textContent = 'Gagal mengambil GPS. Izinkan akses lokasi lalu coba lagi.';
                                        }
                                        reject(error);
                                    },
                                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
                                );
                            };

                            return new Promise((resolve, reject) => {
                                attemptLocation(resolve, reject);
                            });
                        },
                    };

                    this.instances[container.id] = instance;

                    if (editable) {
                        officeMarker.on('dragend', () => {
                            const { lat, lng } = officeMarker.getLatLng();
                            instance.setOffice(lat, lng);

                            const latInput = document.getElementById('office_latitude');
                            const lngInput = document.getElementById('office_longitude');
                            if (latInput) {
                                latInput.value = lat.toFixed(8);
                                latInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                            if (lngInput) {
                                lngInput.value = lng.toFixed(8);
                                lngInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        });
                    }

                    if (trackUser) {
                        instance.trackUserLocation().catch(() => {});
                    }

                    setTimeout(() => map.invalidateSize(), 150);
                },

                initAll() {
                    document.querySelectorAll('[data-geofence-map]').forEach((container) => {
                        this.init(container);
                    });
                },

                getInstance(mapId) {
                    return this.instances[mapId] ?? null;
                },

                bindSettingsRadius() {
                    const radiusInput = document.getElementById('attendance_radius_meters');
                    const mapContainer = document.querySelector('[data-geofence-map][data-editable="1"]');

                    if (!radiusInput || !mapContainer) {
                        return;
                    }

                    const updateRadius = () => {
                        const instance = this.getInstance(mapContainer.id);
                        if (!instance) {
                            return;
                        }

                        const value = parseInt(radiusInput.value, 10);
                        if (value > 0) {
                            instance.setRadius(value);
                        }
                    };

                    radiusInput.addEventListener('input', updateRadius);
                    radiusInput.addEventListener('change', updateRadius);

                    document.querySelectorAll('[data-radius-preset]').forEach((button) => {
                        button.addEventListener('click', () => {
                            radiusInput.value = button.dataset.radiusPreset;
                            updateRadius();
                        });
                    });
                },

                bindAttendanceForms() {
                    document.querySelectorAll('[data-geofence-form]').forEach((form) => {
                        form.addEventListener('submit', async (event) => {
                            event.preventDefault();

                            const mapId = form.dataset.geofenceMapId;
                            const instance = mapId ? this.getInstance(mapId) : null;
                            const submitButton = form.querySelector('[type="submit"]');

                            if (submitButton) {
                                submitButton.disabled = true;
                            }

                            try {
                                if (!instance) {
                                    throw new Error('Map not found');
                                }

                                const result = await instance.trackUserLocation();

                                if (!result.withinRadius) {
                                    return;
                                }

                                form.querySelector('[name="latitude"]').value = result.latitude;
                                form.querySelector('[name="longitude"]').value = result.longitude;
                                form.submit();
                            } catch {
                                // Status message already shown on map.
                            } finally {
                                if (submitButton) {
                                    submitButton.disabled = false;
                                }
                            }
                        });
                    });
                },
            };

            function bootGeofenceMaps() {
                if (window.GeofenceMap.booted) {
                    return;
                }

                window.GeofenceMap.booted = true;
                window.GeofenceMap.initAll();
                window.GeofenceMap.bindSettingsRadius();
                window.GeofenceMap.bindAttendanceForms();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootGeofenceMaps);
            } else {
                bootGeofenceMaps();
            }
        </script>
    @endpush
@endonce
