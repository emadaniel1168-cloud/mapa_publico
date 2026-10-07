<?php

define('MAPA_OTRO_INDEX_RESTRINGIDO', true);
ob_start();
require __DIR__ . '/prueba.php';
$page = ob_get_clean();
$placeHoverEnhancement = <<<'HTML'
<style>
.exclamation-hotspot .exclamation-inner {
	background: #0693e3 !important;
	border-color: #0693e3 !important;
	box-shadow: 0 0 0 3px rgba(6, 147, 227, .5), 0 4px 12px rgba(0, 0, 0, .45) !important;
	color: #fff !important;
}
body.view-mode .label-form-panel:not(.hidden) {
	left: 332px;
	top: 58px;
	width: min(320px, calc(100vw - 356px));
	max-height: calc(100dvh - 70px);
	z-index: 1200;
}
</style>
<script>
(() => {
	const wrapper = document.getElementById('mapa-google-wrapper');
	const layer = document.getElementById('mapa-google-layer');
	const content = document.getElementById('map-guide-content');
	if (!wrapper || !layer || !content) return;

	const guideKey = 'mapa360.school-guide.v1';
	const tooltip = document.createElement('div');
	tooltip.setAttribute('role', 'status');
	tooltip.style.cssText = 'position:absolute;z-index:50;display:none;width:max-content;max-width:min(220px,calc(100% - 16px));padding:5px 9px;border:1px solid rgba(255,255,255,.35);border-radius:5px;background:rgba(15,23,42,.94);box-shadow:0 3px 10px rgba(0,0,0,.35);color:#fff;font:500 13px/1.3 Segoe UI,sans-serif;text-align:center;white-space:normal;overflow-wrap:anywhere;pointer-events:none;transform:translate(-50%,-100%);';
	wrapper.appendChild(tooltip);

	let savedGuide = '';
	let places = [];
	const hideStaticPlaceLabels = () => {
		content.querySelectorAll('text').forEach(label => {
			const labelName = label.textContent.trim();
			const labelX = Number(label.getAttribute('x'));
			const labelY = Number(label.getAttribute('y'));
			const isPlaceLabel = places.some(place => {
				const height = Number(place.h) || (place.source === 'catalogo' ? 2.5 : 7);
				return labelName === place.name && Math.abs(labelX - Number(place.x)) < 0.1 &&
					Math.abs(labelY - (Number(place.y) - height / 2 - 1)) < 0.1;
			});
			if (isPlaceLabel) label.setAttribute('visibility', 'hidden');
		});
	};
	const refreshPlaces = () => {
		const saved = localStorage.getItem(guideKey) || '';
		if (saved === savedGuide) return;
		savedGuide = saved;
		try {
			const guide = JSON.parse(saved || '{}');
			places = guide.layers?.places === false ? [] : (Array.isArray(guide.places) ? guide.places : [])
				.filter(place => place?.name && Number.isFinite(Number(place.x)) && Number.isFinite(Number(place.y)));
		} catch {
			places = [];
		}
		hideStaticPlaceLabels();
	};
	const hideTooltip = () => { tooltip.style.display = 'none'; };

	wrapper.addEventListener('pointermove', event => {
		refreshPlaces();
		const matrix = layer.getScreenCTM();
		if (!matrix || !places.length) return hideTooltip();

		const nearest = places.reduce((best, place) => {
			const point = layer.createSVGPoint();
			point.x = Number(place.x);
			point.y = Number(place.y);
			const screenPoint = point.matrixTransform(matrix);
			const distance = Math.hypot(event.clientX - screenPoint.x, event.clientY - screenPoint.y);
			return distance < best.distance ? { place, screenPoint, distance } : best;
		}, { place: null, screenPoint: null, distance: 22 });

		if (!nearest.place) return hideTooltip();
		const bounds = wrapper.getBoundingClientRect();
		const tooltipWidth = Math.min(220, bounds.width - 16);
		tooltip.textContent = nearest.place.name;
		tooltip.style.left = `${Math.max(tooltipWidth / 2 + 8, Math.min(bounds.width - tooltipWidth / 2 - 8, nearest.screenPoint.x - bounds.left))}px`;
		tooltip.style.top = `${Math.max(30, nearest.screenPoint.y - bounds.top - 8)}px`;
		tooltip.style.display = 'block';
	});
	wrapper.addEventListener('pointerleave', hideTooltip);
	new MutationObserver(() => { refreshPlaces(); hideStaticPlaceLabels(); }).observe(content, { childList: true, subtree: true });
	refreshPlaces();
})();
</script>
<script>
(() => {
	const panel = document.getElementById('label-form-panel');
	const menu = document.getElementById('side-menu');
	const viewer = document.getElementById('viewer-wrapper');
	if (!panel || !menu || !viewer) return;

	const positionPanel = () => {
		if (panel.classList.contains('hidden') || getComputedStyle(menu).display === 'none') return;
		const menuRect = menu.getBoundingClientRect();
		const viewerRect = viewer.getBoundingClientRect();
		const gap = 12;
		const edge = 10;
		const panelWidth = Math.min(320, window.innerWidth - edge * 2);
		const rightSpace = window.innerWidth - menuRect.right - gap - edge;
		const leftSpace = menuRect.left - gap - edge;
		let left;
		let top;
		let width;

		if (rightSpace >= 240) {
			left = menuRect.right + gap;
			top = Math.max(edge, menuRect.top);
			width = Math.min(panelWidth, rightSpace);
		} else if (leftSpace >= 240) {
			width = Math.min(panelWidth, leftSpace);
			left = menuRect.left - gap - width;
			top = Math.max(edge, menuRect.top);
		} else {
			left = Math.max(edge, Math.min(menuRect.left, window.innerWidth - panelWidth - edge));
			top = Math.min(menuRect.bottom + gap, window.innerHeight - 120);
			width = panelWidth;
		}

		panel.style.left = `${left - viewerRect.left}px`;
		panel.style.top = `${top - viewerRect.top}px`;
		panel.style.width = `${width}px`;
		panel.style.maxHeight = `${Math.max(120, window.innerHeight - top - edge)}px`;
		panel.style.zIndex = '1201';
	};

	window.addEventListener('resize', positionPanel);
	new MutationObserver(positionPanel).observe(panel, { attributes: true, attributeFilter: ['class'] });
	new MutationObserver(positionPanel).observe(menu, { attributes: true, childList: true, subtree: true });
	new MutationObserver(positionPanel).observe(document.body, { attributes: true, attributeFilter: ['class'] });
	positionPanel();
})();
</script>
HTML;
$closingBody = strripos($page, '</body>');
if ($closingBody === false) {
	echo $page, $placeHoverEnhancement;
} else {
	echo substr_replace($page, $placeHoverEnhancement . '</body>', $closingBody, strlen('</body>'));
}
