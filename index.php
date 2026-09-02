<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>360 Studio PRO - Versión Estable</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        :root { --primary: #0078d4; --bg: #1e1e1e; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #000; color: #fff; overflow: hidden; height: 100vh; }
        #top-bar { height: 50px; background: #2d2d2d; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; z-index: 1000; position: relative; }
        .mode-switch button { background: transparent; border: 1px solid #444; color: white; padding: 5px 15px; cursor: pointer; }
        .mode-switch button.active { background: var(--primary); border-color: var(--primary); }
        #main-layout { display: flex; height: calc(100vh - 50px); }
        #viewer-wrapper { flex: 1; position: relative; }
        #panorama-container { width: 100%; height: 100%; }
        /* Mapa guía fijo en la esquina inferior izquierda del visor */
        #mapa-google-wrapper {
            position: absolute;
            left: 16px;
            bottom: 16px;
            z-index: 30;
            width: clamp(180px, 24vw, 300px);
            max-height: 30vh;
            aspect-ratio: 16 / 10;
            overflow: visible;
        }
        #mapa-google-overlay {
            position: relative;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center center;
            filter: grayscale(100%);
            opacity: 0.92;
            border: 2px solid rgba(255, 255, 255, 0.75);
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.55);
            background: #777;
            pointer-events: auto;
            cursor: pointer;
            display: block;
        }
        #mapa-google-overlay:hover { opacity: 1; transform: scale(1.015); }
        #mapa-google-wrapper.map-editing { outline: 3px solid var(--primary); border-radius: 8px; }
        #mapa-google-overlay.map-editing { outline: 3px solid var(--primary); }
        #map-guide-modal { display: none; position: fixed; inset: 0; z-index: 3000; background: rgba(8, 12, 20, .82); padding: 3vh 3vw; }
        #map-guide-modal.open { display: flex; flex-direction: column; }
        #map-guide-modal-header { display: flex; align-items: center; justify-content: space-between; background: #1f2937; padding: 12px 16px; border-radius: 10px 10px 0 0; }
        #map-guide-modal-header h2 { font-size: 20px; }
        #map-guide-modal-close { background: #dc3545; color: white; border: 0; border-radius: 5px; padding: 8px 14px; cursor: pointer; }
        #map-guide-modal-body { display: flex; flex: 1; min-height: 0; background: #111827; border-radius: 0 0 10px 10px; overflow: hidden; }
        #map-guide-modal-stage { flex: 1; position: relative; min-width: 0; display: flex; align-items: center; justify-content: center; padding: 18px; }
        #map-guide-modal-stage #mapa-google-wrapper { position: relative; inset: auto; width: 100%; height: 100%; max-height: none; aspect-ratio: auto; }
        #map-guide-modal-stage #mapa-google-overlay { width: 100%; height: 100%; border-radius: 4px; }
        #map-guide-modal .map-editor-panel { width: 330px; overflow-y: auto; background: #1f2937; padding: 18px; }
        #map-element-editor { display: none; border-top: 1px solid #4b5563; margin-top: 14px; padding-top: 12px; }
        #map-element-editor.visible { display: block; }
        #map-element-editor input, #map-element-editor select { width: 100%; margin: 5px 0 9px; padding: 7px; background: #111827; color: #fff; border: 1px solid #4b5563; border-radius: 4px; }
        #map-element-delete { background: #b91c1c; color: white; border: 0; padding: 8px; border-radius: 4px; cursor: pointer; width: 100%; }
        #map-editor-section { border-top: 1px solid rgba(255,255,255,.12); padding-top: 15px; }
        #map-editor-section h3 { font-size: 15px; margin-bottom: 8px; }
        .map-editor-help { font-size: 11px; color: #bfc7d1; line-height: 1.4; margin-bottom: 10px; }
        .map-control-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin: 8px 0; font-size: 12px; }
        .map-control-row input[type="range"] { flex: 1; }
        .map-control-row button { flex: 1; background: #2563c7; color: #fff; border: 0; padding: 7px 4px; border-radius: 4px; cursor: pointer; font-size: 11px; }
        .map-control-row button.active { background: #16a34a; outline: 2px solid #8ff0b6; }
        .map-layers-title { font-size: 12px; font-weight: bold; margin-top: 10px; }
        .map-layer-toggle { display: inline-flex; width: 49%; align-items: center; gap: 4px; font-size: 11px; color: #ddd; margin-top: 6px; }
        #btn-close-map-editor { background: #444; color: #fff; border: 1px solid #666; padding: 8px; border-radius: 4px; cursor: pointer; width: 100%; margin-top: 8px; }
        #mapa-google-layer { position: absolute; left: 0; top: 0; width: 100%; height: 100%; z-index: 31; pointer-events: none; overflow: visible; }
        #mapa-google-layer.editing { pointer-events: auto; cursor: crosshair; }
        @media (max-width: 700px) {
            #mapa-google-wrapper { left: 10px; bottom: 10px; width: min(42vw, 220px); max-height: 24vh; }
        }

        #route-line { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 20; overflow: visible; }
        #route-line { z-index: 10; }
        #route-line path { fill: none; stroke: #21c46b; stroke-width: 5; stroke-linecap: round; stroke-linejoin: round; filter: drop-shadow(0 0 3px #073b20); }
        #route-line circle { fill: #21c46b; stroke: #fff; stroke-width: 3; }
        .label-form-panel {
            position: absolute; left: 24px; top: 24px; width: 240px; z-index: 60;
            background: rgba(17, 24, 39, 0.92); border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,0.35); backdrop-filter: blur(8px);
        }
        .label-form-panel.hidden { display: none; }
        .label-form-header {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 10px 12px; background: rgba(13, 110, 253, 0.18); border-bottom: 1px solid rgba(255,255,255,0.08); cursor: grab;
            user-select: none; border-radius: 12px 12px 0 0; font-size: 13px; font-weight: 700; color: #dfeeff;
        }
        .label-form-header button {
            border: none; background: rgba(255,255,255,0.08); color: white; width: 24px; height: 24px; border-radius: 50%; cursor: pointer;
        }
        .label-form-body { padding: 12px; display: grid; gap: 8px; }
        .label-form-body input, .label-form-body button, .label-form-body select, .label-form-body textarea { width: 100%; }
        .label-form-body input[type="text"], .label-form-body input[type="number"], .label-form-body input[type="time"], .label-form-body select, .label-form-body textarea { padding: 8px 10px; border-radius: 8px; border: 1px solid #3b465f; background: rgba(8, 15, 28, 0.9); color: #fff; }
        .label-form-body input[type="color"] { height: 42px; padding: 3px; border-radius: 8px; border: 1px solid #3b465f; background: rgba(8, 15, 28, 0.9); }
        .label-form-body button { border: none; border-radius: 8px; padding: 9px 10px; background: #2d7ff9; color: white; font-weight: 700; cursor: pointer; }
        .sugerencias-profesores { display: grid; gap: 5px; margin-top: 6px; }
        .sugerencia-profesor { width: 100%; text-align: left; padding: 7px 8px; border-radius: 6px; border: 1px solid #334155; background: rgba(15, 23, 42, 0.88); color: white; cursor: pointer; }
        .label-position-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .label-position-grid label { display: block; font-size: 11px; color: #d3e1f4; }
        .label-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .label-detail-grid label { display: block; font-size: 11px; color: #d3e1f4; }
        .viewer-label {
            position: absolute; z-index: 55; padding: 6px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; color: #fff;
            border: 1px solid rgba(255,255,255,0.45); box-shadow: 0 8px 20px rgba(0,0,0,0.28); transform: translate(-50%, -50%);
            cursor: pointer; user-select: none; white-space: nowrap; max-width: 180px; overflow: hidden; text-overflow: ellipsis;
        }
        .viewer-label.selected { outline: 2px solid #7dd3fc; box-shadow: 0 0 0 4px rgba(125,211,252,0.25); }
        .viewer-label .viewer-label-delete {
            position: absolute; top: -6px; right: -6px; width: 18px; height: 18px; border: none; border-radius: 50%; background: #ef4444; color: #fff;
            font-size: 11px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: 700;
        }
        .viewer-alert {
            position: absolute; z-index: 56; width: 28px; height: 28px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.8);
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); color: #fff; font-size: 18px; font-weight: 800;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 18px rgba(0,0,0,0.35); transform: translate(-50%, -50%);
            cursor: pointer; user-select: none;
        }
        .viewer-alert:hover { transform: translate(-50%, -50%) scale(1.08); }
        .custom-alert {
            width: 28px !important; height: 28px !important; border-radius: 50% !important; border: 2px solid rgba(255,255,255,0.9) !important;
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%) !important; box-shadow: 0 8px 18px rgba(0,0,0,0.35) !important;
            color: #fff !important; font-size: 18px !important; font-weight: 800 !important; display: flex !important; align-items: center !important;
            justify-content: center !important; cursor: grab !important; user-select: none !important;
            /* Pannellum mueve este hotspot según pitch/yaw; solo lo centramos sobre la coordenada. */
            transform: translate(-50%, -50%) !important;
            touch-action: none !important; transition: box-shadow .12s ease, filter .12s ease !important;
        }
        .custom-alert:hover { filter: brightness(1.08); }
        .custom-alert.is-dragging { cursor: grabbing !important; box-shadow: 0 0 0 4px rgba(245,158,11,.35), 0 10px 22px rgba(0,0,0,.45) !important; }
        .custom-alert:before { content: "!"; }
        .alert-menu-item {
            display: flex; align-items: center; justify-content: space-between; gap: 8px; width: 100%; background: #1f2937; border: 1px solid #3b475b;
            color: #fff; padding: 8px 10px; border-radius: 8px; margin-top: 6px; cursor: pointer; text-align: left;
        }
        .alert-menu-item span { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .alert-menu-delete {
            width: 24px; height: 24px; border: none; border-radius: 50%; background: #ef4444; color: #fff; cursor: pointer; font-weight: 700;
        }
        #alert-popup {
            position: absolute; z-index: 90; min-width: 180px; max-width: 240px; background: rgba(17, 24, 39, 0.95); border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.35); color: #fff; padding: 12px; transform: translate(-50%, -100%);
            display: none;
        }
        #alert-popup.visible { display: block; }
        #alert-popup h4 { font-size: 14px; margin-bottom: 6px; color: #fbbf24; }
        #alert-popup p { font-size: 12px; line-height: 1.4; color: #e5e7eb; }
        #alert-popup button {
            position: absolute; right: 8px; top: 8px; width: 20px; height: 20px; border: none; border-radius: 50%; background: rgba(255,255,255,0.1);
            color: white; cursor: pointer;
        }
        #side-menu { width: 300px; background: #2d2d2d; padding: 20px; display: flex; flex-direction: column; gap: 15px; overflow-y: auto; }
        body.view-mode #side-menu { display: none; }

        .custom-arrow { cursor: pointer; }
        #minimap-overlay {
            position: absolute; inset: 0; z-index: 20; pointer-events: none;
        }
        #minimap__nodes,
        #minimap__user {
            position: absolute; inset: 0;
        }
        .minimap-node,
        .minimap-route-node,
        .minimap-user-marker {
            position: absolute; transform: translate(-50%, -50%); border-radius: 50%; pointer-events: auto;
        }
        .minimap-node {
            width: 10px; height: 10px; background: rgba(255,255,255,0.8); border: 2px solid rgba(17,24,39,.9);
            box-shadow: 0 0 0 2px rgba(255,255,255,0.2);
        }
        .minimap-node.current {
            width: 12px; height: 12px; background: #22c55e; border-color: #ffffff; box-shadow: 0 0 0 3px rgba(34,197,94,0.35);
        }
        .minimap-node.active,
        .minimap-node.target {
            width: 12px; height: 12px; background: #f59e0b; border-color: #fff; box-shadow: 0 0 0 3px rgba(245,158,11,0.35);
        }
        .minimap-route-node {
            width: 8px; height: 8px; background: rgba(37,99,235,0.9); border: 1px solid rgba(255,255,255,0.9);
        }
        .minimap-user-marker {
            width: 14px; height: 14px; background: #38bdf8; border: 2px solid #ffffff; box-shadow: 0 0 0 3px rgba(56,189,248,0.35);
            z-index: 10;
        }
        .arrow-inner {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M12 2L4.5 20.29L5.21 21L12 18L18.79 21L19.5 20.29L12 2Z'/%3E%3C/svg%3E");
            background-size: 100% 100% !important; background-repeat: no-repeat !important;
            background-color: transparent !important;
            display: inline-block;
            transform-origin: 50% 50%;
            transform-box: fill-box;
            will-change: transform;
        }
        .rotate-handle:hover { cursor: grab; }
        .arrow-inner.selected { border: 2px dashed var(--primary); }
        
        .menu-section label { display: block; font-size: 11px; opacity: 0.7; margin-bottom: 5px; }
        .menu-section input[type="range"] { width: 100%; margin-bottom: 10px; }
        .val-text { float: right; font-weight: bold; color: var(--primary); }
    </style>
</head>
<body class="edit-mode">
    <div id="top-bar">
        <div style="font-weight:bold">360° Studio PRO</div>
        <div class="mode-switch">
            <button id="btn-mode-view">👁️ Vista</button>
            <button id="btn-mode-edit" class="active">✏️ Editar</button>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div id="save-status" style="font-size:12px; color:#c8f7c5;">Guardado local</div>
            <button id="btn-export" style="background:#28a745; color:white; border:none; padding:5px 15px; border-radius:4px; cursor:pointer">💾 Guardar</button>
        </div>
    </div>
    <div id="main-layout">
        <div id="viewer-wrapper">
            <div id="label-form-panel" class="label-form-panel hidden" aria-label="Formulario de etiqueta">
                <div class="label-form-header" id="label-form-header">
                    <span>Etiqueta</span>
                    <button id="btn-close-label-form" type="button" aria-label="Cerrar">×</button>
                </div>
                <div class="label-form-body">
                    <label style="font-size:11px;color:#d3e1f4;">Profesor
                        <input id="label-profesor" type="text" placeholder="Escribe el profesor" value="">
                        <div id="sugerencias-profesores" class="sugerencias-profesores"></div>
                    </label>
                    <div class="label-detail-grid">
                        <label>Grado
                            <input id="label-grado" type="text" placeholder="Ej: 4A" value="">
                        </label>
                        <label>Salón
                            <input id="label-salon" type="text" placeholder="Ej: 204" value="">
                        </label>
                    </div>
                    <label>Día
                        <select id="label-dia">
                            <option value="">Selecciona un día</option>
                            <option value="Lunes">Lunes</option>
                            <option value="Martes">Martes</option>
                            <option value="Miércoles">Miércoles</option>
                            <option value="Jueves">Jueves</option>
                            <option value="Viernes">Viernes</option>
                        </select>
                    </label>
                    <label>Horario
                        <select id="label-id-horario">
                            <option value="">Selecciona un horario</option>
                        </select>
                    </label>
                    <label>Hora inicio
                        <input id="label-hora-inicio" type="time" value="">
                    </label>
                    <label>Hora finaliza
                        <input id="label-hora-fin" type="time" value="">
                    </label>
                    <label>Título
                        <input id="label-titulo" type="text" placeholder="Nombre del lugar" value="">
                    </label>
                    <label>Descripción
                        <input id="label-descripcion" type="text" placeholder="Descripción opcional" value="">
                    </label>
                    <div class="label-position-grid">
                        <label>Pitch
                            <input id="label-pitch" type="number" step="0.1" min="-90" max="90" placeholder="0" value="">
                        </label>
                        <label>Yaw
                            <input id="label-yaw" type="number" step="0.1" min="-360" max="360" placeholder="0" value="">
                        </label>
                    </div>
                    <input id="label-color" type="color" value="#ffffff">
                    <label style="font-size:11px;color:#d3e1f4;">Tamaño
                        <input id="label-size" type="range" min="10" max="28" value="12">
                    </label>
                    <div id="label-size-value" style="font-size:11px;color:#d3e1f4;margin-top:-2px;">12 px</div>
                    <label style="display:flex; align-items:center; gap:8px; font-size:11px; color:#d3e1f4;">
                        <input id="label-is-alert" type="checkbox">
                        Mostrar como signo de exclamación
                    </label>
                    <button id="btn-add-label" type="button">Guardar etiqueta / aviso</button>
                </div>
            </div>
            <div id="panorama-container"></div>
            <div id="mapa-google-wrapper">
                <img id="mapa-google-overlay" src="images/mapa_google.jpeg" alt="Mapa guía del colegio" loading="eager">
                <div class="minimap-overlay" id="minimap-overlay" aria-hidden="true">
                    <div id="minimap__nodes"></div>
                    <div id="minimap__user" class="minimap-user-marker"></div>
                </div>
                <svg id="mapa-google-layer" viewBox="0 0 100 100" preserveAspectRatio="none" aria-label="Capas editables del mapa guía"><defs><pattern id="map-guide-grid-pattern" width="5" height="5" patternUnits="userSpaceOnUse"><path d="M 5 0 L 0 0 0 5" fill="none" stroke="#1683d8" stroke-opacity=".28" stroke-width=".18"/></pattern></defs><rect id="map-guide-grid" x="0" y="0" width="100" height="100" fill="url(#map-guide-grid-pattern)" visibility="hidden"></rect><g id="map-guide-content"></g></svg>
            </div>

            <div class="corner-badge" aria-label="Indicador de vista">
                <div class="corner-badge__icon">↗</div>
                <div class="corner-badge__label">Vista 360</div>
            </div>
            <svg id="route-line" aria-hidden="true"><path id="route-path"></path><circle id="route-end" r="8"></circle></svg>
        </div>
                <div id="side-menu">
            <button id="btn-add" style="background:var(--primary); color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">➕ Añadir Flecha Aquí</button>
            <button id="btn-add-alert" style="background:#f59e0b; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">⚠️ Añadir signo de exclamación</button>
            <button id="btn-open-label-form" style="background:#0ea5e9; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">🏷️ Mini formulario de etiqueta</button>
            <button id="btn-place-indicator" style="background:#0ea5e9; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">📍 Colocar indicador</button>
            <button id="btn-place-advance" style="background:#f59e0b; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">🎯 Colocar punto avance</button>

            <div class="menu-section" id="alert-menu-section">
                <label>Menú de avisos</label>
                <div id="alerts-list"></div>
            </div>
            
            <div class="menu-section">
                <label>Imagen donde estás:</label>
                <div id="label-source" style="padding:8px; background:#111; border:1px solid #222; color:#ddd; border-radius:4px">-</div>
            </div>

            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#ddd;">
                <input id="allow-hotspot-move" type="checkbox">
                Permitir mover flechas
            </label>

            <div class="menu-section">
                <label>Buscar imagen destino:</label>
                <input id="route-destination" list="route-images" placeholder="Escribe imagen 4" style="width:100%; padding:8px; background:#333; color:white; border:1px solid #444">
                <datalist id="route-images"></datalist>
                <button id="btn-show-route" style="margin-top:8px; width:100%; background:#168a4a; color:white; border:1px solid #21c46b; padding:8px; border-radius:4px; cursor:pointer">Mostrar ruta verde</button>
                <div id="route-status" style="margin-top:7px; font-size:11px; color:#8ff0b6"></div>
            </div>

            <div class="menu-section">
                <label>Vista inicial de esta imagen:</label>
                <div id="initial-view-label" style="padding:8px; background:#111; border:1px solid #222; color:#ddd; border-radius:4px">-</div>
                <button id="btn-set-forward-view" style="margin-top:8px; width:100%; background:#087f5b; color:white; border:1px solid #099268; padding:8px; border-radius:4px; cursor:pointer">Guardar vista adelante</button>
                <button id="btn-set-backward-view" style="margin-top:6px; width:100%; background:#8d4a00; color:white; border:1px solid #b35c00; padding:8px; border-radius:4px; cursor:pointer">Guardar vista atrás</button>
            </div>

            <div class="menu-section">
                <label>Destino (imagen a donde va):</label>
                <select id="select-target" style="width:100%; padding:8px; background:#333; color:white; border:1px solid #444"></select>
            </div>
            
            <div class="menu-section">
                <label>Color:</label>
                <select id="select-color" style="width:100%; padding:8px; background:#333; color:white; border:1px solid #444">
                    <option value="white">Blanco</option>
                    <option value="red">Rojo</option>
                    <option value="blue">Azul</option>
                    <option value="yellow">Amarillo</option>
                </select>
            </div>

            <hr style="opacity:0.1">

            <div class="menu-section">
                <label>Giro (Dirección): <span id="val-rot" class="val-text">0°</span></label>
                <input type="range" id="range-rot" min="0" max="360" value="0">
                
                <label>Inclinación (Perspectiva): <span id="val-tilt" class="val-text">0°</span></label>
                <input type="range" id="range-tilt" min="-90" max="90" value="0">
                
                <label>Ancho: <span id="val-w" class="val-text">60px</span></label>
                <input type="range" id="range-w" min="20" max="200" value="60">
                
                <label>Alto: <span id="val-h" class="val-text">60px</span></label>
                <input type="range" id="range-h" min="20" max="200" value="60">
            </div>

            <button id="btn-del" style="background:#dc3545; color:white; border:none; padding:10px; border-radius:4px; cursor:pointer; width:100%">🗑️ Eliminar Flecha</button>
                </div>
    </div>
    <div id="map-guide-modal" role="dialog" aria-modal="true" aria-labelledby="map-guide-title">
        <div id="map-guide-modal-header"><h2 id="map-guide-title">Editor del mapa guía del colegio</h2><div><button id="map-guide-modal-save" type="button" style="background:#198754;color:#fff;border:0;border-radius:5px;padding:8px 14px;cursor:pointer;margin-right:8px">Guardar mapa</button><button id="map-guide-modal-close" type="button">Cerrar</button></div></div>
        <div id="map-guide-modal-body">
            <div id="map-guide-modal-stage"></div>
            <div id="map-editor-section" class="map-editor-panel">
                <h3>Elementos del mapa</h3>
                <p class="map-editor-help">Elige una herramienta y pulsa sobre el mapa grande. Haz clic en un elemento para editar sus atributos.</p>
                <div class="map-control-row"><button id="map-tool-path" type="button">＋ Camino</button><button id="map-tool-place" type="button">＋ Salón/Lugar</button><button id="map-tool-zone" type="button">＋ Área prohibida</button></div>
                <div class="map-control-row"><button id="map-finish-path" type="button">Terminar camino</button><button id="map-clear-guide" type="button">Limpiar mapa</button></div>
                <div class="map-layers-title">Capas visibles</div>
                <label class="map-layer-toggle"><input id="layer-paths" type="checkbox" checked> Caminos</label><label class="map-layer-toggle"><input id="layer-places" type="checkbox" checked> Salones y lugares</label><label class="map-layer-toggle"><input id="layer-zones" type="checkbox" checked> Áreas prohibidas</label><label class="map-layer-toggle"><input id="map-grid-visible" type="checkbox"> Líneas X/Y</label><label class="map-layer-toggle"><input id="map-snap-grid" type="checkbox" checked> Ajustar a líneas</label>
                <div class="map-layers-title">Puntos de control y avance</div>
                <div id="minimap-elements-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #4b5563; border-radius: 4px; padding: 8px; margin-bottom: 10px; background: rgba(0,0,0,0.3);"></div>
                <div class="map-control-row"><button id="map-add-control-point" type="button" style="background: #2563c7; flex: 1;">➕ Indicador</button><button id="map-add-advance-point" type="button" style="background: #f59e0b; flex: 1;">➕ Punto avance</button></div>
                <div id="advance-point-editor" style="display: none; border-top: 1px solid #4b5563; margin-top: 10px; padding-top: 10px;">
                    <strong style="color: #ddd;">Editar punto de avance</strong>
                    <label style="font-size: 11px; color: #d3e1f4; display: block; margin-top: 8px;">Panel/Imagen</label>
                    <select id="advance-point-panoId" style="width: 100%; padding: 6px; background: #111827; color: #fff; border: 1px solid #3b465f; border-radius: 4px; font-size: 12px;">
                        <option value="">Selecciona un panel</option>
                        <option value="imagen1">imagen1</option>
                        <option value="imagen2">imagen2</option>
                        <option value="imagen3">imagen3</option>
                        <option value="imagen4">imagen4</option>
                        <option value="imagen5">imagen5</option>
                        <option value="imagen6">imagen6</option>
                        <option value="imagen7">imagen7</option>
                        <option value="imagen8">imagen8</option>
                        <option value="imagen9">imagen9</option>
                        <option value="imagen10">imagen10</option>
                        <option value="imagen11">imagen11</option>
                        <option value="imagen12">imagen12</option>
                        <option value="imagen13">imagen13</option>
                    </select>
                    <label style="font-size: 11px; color: #d3e1f4; display: block; margin-top: 6px;">Coordenadas X, Y</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                        <input id="advance-point-x" type="number" placeholder="X" step="0.1" min="0" max="100" style="padding: 6px; background: #111827; color: #fff; border: 1px solid #3b465f; border-radius: 4px; font-size: 12px;">
                        <input id="advance-point-y" type="number" placeholder="Y" step="0.1" min="0" max="100" style="padding: 6px; background: #111827; color: #fff; border: 1px solid #3b465f; border-radius: 4px; font-size: 12px;">
                    </div>
                    <button id="advance-point-save" type="button" style="width: 100%; background: #198754; color: white; border: 0; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-top: 8px;">Guardar cambios</button>
                </div>
                <div id="map-element-editor"><strong>Elemento seleccionado</strong><label>Nombre</label><input id="map-element-name" type="text"><label>Color</label><input id="map-element-color" type="color" value="#2563c7"><label>Forma</label><select id="map-element-shape"><option value="street">Calle rectangular blanca</option><option value="line">Línea</option><option value="circle">Circular</option><option value="semicircle">Semicírculo</option><option value="rect">Rectangular</option><option value="diamond">Rombo</option></select><label>Ancho / grosor de la calle</label><input id="map-element-width" type="range" min="1" max="35" value="7"><span id="map-element-width-value">7</span><label>Largo de la calle</label><input id="map-element-length" type="range" min="40" max="180" value="100"><span id="map-element-length-value">100%</span><p class="map-editor-help">Arrastra los puntos blancos que aparecen sobre la calle para cambiar su recorrido.</p><label>Alto</label><input id="map-element-height" type="range" min="1" max="35" value="8"><span id="map-element-height-value">8</span><div class="map-control-row"><button id="map-element-copy" type="button">Copiar</button><button id="map-element-paste" type="button">Pegar</button></div><div class="map-control-row"><button id="map-element-front" type="button">Al frente</button><button id="map-element-back" type="button">Atrás</button></div><button id="map-element-delete" type="button">Eliminar elemento</button></div>
                <div class="map-control-row"><label for="map-opacity">Opacidad</label><input id="map-opacity" type="range" min="35" max="100" value="92"><span id="map-opacity-value">92%</span></div>
                <div class="map-control-row"><label for="map-grayscale">Grises</label><input id="map-grayscale" type="range" min="0" max="100" value="100"><span id="map-grayscale-value">100%</span></div>
                <button id="btn-close-map-editor" type="button">Cerrar sin salir del visor</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

    <script>
        let panoramas = [];

        let viewer = null, currentPano = null, selectedHS = null, isEdit = true, draggingHS = null, isDragging = false, rotateDragging = false, rotateStartX = 0, rotateStartRotate = 0;
        let allowHotspotMove = false;
        let routeTargetId = null, routePath = [], routeNextHotspot = null;
        let selectedMinimapElement = null;
        let labelDragState = null;
        let selectedLabel = null;
        let alertPlacementMode = false;
        let draggingAlert = null;
        let suppressAlertClick = false;
        let saveTimer = null;
        let minimapaLeaflet = null;
        let minimapaMarker = null;
        let minimapaLinea = null;
        let minimapPlacementMode = null;
        let minimapIndicator = { x: 50, y: 50 };
        let minimapAdvancePoints = [];
        const STORAGE_KEY = 'mapa360.panoramas.v3';
        const PENDING_STORAGE_KEY = 'mapa360.panoramas.pending.v3';
        const MINIMAP_INDICATOR_KEY = 'mapa360.minimap.indicator.v1';
        const MINIMAP_ADVANCE_KEY = 'mapa360.minimap.advance.v1';
        const SAVE_DELAY_MS = 500;
        const MAP_SETTINGS_KEY = 'mapa360.google-map.settings.v1';
        const DEFAULT_MAP_SETTINGS = { width: 300, opacity: 92, grayscale: 100, left: 16, bottom: 16 };
        let mapSettings = { ...DEFAULT_MAP_SETTINGS };
        const MAP_GUIDE_KEY = 'mapa360.school-guide.v1';
        let mapGuide = { paths: [], places: [], zones: [], layers: { paths: true, places: true, zones: true, user: true }, panoramaPositions: {} };
        let activeMapTool = null;
        let draftPath = [];
        let selectedMapElement = null;
        let mapOriginalParent = null;
        let draggingPathPoint = null;
        let mapClipboard = null;

        const COORDENADAS_PANORAMAS = {
            imagen1: [4.4389, -75.2322],
            imagen2: [4.4392, -75.2318],
            imagen3: [4.4395, -75.2312],
            imagen4: [4.4398, -75.2307],
            imagen5: [4.4401, -75.2301],
            imagen6: [4.4404, -75.2296],
            imagen7: [4.4408, -75.2289],
            imagen8: [4.4411, -75.2284],
            imagen9: [4.4414, -75.2278],
            imagen10: [4.4417, -75.2273],
            imagen11: [4.4419, -75.2268],
            imagen12: [4.4423, -75.2262],
            imagen13: [4.4426, -75.2258]
        };

        function setupLeafletMiniMap() {
            const contenedor = document.getElementById('minimap');
            if (!contenedor || minimapaLeaflet) return;

            minimapaLeaflet = L.map('minimap', {
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false,
                dragging: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false
            }).setView(COORDENADAS_PANORAMAS.imagen1, 18);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 20
            }).addTo(minimapaLeaflet);

            const iconoActual = L.divIcon({
                className: '',
                html: '<div class="marcador-actual"></div>',
                iconSize: [28, 28],
                iconAnchor: [14, 28]
            });

            minimapaMarker = L.marker(COORDENADAS_PANORAMAS.imagen1, { icon: iconoActual }).addTo(minimapaLeaflet);
            minimapaMarker.bindPopup('Estás aquí');
            minimapaLinea = L.polyline([], {
                color: '#d62828',
                weight: 4,
                opacity: 0.8
            }).addTo(minimapaLeaflet);
        }

        function updateLeafletMiniMap() {
            if (!minimapaLeaflet || !minimapaMarker) return;
            const coords = currentPano && currentPano.id ? (COORDENADAS_PANORAMAS[currentPano.id] || COORDENADAS_PANORAMAS.imagen1) : COORDENADAS_PANORAMAS.imagen1;
            minimapaMarker.setLatLng(coords);
            minimapaMarker.bindPopup('Estás en: ' + (currentPano && currentPano.title ? currentPano.title : currentPano.id)).openPopup();

            const idsRuta = [currentPano.id, ...routePath.map(step => step.toId)];
            const ruta = idsRuta
                .filter((id, index, lista) => id && lista.indexOf(id) === index)
                .map(id => COORDENADAS_PANORAMAS[id])
                .filter(Boolean);
            if (ruta.length > 1) {
                minimapaLinea.setLatLngs(ruta);
            } else {
                minimapaLinea.setLatLngs([coords, coords]);
            }
            minimapaLeaflet.setView(coords, 18, { animate: true });
        }

        function normalizeHotspot(h) {
            if (!h) return h;
            if (!h.targetId && (h.targetImage || h.target)) {
                h.targetId = h.targetId || h.targetImage || h.target;
            }
            h.pitch = Number(h.pitch) || 0;
            h.yaw = Number(h.yaw) || 0;
            h.w = Number(h.w) || Number(h.width) || 60;
            h.h = Number(h.h) || Number(h.height) || 60;
            h.rotate = Number(h.rotate) || 0;
            h.tilt = Number(h.tilt) || 0;
            h.color = h.color || 'white';
            return h;
        }

        function normalizePanorama(p) {
            if (!p) return p;
            if (!p.hotspots) p.hotspots = [];
            if (!Array.isArray(p.labels)) p.labels = [];
            if (!Array.isArray(p.alerts)) p.alerts = [];
            p.initialYaw = Number.isFinite(Number(p.initialYaw)) ? Number(p.initialYaw) : 0;
            p.initialPitch = Number.isFinite(Number(p.initialPitch)) ? Number(p.initialPitch) : 0;
            p.forwardYaw = Number.isFinite(Number(p.forwardYaw)) ? Number(p.forwardYaw) : p.initialYaw;
            p.forwardPitch = Number.isFinite(Number(p.forwardPitch)) ? Number(p.forwardPitch) : p.initialPitch;
            p.backwardYaw = Number.isFinite(Number(p.backwardYaw)) ? Number(p.backwardYaw) : p.initialYaw;
            p.backwardPitch = Number.isFinite(Number(p.backwardPitch)) ? Number(p.backwardPitch) : p.initialPitch;
            p.hotspots = p.hotspots.map(normalizeHotspot);
            p.labels = p.labels.map(label => ({
                id: label.id || `label-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                text: label.text || 'Etiqueta',
                profesor: label.profesor || '',
                grado: label.grado || '',
                salon: label.salon || '',
                dia: label.dia || '',
                hora: label.hora || '',
                horaInicio: label.horaInicio || '',
                horaFin: label.horaFin || '',
                descripcion: label.descripcion || '',
                x: Number(label.x) || 50,
                y: Number(label.y) || 50,
                color: label.color || '#ffffff',
                bg: label.bg || 'rgba(15, 23, 42, 0.75)',
                fontSize: Number(label.fontSize) || 12
            }));
            p.alerts = p.alerts.map(alert => ({
                id: alert.id || `alert-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                title: alert.title || 'Aviso',
                description: alert.description || '',
                profesor: alert.profesor || '',
                grado: alert.grado || '',
                salon: alert.salon || '',
                dia: alert.dia || '',
                hora: alert.hora || '',
                horaInicio: alert.horaInicio || '',
                horaFin: alert.horaFin || '',
                titulo: alert.titulo || '',
                descripcion: alert.descripcion || '',
                pitch: Number(alert.pitch) || 0,
                yaw: Number(alert.yaw) || 0,
                x: Number(alert.x) || 50,
                y: Number(alert.y) || 50,
                color: alert.color || '#fbbf24',
                bg: alert.bg || 'rgba(101, 35, 18, 0.85)'
            }));
            return p;
        }

        function normalizePanoramas(list) {
            return list.map(normalizePanorama);
        }

        function ensureAllPanels() {
            if (!Array.isArray(panoramas) || !panoramas.length) return;
            const ids = new Set(panoramas.map(p => p.id));
            for (let panelNumber = 1; panelNumber <= 13; panelNumber++) {
                const panelId = `imagen${panelNumber}`;
                if (!ids.has(panelId)) continue;
            }
        }

        function updateSaveStatus(text, isBusy = false) {
            const statusEl = document.getElementById('save-status');
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.style.color = isBusy ? '#ffd166' : '#c8f7c5';
        }

        function openLabelForm() {
            const form = document.getElementById('label-form-panel');
            if (form) form.classList.remove('hidden');
        }

        function closeLabelForm() {
            setLabelFormEditable(true);
            const form = document.getElementById('label-form-panel');
            if (form) form.classList.add('hidden');
        }

        function prepareAlertForm() {
            const title = document.getElementById('label-titulo');
            const description = document.getElementById('label-descripcion');
            const alertChk = document.getElementById('label-is-alert');
            if (alertChk) alertChk.checked = true;
            if (title) title.value = '';
            if (description) description.value = '';
            alertPlacementMode = true;
            openLabelForm();
            if (title) title.focus();
        }

        function setLabelFormEditable(isEditable) {
            const ids = [
                'label-profesor', 'label-grado', 'label-salon', 'label-dia', 'label-id-horario',
                'label-hora-inicio', 'label-hora-fin', 'label-hora', 'label-titulo', 'label-descripcion',
                'label-pitch', 'label-yaw', 'label-color', 'label-size', 'label-is-alert'
            ];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                if (el.tagName === 'SELECT' || el.type === 'checkbox') {
                    el.disabled = !isEditable;
                } else {
                    el.readOnly = !isEditable;
                }
            });
            const saveBtn = document.getElementById('btn-add-label');
            if (saveBtn) {
                saveBtn.disabled = !isEditable;
                saveBtn.textContent = isEditable ? 'Guardar etiqueta / aviso' : 'Solo lectura';
            }
        }

        function openLabelEditorFor(label) {
            if (!label) return;
            selectedLabel = label;
            setLabelFormEditable(true);
            fillLabelFormFromSelection(label);
            openLabelForm();
        }

        function openAlertForm(alert) {
            if (!alert) return;
            selectedLabel = alert;
            setLabelFormEditable(false);
            const professorInput = document.getElementById('label-profesor');
            const gradoInput = document.getElementById('label-grado');
            const salonInput = document.getElementById('label-salon');
            const diaInput = document.getElementById('label-dia');
            const horaInicioInput = document.getElementById('label-hora-inicio');
            const horaFinInput = document.getElementById('label-hora-fin');
            const horaInput = document.getElementById('label-hora');
            const tituloInput = document.getElementById('label-titulo');
            const descripcionInput = document.getElementById('label-descripcion');
            const colorInput = document.getElementById('label-color');
            const alertChk = document.getElementById('label-is-alert');

            if (professorInput) professorInput.value = alert.profesor || '';
            if (gradoInput) gradoInput.value = alert.grado || '';
            if (salonInput) salonInput.value = alert.salon || '';
            if (diaInput) diaInput.value = alert.dia || '';
            if (horaInicioInput) horaInicioInput.value = alert.horaInicio || '';
            if (horaFinInput) horaFinInput.value = alert.horaFin || '';
            if (horaInput) horaInput.value = alert.hora || (alert.horaInicio && alert.horaFin ? `${alert.horaInicio} - ${alert.horaFin}` : '');
            if (tituloInput) tituloInput.value = alert.titulo || alert.title || '';
            if (descripcionInput) descripcionInput.value = alert.descripcion || alert.description || '';
            if (colorInput) colorInput.value = alert.color || '#fbbf24';
            if (alertChk) alertChk.checked = true;
            openLabelForm();
        }

        function addAlertAtPosition(yaw, pitch) {
            const titleInput = document.getElementById('label-titulo');
            const descriptionInput = document.getElementById('label-descripcion');
            const title = titleInput ? titleInput.value.trim() : '';
            const description = descriptionInput ? descriptionInput.value.trim() : '';

            if (!title && !description) {
                alert('Escribe primero el título o la información del aviso antes de colocarlo.');
                return;
            }

            currentPano.alerts = currentPano.alerts || [];
            currentPano.alerts.push({
                id: `alert-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                title: title || 'Aviso importante',
                description: description || 'Sin información adicional.',
                pitch: Number(pitch) || 0,
                yaw: Number(yaw) || 0,
                x: Number(yaw) || 50,
                y: Number(pitch) || 50,
                color: '#fbbf24',
                bg: 'rgba(101, 35, 18, 0.85)'
            });
            renderAlerts();
            markDirty();
            alertPlacementMode = false;
            closeLabelForm();
        }

        function buildLabelText(label) {
            const parts = [];
            if (label.profesor) parts.push(label.profesor);
            if (label.grado) parts.push(label.grado);
            if (label.salon) parts.push(`Salón ${label.salon}`);
            if (label.horaInicio || label.horaFin) {
                const horaText = [label.horaInicio, label.horaFin].filter(Boolean).join(' - ');
                if (horaText) parts.push(horaText);
            }
            if (label.hora) parts.push(label.hora);
            if (label.dia) parts.push(label.dia);
            if (label.descripcion) parts.push(label.descripcion);
            if (!parts.length) return label.text || 'Etiqueta';
            return parts.join(' · ');
        }

        function fillLabelFormFromSelection(label) {
            if (!label) return;
            const professorInput = document.getElementById('label-profesor');
            const gradoInput = document.getElementById('label-grado');
            const salonInput = document.getElementById('label-salon');
            const diaInput = document.getElementById('label-dia');
            const horaInput = document.getElementById('label-hora');
            const horaInicioInput = document.getElementById('label-hora-inicio');
            const horaFinInput = document.getElementById('label-hora-fin');
            const titleInput = document.getElementById('label-titulo');
            const descriptionInput = document.getElementById('label-descripcion');
            const colorInput = document.getElementById('label-color');
            const sizeInput = document.getElementById('label-size');
            const sizeValue = document.getElementById('label-size-value');

            if (professorInput) professorInput.value = label.profesor || '';
            if (gradoInput) gradoInput.value = label.grado || '';
            if (salonInput) salonInput.value = label.salon || '';
            if (diaInput) diaInput.value = label.dia || '';
            if (horaInput) horaInput.value = label.hora || '';
            if (horaInicioInput) horaInicioInput.value = label.horaInicio || (label.hora || '').split(' - ')[0] || '';
            if (horaFinInput) horaFinInput.value = label.horaFin || (label.hora || '').split(' - ')[1] || '';
            if (titleInput) titleInput.value = label.titulo || label.text || '';
            if (descriptionInput) descriptionInput.value = label.descripcion || '';
            if (colorInput) colorInput.value = label.color || '#ffffff';
            if (sizeInput) sizeInput.value = String(label.fontSize || 12);
            if (sizeValue) sizeValue.textContent = `${label.fontSize || 12} px`;
        }

        function renderLabels() {
            const viewerWrap = document.getElementById('viewer-wrapper');
            if (!viewerWrap) return;
            const existing = viewerWrap.querySelectorAll('.viewer-label');
            existing.forEach(node => node.remove());

            (currentPano.labels || []).forEach((label) => {
                const node = document.createElement('div');
                node.className = 'viewer-label' + (label.id === (selectedHS && selectedHS.id) ? ' selected' : '');
                const labelText = buildLabelText(label);
                node.title = labelText;
                node.style.left = `${label.x}%`;
                node.style.top = `${label.y}%`;
                node.style.color = label.color || '#ffffff';
                node.style.background = label.bg || 'rgba(15, 23, 42, 0.75)';
                node.style.fontSize = `${label.fontSize || 12}px`;

                const textNode = document.createElement('span');
                textNode.textContent = labelText;
                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'viewer-label-delete';
                deleteBtn.setAttribute('aria-label', 'Eliminar etiqueta');
                deleteBtn.textContent = '×';

                deleteBtn.onclick = (e) => {
                    e.stopPropagation();
                    currentPano.labels = currentPano.labels.filter(item => item.id !== label.id);
                    renderLabels();
                    markDirty();
                };

                node.onclick = (e) => {
                    if (e.target.closest('.viewer-label-delete')) return;
                    if (!isEdit) return;
                    document.querySelectorAll('.viewer-label').forEach(item => item.classList.remove('selected'));
                    node.classList.add('selected');
                    selectedLabel = label;
                    fillLabelFormFromSelection(label);
                    openLabelEditorFor(label);
                };

                node.onpointerdown = (e) => {
                    if (!isEdit || e.target.closest('.viewer-label-delete')) return;
                    labelDragState = { id: label.id, startX: e.clientX, startY: e.clientY, originalX: label.x, originalY: label.y };
                    node.setPointerCapture?.(e.pointerId);
                };

                node.appendChild(textNode);
                node.appendChild(deleteBtn);
                viewerWrap.appendChild(node);
            });
        }

        function openAlertPopup(alert) {
            if (!alert) return;
            selectedLabel = alert;
            setLabelFormEditable(false);
            const professorInput = document.getElementById('label-profesor');
            const gradoInput = document.getElementById('label-grado');
            const salonInput = document.getElementById('label-salon');
            const diaInput = document.getElementById('label-dia');
            const horaInicioInput = document.getElementById('label-hora-inicio');
            const horaFinInput = document.getElementById('label-hora-fin');
            const horaInput = document.getElementById('label-hora');
            const tituloInput = document.getElementById('label-titulo');
            const descripcionInput = document.getElementById('label-descripcion');
            const colorInput = document.getElementById('label-color');
            const alertChk = document.getElementById('label-is-alert');

            if (professorInput) professorInput.value = alert.profesor || '';
            if (gradoInput) gradoInput.value = alert.grado || '';
            if (salonInput) salonInput.value = alert.salon || '';
            if (diaInput) diaInput.value = alert.dia || '';
            if (horaInicioInput) horaInicioInput.value = alert.horaInicio || '';
            if (horaFinInput) horaFinInput.value = alert.horaFin || '';
            if (horaInput) horaInput.value = alert.hora || (alert.horaInicio && alert.horaFin ? `${alert.horaInicio} - ${alert.horaFin}` : '');
            if (tituloInput) tituloInput.value = alert.titulo || alert.title || '';
            if (descripcionInput) descripcionInput.value = alert.descripcion || alert.description || '';
            if (colorInput) colorInput.value = alert.color || '#fbbf24';
            if (alertChk) alertChk.checked = true;
            openLabelForm();
        }

        function renderAlerts() {
            const viewerWrap = document.getElementById('viewer-wrapper');
            if (!viewerWrap) return;

            if (viewer && viewer.getConfig) {
                const configHotspots = viewer.getConfig().hotSpots || [];
                configHotspots.forEach(h => {
                    if (String(h.id || '').startsWith('alert-')) viewer.removeHotSpot(h.id);
                });
            }

            (currentPano.alerts || []).forEach((alert) => {
                if (!viewer || !viewer.addHotSpot) return;
                const pitch = Number.isFinite(Number(alert.pitch)) ? Number(alert.pitch) : Number(alert.y) || 0;
                const yaw = Number.isFinite(Number(alert.yaw)) ? Number(alert.yaw) : Number(alert.x) || 0;
                viewer.addHotSpot({
                    id: `alert-${alert.id}`,
                    pitch,
                    yaw,
                    cssClass: 'custom-alert',
                    // Estas coordenadas pertenecen a la esfera 360, no a la pantalla.
                    // Por eso el aviso gira y desaparece de la vista al cambiar el yaw/pitch.
                    createTooltipFunc: (el) => {
                        el.dataset.alertId = alert.id;
                        el.dataset.pitch = String(pitch);
                        el.dataset.yaw = String(yaw);
                        el.innerHTML = '!';
                        el.title = alert.title || 'Aviso';
                        el.style.cursor = 'pointer';
                        el.style.display = 'flex';
                        el.style.alignItems = 'center';
                        el.style.justifyContent = 'center';
                        el.onpointerdown = (e) => {
                            if (!isEdit || e.button !== 0) return;
                            e.preventDefault();
                            e.stopPropagation();
                            el.setPointerCapture?.(e.pointerId);
                            el.classList.add('is-dragging');
                            draggingAlert = {
                                id: alert.id,
                                pointerId: e.pointerId,
                                moved: false,
                                element: el,
                                lastClientX: e.clientX,
                                lastClientY: e.clientY,
                                lastCoords: null
                            };
                            suppressAlertClick = false;
                            selectedLabel = alert;
                        };
                        el.onpointerup = (e) => {
                            if (draggingAlert?.id !== alert.id) return;
                            el.releasePointerCapture?.(e.pointerId);
                            el.classList.remove('is-dragging');
                        };
                        el.onclick = (e) => {
                            if (suppressAlertClick) {
                                suppressAlertClick = false;
                                e.preventDefault();
                                e.stopPropagation();
                                return;
                            }
                            e.preventDefault();
                            e.stopPropagation();
                            openAlertPopup(alert);
                        };
                    },
                    clickHandlerFunc: () => {
                        if (suppressAlertClick) {
                            suppressAlertClick = false;
                            return;
                        }
                        openAlertPopup(alert);
                    }
                });
            });

            const alertsList = document.getElementById('alerts-list');
            if (!alertsList) return;
            alertsList.innerHTML = '';
            if (!currentPano.alerts || !currentPano.alerts.length) {
                const empty = document.createElement('div');
                empty.style.opacity = '0.7';
                empty.style.fontSize = '11px';
                empty.style.color = '#d1d5db';
                empty.textContent = 'Sin avisos';
                alertsList.appendChild(empty);
                return;
            }

            currentPano.alerts.forEach((alert) => {
                const row = document.createElement('div');
                row.style.display = 'flex';
                row.style.alignItems = 'center';
                row.style.gap = '8px';

                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'alert-menu-item';
                item.innerHTML = `<span>${(alert.title || 'Aviso').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[ch]))}</span>`;
                item.onclick = () => openAlertPopup(alert);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'alert-menu-delete';
                removeBtn.textContent = '×';
                removeBtn.title = 'Eliminar aviso';
                removeBtn.onclick = (e) => {
                    e.stopPropagation();
                    currentPano.alerts = (currentPano.alerts || []).filter(item => item.id !== alert.id);
                    const popup = document.getElementById('alert-popup');
                    if (popup) popup.classList.remove('visible');
                    renderAlerts();
                    markDirty();
                };

                row.appendChild(item);
                row.appendChild(removeBtn);
                alertsList.appendChild(row);
            });
        }

        async function addLabelFromForm() {
            const profesor = document.getElementById('label-profesor').value.trim();
            const grado = document.getElementById('label-grado').value.trim();
            const salon = document.getElementById('label-salon').value.trim();
            const dia = document.getElementById('label-dia').value || '';
            const horaInicioInput = document.getElementById('label-hora-inicio');
            const horaFinInput = document.getElementById('label-hora-fin');
            const horaInicio = horaInicioInput ? horaInicioInput.value || '' : '';
            const horaFin = horaFinInput ? horaFinInput.value || '' : '';
            const hora = horaInicio && horaFin ? `${horaInicio} - ${horaFin}` : horaInicio || horaFin || '';
            const titulo = document.getElementById('label-titulo').value.trim();
            const descripcion = document.getElementById('label-descripcion').value.trim();
            const color = document.getElementById('label-color').value || '#ffffff';
            const x = 50;
            const y = 50;
            const background = 'rgba(15, 23, 42, 0.75)';
            const isAlert = document.getElementById('label-is-alert')?.checked;

            if (isAlert) {
                const alertTitle = titulo || profesor || 'Aviso importante';
                const alertDescription = [profesor, grado, salon ? `Salón ${salon}` : '', dia, horaInicio && horaFin ? `${horaInicio} - ${horaFin}` : hora, descripcion].filter(Boolean).join(' · ');
                if (!alertTitle && !alertDescription) {
                    alert('Escribe primero el texto del aviso antes de guardarlo.');
                    return;
                }
                const centerYaw = Number((viewer && viewer.getYaw && viewer.getYaw()) || 0);
                const centerPitch = Number((viewer && viewer.getPitch && viewer.getPitch()) || 0);
                currentPano.alerts = currentPano.alerts || [];
                currentPano.alerts.push({
                    id: `alert-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    title: alertTitle,
                    description: alertDescription || 'Sin información adicional.',
                    profesor: profesor,
                    grado: grado,
                    salon: salon,
                    dia: dia,
                    horaInicio: horaInicio,
                    horaFin: horaFin,
                    titulo: titulo,
                    descripcion: descripcion,
                    pitch: centerPitch,
                    yaw: centerYaw,
                    x,
                    y,
                    color: '#fbbf24',
                    bg: 'rgba(101, 35, 18, 0.85)'
                });
                renderAlerts();
                markDirty();
                alertPlacementMode = false;
                closeLabelForm();
                return;
            }

            if (typeof window.guardarLugarEnBaseDeDatos === 'function') {
                const ok = await window.guardarLugarEnBaseDeDatos();
                if (!ok) return;
            }

            const fontSize = Number(document.getElementById('label-size').value || 12);
            currentPano.labels = currentPano.labels || [];
            const labelText = `${profesor || titulo || 'Lugar'}${grado ? ` · ${grado}` : ''}${salon ? ` · Salón ${salon}` : ''}${dia ? ` · ${dia}` : ''}${horaInicio && horaFin ? ` · ${horaInicio} - ${horaFin}` : hora ? ` · ${hora}` : ''}${descripcion ? ` · ${descripcion}` : ''}`;
            currentPano.labels.push({
                id: `label-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                text: labelText,
                profesor,
                grado,
                salon,
                dia,
                hora,
                horaInicio,
                horaFin,
                titulo,
                descripcion,
                x,
                y,
                color,
                bg: background,
                fontSize
            });
            renderLabels();
            markDirty();
            closeLabelForm();
        }

        function clampNumber(value, min, max) {
            if (!Number.isFinite(value)) return min;
            return Math.min(max, Math.max(min, value));
        }

        function markDirty() {
            if (saveTimer) clearTimeout(saveTimer);
            try {
                localStorage.setItem(PENDING_STORAGE_KEY, JSON.stringify(panoramas));
                localStorage.setItem(STORAGE_KEY, JSON.stringify(panoramas));
            } catch (e) {
                console.warn('No se pudo guardar la copia pendiente', e);
            }
            updateSaveStatus('Guardando cambios...', true);
            saveTimer = setTimeout(() => savePanoramas({ showConfirm: false, silent: true }), SAVE_DELAY_MS);
        }

        // Cargar primero la versión del servidor y usar localStorage como respaldo sin conexión.
        async function loadPanoramas() {
            const datasetCandidates = [
                'data/colegio_santander.json',
                'data/panoramas.json'
            ];

            let loadedFromFile = false;

            for (const filePath of datasetCandidates) {
                try {
                    const res = await fetch(filePath, { cache: 'no-store' });
                    if (!res.ok) continue;
                    const data = await res.json();
                    if (Array.isArray(data)) {
                        panoramas = normalizePanoramas(data);
                        loadedFromFile = true;
                        break;
                    }
                    if (data && Array.isArray(data.panoramas)) {
                        panoramas = normalizePanoramas(data.panoramas);
                        loadedFromFile = true;
                        break;
                    }
                } catch (e) {
                    console.warn(`No se pudo cargar ${filePath}`, e);
                }
            }

            if (!loadedFromFile) {
                console.log('No se cargó la base de datos colegio_santander ni el archivo local; se intentará usar el guardado local');
                try {
                    const saved = localStorage.getItem(STORAGE_KEY);
                    if (saved) {
                        const parsed = JSON.parse(saved);
                        if (Array.isArray(parsed) && parsed.length) {
                            panoramas = normalizePanoramas(parsed);
                        }
                    }
                } catch (localError) {
                    console.warn('No se pudo leer el guardado local', localError);
                }
            }

            if (!Array.isArray(panoramas) || !panoramas.length) {
                console.warn('No hay dataset real cargado para colegio_santander.');
            }
            try {
                const pending = localStorage.getItem(PENDING_STORAGE_KEY);
                if (pending) {
                    const parsedPending = JSON.parse(pending);
                    if (Array.isArray(parsedPending) && parsedPending.length) {
                        panoramas = normalizePanoramas(parsedPending);
                    }
                }
            } catch (e) {
                console.warn('No se pudo recuperar la copia pendiente', e);
            }
            ensureAllPanels();
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(panoramas));
            } catch (e) {
                console.warn('No se pudo guardar la copia local inicial', e);
            }
        }

        function getArrivalView(fromPanoId) {
            if (!fromPanoId) {
                return { yaw: currentPano.initialYaw, pitch: currentPano.initialPitch };
            }

            const currentNumber = Number((currentPano.id.match(/\d+/) || [0])[0]);
            const fromNumber = Number((fromPanoId.match(/\d+/) || [0])[0]);
            if (fromNumber < currentNumber) {
                return { yaw: currentPano.forwardYaw, pitch: currentPano.forwardPitch };
            }

            if (fromNumber > currentNumber) {
                return { yaw: currentPano.backwardYaw, pitch: currentPano.backwardPitch };
            }

            return { yaw: currentPano.initialYaw, pitch: currentPano.initialPitch };
        }

        function init(fromPanoId = null) {
            if (!currentPano || !currentPano.path) {
                updateSaveStatus('Sin panoramas cargados', false);
                return;
            }
            if (viewer) viewer.destroy();
            const arrivalView = getArrivalView(fromPanoId);
            viewer = pannellum.viewer('panorama-container', {
                "type": "equirectangular",
                "panorama": currentPano.path,
                "yaw": arrivalView.yaw,
                "pitch": arrivalView.pitch,
                "autoLoad": true,
                "renderer": "webgl",
                "hfov": 90,
                "showControls": false
            });
            viewer.on('load', () => { renderHS(); renderAlerts(); if(typeof updateSourceLabel === 'function') updateSourceLabel(); renderMiniMap(); updateMinimap(); setupLeafletMiniMap(); updateLeafletMiniMap(); });
            viewer.on('viewchange', () => { requestAnimationFrame(updateRouteLine); requestAnimationFrame(updateMinimap); requestAnimationFrame(updateLeafletMiniMap); });
            const cont = document.getElementById('panorama-container');
            cont.ondblclick = (e) => { if (isEdit) { const c = viewer.mouseEventToCoords(e); if(c) addHS(c[0], c[1]); } };
            cont.oncontextmenu = (e) => {
                if (!isEdit) return;
                e.preventDefault();
                const c = viewer.mouseEventToCoords(e);
                if (!c) return;
                if (alertPlacementMode) {
                    addAlertAtPosition(c[0], c[1]);
                    return;
                }
                const titleInput = document.getElementById('label-titulo');
                const descriptionInput = document.getElementById('label-descripcion');
                const sizeInput = document.getElementById('label-size');
                const posX = document.getElementById('label-pos-x');
                const posY = document.getElementById('label-pos-y');
                if (titleInput) titleInput.value = '';
                if (descriptionInput) descriptionInput.value = '';
                if (sizeInput) sizeInput.value = '12';
                const sizeValue = document.getElementById('label-size-value');
                if (sizeValue) sizeValue.textContent = '12 px';
                if (posX) posX.value = String(Math.round(c[0]));
                if (posY) posY.value = String(Math.round(c[1]));
                document.getElementById('label-is-alert')?.click();
                openLabelForm();
                if (titleInput) titleInput.focus();
            };
        }

        function addHS(p, y) {
            const defaultTarget = document.getElementById('select-target').value || panoramas[0].id;
            const newH = { pitch: p, yaw: y, targetId: defaultTarget, sourceImage: currentPano.id, color: 'white', w: 60, h: 60, rotate: 0, tilt: 0 };
            currentPano.hotspots.push(newH);
            selectedHS = newH;
            renderHS();
            syncMenu();
            markDirty();
        }

        function renderHS() {
            const cfg = viewer && viewer.getConfig ? viewer.getConfig() : null;
            if (cfg && cfg.hotSpots) [...cfg.hotSpots].forEach(h => viewer.removeHotSpot(h.id));

            currentPano.hotspots.forEach((hs, i) => {
                viewer.addHotSpot({
                    "id": "h"+i, "pitch": hs.pitch, "yaw": hs.yaw, "cssClass": "custom-arrow",
                    "createTooltipFunc": (el) => {
                        if (selectedHS === hs) el.classList.add('selected');
                        el.innerHTML = '';
                        const inner = document.createElement('div');
                        inner.className = 'arrow-inner' + (selectedHS === hs ? ' selected' : '');
                        inner.style.width = hs.w + "px"; inner.style.height = hs.h + "px";
                        inner.style.transform = `rotate(${hs.rotate}deg) rotateX(${hs.tilt}deg)`;
                        const f = { white: 'brightness(0) invert(1)', red: 'sepia(1) saturate(5) hue-rotate(-50deg)', blue: 'sepia(1) saturate(5) hue-rotate(180deg)', yellow: 'sepia(1) saturate(5) hue-rotate(10deg)' };
                        inner.style.filter = f[hs.color];
                        inner.style.touchAction = 'none';
                        inner.style.cursor = 'pointer';
                        inner.style.position = 'relative';

                        inner.onpointerdown = (ev) => {
                            if(!isEdit || !allowHotspotMove || rotateDragging) return;
                            ev.preventDefault(); ev.stopPropagation();
                            draggingHS = hs; isDragging = true;
                        };

                        const rotHandle = document.createElement('div');
                        rotHandle.className = 'rotate-handle';
                        rotHandle.title = 'Arrastra para rotar';
                        rotHandle.style.position = 'absolute';
                        rotHandle.style.right = '-10px';
                        rotHandle.style.top = '-10px';
                        rotHandle.style.width = '16px';
                        rotHandle.style.height = '16px';
                        rotHandle.style.borderRadius = '50%';
                        rotHandle.style.background = 'rgba(255,255,255,0.95)';
                        rotHandle.style.border = '1px solid rgba(0,0,0,0.2)';
                        rotHandle.style.cursor = 'grab';
                        rotHandle.style.display = 'flex';
                        rotHandle.style.alignItems = 'center';
                        rotHandle.style.justifyContent = 'center';
                        rotHandle.style.fontSize = '10px';
                        rotHandle.textContent = '⤾';

                        rotHandle.onpointerdown = (ev) => {
                            if(!isEdit) return;
                            ev.preventDefault(); ev.stopPropagation();
                            rotateDragging = true;
                            rotateStartX = ev.clientX;
                            rotateStartRotate = Number(hs.rotate) || 0;
                        };
                        rotHandle.onpointerover = () => { rotHandle.style.cursor = 'grab'; };
                        rotHandle.onpointerout = () => { rotHandle.style.cursor = 'default'; };

                        el.appendChild(inner);
                        el.appendChild(rotHandle);
                    },
                    "clickHandlerFunc": () => {
                        if (isEdit) { selectedHS = hs; renderHS(); syncMenu(); }
                        else {
                            const t = panoramas.find(p => p.id === hs.targetId);
                            if (t) {
                                currentPano = t;
                                if (routeTargetId) {
                                    routePath = routePath.slice(1);
                                    routeNextHotspot = routePath[0] || null;
                                    if (currentPano.id === routeTargetId) {
                                        routeTargetId = null;
                                        routePath = [];
                                        routeNextHotspot = null;
                                        document.getElementById('route-status').textContent = 'Has llegado a tu destino.';
                                    }
                                }
                                init(hs.sourceImage || currentPano.id);
                                const advancePoint = minimapAdvancePoints.find(p => p.panoId === t.id);
                                if (advancePoint && advancePoint.x !== undefined && advancePoint.y !== undefined) {
                                    minimapIndicator = { x: advancePoint.x, y: advancePoint.y };
                                    saveMiniMapState();
                                }
                            }
                        }
                    }
                });
            });
            requestAnimationFrame(updateRouteLine);
        }

        function findRoute(fromId, targetId) {
            if (!targetId || fromId === targetId) return [];
            const queue = [[fromId, []]];
            const visited = new Set([fromId]);
            while (queue.length) {
                const [id, path] = queue.shift();
                const pano = panoramas.find(p => p.id === id);
                if (!pano) continue;
                for (const hotspot of pano.hotspots) {
                    if (!hotspot.targetId || visited.has(hotspot.targetId)) continue;
                    const nextPath = [...path, { fromId: id, hotspot, toId: hotspot.targetId }];
                    if (hotspot.targetId === targetId) return nextPath;
                    visited.add(hotspot.targetId);
                    queue.push([hotspot.targetId, nextPath]);
                }
            }
            return [];
        }

        function getForwardHotspot(pano) {
            const currentNumber = Number((pano.id.match(/\d+/) || [0])[0]);
            return pano.hotspots.find(h => {
                const targetNumber = Number(((h.targetId || '').match(/\d+/) || [0])[0]);
                return targetNumber > currentNumber;
            }) || pano.hotspots[0] || null;
        }

        const minimapLayout = {
            imagen1: { x: 20, y: 67 },
            imagen2: { x: 30, y: 40 },
            imagen3: { x: 40, y: 25 },
            imagen4: { x: 57, y: 18 },
            imagen5: { x: 67, y: 32 },
            imagen7: { x: 72, y: 56 },
            imagen8: { x: 58, y: 72 },
            imagen9: { x: 43, y: 76 }
        };

        function loadMiniMapState() {
            try {
                const savedIndicator = JSON.parse(localStorage.getItem(MINIMAP_INDICATOR_KEY) || 'null');
                if (savedIndicator && Number.isFinite(Number(savedIndicator.x)) && Number.isFinite(Number(savedIndicator.y))) {
                    minimapIndicator = { x: clampNumber(Number(savedIndicator.x), 0, 100), y: clampNumber(Number(savedIndicator.y), 0, 100) };
                }
                const savedPoints = JSON.parse(localStorage.getItem(MINIMAP_ADVANCE_KEY) || 'null');
                if (Array.isArray(savedPoints)) {
                    minimapAdvancePoints = savedPoints.filter(item => item && Number.isFinite(Number(item.x)) && Number.isFinite(Number(item.y))).map(item => ({
                        id: item.id || `advance-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                        x: clampNumber(Number(item.x), 0, 100),
                        y: clampNumber(Number(item.y), 0, 100)
                    }));
                }
            } catch (error) {
                console.warn('No se pudo cargar el estado del mini mapa', error);
            }
        }

        function saveMiniMapState() {
            try {
                localStorage.setItem(MINIMAP_INDICATOR_KEY, JSON.stringify(minimapIndicator));
                localStorage.setItem(MINIMAP_ADVANCE_KEY, JSON.stringify(minimapAdvancePoints));
            } catch (error) {
                console.warn('No se pudo guardar el estado del mini mapa', error);
            }
        }

        function setMiniMapPlacementMode(mode) {
            minimapPlacementMode = minimapPlacementMode === mode ? null : mode;
            document.getElementById('btn-place-indicator')?.classList.toggle('active', minimapPlacementMode === 'indicator');
            document.getElementById('btn-place-advance')?.classList.toggle('active', minimapPlacementMode === 'advance');
            const label = document.getElementById('btn-place-indicator');
            if (label) label.textContent = minimapPlacementMode === 'indicator' ? '📍 Clic para ubicar indicador' : '📍 Colocar indicador';
            const advance = document.getElementById('btn-place-advance');
            if (advance) advance.textContent = minimapPlacementMode === 'advance' ? '🎯 Clic para ubicar punto' : '🎯 Colocar punto avance';
        }

        function placeMiniMapPoint(event) {
            const wrapper = document.getElementById('mapa-google-wrapper');
            if (!wrapper || !minimapPlacementMode || event.button !== 0) return;
            event.stopPropagation();
            const rect = wrapper.getBoundingClientRect();
            const x = clampNumber(((event.clientX - rect.left) / rect.width) * 100, 0, 100);
            const y = clampNumber(((event.clientY - rect.top) / rect.height) * 100, 0, 100);
            if (minimapPlacementMode === 'indicator') {
                minimapIndicator = { x, y };
            } else {
                const panoId = currentPano ? currentPano.id : 'imagen1';
                const msg = prompt('¿En qué imagen está este punto de avance? (ej: imagen1, imagen2, etc.)', panoId);
                if (msg) {
                    minimapAdvancePoints.push({ id: 'advance-' + Date.now() + '-' + Math.random().toString(16).slice(2), x, y, panoId: msg.trim() });
                }
            }
            saveMiniMapState();
            renderMiniMap();
            updateMinimap();
            renderMinimapElementsList();
            setMiniMapPlacementMode(null);
        }

        function renderMiniMap() {
            const nodesWrap = document.getElementById('minimap__nodes');
            const userMarker = document.getElementById('minimap__user');
            if (!nodesWrap || !userMarker) return;
            nodesWrap.innerHTML = '';

            Object.entries(minimapLayout).forEach(([id, coord]) => {
                const isCurrent = currentPano && currentPano.id === id;
                const isTarget = routeTargetId && routeTargetId === id;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'minimap__node' + (isCurrent ? ' current' : '') + (isTarget ? ' active' : '');
                button.style.left = coord.x + '%';
                button.style.top = coord.y + '%';
                button.title = id;
                button.onclick = () => {
                    routeTargetId = id;
                    document.getElementById('route-destination').value = id;
                    showRoute();
                    renderMiniMap();
                };
                nodesWrap.appendChild(button);
            });

            if (isEdit) {
                minimapAdvancePoints.forEach(point => {
                    const advance = document.createElement('div');
                    advance.className = 'minimap-route-node';
                    advance.style.left = point.x + '%';
                    advance.style.top = point.y + '%';
                    advance.style.width = '12px';
                    advance.style.height = '12px';
                    advance.style.background = '#f59e0b';
                    advance.style.border = '2px solid #fff';
                    advance.style.boxShadow = '0 0 0 3px rgba(245,158,11,0.35)';
                    advance.title = 'Punto de avance para ' + (point.panoId || '?');
                    advance.onclick = (event) => {
                        event.stopPropagation();
                        minimapAdvancePoints = minimapAdvancePoints.filter(item => item.id !== point.id);
                        saveMiniMapState();
                        renderMiniMap();
                    };
                    nodesWrap.appendChild(advance);
                });
            }

            userMarker.style.left = minimapIndicator.x + '%';
            userMarker.style.top = minimapIndicator.y + '%';
            userMarker.style.transform = 'translate(-50%, -50%)';
            userMarker.title = 'Indicador del usuario';
        }

        function updateMinimap() {
            const minimap = document.getElementById('minimap__user');
            if (!minimap || !viewer) { renderMapGuide(); return; }
            renderMapGuide();

            if (minimapIndicator) {
                minimap.style.left = minimapIndicator.x + '%';
                minimap.style.top = minimapIndicator.y + '%';
                minimap.style.transform = 'translate(-50%, -50%)';
            } else {
                const yaw = Number(viewer.getYaw()) || 0;
                const angle = ((yaw + 180) % 360 + 360) % 360;
                const x = 50 + Math.sin((angle - 90) * Math.PI / 180) * 18;
                const y = 50 + Math.cos((angle - 90) * Math.PI / 180) * 16;
                minimap.style.left = x + '%';
                minimap.style.top = y + '%';
                minimap.style.transform = 'translate(-50%, -50%) rotate(' + yaw + 'deg)';
            }
            updateLeafletMiniMap();
        }

        function updateRouteLine() {
            const svg = document.getElementById('route-line');
            const pathEl = document.getElementById('route-path');
            const endEl = document.getElementById('route-end');
            if (!svg || !pathEl || !endEl || !viewer || !routeTargetId || !routeNextHotspot || !routeNextHotspot.hotspot) {
                pathEl.setAttribute('d', '');
                endEl.style.display = 'none';
                return;
            }
            const guideStep = routeNextHotspot;
            const hotspotElements = document.querySelectorAll('#panorama-container .custom-arrow');
            const hotspotIndex = currentPano.hotspots.indexOf(guideStep.hotspot);
            const hotspotElement = hotspotElements[hotspotIndex];
            if (!hotspotElement) {
                pathEl.setAttribute('d', '');
                endEl.style.display = 'none';
                return;
            }
            const wrapperRect = document.getElementById('viewer-wrapper').getBoundingClientRect();
            const targetRect = hotspotElement.getBoundingClientRect();
            const arrowX = targetRect.left + targetRect.width / 2 - wrapperRect.left;
            const arrowY = targetRect.top + targetRect.height / 2 - wrapperRect.top;
            const horizonX = wrapperRect.width / 2;
            const horizonY = Math.max(24, wrapperRect.height * 0.16);
            const tipX = arrowX;
            const tipY = arrowY - targetRect.height * 0.45;
            const endX = horizonX + (arrowX - horizonX) * 0.35;
            const endY = horizonY;
            const bendX = tipX + (endX - tipX) * 0.55;
            const bendY = tipY + (endY - tipY) * 0.55;
            pathEl.setAttribute('d', `M ${tipX} ${tipY} Q ${bendX} ${bendY} ${endX} ${endY}`);
            endEl.setAttribute('cx', endX);
            endEl.setAttribute('cy', endY);
            endEl.style.display = '';
        }

        function showRoute() {
            const input = document.getElementById('route-destination');
            const typed = (input ? (input.value || '').trim().toLowerCase() : '');
            const target = panoramas.find(p => p.id.toLowerCase() === typed.replace(/\s+/g, '') || p.id.toLowerCase() === typed.replace('panel', 'imagen').replace(/\s+/g, '') || p.title.toLowerCase() === typed);
            const status = document.getElementById('route-status');
            if (!target) {
                routeTargetId = null; routePath = []; routeNextHotspot = null; updateRouteLine();
                if (status) status.textContent = 'No encontré esa imagen.';
                renderMiniMap();
                return;
            }
            routeTargetId = target.id;
            routePath = findRoute(currentPano.id, routeTargetId);
            if (!routePath.length) {
                routeNextHotspot = null;
                updateRouteLine();
                if (status) status.textContent = currentPano.id === routeTargetId ? 'Ya estás en esa imagen.' : 'No hay una ruta con las flechas actuales.';
                renderMiniMap();
                return;
            }
            routeNextHotspot = routePath[0];
            if (status) status.textContent = `Ruta: ${[currentPano.id, ...routePath.map(step => step.toId)].join(' → ')}`;
            renderHS();
            renderMiniMap();
        }

        document.onpointermove = (e) => {
            if (rotateDragging && selectedHS) {
                const dx = e.clientX - rotateStartX;
                const sensitivity = 0.6;
                selectedHS.rotate = (rotateStartRotate + dx * sensitivity) % 360;
                if (selectedHS.rotate < 0) selectedHS.rotate += 360;
                renderHS();
                syncMenu();
                markDirty();
                return;
            }

            if (draggingAlert) {
                const alert = (currentPano.alerts || []).find(item => item.id === draggingAlert.id);
                const element = draggingAlert.element;
                const container = document.getElementById('panorama-container');
                if (!alert || !element || !container) return;

                // Durante el arrastre solo movemos el elemento en pantalla.
                // Así el signo sigue al cursor con libertad, sin que Pannellum lo recoloque en cada frame.
                const rect = container.getBoundingClientRect();
                const x = Math.max(14, Math.min(rect.width - 14, e.clientX - rect.left));
                const y = Math.max(14, Math.min(rect.height - 14, e.clientY - rect.top));
                element.style.left = `${x}px`;
                element.style.top = `${y}px`;
                element.style.transform = 'translate(-50%, -50%)';
                draggingAlert.lastClientX = rect.left + x;
                draggingAlert.lastClientY = rect.top + y;
                // Convertimos la posición visual exacta del signo usando como target el visor.
                // Esto evita que Pannellum interprete el hotspot original como punto de soltado.
                if (viewer && viewer.mouseEventToCoords) {
                    const visualEvent = {
                        clientX: rect.left + x,
                        clientY: rect.top + y,
                        target: container,
                        currentTarget: container
                    };
                    const liveCoords = viewer.mouseEventToCoords(visualEvent);
                    if (liveCoords) {
                        draggingAlert.lastCoords = [Number(liveCoords[0]), Number(liveCoords[1])];
                        // Dejamos el modelo actualizado antes de reconstruir el hotspot.
                        alert.pitch = draggingAlert.lastCoords[0];
                        alert.yaw = draggingAlert.lastCoords[1];
                        alert.x = alert.yaw;
                        alert.y = alert.pitch;
                    }
                }
                if (!draggingAlert.moved) {
                    draggingAlert.moved = true;
                    suppressAlertClick = true;
                }
                return;
            }

            if (!isDragging || !draggingHS) return;
            const coords = viewer.mouseEventToCoords(e);
            if (!coords) return;
            draggingHS.pitch = coords[0];
            draggingHS.yaw = coords[1];
            renderHS();
            syncMenu();
            markDirty();
        };

        document.onpointerup = (e) => {
            if (draggingAlert) {
                const draggedAlert = draggingAlert;
                const alert = (currentPano.alerts || []).find(item => item.id === draggedAlert.id);
                // La última conversión tomada durante el movimiento es la posición real de soltado.
                // Usamos el evento final solo como respaldo, porque el hotspot puede desaparecer
                // temporalmente al liberar el puntero.
                const finalEvent = {
                    clientX: e.clientX ?? draggedAlert.lastClientX,
                    clientY: e.clientY ?? draggedAlert.lastClientY
                };
                if (alert && draggedAlert.moved) {
                    const coords = draggedAlert.lastCoords || (viewer && viewer.mouseEventToCoords ? viewer.mouseEventToCoords(finalEvent) : null);
                    if (coords) {
                        alert.pitch = Number(coords[0]);
                        alert.yaw = Number(coords[1]);
                        alert.x = alert.yaw;
                        alert.y = alert.pitch;
                        markDirty();
                    }
                }
                draggedAlert.element?.classList.remove('is-dragging');
                draggingAlert = null;
                if (draggedAlert.moved) {
                    suppressAlertClick = true;
                    renderAlerts();
                    window.setTimeout(() => { suppressAlertClick = false; }, 0);
                }
                return;
            }
            if(isDragging) { isDragging = false; draggingHS = null; }
            if(rotateDragging) { rotateDragging = false; }
        };

        function syncMenu() {
            if (selectedHS) {
                document.getElementById('select-target').value = selectedHS.targetId;
                document.getElementById('select-color').value = selectedHS.color;
                document.getElementById('range-rot').value = selectedHS.rotate;
                document.getElementById('range-tilt').value = selectedHS.tilt;
                document.getElementById('range-w').value = selectedHS.w;
                document.getElementById('range-h').value = selectedHS.h;
                
                document.getElementById('val-rot').textContent = selectedHS.rotate + "°";
                document.getElementById('val-tilt').textContent = selectedHS.tilt + "°";
                document.getElementById('val-w').textContent = selectedHS.w + "px";
                document.getElementById('val-h').textContent = selectedHS.h + "px";
            }
            const initialViewLabel = document.getElementById('initial-view-label');
            if (initialViewLabel) {
                initialViewLabel.textContent = `Adelante: ${Math.round(currentPano.forwardYaw)}° · Atrás: ${Math.round(currentPano.backwardYaw)}°`;
            }
        }

        document.getElementById('range-rot').oninput = (e) => { if(selectedHS) { selectedHS.rotate = Number(e.target.value); renderHS(); syncMenu(); markDirty(); } };
        document.getElementById('range-tilt').oninput = (e) => { if(selectedHS) { selectedHS.tilt = Number(e.target.value); renderHS(); syncMenu(); markDirty(); } };
        document.getElementById('range-w').oninput = (e) => { if(selectedHS) { selectedHS.w = Number(e.target.value); renderHS(); syncMenu(); markDirty(); } };
        document.getElementById('range-h').oninput = (e) => { if(selectedHS) { selectedHS.h = Number(e.target.value); renderHS(); syncMenu(); markDirty(); } };
        
        document.getElementById('btn-add').onclick = () => addHS(viewer.getPitch(), viewer.getYaw());
        document.getElementById('btn-set-forward-view').onclick = () => {
            currentPano.forwardYaw = viewer.getYaw();
            currentPano.forwardPitch = viewer.getPitch();
            syncMenu();
            markDirty();
        };
        document.getElementById('btn-set-backward-view').onclick = () => {
            currentPano.backwardYaw = viewer.getYaw();
            currentPano.backwardPitch = viewer.getPitch();
            syncMenu();
            markDirty();
        };
        document.getElementById('btn-del').onclick = () => { if(selectedHS) { currentPano.hotspots = currentPano.hotspots.filter(h => h !== selectedHS); selectedHS = null; renderHS(); markDirty(); } };
        document.getElementById('select-target').onchange = (e) => { if(selectedHS) { selectedHS.targetId = e.target.value; markDirty(); } };
        document.getElementById('select-color').onchange = (e) => { if(selectedHS) { selectedHS.color = e.target.value; renderHS(); markDirty(); } };
                function svgElement(name, attrs = {}) {
            const element = document.createElementNS('http://www.w3.org/2000/svg', name);
            Object.entries(attrs).forEach(([key, value]) => element.setAttribute(key, value));
            return element;
        }

        function mapPointFromEvent(event) {
            const layer = document.getElementById('mapa-google-layer');
            const rect = layer.getBoundingClientRect();
            let x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
            let y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100));
            if (document.getElementById('map-snap-grid')?.checked) { x = Math.round(x / 5) * 5; y = Math.round(y / 5) * 5; }
            return { x, y };
        }

        function renderMapGuide() {
            const content = document.getElementById('map-guide-content');
            const grid = document.getElementById('map-guide-grid');
            if (!content) return;
            content.innerHTML = '';
            if (grid) grid.setAttribute('visibility', document.getElementById('map-grid-visible')?.checked ? 'visible' : 'hidden');
            if (mapGuide.layers.paths) {
                mapGuide.paths.forEach(path => { const isStreet = path.shape !== 'line'; const element = svgElement('polyline', { points: path.points.map(p => `${p.x},${p.y}`).join(' '), fill: 'none', stroke: isStreet ? (path.color || '#ffffff') : (path.color || '#1677e8'), 'stroke-width': path.thickness || (isStreet ? 7 : 2.2), 'stroke-linecap': isStreet ? 'butt' : 'round', 'stroke-linejoin': isStreet ? 'miter' : 'round', 'data-kind': 'path' }); element.addEventListener('click', event => { event.stopPropagation(); selectMapElement({ kind: 'path', item: path }); }); content.appendChild(element); if (selectedMapElement?.kind === 'path' && selectedMapElement.item === path) path.points.forEach((point, index) => { const handle = svgElement('circle', { cx: point.x, cy: point.y, r: 1.8, fill: '#ffffff', stroke: '#1677e8', 'stroke-width': .8 }); handle.addEventListener('pointerdown', event => { event.stopPropagation(); draggingPathPoint = { path, index }; }); handle.addEventListener('click', event => event.stopPropagation()); content.appendChild(handle); }); });
                if (draftPath.length > 0) content.appendChild(svgElement('polyline', { points: draftPath.map(p => `${p.x},${p.y}`).join(' '), fill: 'none', stroke: '#ffffff', 'stroke-width': 7, 'stroke-linecap': 'butt', 'stroke-linejoin': 'miter', 'stroke-dasharray': '3 2' }));
            }
            if (mapGuide.layers.zones) mapGuide.zones.forEach(zone => {
                const shape = zone.shape === 'circle' ? svgElement('ellipse', { cx: zone.x, cy: zone.y, rx: zone.w / 2, ry: zone.h / 2, fill: zone.color || '#dc3545', 'fill-opacity': zone.opacity || '.35', stroke: zone.color || '#b42318', 'stroke-width': 1 }) : zone.shape === 'semicircle' ? svgElement('path', { d: `M ${zone.x-zone.w/2} ${zone.y+zone.h/2} A ${zone.w/2} ${zone.h/2} 0 0 1 ${zone.x+zone.w/2} ${zone.y+zone.h/2} L ${zone.x-zone.w/2} ${zone.y+zone.h/2} Z`, fill: zone.color || '#dc3545', 'fill-opacity': zone.opacity || '.35', stroke: zone.color || '#b42318', 'stroke-width': 1 }) : zone.shape === 'diamond' ? svgElement('polygon', { points: `${zone.x},${zone.y-zone.h/2} ${zone.x+zone.w/2},${zone.y} ${zone.x},${zone.y+zone.h/2} ${zone.x-zone.w/2},${zone.y}`, fill: zone.color || '#dc3545', 'fill-opacity': zone.opacity || '.35', stroke: zone.color || '#b42318', 'stroke-width': 1 }) : svgElement('rect', { x: zone.x - zone.w / 2, y: zone.y - zone.h / 2, width: zone.w, height: zone.h, rx: 1, fill: zone.color || '#dc3545', 'fill-opacity': zone.opacity || '.35', stroke: zone.color || '#b42318', 'stroke-width': 1 });
                shape.addEventListener('click', event => { event.stopPropagation(); selectMapElement({ kind: 'zone', item: zone }); }); content.appendChild(shape);
                const label = svgElement('text', { x: zone.x, y: zone.y, 'text-anchor': 'middle', 'font-size': 3.2, fill: '#7f1d1d', 'font-weight': 'bold' }); label.textContent = zone.name; content.appendChild(label);
            });
            if (mapGuide.layers.places) mapGuide.places.forEach(place => {
                const w = place.w || 7, h = place.h || 7;
                const shape = place.shape === 'rect' ? svgElement('rect', { x: place.x - w/2, y: place.y - h/2, width: w, height: h, fill: place.color || (place.type === 'Aula' ? '#2563c7' : '#f59e0b'), stroke: '#fff', 'stroke-width': .7 }) : place.shape === 'semicircle' ? svgElement('path', { d: `M ${place.x-w/2} ${place.y+h/2} A ${w/2} ${h/2} 0 0 1 ${place.x+w/2} ${place.y+h/2} L ${place.x-w/2} ${place.y+h/2} Z`, fill: place.color || (place.type === 'Aula' ? '#2563c7' : '#f59e0b'), stroke: '#fff', 'stroke-width': .7 }) : place.shape === 'diamond' ? svgElement('polygon', { points: `${place.x},${place.y-h/2} ${place.x+w/2},${place.y} ${place.x},${place.y+h/2} ${place.x-w/2},${place.y}`, fill: place.color || (place.type === 'Aula' ? '#2563c7' : '#f59e0b'), stroke: '#fff', 'stroke-width': .7 }) : svgElement('circle', { cx: place.x, cy: place.y, r: Math.min(w, h)/2, fill: place.color || (place.type === 'Aula' ? '#2563c7' : '#f59e0b'), stroke: '#fff', 'stroke-width': .7 });
                shape.addEventListener('click', event => { event.stopPropagation(); selectMapElement({ kind: 'place', item: place }); }); content.appendChild(shape);
                const label = svgElement('text', { x: place.x, y: place.y - h/2 - 1, 'text-anchor': 'middle', 'font-size': 3.2, fill: '#111827', 'font-weight': 'bold' }); label.textContent = place.name; content.appendChild(label);
            });
        }

        function saveMapGuide() {
            try { localStorage.setItem(MAP_GUIDE_KEY, JSON.stringify(mapGuide)); } catch (error) { console.warn('No se pudo guardar el mapa guía', error); }
        }

        function loadMapGuide() {
            try { const saved = JSON.parse(localStorage.getItem(MAP_GUIDE_KEY) || 'null'); if (saved) mapGuide = { ...mapGuide, ...saved, layers: { ...mapGuide.layers, ...(saved.layers || {}) } }; } catch (error) { console.warn('No se pudo cargar el mapa guía', error); }
            renderMapGuide();
        }

        function setMapTool(tool) {
            activeMapTool = activeMapTool === tool ? null : tool;
            document.querySelectorAll('#map-tool-path, #map-tool-place, #map-tool-zone').forEach(button => button.classList.remove('active'));
            if (activeMapTool) document.getElementById(`map-tool-${activeMapTool}`).classList.add('active');
            if (activeMapTool !== 'path' && draftPath.length) finishMapPath();
        }

        function finishMapPath() { if (draftPath.length >= 2) mapGuide.paths.push({ name: `Camino ${mapGuide.paths.length + 1}`, shape: 'street', points: [...draftPath], thickness: 7, lengthScale: 100 }); draftPath = []; renderMapGuide(); saveMapGuide(); }

        function handleMapGuideClick(event) {
            if (!isEdit || !activeMapTool) return;
            const point = mapPointFromEvent(event);
            if (activeMapTool === 'path') { if (event.shiftKey && draftPath.length) { const previous = draftPath[draftPath.length - 1]; if (Math.abs(point.x - previous.x) >= Math.abs(point.y - previous.y)) point.y = previous.y; else point.x = previous.x; } draftPath.push(point); renderMapGuide(); return; }
            if (activeMapTool === 'place') { const name = prompt('Nombre del aula o lugar:'); if (name) { const type = prompt('Escribe Aula o Lugar:', 'Aula') || 'Lugar';                 const shapeName = (prompt('Forma: círculo, semicírculo, rectángulo o rombo:', 'círculo') || 'círculo').toLowerCase(); const shape = shapeName.includes('semi') ? 'semicircle' : shapeName.includes('rect') ? 'rect' : shapeName.includes('rombo') ? 'diamond' : 'circle'; mapGuide.places.push({ name: name.trim(), type: /^aula$/i.test(type) ? 'Aula' : 'Lugar', shape, w: 7, h: 7, x: point.x, y: point.y });
 renderMapGuide(); saveMapGuide(); } }
            if (activeMapTool === 'zone') { const name = prompt('Nombre del área no transitable:', 'Área restringida'); if (name) {                 const shapeName = (prompt('Forma: rectángulo, círculo, semicírculo o rombo:', 'rectángulo') || 'rectángulo').toLowerCase(); const shape = shapeName.includes('semi') ? 'semicircle' : shapeName.includes('cir') ? 'circle' : shapeName.includes('rombo') ? 'diamond' : 'rect'; mapGuide.zones.push({ name: name.trim(), shape, x: point.x, y: point.y, w: 18, h: 12 });
 renderMapGuide(); saveMapGuide(); } }
        }

        document.getElementById('map-tool-path').onclick = () => setMapTool('path');
        document.getElementById('map-tool-place').onclick = () => setMapTool('place');
        document.getElementById('map-tool-zone').onclick = () => setMapTool('zone');
        document.getElementById('map-finish-path').onclick = finishMapPath;
        document.getElementById('map-clear-guide').onclick = () => { if (confirm('¿Deseas borrar todos los caminos, lugares y áreas del mapa?')) { mapGuide.paths = []; mapGuide.places = []; mapGuide.zones = []; draftPath = []; renderMapGuide(); saveMapGuide(); } };
        
        function renderMinimapElementsList() {
            const list = document.getElementById('minimap-elements-list');
            const editor = document.getElementById('advance-point-editor');
            if (!list) return;
            list.innerHTML = '';
            
            if (minimapIndicator && minimapIndicator.x !== undefined && minimapIndicator.y !== undefined) {
                const item = document.createElement('div');
                const isSelected = selectedMinimapElement?.type === 'indicator';
                item.style.cssText = 'padding: 8px; background: rgba(56,189,248,0.2); border-radius: 4px; margin-bottom: 6px; cursor: pointer; border: 2px solid ' + (isSelected ? '#38bdf8' : 'transparent') + '; display: flex; justify-content: space-between; align-items: center;';
                item.innerHTML = '<span>📍 Indicador (' + minimapIndicator.x.toFixed(1) + ', ' + minimapIndicator.y.toFixed(1) + ')</span><button type="button" style="background: #dc3545; color: white; border: 0; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px;">Eliminar</button>';
                item.onclick = (e) => { if (e.target.tagName !== 'BUTTON') { selectedMinimapElement = { type: 'indicator' }; if (editor) editor.style.display = 'none'; renderMinimapElementsList(); } };
                item.querySelector('button').onclick = (e) => { e.stopPropagation(); minimapIndicator = null; saveMiniMapState(); renderMinimapElementsList(); renderMiniMap(); };
                list.appendChild(item);
            }
            
            minimapAdvancePoints.forEach((point, idx) => {
                const item = document.createElement('div');
                const isSelected = selectedMinimapElement?.type === 'advance' && selectedMinimapElement.index === idx;
                item.style.cssText = 'padding: 8px; background: rgba(245,158,11,0.2); border-radius: 4px; margin-bottom: 6px; cursor: pointer; border: 2px solid ' + (isSelected ? '#f59e0b' : 'transparent') + '; display: flex; justify-content: space-between; align-items: center;';
                const panoLabel = point.panoId ? ' [' + point.panoId + ']' : ' [sin panel]';
                item.innerHTML = '<span>🎯 Punto ' + (idx + 1) + panoLabel + '</span><button type="button" style="background: #dc3545; color: white; border: 0; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 11px;">Eliminar</button>';
                item.onclick = (e) => { 
                    if (e.target.tagName !== 'BUTTON') { 
                        selectedMinimapElement = { type: 'advance', index: idx }; 
                        renderMinimapElementsList(); 
                        showAdvancePointEditor(point, idx);
                    } 
                };
                item.querySelector('button').onclick = (e) => { e.stopPropagation(); minimapAdvancePoints.splice(idx, 1); saveMiniMapState(); selectedMinimapElement = null; renderMinimapElementsList(); renderMiniMap(); };
                list.appendChild(item);
            });
            
            if (!minimapIndicator && minimapAdvancePoints.length === 0) {
                list.innerHTML = '<p style="color: #888; font-size: 12px; margin: 0;">Sin elementos. Haz clic en "Indicador" o "Punto avance".</p>';
            }
            
            if (editor && selectedMinimapElement?.type !== 'advance') {
                editor.style.display = 'none';
            }
        }
        
        function showAdvancePointEditor(point, idx) {
            const editor = document.getElementById('advance-point-editor');
            const panoSelect = document.getElementById('advance-point-panoId');
            const xInput = document.getElementById('advance-point-x');
            const yInput = document.getElementById('advance-point-y');
            const saveBtn = document.getElementById('advance-point-save');
            
            if (!editor || !panoSelect || !xInput || !yInput || !saveBtn) return;
            
            editor.style.display = 'block';
            panoSelect.value = point.panoId || '';
            xInput.value = point.x || 0;
            yInput.value = point.y || 0;
            
            saveBtn.onclick = () => {
                point.panoId = panoSelect.value || 'imagen1';
                point.x = clampNumber(Number(xInput.value) || 0, 0, 100);
                point.y = clampNumber(Number(yInput.value) || 0, 0, 100);
                saveMiniMapState();
                renderMinimapElementsList();
                renderMiniMap();
                updateMinimap();
            };
        }
        
        document.getElementById('map-add-control-point').onclick = () => {
            minimapPlacementMode = minimapPlacementMode === 'indicator' ? null : 'indicator';
            document.getElementById('map-add-control-point').classList.toggle('active', minimapPlacementMode === 'indicator');
            document.getElementById('map-add-control-point').textContent = minimapPlacementMode === 'indicator' ? '✓ Clic en el mapa' : '➕ Indicador';
        };
        
        document.getElementById('map-add-advance-point').onclick = () => {
            minimapPlacementMode = minimapPlacementMode === 'advance' ? null : 'advance';
            document.getElementById('map-add-advance-point').classList.toggle('active', minimapPlacementMode === 'advance');
            document.getElementById('map-add-advance-point').textContent = minimapPlacementMode === 'advance' ? '✓ Clic en el mapa' : '➕ Punto avance';
        };
        document.getElementById('mapa-google-layer').addEventListener('click', handleMapGuideClick);
        document.getElementById('mapa-google-layer').addEventListener('dblclick', event => { event.preventDefault(); if (activeMapTool === 'path') finishMapPath(); });
        ['paths', 'places', 'zones'].forEach(layer => document.getElementById(`layer-${layer}`).addEventListener('change', event => { mapGuide.layers[layer] = event.target.checked; renderMapGuide(); saveMapGuide(); }));
        document.getElementById('map-grid-visible').addEventListener('change', renderMapGuide);
        document.getElementById('map-snap-grid').addEventListener('change', renderMapGuide);

        function applyMapSettings() {
            const mapa = document.getElementById('mapa-google-overlay');
            if (!mapa) return;
            const wrapper = document.getElementById('mapa-google-wrapper');
            mapa.style.width = '100%';
            mapa.style.left = 'auto';
            mapa.style.bottom = 'auto';
            if (wrapper && !wrapper.closest('#map-guide-modal')) { wrapper.style.width = `${mapSettings.width}px`; wrapper.style.left = `${mapSettings.left}px`; wrapper.style.bottom = `${mapSettings.bottom}px`; } else if (wrapper) { wrapper.style.width = '100%'; wrapper.style.left = 'auto'; wrapper.style.bottom = 'auto'; }
            mapa.style.opacity = String(mapSettings.opacity / 100);
            mapa.style.filter = `grayscale(${mapSettings.grayscale}%)`;
            const controls = [
                ['map-width', 'map-width-value', mapSettings.width, 'px'],
                ['map-opacity', 'map-opacity-value', mapSettings.opacity, '%'],
                ['map-grayscale', 'map-grayscale-value', mapSettings.grayscale, '%'],
                ['map-left', 'map-left-value', mapSettings.left, 'px'],
                ['map-bottom', 'map-bottom-value', mapSettings.bottom, 'px']
            ];
            controls.forEach(([inputId, valueId, value, unit]) => {
                const input = document.getElementById(inputId);
                const output = document.getElementById(valueId);
                if (input) input.value = value;
                if (output) output.textContent = `${value}${unit}`;
            });
        }

        function loadMapSettings() {
            try {
                const saved = JSON.parse(localStorage.getItem(MAP_SETTINGS_KEY) || 'null');
                if (saved && typeof saved === 'object') mapSettings = { ...DEFAULT_MAP_SETTINGS, ...saved };
            } catch (error) {
                console.warn('No se pudieron cargar los ajustes del mapa', error);
            }
            applyMapSettings();
        }

        function saveMapSettings() {
            try {
                localStorage.setItem(MAP_SETTINGS_KEY, JSON.stringify(mapSettings));
                saveMapGuide();
                updateSaveStatus('Mapa y vistas guardados localmente', false);
            } catch (error) {
                console.warn('No se pudieron guardar los ajustes del mapa', error);
                updateSaveStatus('No se pudo guardar el mapa', false);
            }
        }

        function selectMapElement(selection) {
            selectedMapElement = selection;
            const editor = document.getElementById('map-element-editor');
            const item = selection.item;
            editor.classList.add('visible');
            document.getElementById('map-element-name').value = item.name || '';
            document.getElementById('map-element-color').value = item.color || (selection.kind === 'zone' ? '#dc3545' : selection.kind === 'path' ? '#ffffff' : '#2563c7');
            document.getElementById('map-element-shape').value = item.shape || 'rect';
            document.getElementById('map-element-width').value = item.w || item.thickness || 7;
            document.getElementById('map-element-length').value = item.lengthScale || 100;
            document.getElementById('map-element-height').value = item.h || 8;
            document.getElementById('map-element-width-value').textContent = document.getElementById('map-element-width').value;
            document.getElementById('map-element-length-value').textContent = `${document.getElementById('map-element-length').value}%`;
            document.getElementById('map-element-height-value').textContent = document.getElementById('map-element-height').value;
        }

        function closeMapGuideModal() {
            finishMapPath();
            activeMapTool = null;
            selectedMapElement = null;
            selectedMinimapElement = null;
            document.getElementById('map-guide-modal').classList.remove('open');
            const wrapper = document.getElementById('mapa-google-wrapper');
            if (mapOriginalParent && wrapper) mapOriginalParent.appendChild(wrapper);
            mapOriginalParent = null;
            updateMapPanelState(false);
            applyMapSettings();
        }

        function updateSelectedMapElement() {
            if (!selectedMapElement) return;
            const item = selectedMapElement.item;
            item.name = document.getElementById('map-element-name').value.trim() || item.name;
            item.color = document.getElementById('map-element-color').value;
            item.shape = document.getElementById('map-element-shape').value;
            if (selectedMapElement.kind === 'path') {
                item.thickness = Number(document.getElementById('map-element-width').value);
                const nextScale = Number(document.getElementById('map-element-length').value);
                const previousScale = item.lengthScale || 100;
                if (nextScale !== previousScale && item.points.length) { const origin = item.points[0]; const factor = nextScale / previousScale; item.points = item.points.map((point, index) => index === 0 ? point : { x: origin.x + (point.x - origin.x) * factor, y: origin.y + (point.y - origin.y) * factor }); }
                item.lengthScale = nextScale;
            } else { item.w = Number(document.getElementById('map-element-width').value); item.h = Number(document.getElementById('map-element-height').value); }
            document.getElementById('map-element-width-value').textContent = document.getElementById('map-element-width').value;
            document.getElementById('map-element-length-value').textContent = `${document.getElementById('map-element-length').value}%`;
            document.getElementById('map-element-height-value').textContent = document.getElementById('map-element-height').value;
            renderMapGuide();
            saveMapGuide();
        }

        function updateMapPanelState(isOpen) {
            const mapa = document.getElementById('mapa-google-overlay');
            const panel = document.getElementById('map-editor-section');
            const layer = document.getElementById('mapa-google-layer');
            const wrapper = document.getElementById('mapa-google-wrapper');
            if (mapa) mapa.classList.toggle('map-editing', Boolean(isOpen));
            if (wrapper) wrapper.classList.toggle('map-editing', Boolean(isOpen));
            if (layer) layer.classList.toggle('editing', Boolean(isOpen));
            if (panel) panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            renderMapGuide();
        }

        function openMapEditor(event) {
            if (event) event.stopPropagation();
            isEdit = true;
            document.body.classList.replace('view-mode', 'edit-mode');
            document.getElementById('btn-mode-view')?.classList.remove('active');
            document.getElementById('btn-mode-edit')?.classList.add('active');
            const modal = document.getElementById('map-guide-modal');
            const wrapper = document.getElementById('mapa-google-wrapper');
            const stage = document.getElementById('map-guide-modal-stage');
            if (wrapper && stage && !mapOriginalParent) { mapOriginalParent = wrapper.parentElement; stage.appendChild(wrapper); }
            modal.classList.add('open');
            updateMapPanelState(true);
            applyMapSettings();
            renderMinimapElementsList();
        }

        document.getElementById('mapa-google-overlay').addEventListener('click', openMapEditor);
        document.getElementById('map-guide-modal-close').onclick = closeMapGuideModal;
        document.getElementById('map-guide-modal-save').onclick = () => { finishMapPath(); saveMapSettings(); saveMapGuide(); updateSaveStatus('Mapa guía guardado', false); };
        document.getElementById('map-guide-modal').addEventListener('click', event => { if (event.target.id === 'map-guide-modal') closeMapGuideModal(); });
        document.getElementById('btn-close-map-editor').onclick = closeMapGuideModal;
        document.getElementById('btn-open-label-form').onclick = openLabelForm;
        document.getElementById('btn-add-alert').onclick = prepareAlertForm;
        document.getElementById('btn-close-label-form').onclick = closeLabelForm;
        document.getElementById('btn-add-label').onclick = addLabelFromForm;
        document.getElementById('label-size').oninput = (e) => {
            const sizeValue = document.getElementById('label-size-value');
            if (sizeValue) sizeValue.textContent = `${e.target.value} px`;
            if (selectedLabel) {
                selectedLabel.fontSize = Number(e.target.value) || 12;
                renderLabels();
                markDirty();
            }
        };
        document.getElementById('label-color').oninput = (e) => {
            if (selectedLabel) {
                selectedLabel.color = e.target.value;
                renderLabels();
                markDirty();
            }
        };
        document.getElementById('label-form-header').onpointerdown = (event) => {
            if (event.target.closest('button')) return;
            const panel = document.getElementById('label-form-panel');
            if (!panel) return;
            const rect = panel.getBoundingClientRect();
            const startX = event.clientX;
            const startY = event.clientY;
            const startLeft = rect.left;
            const startTop = rect.top;

            function move(e) {
                const moveX = e.clientX - startX;
                const moveY = e.clientY - startY;
                const wrapper = document.getElementById('viewer-wrapper');
                const wrapperRect = wrapper.getBoundingClientRect();
                const nextLeft = clampNumber(startLeft + moveX - wrapperRect.left, 10, wrapperRect.width - 260);
                const nextTop = clampNumber(startTop + moveY - wrapperRect.top, 10, wrapperRect.height - 120);
                panel.style.left = `${nextLeft}px`;
                panel.style.top = `${nextTop}px`;
            }

            function stop() {
                document.removeEventListener('pointermove', move);
                document.removeEventListener('pointerup', stop);
            }

            document.addEventListener('pointermove', move);
            document.addEventListener('pointerup', stop);
        };
        document.getElementById('map-element-name').oninput = updateSelectedMapElement;
        document.getElementById('map-element-color').oninput = updateSelectedMapElement;
        document.getElementById('map-element-shape').onchange = updateSelectedMapElement;
        document.getElementById('map-element-width').oninput = updateSelectedMapElement;
        document.getElementById('map-element-length').oninput = updateSelectedMapElement;
        document.getElementById('map-element-height').oninput = updateSelectedMapElement;
        window.addEventListener('pointermove', event => { if (!draggingPathPoint) return; const point = mapPointFromEvent(event); const path = draggingPathPoint.path; const index = draggingPathPoint.index; if (event.shiftKey && index > 0) { const previous = path.points[index - 1]; if (Math.abs(point.x - previous.x) >= Math.abs(point.y - previous.y)) point.y = previous.y; else point.x = previous.x; } path.points[index] = point; renderMapGuide(); });
        window.addEventListener('pointerup', () => { if (draggingPathPoint) { saveMapGuide(); draggingPathPoint = null; } });
        function mapElementCollection(kind) { return kind === 'path' ? 'paths' : kind === 'place' ? 'places' : 'zones'; }
        document.getElementById('map-element-copy').onclick = () => { if (!selectedMapElement) return; mapClipboard = { kind: selectedMapElement.kind, item: JSON.parse(JSON.stringify(selectedMapElement.item)) }; updateSaveStatus('Elemento copiado', false); };
        document.getElementById('map-element-paste').onclick = () => { if (!mapClipboard) { updateSaveStatus('Primero copia un elemento', false); return; } const copy = JSON.parse(JSON.stringify(mapClipboard.item)); if (mapClipboard.kind === 'path') copy.points = copy.points.map(point => ({ x: Math.min(98, point.x + 5), y: Math.min(98, point.y + 5) })); else { copy.x = Math.min(95, copy.x + 5); copy.y = Math.min(95, copy.y + 5); } copy.name = `${copy.name || 'Elemento'} copia`; const collection = mapElementCollection(mapClipboard.kind); mapGuide[collection].push(copy); selectedMapElement = { kind: mapClipboard.kind, item: copy }; renderMapGuide(); selectMapElement(selectedMapElement); saveMapGuide(); updateSaveStatus('Elemento pegado delante de la copia original', false); };
        function reorderSelectedElement(direction) { if (!selectedMapElement) return; const collection = mapElementCollection(selectedMapElement.kind); const items = mapGuide[collection]; const index = items.indexOf(selectedMapElement.item); const target = direction === 'front' ? items.length - 1 : 0; items.splice(index, 1); items.splice(target, 0, selectedMapElement.item); renderMapGuide(); saveMapGuide(); }
        document.getElementById('map-element-front').onclick = () => reorderSelectedElement('front');
        document.getElementById('map-element-back').onclick = () => reorderSelectedElement('back');
        document.getElementById('map-element-delete').onclick = () => { if (!selectedMapElement) return; const collection = mapElementCollection(selectedMapElement.kind); mapGuide[collection] = mapGuide[collection].filter(item => item !== selectedMapElement.item); selectedMapElement = null; document.getElementById('map-element-editor').classList.remove('visible'); renderMapGuide(); saveMapGuide(); };
        [
            ['map-opacity', 'opacity', 'map-opacity-value', '%'],
            ['map-grayscale', 'grayscale', 'map-grayscale-value', '%'],
        ].forEach(([inputId, setting, outputId, unit]) => {
            document.getElementById(inputId).addEventListener('input', (event) => {
                mapSettings[setting] = Number(event.target.value);
                document.getElementById(outputId).textContent = `${mapSettings[setting]}${unit}`;
                applyMapSettings();
                updateMapPanelState(true);
            });
        });

        document.getElementById('btn-mode-view').onclick = () => {

            isEdit = false;
            document.body.classList.replace('edit-mode', 'view-mode');
            updateMapPanelState(false);
            renderHS();
            renderMiniMap();
        };

        window.addEventListener('pointermove', (event) => {
            if (!labelDragState) return;
            const viewerWrap = document.getElementById('viewer-wrapper');
            if (!viewerWrap) return;
            const label = (currentPano.labels || []).find(item => item.id === labelDragState.id);
            if (!label) return;
            const wrapperRect = viewerWrap.getBoundingClientRect();
            const x = clampNumber(((event.clientX - wrapperRect.left) / wrapperRect.width) * 100, 0, 100);
            const y = clampNumber(((event.clientY - wrapperRect.top) / wrapperRect.height) * 100, 0, 100);
            label.x = x;
            label.y = y;
            renderLabels();
        });

        window.addEventListener('pointerup', () => {
            if (labelDragState) {
                markDirty();
                labelDragState = null;
            }
        });
        document.getElementById('btn-mode-edit').onclick = () => {
            isEdit = true;
            document.body.classList.replace('view-mode', 'edit-mode');
            updateMapPanelState(true);
            renderHS();
            renderMiniMap();
        };
        document.getElementById('btn-show-route').onclick = showRoute;
        document.getElementById('route-destination').onkeydown = (e) => { if (e.key === 'Enter') showRoute(); };
        document.getElementById('btn-place-indicator').onclick = () => setMiniMapPlacementMode('indicator');
        document.getElementById('btn-place-advance').onclick = () => setMiniMapPlacementMode('advance');
        document.getElementById('mapa-google-wrapper').addEventListener('click', placeMiniMapPoint);
        document.getElementById('allow-hotspot-move').onchange = (e) => {
            allowHotspotMove = e.target.checked;
        };

        function buildExportCopy() {
            const copy = JSON.parse(JSON.stringify(panoramas));
            copy.forEach(p => {
                p.initialYaw = Number(p.initialYaw) || 0;
                p.initialPitch = Number(p.initialPitch) || 0;
                p.forwardYaw = Number(p.forwardYaw) || 0;
                p.forwardPitch = Number(p.forwardPitch) || 0;
                p.backwardYaw = Number(p.backwardYaw) || 0;
                p.backwardPitch = Number(p.backwardPitch) || 0;
                p.labels = Array.isArray(p.labels) ? p.labels.map(label => ({
                    id: label.id,
                    text: label.text || '',
                    profesor: label.profesor || '',
                    grado: label.grado || '',
                    salon: label.salon || '',
                    dia: label.dia || '',
                    hora: label.hora || '',
                    horaInicio: label.horaInicio || '',
                    horaFin: label.horaFin || '',
                    titulo: label.titulo || '',
                    descripcion: label.descripcion || '',
                    x: Number(label.x) || 50,
                    y: Number(label.y) || 50,
                    color: label.color || '#ffffff',
                    bg: label.bg || 'rgba(15, 23, 42, 0.75)',
                    fontSize: Number(label.fontSize) || 12
                })) : [];
                p.alerts = Array.isArray(p.alerts) ? p.alerts.map(alert => ({
                    id: alert.id,
                    title: alert.title || 'Aviso',
                    description: alert.description || '',
                    profesor: alert.profesor || '',
                    grado: alert.grado || '',
                    salon: alert.salon || '',
                    dia: alert.dia || '',
                    horaInicio: alert.horaInicio || '',
                    horaFin: alert.horaFin || '',
                    titulo: alert.titulo || '',
                    descripcion: alert.descripcion || '',
                    pitch: Number(alert.pitch) || 0,
                    yaw: Number(alert.yaw) || 0,
                    x: Number(alert.x) || 50,
                    y: Number(alert.y) || 50,
                    color: alert.color || '#fbbf24',
                    bg: alert.bg || 'rgba(101, 35, 18, 0.85)'
                })) : [];
                if (p.hotspots && p.hotspots.length) {
                    p.hotspots = p.hotspots.map(h => ({
                        pitch: h.pitch,
                        yaw: h.yaw,
                        sourceImage: p.id,
                        targetId: h.targetId || h.target || h.targetImage || null,
                        color: h.color,
                        w: h.w,
                        h: h.h,
                        rotate: h.rotate,
                        tilt: h.tilt
                    }));
                }
            });
            return copy;
        }

        function downloadJSON(obj, filename = 'colegio_santander_export.json') {
            const blob = new Blob([JSON.stringify(obj, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
        }

        function savePanoramas(options = {}) {
            const { showConfirm = true, silent = false } = options;
            const copy = buildExportCopy();
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(panoramas));
                localStorage.setItem(PENDING_STORAGE_KEY, JSON.stringify(panoramas));
            } catch (e) {
                console.warn('No se pudo guardar localmente', e);
            }

            if (!silent) updateSaveStatus('Guardando...', true);

            if (!silent) updateSaveStatus('Vistas guardadas localmente', false);

            const saveToServer = () => {
                fetch('/', { method: 'GET' }).then(resp => {
                    if (!resp.ok) throw new Error('Servidor no disponible');
                    if (showConfirm && !confirm('Esto sobrescribirá data/panoramas.json en el proyecto. ¿Deseas continuar?')) {
                        updateSaveStatus('Guardado local', false);
                        return;
                    }
                    fetch('/save', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(copy)
                    }).then(res => res.json()).then(obj => {
                        if (obj && obj.ok) {
                            localStorage.removeItem(PENDING_STORAGE_KEY);
                            updateSaveStatus('Guardado en servidor', false);
                            if (!silent) alert('Guardado en servidor: ' + obj.path + '\n(Se creó una copia de seguridad si ya existía)');
                        } else {
                            throw new Error(obj && obj.error ? obj.error : 'Error al guardar');
                        }
                    }).catch(err => {
                        console.error(err);
                        updateSaveStatus('Guardado local', false);
                        if (!silent) {
                            if (confirm('No fue posible guardar en el servidor. ¿Deseas descargar el JSON en su lugar?')) {
                                downloadJSON(copy);
                                alert('Se descargó colegio_santander_export.json. Puedes copiarlo sobre data/colegio_santander.json o iniciar el servidor local para guardarlo automáticamente.');
                            }
                        }
                    });
                }).catch(() => {
                    updateSaveStatus('Guardado local', false);
                    if (!silent && showConfirm && confirm('Servidor de guardado no está disponible. ¿Deseas descargar el JSON en su lugar?')) {
                        downloadJSON(copy);
                        alert('Se descargó panoramas_export.json. Puedes copiarlo sobre data/panoramas.json o iniciar el servidor local para guardarlo automáticamente.');
                    }
                });
            };

            if (showConfirm) {
                saveToServer();
            } else {
                saveToServer();
            }
        }

        document.getElementById('btn-export').onclick = () => {
            saveMapSettings();
            saveMapGuide();
            savePanoramas({ showConfirm: true, silent: false });
        };

        const labelSource = document.getElementById('label-source');
        function updateSourceLabel() {
            if (labelSource) labelSource.textContent = currentPano.title || currentPano.id;
            syncMenu();
        }

                loadMapSettings();
        loadMapGuide();
        loadMiniMapState();
        loadPanoramas().then(() => {
            if (!Array.isArray(panoramas) || !panoramas.length) {
                updateSaveStatus('No hay datos del colegio', false);
                return;
            }

            document.getElementById('select-target').innerHTML = panoramas
                .filter(p => /^imagen(?:[1-9]|1[0-3])$/.test(p.id))
                .sort((a, b) => Number(a.id.replace('imagen', '')) - Number(b.id.replace('imagen', '')))
                .map(p => `<option value="${p.id}">Panel ${p.id.replace('imagen', '')}</option>`)
                .join('');
            document.getElementById('route-images').innerHTML = panoramas
                .filter(p => /^imagen\d+$/.test(p.id))
                .sort((a, b) => Number(a.id.replace('imagen', '')) - Number(b.id.replace('imagen', '')))
                .map(p => `<option value="${p.id.replace('imagen', 'imagen ')}">${p.title}</option>`)
                .join('');
            currentPano = panoramas.find(p => p && currentPano && p.id === currentPano.id) || panoramas[0];
            if (!currentPano) {
                updateSaveStatus('No hay panoramas válidos', false);
                return;
            }
            currentPano.labels = currentPano.labels || [];
            currentPano.alerts = currentPano.alerts || [];
            updateSaveStatus('Guardado local', false);
            setupLeafletMiniMap();
            init();
            renderLabels();
            renderAlerts();
            updateSourceLabel();
            updateLeafletMiniMap();

        });
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="integracion-formulario.js"></script>
</body>
</html>

