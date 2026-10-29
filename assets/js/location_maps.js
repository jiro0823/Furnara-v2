(() => {
    const styleUrl = 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json';
    let lastGeocoderRequest = 0;
    const respectGeocoderLimit = async () => {
        const delay = Math.max(0, 1100 - (Date.now() - lastGeocoderRequest));
        if (delay) await new Promise((resolve) => window.setTimeout(resolve, delay));
        lastGeocoderRequest = Date.now();
    };

    const geocode = async (query) => {
        await respectGeocoderLimit();
        const url = new URL('https://nominatim.openstreetmap.org/search');
        url.search = new URLSearchParams({ q: query, format: 'jsonv2', limit: '1' });
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Address search is temporarily unavailable.');
        const matches = await response.json();
        if (!matches.length) throw new Error('No matching address found. Try adding your city or barangay.');
        return { lat: Number(matches[0].lat), lng: Number(matches[0].lon), address: matches[0].display_name };
    };

    const reverseGeocode = async (lat, lng) => {
        await respectGeocoderLimit();
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.search = new URLSearchParams({ lat: String(lat), lon: String(lng), format: 'jsonv2' });
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Address lookup is temporarily unavailable.');
        const result = await response.json();
        return result.display_name || '';
    };

    function setupPicker(root) {
        const mapElement = root.querySelector('[data-location-map]');
        if (!mapElement || !window.maplibregl) return;
        const addressInput = root.querySelector('[data-location-address]');
        const latInput = root.querySelector('[data-location-latitude]');
        const lngInput = root.querySelector('[data-location-longitude]');
        const searchInput = root.querySelector('[data-location-search]');
        const message = root.querySelector('[data-location-message]');
        const updateMessage = (value) => { if (message) message.textContent = value; };
        const storedLat = Number(latInput?.value || mapElement.dataset.lat);
        const storedLng = Number(lngInput?.value || mapElement.dataset.lng);
        const initialLat = Number.isFinite(storedLat) && (latInput?.value || mapElement.dataset.lat) !== '' ? storedLat : 14.5995;
        const initialLng = Number.isFinite(storedLng) && (lngInput?.value || mapElement.dataset.lng) !== '' ? storedLng : 120.9842;
        const map = new maplibregl.Map({ container: mapElement, style: styleUrl, center: [initialLng, initialLat], zoom: latInput?.value && lngInput?.value ? 15 : 5, attributionControl: true });
        map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
        const marker = new maplibregl.Marker({ draggable: true, color: '#292d29' }).setLngLat([initialLng, initialLat]).addTo(map);
        let lookupTimer;
        const setLocation = async (lat, lng, shouldReverse = true) => {
            latInput.value = lat.toFixed(7); lngInput.value = lng.toFixed(7);
            marker.setLngLat([lng, lat]);
            if (shouldReverse) {
                root.dataset.locationPending = '1';
                updateMessage('Looking up the address…');
                try { addressInput.value = await reverseGeocode(lat, lng); updateMessage('Map © CARTO · Address © OpenStreetMap contributors'); }
                catch (error) { updateMessage(error.message); }
                finally { delete root.dataset.locationPending; }
            }
        };
        marker.on('dragend', () => { const point = marker.getLngLat(); setLocation(point.lat, point.lng); });
        map.on('click', (event) => setLocation(event.lngLat.lat, event.lngLat.lng));
        root.querySelector('[data-location-search-button]')?.addEventListener('click', async () => {
            if (!searchInput.value.trim()) return;
            updateMessage('Searching address…');
            try { const result = await geocode(searchInput.value.trim()); addressInput.value = result.address; await setLocation(result.lat, result.lng, false); map.flyTo({ center: [result.lng, result.lat], zoom: 15, duration: 900 }); updateMessage('Map © CARTO · Address © OpenStreetMap contributors'); }
            catch (error) { updateMessage(error.message); }
        });
        searchInput?.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); root.querySelector('[data-location-search-button]')?.click(); } });
        addressInput?.addEventListener('change', () => {
            window.clearTimeout(lookupTimer);
            lookupTimer = window.setTimeout(() => root.querySelector('[data-location-search-button]')?.click(), 250);
            if (searchInput) searchInput.value = addressInput.value;
        });
        root.closest('form')?.addEventListener('submit', (event) => {
            if (root.dataset.locationPending === '1') {
                event.preventDefault();
                updateMessage('Wait for the map address lookup to finish before saving.');
            }
        });
    }

    function setupRoute(root) {
        const mapElement = root.querySelector('[data-delivery-route-map]');
        if (!mapElement || !window.maplibregl) return;
        const origin = [Number(mapElement.dataset.originLng), Number(mapElement.dataset.originLat)];
        const destination = [Number(mapElement.dataset.destinationLng), Number(mapElement.dataset.destinationLat)];
        if (![...origin, ...destination].every(Number.isFinite)) return;
        const map = new maplibregl.Map({ container: mapElement, style: styleUrl, center: destination, zoom: 5, attributionControl: true });
        map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
        new maplibregl.Marker({ color: '#292d29' }).setLngLat(origin).setPopup(new maplibregl.Popup().setText('Furnara store')).addTo(map);
        new maplibregl.Marker({ color: '#b28b62' }).setLngLat(destination).setPopup(new maplibregl.Popup().setText('Delivery address')).addTo(map);
        const line = (coords) => ({ type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates: coords } });
        map.on('load', async () => {
            let coordinates = [origin, destination];
            try {
                const url = `https://router.project-osrm.org/route/v1/driving/${origin[0]},${origin[1]};${destination[0]},${destination[1]}?overview=full&geometries=geojson`;
                const response = await fetch(url);
                if (response.ok) { const data = await response.json(); if (data.routes?.[0]?.geometry?.coordinates) coordinates = data.routes[0].geometry.coordinates; }
            } catch (_) { /* The straight line remains visible if routing is unavailable. */ }
            map.addSource('delivery-route', { type: 'geojson', data: line([]) });
            map.addLayer({ id: 'delivery-route-casing', type: 'line', source: 'delivery-route', paint: { 'line-color': '#fff', 'line-width': 8, 'line-opacity': 0.8 } });
            map.addLayer({ id: 'delivery-route-line', type: 'line', source: 'delivery-route', paint: { 'line-color': '#2bb7ae', 'line-width': 4, 'line-dasharray': [1.2, 1.2] } });
            const bounds = coordinates.reduce((b, point) => b.extend(point), new maplibregl.LngLatBounds(coordinates[0], coordinates[0]));
            map.fitBounds(bounds, { padding: 55, maxZoom: 13, duration: 800 });
            let index = 2;
            const source = map.getSource('delivery-route');
            const animate = () => {
                if (index >= coordinates.length) index = 2;
                source.setData(line(coordinates.slice(0, index++)));
                window.setTimeout(animate, 45);
            };
            animate();
        });
    }

    document.querySelectorAll('[data-location-picker]').forEach(setupPicker);
    document.querySelectorAll('[data-delivery-route]').forEach(setupRoute);
})();
