import L from 'leaflet';

const defaultMapCenter = [14.0723, -87.1921];

function initializeClosureMaps(root = document) {
	const maps = [];

	if (root.matches?.('[data-closure-map]')) {
		maps.push(root);
	}

	maps.push(...root.querySelectorAll?.('[data-closure-map]') ?? []);

	maps.forEach((element) => {
		if (element.dataset.mapInitialized === 'true') {
			return;
		}

		const latitudeValue = element.dataset.latitude;
		const longitudeValue = element.dataset.longitude;
		const latitude = Number(latitudeValue);
		const longitude = Number(longitudeValue);
		const hasLocation = latitudeValue !== ''
			&& longitudeValue !== ''
			&& Number.isFinite(latitude)
			&& Number.isFinite(longitude);
		const center = hasLocation ? [latitude, longitude] : defaultMapCenter;
		const map = L.map(element).setView(center, hasLocation ? 17 : 13);
		const marker = hasLocation
			? L.marker(center, { draggable: true }).addTo(map)
			: null;

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap contributors',
			maxZoom: 19,
		}).addTo(map);

		const updateLocation = (nextMarker) => {
			const position = nextMarker.getLatLng();
			const wireId = element.closest('[wire\\:id]')?.getAttribute('wire:id');
			const component = wireId ? Livewire.find(wireId) : null;

			if (component) {
				component.call('saveLocation', position.lat, position.lng);
			}
		};

		let activeMarker = marker;

		if (activeMarker) {
			activeMarker.on('dragend', () => updateLocation(activeMarker));
		}

		map.on('click', (event) => {
			if (!activeMarker) {
				activeMarker = L.marker(event.latlng, { draggable: true }).addTo(map);
				activeMarker.on('dragend', () => updateLocation(activeMarker));
			} else {
				activeMarker.setLatLng(event.latlng);
			}

			updateLocation(activeMarker);
		});

		element._closureMap = map;
		element.dataset.mapInitialized = 'true';
	});
}

document.addEventListener('livewire:init', () => {
	initializeClosureMaps();

	Livewire.hook('morph.added', ({ el }) => {
		initializeClosureMaps(el);
	});
});
