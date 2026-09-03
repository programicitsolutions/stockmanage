document.addEventListener('alpine:init', () => {
    window.Alpine.data('stockScanner', () => ({
        open: false,
        status: 'Point the camera at a barcode or QR code.',
        stream: null,
        timer: null,
        detector: null,
        lastCode: '',

        async toggle(wire) {
            if (this.open) {
                this.stop();
                return;
            }
            this.wire = wire;
            await this.start();
        },

        async start() {
            this.status = 'Starting camera…';
            if (! window.isSecureContext) {
                this.status = 'Camera scan needs https or localhost.';
                this.open = true;
                return;
            }
            if (! ('BarcodeDetector' in window)) {
                this.open = true;
                this.status = 'This browser cannot decode barcodes. Type or USB-scan the SKU instead.';
                return;
            }
            try {
                this.detector = new BarcodeDetector({
                    formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code', 'upc_a', 'upc_e', 'codabar', 'itf'],
                });
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });
                this.open = true;
                await this.$nextTick();
                this.$refs.video.srcObject = this.stream;
                await this.$refs.video.play();
                this.status = 'Point at the barcode…';
                this.tick();
            } catch (error) {
                this.open = true;
                this.status = 'Camera permission denied. Type the SKU instead.';
            }
        },

        async tick() {
            if (! this.open || ! this.detector || ! this.$refs.video) {
                return;
            }
            try {
                const codes = await this.detector.detect(this.$refs.video);
                const raw = codes[0]?.rawValue?.trim();
                if (raw && raw !== this.lastCode) {
                    this.lastCode = raw;
                    this.status = 'Found ' + raw;
                    if (this.wire) {
                        await this.wire.applyScannedCode(raw);
                    }
                    this.stop();
                    return;
                }
            } catch {
                // keep scanning
            }
            this.timer = window.setTimeout(() => this.tick(), 250);
        },

        stop() {
            this.open = false;
            if (this.timer) {
                window.clearTimeout(this.timer);
                this.timer = null;
            }
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }
            if (this.$refs.video) {
                this.$refs.video.srcObject = null;
            }
        },
    }));
});
