export function registerAttendanceEvidenceViewer(Alpine) {
    Alpine.data('attendanceEvidenceViewer', () => ({
        isOpen: false,
        isImage: true,
        url: '',
        title: '',
        scale: 1,
        minScale: 1,
        maxScale: 5,
        zoomStep: 0.5,
        translateX: 0,
        translateY: 0,
        isDragging: false,
        pointerId: null,
        pointerStartX: 0,
        pointerStartY: 0,
        previousPointerX: 0,
        previousPointerY: 0,
        pointerMoved: false,
        lastTapAt: 0,
        lastTapX: 0,
        lastTapY: 0,
        previousBodyOverflow: '',

        get imageTransform() {
            return `transform: translate3d(${this.translateX}px, ${this.translateY}px, 0) scale(${this.scale});`;
        },

        openEvidence(data) {
            this.url = data.url;
            this.title = data.title;
            this.isImage = data.isImage === '1';
            this.resetZoom();
            this.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = 'hidden';
            this.isOpen = true;
        },

        closeEvidence() {
            if (!this.isOpen) {
                return;
            }

            this.isOpen = false;
            this.stopDragging();
            this.resetZoom();
            document.body.style.overflow = this.previousBodyOverflow;
        },

        resetZoom() {
            this.scale = this.minScale;
            this.translateX = 0;
            this.translateY = 0;
        },

        clamp(value, minimum, maximum) {
            return Math.min(maximum, Math.max(minimum, value));
        },

        zoomFromCenter(delta) {
            const viewport = this.$refs.imageViewport;
            if (!viewport) {
                return;
            }

            const rect = viewport.getBoundingClientRect();
            this.zoomAt(rect.left + (rect.width / 2), rect.top + (rect.height / 2), this.scale + delta);
        },

        zoomAt(clientX, clientY, requestedScale) {
            const viewport = this.$refs.imageViewport;
            if (!viewport) {
                return;
            }

            const nextScale = this.clamp(requestedScale, this.minScale, this.maxScale);
            if (nextScale === this.scale) {
                return;
            }

            const rect = viewport.getBoundingClientRect();
            const pointX = clientX - (rect.left + (rect.width / 2));
            const pointY = clientY - (rect.top + (rect.height / 2));
            const ratio = nextScale / this.scale;

            this.translateX = pointX - ((pointX - this.translateX) * ratio);
            this.translateY = pointY - ((pointY - this.translateY) * ratio);
            this.scale = nextScale;

            if (nextScale === this.minScale) {
                this.translateX = 0;
                this.translateY = 0;
            } else {
                this.constrainPan();
            }
        },

        handleWheel(event) {
            const direction = event.deltaY < 0 ? this.zoomStep : -this.zoomStep;
            this.zoomAt(event.clientX, event.clientY, this.scale + direction);
        },

        toggleZoomAt(clientX, clientY) {
            const nextScale = this.scale > this.minScale ? this.minScale : 2.5;
            this.zoomAt(clientX, clientY, nextScale);
        },

        startPan(event) {
            this.pointerId = event.pointerId;
            this.pointerStartX = event.clientX;
            this.pointerStartY = event.clientY;
            this.previousPointerX = event.clientX;
            this.previousPointerY = event.clientY;
            this.pointerMoved = false;

            if (this.scale > this.minScale) {
                this.isDragging = true;
                event.currentTarget.setPointerCapture?.(event.pointerId);
            }
        },

        movePan(event) {
            if (this.pointerId !== event.pointerId) {
                return;
            }

            const totalDistance = Math.hypot(
                event.clientX - this.pointerStartX,
                event.clientY - this.pointerStartY,
            );

            if (totalDistance > 6) {
                this.pointerMoved = true;
            }

            if (!this.isDragging || this.scale <= this.minScale) {
                return;
            }

            this.translateX += event.clientX - this.previousPointerX;
            this.translateY += event.clientY - this.previousPointerY;
            this.previousPointerX = event.clientX;
            this.previousPointerY = event.clientY;
            this.constrainPan();
        },

        endPan(event) {
            if (this.pointerId !== event.pointerId) {
                return;
            }

            const isTouchTap = event.pointerType === 'touch' && !this.pointerMoved;
            this.stopDragging();

            if (!isTouchTap) {
                return;
            }

            const now = Date.now();
            const closeToPreviousTap = Math.hypot(
                event.clientX - this.lastTapX,
                event.clientY - this.lastTapY,
            ) < 48;

            if ((now - this.lastTapAt) < 320 && closeToPreviousTap) {
                this.toggleZoomAt(event.clientX, event.clientY);
                this.lastTapAt = 0;
                return;
            }

            this.lastTapAt = now;
            this.lastTapX = event.clientX;
            this.lastTapY = event.clientY;
        },

        stopDragging() {
            this.isDragging = false;
            this.pointerId = null;
        },

        constrainPan() {
            const viewport = this.$refs.imageViewport;
            const image = this.$refs.evidenceImage;
            if (!viewport || !image || this.scale <= this.minScale) {
                return;
            }

            const maxX = Math.max(0, ((image.clientWidth * this.scale) - viewport.clientWidth) / 2);
            const maxY = Math.max(0, ((image.clientHeight * this.scale) - viewport.clientHeight) / 2);

            this.translateX = this.clamp(this.translateX, -maxX, maxX);
            this.translateY = this.clamp(this.translateY, -maxY, maxY);
        },
    }));
}
