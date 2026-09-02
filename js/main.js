/**
 * 360 Studio - Editor con Manipulación Directa (Estilo Paint 3D)
 */

document.addEventListener('DOMContentLoaded', () => {
    let panoramas = [
        { id: 'imagen1', title: 'Panorama 1', path: 'images/imagen1.jpeg', hotspots: [] },
        { id: 'imagen2', title: 'Panorama 2', path: 'images/imagen2.jpeg', hotspots: [] },
        { id: 'imagen3', title: 'Panorama 3', path: 'images/imagen3.jpeg', hotspots: [] },
        { id: 'imagen4', title: 'Panorama 4', path: 'images/imagen4.jpeg', hotspots: [] },
        { id: 'imagen5', title: 'Panorama 5', path: 'images/imagen5.jpeg', hotspots: [] },
        { id: 'imagen7', title: 'Panorama 7', path: 'images/imagen7.jpeg', hotspots: [] },
        { id: 'imagen8', title: 'Panorama 8', path: 'images/imagen8.jpeg', hotspots: [] },
        { id: 'imagen9', title: 'Panorama 9', path: 'images/imagen9.jpeg', hotspots: [] }
    ];

    let viewer = null;
    let currentPano = panoramas[0];
    let selectedHotspot = null;
    let isEditMode = true;

    const iconMap = {
        classic: 'https://cdn-icons-png.flaticon.com/512/109/109617.png',
        slim: 'https://cdn-icons-png.flaticon.com/512/271/271228.png',
        pointer: 'https://cdn-icons-png.flaticon.com/512/684/684908.png'
    };

    function initViewer(panorama) {
        if (viewer) viewer.destroy();

        viewer = pannellum.viewer('panorama-container', {
            "type": "equirectangular",
            "panorama": panorama.path,
            "autoLoad": true,
            "renderer": "webgl",
            "hfov": 90,
            "showControls": false,
            "crossOrigin": "anonymous",
            "hotSpots": panorama.hotspots.map((hs) => ({
                "pitch": hs.pitch,
                "yaw": hs.yaw,
                "createTooltipFunc": (el) => {
                    el.classList.add('custom-hotspot');
                    if (selectedHotspot === hs) el.classList.add('selected');
                    
                    el.style.backgroundImage = `url(${iconMap[hs.style || 'classic']})`;
                    el.style.width = `${hs.size || 60}px`;
                    el.style.height = `${hs.size || 60}px`;
                    el.style.transform = `rotate(${hs.rotate || 0}deg)`;
                    
                    const filters = {
                        red: 'invert(27%) sepia(91%) saturate(6478%) hue-rotate(352deg)',
                        blue: 'invert(13%) sepia(95%) saturate(7483%) hue-rotate(235deg)',
                        yellow: 'invert(93%) sepia(93%) saturate(1352%) hue-rotate(356deg)',
                        black: 'invert(0%)',
                        white: 'invert(100%)'
                    };
                    el.style.filter = filters[hs.color || 'white'];

                    // AÑADIR MANEJADORES SI ESTÁ EN MODO EDICIÓN
                    if (isEditMode) {
                        const box = document.createElement('div');
                        box.className = 'hotspot-editor-box';
                        
                        const rotateHandle = document.createElement('div');
                        rotateHandle.className = 'handle handle-rotate';
                        
                        const resizeHandle = document.createElement('div');
                        resizeHandle.className = 'handle handle-resize';
                        
                        box.appendChild(rotateHandle);
                        box.appendChild(resizeHandle);
                        el.appendChild(box);

                        // LÓGICA DE MANIPULACIÓN
                        let isRotating = false;
                        let isResizing = false;
                        let startX, startY, startSize, startRotate;

                        rotateHandle.onmousedown = (e) => {
                            e.stopPropagation();
                            isRotating = true;
                            startX = e.clientX;
                            startY = e.clientY;
                            startRotate = hs.rotate || 0;
                            document.addEventListener('mousemove', onMouseMove);
                            document.addEventListener('mouseup', onMouseUp);
                        };

                        resizeHandle.onmousedown = (e) => {
                            e.stopPropagation();
                            isResizing = true;
                            startX = e.clientX;
                            startSize = hs.size || 60;
                            document.addEventListener('mousemove', onMouseMove);
                            document.addEventListener('mouseup', onMouseUp);
                        };

                        function onMouseMove(e) {
                            if (isRotating) {
                                const dx = e.clientX - startX;
                                hs.rotate = (startRotate + dx) % 360;
                                el.style.transform = `rotate(${hs.rotate}deg)`;
                            }
                            if (isResizing) {
                                const dx = e.clientX - startX;
                                hs.size = Math.max(30, Math.min(200, startSize + dx));
                                el.style.width = `${hs.size}px`;
                                el.style.height = `${hs.size}px`;
                            }
                        }

                        function onMouseUp() {
                            isRotating = false;
                            isResizing = false;
                            document.removeEventListener('mousemove', onMouseMove);
                            document.removeEventListener('mouseup', onMouseUp);
                            seleccionarHotspot(hs); // Actualizar menú lateral
                        }
                    }
                },
                "clickHandlerFunc": () => {
                    if (isEditMode) {
                        seleccionarHotspot(hs);
                    } else {
                        const target = panoramas.find(p => p.id === hs.targetId);
                        if (target) cambiarPanorama(target);
                    }
                }
            }))
        });

        // CLIC DERECHO PARA CREAR
        const container = document.getElementById('panorama-container');
        container.onmousedown = (e) => {
            if (isEditMode && e.button === 2) {
                const coords = viewer.mouseEventToCoords(e);
                const newHS = { pitch: coords[0], yaw: coords[1], targetId: panoramas[0].id, color: 'white', size: 60, rotate: 0, style: 'classic' };
                currentPano.hotspots.push(newHS);
                seleccionarHotspot(newHS);
                actualizarVisor();
            }
        };
        container.oncontextmenu = (e) => { if (isEditMode) { e.preventDefault(); return false; } };
    }

    function seleccionarHotspot(hs) {
        selectedHotspot = hs;
        document.getElementById('select-target').value = hs.targetId;
        document.getElementById('range-size').value = hs.size;
        document.getElementById('range-rotate').value = hs.rotate;
        document.getElementById('val-size').textContent = hs.size;
        document.getElementById('val-rotate').textContent = hs.rotate;
        
        document.querySelectorAll('.icon-btn').forEach(b => b.classList.toggle('active', b.dataset.style === hs.style));
        document.querySelectorAll('.color-circle').forEach(b => b.classList.toggle('active', b.dataset.color === hs.color));
        
        // Resaltar visualmente sin recargar todo el visor si es posible
        document.querySelectorAll('.custom-hotspot').forEach(el => el.classList.remove('selected'));
        // (Nota: Pannellum recrea el DOM, así que para resaltar necesitamos refrescar o usar selectores externos)
    }

    function actualizarVisor() { initViewer(currentPano); }

    function cambiarPanorama(p) { currentPano = p; selectedHotspot = null; actualizarVisor(); renderThumbnails(); }

    // EVENTOS DEL MENÚ LATERAL (Sincronización)
    document.getElementById('select-target').onchange = (e) => { if(selectedHotspot) selectedHotspot.targetId = e.target.value; };
    document.getElementById('range-size').oninput = (e) => { if(selectedHotspot) { selectedHotspot.size = parseInt(e.target.value); actualizarVisor(); seleccionarHotspot(selectedHotspot); } };
    document.getElementById('range-rotate').oninput = (e) => { if(selectedHotspot) { selectedHotspot.rotate = parseInt(e.target.value); actualizarVisor(); seleccionarHotspot(selectedHotspot); } };

    document.querySelectorAll('.icon-btn').forEach(btn => {
        btn.onclick = () => { if(selectedHotspot) { selectedHotspot.style = btn.dataset.style; actualizarVisor(); seleccionarHotspot(selectedHotspot); } };
    });

    document.querySelectorAll('.color-circle').forEach(btn => {
        btn.onclick = () => { if(selectedHotspot) { selectedHotspot.color = btn.dataset.color; actualizarVisor(); seleccionarHotspot(selectedHotspot); } };
    });

    document.getElementById('btn-delete-hotspot').onclick = () => { if(selectedHotspot) { currentPano.hotspots = currentPano.hotspots.filter(h => h !== selectedHotspot); selectedHotspot = null; actualizarVisor(); } };

    document.getElementById('btn-mode-view').onclick = () => { isEditMode = false; document.body.classList.replace('edit-mode', 'view-mode'); actualizarVisor(); };
    document.getElementById('btn-mode-edit').onclick = () => { isEditMode = true; document.body.classList.replace('view-mode', 'edit-mode'); actualizarVisor(); };

    document.getElementById('btn-export').onclick = () => { console.log("PROYECTO:", JSON.stringify(panoramas, null, 4)); alert("Copia el código de la consola (F12)."); };

    function renderThumbnails() {
        const box = document.getElementById('thumbnails'); box.innerHTML = '';
        panoramas.forEach(p => {
            const img = document.createElement('img'); img.src = p.path; img.className = 'thumb' + (p.id === currentPano.id ? ' active' : '');
            img.onclick = () => cambiarPanorama(p); box.appendChild(img);
        });
        document.getElementById('select-target').innerHTML = panoramas.map(p => `<option value="${p.id}">${p.title}</option>`).join('');
    }

    renderThumbnails();
    initViewer(currentPano);
});
