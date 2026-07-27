@php
    $initialLat = (float) ($get('lintang') ?: -6.2000000);
    $initialLng = (float) ($get('bujur') ?: 106.8166660);
    $initialRadius = (int) ($get('radius_meter') ?: 150);
@endphp

@assets
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>
@endassets

<div
    wire:ignore
    x-data="lokasiAbsensiMapPicker({
        lat: {{ $initialLat }},
        lng: {{ $initialLng }},
        radius: {{ $initialRadius }},
        setLat(v) { $wire.set('data.lintang', v) },
        setLng(v) { $wire.set('data.bujur', v) },
        getLat() { return Number($wire.get('data.lintang')) },
        getLng() { return Number($wire.get('data.bujur')) },
        getRadius() { return Number($wire.get('data.radius_meter') || 150) },
    })"
    class="space-y-3"
>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <input
            type="search"
            x-model="query"
            @keydown.enter.prevent="search()"
            placeholder="Cari alamat / tempat…"
            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20"
        >
        <button
            type="button"
            @click="search()"
            class="fi-btn relative grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
        >
            Cari
        </button>
    </div>

    <p class="text-sm text-gray-500 dark:text-gray-400">
        Klik pada peta untuk menentukan titik kantor. Lingkaran hijau = radius absensi.
    </p>

    <div x-ref="map" class="h-80 w-full overflow-hidden rounded-xl border border-gray-200 dark:border-white/10"></div>

    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="statusText"></p>
</div>

@script
<script>
    Alpine.data('lokasiAbsensiMapPicker', ({ lat, lng, radius, setLat, setLng, getLat, getLng, getRadius }) => ({
        query: '',
        statusText: 'Memuat peta…',
        map: null,
        marker: null,
        circle: null,
        lat,
        lng,
        radius,
        updatingFromMap: false,
        syncTimer: null,

        init() {
            this.$nextTick(() => this.bootMap())
        },

        destroy() {
            if (this.syncTimer) {
                clearInterval(this.syncTimer)
            }
            if (this.map) {
                this.map.remove()
            }
        },

        async bootMap() {
            let attempts = 0
            while (typeof L === 'undefined' && attempts < 40) {
                await new Promise((r) => setTimeout(r, 100))
                attempts++
            }

            if (typeof L === 'undefined') {
                this.statusText = 'Gagal memuat peta. Muat ulang halaman.'
                return
            }

            this.map = L.map(this.$refs.map).setView([this.lat, this.lng], 16)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(this.map)

            this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map)
            this.circle = L.circle([this.lat, this.lng], {
                radius: this.radius,
                color: '#16a34a',
                fillColor: '#22c55e',
                fillOpacity: 0.2,
                weight: 2,
            }).addTo(this.map)

            this.marker.on('dragend', (e) => {
                const pos = e.target.getLatLng()
                this.applyPosition(pos.lat, pos.lng, false)
            })

            this.map.on('click', (e) => {
                this.applyPosition(e.latlng.lat, e.latlng.lng, false)
            })

            this.statusText = 'Siap. Klik peta untuk memilih lokasi.'
            this.syncTimer = setInterval(() => this.syncFromForm(), 700)
            setTimeout(() => this.map.invalidateSize(), 250)
        },

        syncFromForm() {
            if (!this.map || this.updatingFromMap) return

            const nextRadius = Number(getRadius() || 150)
            if (nextRadius !== this.radius && this.circle) {
                this.radius = nextRadius
                this.circle.setRadius(nextRadius)
                this.statusText = `Radius diperbarui: ${nextRadius} m`
            }

            const formLat = getLat()
            const formLng = getLng()
            if (!Number.isFinite(formLat) || !Number.isFinite(formLng)) return

            if (
                Math.abs(formLat - this.lat) > 0.0000001 ||
                Math.abs(formLng - this.lng) > 0.0000001
            ) {
                this.lat = formLat
                this.lng = formLng
                this.marker.setLatLng([this.lat, this.lng])
                this.circle.setLatLng([this.lat, this.lng])
                this.map.setView([this.lat, this.lng])
                this.statusText = `Titik dari form: ${this.lat.toFixed(7)}, ${this.lng.toFixed(7)}`
            }
        },

        applyPosition(lat, lng, pan = true) {
            this.updatingFromMap = true
            this.lat = Number(lat)
            this.lng = Number(lng)
            this.marker.setLatLng([this.lat, this.lng])
            this.circle.setLatLng([this.lat, this.lng])
            if (pan) {
                this.map.setView([this.lat, this.lng], Math.max(this.map.getZoom(), 16))
            }
            setLat(this.lat.toFixed(7))
            setLng(this.lng.toFixed(7))
            this.statusText = `Titik dipilih: ${this.lat.toFixed(7)}, ${this.lng.toFixed(7)}`
            setTimeout(() => { this.updatingFromMap = false }, 800)
        },

        async search() {
            if (!this.query.trim()) return
            this.statusText = 'Mencari lokasi…'
            try {
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.query)}&limit=1`
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                })
                const data = await res.json()
                if (!data.length) {
                    this.statusText = 'Lokasi tidak ditemukan. Coba kata kunci lain.'
                    return
                }
                this.applyPosition(data[0].lat, data[0].lon, true)
                this.statusText = data[0].display_name || this.statusText
            } catch (e) {
                this.statusText = 'Gagal mencari lokasi. Coba lagi.'
            }
        },
    }))
</script>
@endscript
