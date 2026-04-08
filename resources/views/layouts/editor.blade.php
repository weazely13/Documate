<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documate | Template Editor</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

     @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen">

    {{ $slot }}

    @livewireScripts
    <script>
        window.fieldMeta = window.fieldMeta || {};
        window.constraintVersion = 0;
        window.fieldConstraints = window.fieldConstraints || {};
        window.fieldState = window.fieldState || {};
        window.fieldInstances = window.fieldInstances || {};
        window.imageState = window.imageState || {
            preview: null
        };
        window.previewValues = window.previewValues || {};
        window.typographyState = window.typographyState || {};
        document.addEventListener('livewire:load', () => {
            Livewire.on('refreshCanvas', () => {
                location.reload(); // simplest safe sync
            });
        });


        function defaultTypography() {
            return {
                fontFamily: 'Arial',
                fontWeight: 'normal',
                fontStyle: 'normal',
                fontSize: 12,
                color: '#000000',
                textAlign: 'left',
                lineHeight: 1.4,
                letterSpacing: 0,
            };
        };
        function defaultConstraints() {
            return {
                maxLength: null,
                maxLines: null,
                required: false,
                dateMode: 'current',   // 'current' | 'user'
            };
        }


        function fieldInteraction(config) {
            return {
                dragging: false,
                resizing: false,
                direction: null,
                zoom: 100,
                offsetX: 0, offsetY: 0,
                startX: 0, startY: 0,
                startW: 0, startH: 0,
                startLeft: 0, startTop: 0,
                localX: 100, localY: 100,
                localW: 150, localH: 50,

                // Store bound references so removeEventListener works correctly
                _boundDrag: null,
                _boundResize: null,
                _boundStop: null,

                init() {
                    const root = this.$el.closest('[x-data]');
                    this.zoom = root.__x.$data.zoom;
                    this.$watch(() => root.__x.$data.zoom, val => { this.zoom = val; });

                    let id    = config.index;
                    let field = config.field || {};

                    window.fieldInstances[id] = this;

                    this.localX = field.x     !== undefined ? field.x     : 100;
                    this.localY = field.y     !== undefined ? field.y     : 100;
                    this.localW = field.width !== undefined ? field.width : 150;
                    this.localH = field.height!== undefined ? field.height: 50;

                    window.fieldState[id] = {
                        x: this.localX, y: this.localY,
                        width: this.localW, height: this.localH
                    };
                },

                getScale() { return this.zoom / 100; },

                getCanvas() { return document.querySelector('[data-editor-canvas]'); },

                startDrag(e) {
                    if (this.resizing) return;
                    document.body.style.userSelect = 'none';
                    this.dragging = true;

                    let rect  = this.getCanvas().getBoundingClientRect();
                    let scale = this.getScale();
                    this.offsetX = (e.clientX - rect.left) / scale - this.localX;
                    this.offsetY = (e.clientY - rect.top)  / scale - this.localY;

                    // Create fresh bound references
                    this._boundDrag = (e) => this.onDrag(e);
                    this._boundStop = ()  => this.stopAll('drag');

                    window.addEventListener('mousemove', this._boundDrag);
                    window.addEventListener('mouseup',   this._boundStop);
                },

                startResize(e, dir) {
                    e.preventDefault();
                    document.body.style.userSelect = 'none';
                    this.resizing  = true;
                    this.direction = dir;

                    let rect  = this.getCanvas().getBoundingClientRect();
                    let scale = this.getScale();
                    this.startX    = (e.clientX - rect.left) / scale;
                    this.startY    = (e.clientY - rect.top)  / scale;
                    this.startW    = this.localW;
                    this.startH    = this.localH;
                    this.startLeft = this.localX;
                    this.startTop  = this.localY;

                    // Create fresh bound references
                    this._boundResize = (e) => this.onResize(e);
                    this._boundStop   = ()  => this.stopAll('resize');

                    window.addEventListener('mousemove', this._boundResize);
                    window.addEventListener('mouseup',   this._boundStop);
                },

                onDrag(e) {
                    if (!this.dragging) return;
                    let rect  = this.getCanvas().getBoundingClientRect();
                    let scale = this.getScale();
                    this.localX = (e.clientX - rect.left) / scale - this.offsetX;
                    this.localY = (e.clientY - rect.top)  / scale - this.offsetY;
                    this._syncToParent();
                },

                onResize(e) {
                    if (!this.resizing) return;
                    let rect  = this.getCanvas().getBoundingClientRect();
                    let scale = this.getScale();
                    let dx = ((e.clientX - rect.left) / scale) - this.startX;
                    let dy = ((e.clientY - rect.top)  / scale) - this.startY;

                    let newW = this.startW, newH = this.startH;
                    let newX = this.startLeft, newY = this.startTop;

                    if (this.direction.includes('e')) newW = this.startW + dx;
                    if (this.direction.includes('w')) { newW = this.startW - dx; newX = this.startLeft + dx; }
                    if (this.direction.includes('s')) newH = this.startH + dy;
                    if (this.direction.includes('n')) { newH = this.startH - dy; newY = this.startTop + dy; }

                    if (newW < 50 || newH < 30) return;

                    this.localW = newW; this.localH = newH;
                    this.localX = newX; this.localY = newY;
                    this._syncToParent();
                },

                stopAll(source) {
                    document.body.style.userSelect = '';
                    this.dragging  = false;
                    this.resizing  = false;

                    let id = config.index;
                    window.fieldState[id] = {
                        x: this.localX, y: this.localY,
                        width: this.localW, height: this.localH
                    };

                    // Remove only the listeners we actually added
                    if (source === 'drag') {
                        window.removeEventListener('mousemove', this._boundDrag);
                    } else {
                        window.removeEventListener('mousemove', this._boundResize);
                    }
                    window.removeEventListener('mouseup', this._boundStop);

                    // This triggers scheduleAutosave via @field-drag-end.window on the canvas
                    this.$el.dispatchEvent(new CustomEvent('field-drag-end', { bubbles: true }));
                },

                _syncToParent() {
                    let id   = config.index;
                    let root = this.$el.closest('[x-data]');
                    if (!root || !root.__x) return;
                    let parent = root.__x.$data;
                    if (parent.selectedFieldData && parent.selectedFieldData.id === id) {
                        parent.selectedFieldState.x      = Math.round(this.localX);
                        parent.selectedFieldState.y      = Math.round(this.localY);
                        parent.selectedFieldState.width  = Math.round(this.localW);
                        parent.selectedFieldState.height = Math.round(this.localH);
                    }
                },
            };
        }
    </script>
</body>
</html>