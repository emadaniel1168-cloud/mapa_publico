<<!DOCTYPE html>
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
        #panorama-loading {
            position: absolute; inset: 0; z-index: 80; display: none; align-items: center; justify-content: center;
            background:
                linear-gradient(rgba(17, 24, 39, 0.35), rgba(17, 24, 39, 0.5)),
                url('imagenes_del_colegio/pantalla_de_carga.jpeg') center center / cover no-repeat;
            color: #fff; font-weight: 700; pointer-events: none;
            overflow: hidden;
        }
        #panorama-loading.visible { display: flex; }
        #panorama-loading::before {
            content: "";
            position: absolute;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.28);
            animation: panorama-loader-spin 1s linear infinite;
            box-shadow: 0 0 18px rgba(255, 255, 255, 0.2);
        }
        #panorama-loading span {
            position: relative;
            z-index: 1;
            margin-top: 92px;
            font-size: 1.1rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            text-shadow: 0 3px 12px rgba(0, 0, 0, 0.5);
        }
        @keyframes panorama-loader-spin {
            to { transform: rotate(360deg); }
        }
        /* Mapa guía fijo en la esquina inferior izquierda del visor */
        #mapa-google-wrapper {
            position: absolute;
            left: 16px;
            bottom: 16px;
            z-index: 30;
            width: clamp(180px, 24vw, 300px);
            max-height: 30vh;
            aspect-ratio: 16 / 10;
            overflow: hidden;
            background: #777;
        }
        #mapa-google-surface { position: absolute; inset: 0; width: 100%; height: 100%; transform-origin: 0 0; }
        #mapa-google-surface.map-cropped { width: 200%; height: 200%; }
        #mapa-google-overlay {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: fill;
            object-position: center center;
            filter: contrast(1.18);
            opacity: 1;
            border: 2px solid rgba(255, 255, 255, 0.75);
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.55);
            pointer-events: auto;
            cursor: pointer;
            display: block;
        }
            const targetDirection = resolveKeyboardTravelDirection(direction);
            const chosenMatch = availableByDirection.get(targetDirection);
        #mapa-google-overlay:hover { opacity: 1; transform: scale(1.015); }
        #mapa-google-overlay.map-editing:hover { transform: none; }
        #mapa-google-wrapper.map-editing { outline: 3px solid var(--primary); border-radius: 8px; }
        #mapa-google-overlay.map-editing { outline: 3px solid var(--primary); }
        #map-guide-modal { display: none; position: fixed; inset: 0; z-index: 3000; background: rgba(8, 12, 20, .82); padding: 3vh 3vw; }
        #map-guide-modal.open { display: flex; flex-direction: column; }
        #schedule-alert-overlay { display: none; position: fixed; inset: 0; z-index: 5000; align-items: center; justify-content: center; padding: 24px; background: rgba(3, 7, 18, .9); backdrop-filter: blur(8px); }
        #schedule-alert-overlay.open { display: flex; }
        .schedule-alert-panel { width: min(680px, 100%); padding: 36px; border: 2px solid #f59e0b; border-radius: 16px; background: #111827; color: #fff; text-align: center; box-shadow: 0 24px 80px rgba(0, 0, 0, .55); }
        .schedule-alert-icon { display: grid; place-items: center; width: 76px; height: 76px; margin: 0 auto 20px; border: 4px solid #fbbf24; border-radius: 50%; color: #fbbf24; font-size: 48px; font-weight: 900; line-height: 1; }
        .schedule-alert-panel h2 { margin-bottom: 14px; font-size: 28px; line-height: 1.2; }
        .schedule-alert-panel p { margin-bottom: 28px; color: #e5e7eb; font-size: 20px; line-height: 1.5; overflow-wrap: anywhere; }
        #schedule-alert-close { min-width: 180px; min-height: 48px; padding: 10px 24px; border: 0; border-radius: 8px; background: #f59e0b; color: #111827; font-size: 16px; font-weight: 800; cursor: pointer; }
        #schedule-alert-close:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
        @media (max-width: 600px) { .schedule-alert-panel { padding: 26px 20px; } .schedule-alert-panel h2 { font-size: 23px; } .schedule-alert-panel p { font-size: 18px; } }
        #map-guide-modal-header { display: flex; align-items: center; justify-content: space-between; background: #1f2937; padding: 12px 16px; border-radius: 10px 10px 0 0; }
        #map-guide-modal-header h2 { font-size: 20px; }
        #map-guide-modal-close { background: #dc3545; color: white; border: 0; border-radius: 5px; padding: 8px 14px; cursor: pointer; }
        #map-guide-modal-body { display: flex; flex: 1; min-height: 0; background: #111827; border-radius: 0 0 10px 10px; overflow: hidden; }
        #map-guide-modal-stage { flex: 1; position: relative; min-width: 0; display: flex; align-items: center; justify-content: center; padding: 18px; }
        #map-guide-modal-stage #mapa-google-wrapper { position: relative; inset: auto; width: 100%; height: 100%; max-height: none; aspect-ratio: auto; }
        #map-guide-modal-stage #mapa-google-surface { width: 100%; height: 100%; transform: none !important; }
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

        #route-line { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; z-index: 20; overflow: visible; display: none !important; }
        #route-line { z-index: 10; }
        #route-line path { fill: none; stroke: #38bdf8; stroke-width: 5; stroke-linecap: round; stroke-linejoin: round; filter: drop-shadow(0 0 3px rgba(56,189,248,0.9)); }
        #route-line circle { fill: #38bdf8; stroke: #fff; stroke-width: 3; }
        #btn-show-route { background: #2563eb !important; border-color: #60a5fa !important; }
        #route-status { color: #93c5fd !important; }
        .label-form-panel {
            position: absolute; left: 24px; top: 24px; width: min(320px, calc(100% - 48px)); max-height: calc(100% - 48px); z-index: 60;
            background: rgba(17, 24, 39, 0.92); border: 1px solid rgba(255,255,255,0.12);
            border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,0.35); backdrop-filter: blur(8px); overflow: hidden; display: flex; flex-direction: column;
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
        .label-form-body { padding: 12px; display: grid; gap: 8px; flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; }
        .label-form-body input, .label-form-body button, .label-form-body select, .label-form-body textarea { width: 100%; }
        .label-form-body input[type="text"], .label-form-body input[type="number"], .label-form-body input[type="time"], .label-form-body select, .label-form-body textarea { padding: 8px 10px; border-radius: 8px; border: 1px solid #3b465f; background: rgba(8, 15, 28, 0.9); color: #fff; }
        .label-form-body input[type="color"] { height: 42px; padding: 3px; border-radius: 8px; border: 1px solid #3b465f; background: rgba(8, 15, 28, 0.9); }
        .label-form-body button { border: none; border-radius: 8px; padding: 9px 10px; background: #2d7ff9; color: white; font-weight: 700; cursor: pointer; }
        .alert-form-selector { margin-bottom: 2px; }
        .alert-form-selector label { display: block; font-size: 11px; color: #d3e1f4; }
        .alert-form-selector select { margin-top: 4px; }
        .alert-form-simple .alert-form-selector { display: none !important; }
        .alert-full-fields { display: grid; gap: 8px; }
        .alert-form-simple .alert-full-fields { display: none !important; }
        .alert-form-simple .form-simple-hide { display: none !important; }
        .form-simple-only { display: none; }
        .alert-form-simple .form-simple-only { display: block; }
        .view-form-mode .form-simple-only { display: none !important; }
        .sugerencias-profesores { display: grid; gap: 5px; margin-top: 6px; }
        .view-form-mode .sugerencias-profesores { display: none !important; }
        .sugerencia-profesor { width: 100%; text-align: left; padding: 7px 8px; border-radius: 6px; border: 1px solid #334155; background: rgba(15, 23, 42, 0.88); color: white; cursor: pointer; }
        .label-position-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .info-position-controls { display: none; }
        .circle-form-mode .circle-form-hide { display: none !important; }
        .view-form-mode .view-form-hide { display: none !important; }
        .class-data-mode .class-data-hide:not(#btn-add-label) { display: none !important; }
        .no-class-mode .no-class-message { display: flex !important; }
        .no-class-mode .label-form-body > *:not(.no-class-message) { display: none !important; }
        .no-class-message {
            display: none;
            min-height: 120px;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px 16px;
            border-radius: 10px;
            font-size: clamp(18px, 2.2vw, 28px);
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: 0.04em;
            color: #ffffff;
            background: rgba(12, 18, 32, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        .label-position-grid label { display: block; font-size: 11px; color: #d3e1f4; }
        .label-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .label-detail-grid label { display: block; font-size: 11px; color: #d3e1f4; }
        .quick-nav-card {
            display: grid; gap: 10px; padding: 12px; border-radius: 12px; background: linear-gradient(180deg, rgba(17,24,39,0.92), rgba(15,23,42,0.8));
            border: 1px solid rgba(148,163,184,0.2); box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
        }
        .quick-nav-toggle {
            width: 100%; border: 0; border-radius: 10px; padding: 10px 12px; background: linear-gradient(135deg, #2563c7, #3b82f6); color: white; font-weight: 700; cursor: pointer;
        }
        .quick-nav-panel {
            display: grid; gap: 8px;
        }
        .quick-nav-panel.collapsed {
            display: none;
        }
        .quick-nav-card label {
            font-size: 11px; color: #dfe7f5;
        }
        .quick-nav-card select,
        .quick-nav-card input {
            width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #3b465f; background: rgba(8,15,28,0.9); color: white; font-size: 13px;
        }
        .quick-nav-actions {
            display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
        }
        .quick-nav-actions button {
            width: 100%; border: 0; border-radius: 10px; padding: 10px 8px; cursor: pointer; font-weight: 700; color: white;
            background: linear-gradient(135deg, #2563c7, #3b82f6);
        }
        .quick-nav-actions button.secondary {
            background: linear-gradient(135deg, #0f766e, #14b8a6);
        }
        #quick-nav-status {
            min-height: 18px; font-size: 11px; color: #b5f0d1;
        }
        .weekly-calendar { display: grid; gap: 6px; padding: 9px; border: 1px solid #3b465f; border-radius: 8px; background: rgba(8, 15, 28, 0.55); }
        #map-calendar { position: absolute; top: 16px; right: 16px; z-index: 45; width: min(260px, calc(100% - 32px)); }
        .weekly-calendar-title { font-size: 11px; color: #d3e1f4; }
        .weekly-calendar-days { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
        .weekly-calendar-day { padding: 6px 3px; border: 1px solid #3b465f; border-radius: 5px; background: #111827; color: #d3e1f4; cursor: pointer; font-size: 11px; }
        .weekly-calendar-day.active { background: #2563c7; border-color: #60a5fa; color: #fff; }
        .weekly-calendar-time { width: 100%; }
        @media (max-width: 700px) {
            .label-form-panel { left: 10px; right: 10px; top: 10px; width: auto; max-width: none; max-height: calc(100% - 20px); }
            .label-form-header { cursor: default; }
            .label-form-body { max-height: none; padding: 10px; }
        }
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
        .info-marker-label { font-size: 10px; margin-left: 3px; }
        .custom-alert.is-dragging { cursor: grabbing !important; box-shadow: 0 0 0 4px rgba(245,158,11,.35), 0 10px 22px rgba(0,0,0,.45) !important; }
        .custom-alert:before { content: "+"; }
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
        body.view-mode #side-menu {
            position: absolute;
            top: 58px;
            left: 16px;
            z-index: 80;
            width: min(300px, calc(100vw - 32px));
            background: transparent;
            padding: 0;
            box-shadow: none;
            display: flex;
        }
        body.view-mode #side-menu > :not(.quick-nav-view-only) { display: none !important; }
        body.view-mode .quick-nav-view-only {
            display: flex !important;
            width: 100%;
        }
        body.view-mode .quick-nav-view-only .quick-nav-card {
            width: 100%;
            background: rgba(8, 15, 28, 0.38);
            border: 1px solid rgba(148, 163, 184, 0.28);
            backdrop-filter: blur(6px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
        }

        .custom-arrow { cursor: pointer; }
        .circle-hotspot { cursor: pointer; }
        .circle-hotspot .circle-inner {
            border-radius: 50%; background: #22c55e; border: 2px solid #bbf7d0;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.35), 0 4px 12px rgba(0, 0, 0, 0.45);
        }
        .exclamation-hotspot { cursor: pointer; }
        .exclamation-inner {
            display: grid; place-items: center; background: #f59e0b; color: #111827;
            border: 2px solid #fef3c7; border-radius: 8px; font-weight: 900; line-height: 1;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.35), 0 4px 12px rgba(0, 0, 0, 0.45);
        }
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
        body.view-mode .minimap-route-node { display: none; }
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

        /* Ajustes de uso táctil y distribución para pantallas pequeñas. */
        @media (max-width: 700px) {
            body { display: flex; flex-direction: column; height: 100vh; height: 100dvh; min-height: 0; overflow: hidden; }
            #top-bar {
                height: auto;
                flex: 0 0 auto;
                min-height: 56px;
                padding: 8px 10px;
                gap: 8px;
                flex-wrap: wrap;
            }
            #top-bar > div:first-child { font-size: 13px; }
            .mode-switch { display: flex; flex: 1 1 auto; justify-content: center; }
            .mode-switch button { min-height: 38px; padding: 7px 10px; font-size: 12px; }
            #top-bar > div:last-child { gap: 6px !important; }
            #save-status { display: none; }
            #btn-export { min-height: 38px; padding: 7px 10px !important; font-size: 12px; }
            #main-layout {
                height: auto;
                flex: 1 1 auto;
                min-height: 0;
                flex-direction: column;
            }
            #viewer-wrapper { flex: 1 1 56%; min-height: 0; min-width: 0; width: 100%; }
            #panorama-container { min-height: 0; }
            #side-menu {
                width: 100%;
                height: 44%;
                min-height: 180px;
                flex: 0 1 44%;
                min-width: 0;
                padding: 10px 10px max(10px, env(safe-area-inset-bottom));
                gap: 10px;
                border-top: 2px solid #444;
                -webkit-overflow-scrolling: touch;
            }
            #side-menu > button,
            #side-menu select,
            #side-menu input,
            #side-menu .quick-nav-toggle,
            #side-menu .quick-nav-actions button { min-height: 42px; }
            #side-menu .menu-section { width: 100%; }
            #side-menu button { overflow-wrap: anywhere; }
            #mapa-google-wrapper { left: 10px !important; bottom: 10px !important; width: clamp(155px, 42vw, 185px) !important; max-height: 22vh !important; }
            #map-calendar { top: 8px; right: 8px; width: min(190px, calc(100% - 16px)); padding: 7px; }
            body.view-mode #side-menu {
                position: fixed;
                top: calc(80px + env(safe-area-inset-top));
                left: 10px;
                width: min(190px, calc(100vw - 20px));
                height: auto;
                min-height: 0;
                flex: 0 0 auto;
                max-height: calc(100dvh - 92px - env(safe-area-inset-bottom));
                overflow-y: auto;
                z-index: 1100;
            }
            body.view-mode .quick-nav-card { gap: 6px; padding: 6px; }
            body.view-mode .quick-nav-toggle { min-height: 38px; padding: 7px 8px; border-radius: 7px; font-size: 12px; line-height: 1.2; }
            body.view-mode .quick-nav-panel { gap: 6px; }
            body.view-mode .quick-nav-card select { padding: 8px; font-size: 12px; }
            .weekly-calendar-days { grid-template-columns: repeat(4, 1fr); }
            .weekly-calendar-day { min-height: 32px; padding: 5px 2px; }
            .label-form-panel { z-index: 100; }
            .viewer-label { max-width: min(180px, 42vw); }
            #map-guide-modal { padding: 8px; }
            #map-guide-modal-header { gap: 8px; padding: 10px; }
            #map-guide-modal-header h2 { font-size: 15px; line-height: 1.2; }
            #map-guide-modal-header > div { display: flex; gap: 6px; flex-shrink: 0; }
            #map-guide-modal-header button { min-height: 38px; padding: 7px 9px !important; margin-right: 0 !important; font-size: 11px; }
            #map-guide-modal-body { flex-direction: column; overflow-y: auto; }
            #map-guide-modal-stage { flex: 0 0 42vh; min-height: 230px; padding: 8px; }
            #map-guide-modal .map-editor-panel { width: 100%; flex: 1 1 auto; padding: 12px; overflow: visible; }
            .map-control-row { flex-wrap: wrap; }
            .map-control-row button { min-height: 40px; }
            .map-layer-toggle { width: 100%; min-height: 30px; }
        }

        @media (max-width: 420px) {
            #top-bar > div:first-child { flex-basis: 100%; text-align: center; }
            .mode-switch { order: 2; }
            #top-bar > div:last-child { order: 3; margin-left: auto; }
            #viewer-wrapper { flex-basis: 52%; }
            #side-menu { height: 48%; flex-basis: 48%; }
            #mapa-google-wrapper { width: clamp(150px, 40vw, 160px) !important; }
            body.view-mode #side-menu {
                top: calc(112px + env(safe-area-inset-top));
                max-height: calc(100dvh - 124px - env(safe-area-inset-bottom));
            }
            #map-calendar { width: 170px; }
            #map-guide-modal-stage { flex-basis: 34vh; min-height: 190px; }
            #map-guide-modal-header h2 { max-width: 42vw; }
        }

        @media (max-height: 500px) and (orientation: landscape) {
            body { display: flex; flex-direction: column; height: 100vh; height: 100dvh; }
            #top-bar { flex: 0 0 auto; min-height: 44px; padding: 4px 10px; }
            #main-layout { height: auto; flex: 1 1 auto; min-height: 0; flex-direction: row; }
            #viewer-wrapper { flex: 1 1 auto; min-width: 0; min-height: 0; width: auto; }
            #mapa-google-wrapper { left: 10px !important; bottom: 10px !important; width: clamp(145px, 20vw, 175px) !important; max-height: 24vh !important; }
            #side-menu {
                width: clamp(220px, 30vw, 300px);
                height: 100%;
                min-height: 0;
                flex: 0 0 clamp(220px, 30vw, 300px);
                padding: 8px;
                gap: 8px;
                border-top: 0;
                border-left: 2px solid #444;
            }
            body.view-mode #side-menu {
                position: fixed;
                top: calc(78px + env(safe-area-inset-top));
                left: 10px;
                width: min(280px, calc(100vw - 20px));
                height: auto;
                min-height: 0;
                flex: 0 0 auto;
                max-height: calc(100dvh - 90px - env(safe-area-inset-bottom));
                overflow-y: auto;
                z-index: 1100;
            }
            #side-menu > button,
            #side-menu select,
            #side-menu input,
            #side-menu .quick-nav-toggle,
            #side-menu .quick-nav-actions button { min-height: 38px; }
        }
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
                    <div id="no-class-message" class="no-class-message">NO HAY CLASE POR EL MOMENTO</div>
                    <div class="alert-form-selector">
                        <label for="label-alert-form-mode">Tipo de formulario</label>
                        <select id="label-alert-form-mode">
                            <option value="full">Completo</option>
                            <option value="simple">Solo nombre</option>
                        </select>
                    </div>
                    <label class="form-simple-only">Lugar guardado
                        <select id="label-lugar-db">
                            <option value="">Cargando lugares...</option>
                        </select>
                    </label>
                    <label id="label-profesor-field" style="font-size:11px;color:#d3e1f4;">
                        <span id="label-profesor-label">Profesor</span>
                        <input id="label-profesor" type="text" placeholder="Escribe el profesor" value="">
                        <div id="sugerencias-profesores" class="sugerencias-profesores"></div>
                    </label>
                    <div id="alert-full-fields" class="alert-full-fields">
                        <label>Curso / materia
                            <input id="label-curso" type="text" placeholder="Ej: Matemáticas" value="">
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
                                <option value="Sábado">Sábado</option>
                                <option value="Domingo">Domingo</option>
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
                    </div>
                    <label class="view-form-hide class-data-hide form-simple-hide">Título
                        <input id="label-titulo" type="text" placeholder="Nombre del lugar" value="">
                    </label>
                    <div class="label-position-grid info-position-controls class-data-hide" aria-hidden="true">
                        <label>Pitch
                            <input id="label-pitch" type="number" step="0.1" min="-90" max="90" placeholder="0" value="">
                        </label>
                        <label>Yaw
                            <input id="label-yaw" type="number" step="0.1" min="-360" max="360" placeholder="0" value="">
                        </label>
                    </div>
                    <input id="label-color" class="circle-form-hide class-data-hide" type="color" value="#ffffff">
                    <label class="circle-form-hide class-data-hide" style="font-size:11px;color:#d3e1f4;">Tamaño
                        <input id="label-size" type="range" min="10" max="28" value="12">
                    </label>
                    <div id="label-size-value" class="circle-form-hide class-data-hide" style="font-size:11px;color:#d3e1f4;margin-top:-2px;">12 px</div>
                    <label class="circle-form-hide class-data-hide" style="font-size:11px;color:#d3e1f4;">Tamaño del círculo
                        <input id="label-marker-size" type="range" min="18" max="120" value="70">
                    </label>
                    <div id="label-marker-size-value" class="circle-form-hide class-data-hide" style="font-size:11px;color:#d3e1f4;margin-top:-2px;">70 px</div>
                    <label class="circle-form-hide class-data-hide" style="display:flex; align-items:center; gap:8px; font-size:11px; color:#d3e1f4;">
                        <input id="label-is-alert" type="checkbox">
                        Marcador + Info
                    </label>
                    <button id="btn-add-label" class="view-form-hide" type="button">Guardar + Info</button>
                    <div id="alert-save-status" role="status" aria-live="polite" style="font-size:12px;color:#8ff0b6;min-height:18px;"></div>
                </div>
            </div>
            <div id="panorama-container"></div>
            <div id="panorama-loading"><span>Cargando imagen...</span></div>
            <div id="mapa-google-wrapper">
                <div id="mapa-google-surface">
                    <img id="mapa-google-overlay" src="imagenes_del_colegio/mapa_google.jpeg" alt="Mapa guía del colegio" loading="eager">
                    <div class="minimap-overlay" id="minimap-overlay" aria-hidden="true">
                        <div id="minimap__nodes"></div>
                        <div id="minimap__user" class="minimap-user-marker"></div>
                    </div>
                    <svg id="mapa-google-layer" viewBox="0 0 100 100" preserveAspectRatio="none" aria-label="Capas editables del mapa guía"><defs><pattern id="map-guide-grid-pattern" width="5" height="5" patternUnits="userSpaceOnUse"><path d="M 5 0 L 0 0 0 5" fill="none" stroke="#1683d8" stroke-opacity=".28" stroke-width=".18"/></pattern></defs><rect id="map-guide-grid" x="0" y="0" width="100" height="100" fill="url(#map-guide-grid-pattern)" visibility="hidden"></rect><g id="map-guide-content"></g></svg>
                </div>
            </div>
            <div id="map-calendar" class="weekly-calendar" aria-label="Calendario semanal">
                <div class="weekly-calendar-title">Calendario semanal</div>
                <div class="weekly-calendar-days" id="weekly-calendar-days">
                    <button type="button" class="weekly-calendar-day" data-day="Lunes">Lun</button>
                    <button type="button" class="weekly-calendar-day" data-day="Martes">Mar</button>
                    <button type="button" class="weekly-calendar-day" data-day="Miércoles">Mié</button>
                    <button type="button" class="weekly-calendar-day" data-day="Jueves">Jue</button>
                    <button type="button" class="weekly-calendar-day" data-day="Viernes">Vie</button>
                    <button type="button" class="weekly-calendar-day" data-day="Sábado">Sáb</button>
                    <button type="button" class="weekly-calendar-day" data-day="Domingo">Dom</button>
                </div>
                <input id="weekly-calendar-time" class="weekly-calendar-time" type="time" aria-label="Hora del calendario">
            </div>

            <div class="corner-badge" aria-label="Indicador de vista">
                <div class="corner-badge__icon">↗</div>
                <div class="corner-badge__label">Vista 360</div>
            </div>
            <svg id="route-line" aria-hidden="true"><path id="route-path"></path><circle id="route-end" r="8"></circle></svg>
        </div>
                <div id="side-menu">
            <button id="btn-add" style="background:var(--primary); color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">➕ Añadir Flecha Aquí</button>
            <button id="btn-add-circle" type="button" style="background:#16a34a; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">🟢 Crear círculo (doble clic)</button>
            <button id="btn-add-exclamation" type="button" style="background:#f59e0b; color:#111827; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">❗ Crear exclamación (doble clic)</button>
            <button id="btn-add-info" style="background:#f59e0b; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">＋ Añadir + Info</button>
            <button id="btn-add-simple-alert" type="button" style="background:#f97316; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">❗ Crear nombre simple</button>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <button id="btn-copy-viewer-object" type="button" title="Copiar el objeto seleccionado (Ctrl+C)" style="background:#2563eb; color:white; border:0; padding:10px 6px; border-radius:6px; cursor:pointer; font-weight:bold">⧉ Copiar</button>
                <button id="btn-paste-viewer-object" type="button" title="Pegar el objeto copiado (Ctrl+V)" style="background:#475569; color:white; border:0; padding:10px 6px; border-radius:6px; cursor:pointer; font-weight:bold">📋 Pegar</button>
            </div>
            <button id="btn-place-indicator" style="background:#0ea5e9; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">📍 Colocar indicador</button>
            <button id="btn-place-advance" style="background:#f59e0b; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">🎯 Colocar punto avance</button>
                    <button id="btn-open-map-editor" type="button" style="background:#2563c7; color:white; border:none; padding:12px; border-radius:6px; cursor:pointer; font-weight:bold">🗺️ Editar mapa guía</button>

            <div class="menu-section" id="alert-menu-section">
                <label>Menú de información</label>
                <div id="alerts-list"></div>
            </div>
            
            <div class="menu-section">
                <label>Imagen donde estás:</label>
                <div id="label-source" style="padding:8px; background:#111; border:1px solid #222; color:#ddd; border-radius:4px">-</div>
            </div>

            <div class="menu-section">
                <label>Seleccionar imagen del colegio:</label>
                <select id="select-image-folder" style="width:100%; padding:8px; margin-bottom:8px; background:#333; color:white; border:1px solid #444">
                    <option value="">Todas las carpetas</option>
                </select>
                <select id="select-current-image" style="width:100%; padding:8px; background:#333; color:white; border:1px solid #444"></select>
                <div id="image-catalog-status" style="margin-top:6px; font-size:11px; color:#9ca3af">Cargando fotos...</div>
                <button id="btn-set-start-image" type="button" style="margin-top:8px; width:100%; background:#0f766e; color:white; border:0; padding:8px; border-radius:4px; cursor:pointer">⭐ Fijar como imagen inicial</button>
                <button id="btn-place-panorama" type="button" style="margin-top:8px; width:100%; background:#7c3aed; color:white; border:0; padding:8px; border-radius:4px; cursor:pointer">📷 Colocar esta imagen en el mapa</button>
                <button id="btn-download-map-points" type="button" style="margin-top:8px; width:100%; background:#0891b2; color:white; border:0; padding:8px; border-radius:4px; cursor:pointer">⬇️ Descargar ubicaciones</button>
            </div>

            <label style="display:flex; align-items:center; gap:8px; font-size:12px; color:#ddd;">
                <input id="allow-hotspot-move" type="checkbox">
                Permitir mover flechas
            </label>

            <div class="menu-section quick-nav-view-only">
                <div class="quick-nav-card">
                    <button id="btn-toggle-quick-nav" type="button" class="quick-nav-toggle">☰ Mostrar menú de vista</button>
                    <div id="quick-nav-panel" class="quick-nav-panel collapsed">
                        <label>Buscar por</label>
                        <select id="quick-nav-category">
                            <option value="grado">Grado</option>
                            <option value="salon">Salón</option>
                            <option value="profesor">Profesor</option>
                            <option value="lugar">Lugar</option>
                        </select>
                        <label>Opción</label>
                        <select id="quick-nav-target">
                            <option value="">Selecciona una opción</option>
                        </select>
                        <div class="quick-nav-actions">
                            <button id="btn-go-to-location" type="button">Ir ahora</button>
                            <button id="btn-route-to-location" type="button" class="secondary">Ruta</button>
                        </div>
                        <div id="quick-nav-status"></div>
                    </div>
                </div>
            </div>

            <div class="menu-section">
                <label>Lugares guardados</label>
                <select id="select-lugares-guardados" style="width:100%; padding:8px; background:#333; color:white; border:1px solid #444">
                    <option value="">Selecciona un lugar</option>
                </select>
                <button id="btn-guardar-lugar-db" type="button" style="margin-top:8px; width:100%; background:#198754; color:white; border:0; padding:8px; border-radius:4px; cursor:pointer">Guardar</button>
            </div>

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
                <button id="btn-set-left-view" style="margin-top:6px; width:100%; background:#2563c7; color:white; border:1px solid #60a5fa; padding:8px; border-radius:4px; cursor:pointer">Guardar vista izquierda</button>
                <button id="btn-set-right-view" style="margin-top:6px; width:100%; background:#6d28d9; color:white; border:1px solid #a78bfa; padding:8px; border-radius:4px; cursor:pointer">Guardar vista derecha</button>
            </div>

            <div class="menu-section">
                <label>Destino (imagen a donde va):</label>
                <select id="select-target-folder" style="width:100%; padding:8px; margin-bottom:8px; background:#333; color:white; border:1px solid #444">
                    <option value="">Todas las carpetas</option>
                </select>
                <select id="select-target" style="width:100%; padding:8px; background:#333; color:white; border:1px solid #444"></select>
            </div>

            <div class="menu-section">
                <label>Dirección de la flecha:</label>
                <select id="select-direction" style="width:100%; padding:8px; background:#333; color:white; border:1px solid #444">
                    <option value="forward">Adelante</option>
                    <option value="backward">Atrás</option>
                    <option value="down">Abajo</option>
                    <option value="right">Derecha</option>
                    <option value="left">Izquierda</option>
                </select>
            </div>
            
            <div class="menu-section">
                <label>Color:</label>
                <select id="select-color" style="width:100%; padding:8px; background:#333; color:white; border:1px solid #444">
                    <option value="white">Blanco</option>
                    <option value="red">Rojo</option>
                    <option value="blue">Azul</option>
                    <option value="yellow">Amarillo</option>
                    <option value="green">Verde</option>
                </select>
            </div>

            <hr style="opacity:0.1">

            <div class="menu-section">
                <label>Giro (Dirección): <span id="val-rot" class="val-text">0°</span></label>
                <input type="range" id="range-rot" min="0" max="360" value="0">
                
                <label>Inclinación (Perspectiva): <span id="val-tilt" class="val-text">0°</span></label>
                <input type="range" id="range-tilt" min="-90" max="90" value="0">
                
                <label>Ancho: <span id="val-w" class="val-text">100px</span></label>
                <input type="range" id="range-w" min="20" max="200" value="100">
                
                <label>Alto: <span id="val-h" class="val-text">100px</span></label>
                <input type="range" id="range-h" min="20" max="200" value="100">
            </div>

            <button id="btn-del" style="background:#dc3545; color:white; border:none; padding:10px; border-radius:4px; cursor:pointer; width:100%">🗑️ Eliminar Flecha</button>
                </div>
    </div>
    <div id="schedule-alert-overlay" role="alertdialog" aria-modal="true" aria-hidden="true" aria-labelledby="schedule-alert-title" aria-describedby="schedule-alert-message">
        <div class="schedule-alert-panel">
            <div class="schedule-alert-icon" aria-hidden="true">!</div>
            <h2 id="schedule-alert-title">Aviso de horario</h2>
            <p id="schedule-alert-message"></p>
            <button id="schedule-alert-close" type="button">Entendido</button>
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
                <label for="map-database-place-select" style="display:block;font-size:11px;color:#d3e1f4;margin-top:8px;">Lugar de la base de datos</label>
                <select id="map-database-place-select" style="width:100%;margin:5px 0 8px;padding:7px;background:#111827;color:#fff;border:1px solid #4b5563;border-radius:4px;"><option value="">Abre el editor para cargar lugares</option></select>
                <button id="map-tool-database-place" type="button" style="width:100%;margin-bottom:8px;">＋ Punto lugar</button>
                <div class="map-control-row"><button id="map-finish-path" type="button">Terminar camino</button><button id="map-clear-guide" type="button">Limpiar mapa</button></div>
                <div class="map-layers-title">Capas visibles</div>
                <label class="map-layer-toggle"><input id="layer-paths" type="checkbox" checked> Caminos</label><label class="map-layer-toggle"><input id="layer-places" type="checkbox" checked> Salones y lugares</label><label class="map-layer-toggle"><input id="layer-zones" type="checkbox" checked> Áreas prohibidas</label><label class="map-layer-toggle"><input id="map-grid-visible" type="checkbox"> Líneas X/Y</label><label class="map-layer-toggle"><input id="map-snap-grid" type="checkbox" checked> Ajustar a líneas</label>
                <div class="map-layers-title">Puntos de control y avance</div>
                <div id="minimap-elements-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #4b5563; border-radius: 4px; padding: 8px; margin-bottom: 10px; background: rgba(0,0,0,0.3);"></div>
                <div class="map-control-row"><button id="map-add-control-point" type="button" style="background: #2563c7; flex: 1;">➕ Indicador</button><button id="map-add-advance-point" type="button" style="background: #f59e0b; flex: 1;">➕ Punto avance</button></div>
                <div class="map-layers-title">Ventana visible del plano</div>
                <label class="map-layer-toggle"><input id="map-follow-indicator" type="checkbox" checked> Seguir indicador</label>
                <div class="map-control-row"><label for="map-zoom">Zoom</label><input id="map-zoom" type="range" min="100" max="500" value="200"><span id="map-zoom-value">200%</span></div>
                <div class="map-control-row"><label for="map-view-x">Sector X</label><input id="map-view-x" type="range" min="0" max="100" value="50"><span id="map-view-x-value">50%</span></div>
                <div class="map-control-row"><label for="map-view-y">Sector Y</label><input id="map-view-y" type="range" min="0" max="100" value="50"><span id="map-view-y-value">50%</span></div>
                <div id="advance-point-editor" style="display: none; border-top: 1px solid #4b5563; margin-top: 10px; padding-top: 10px;">
                    <strong style="color: #ddd;">Editar punto de avance</strong>
                    <label style="font-size: 11px; color: #d3e1f4; display: block; margin-top: 8px;">Carpeta</label>
                    <select id="advance-point-folder" style="width: 100%; padding: 6px; background: #111827; color: #fff; border: 1px solid #3b465f; border-radius: 4px; font-size: 12px;"></select>
                    <label style="font-size: 11px; color: #d3e1f4; display: block; margin-top: 6px;">Archivo / imagen</label>
                    <select id="advance-point-image" style="width: 100%; padding: 6px; background: #111827; color: #fff; border: 1px solid #3b465f; border-radius: 4px; font-size: 12px;"></select>
                    <label style="font-size: 11px; color: #d3e1f4; display: block; margin-top: 6px;">Coordenadas X, Y</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
                        <input id="advance-point-x" type="number" placeholder="X" step="0.1" min="0" max="100" style="padding: 6px; background: #111827; color: #fff; border: 1px solid #3b465f; border-radius: 4px; font-size: 12px;">
                        <input id="advance-point-y" type="number" placeholder="Y" step="0.1" min="0" max="100" style="padding: 6px; background: #111827; color: #fff; border: 1px solid #3b465f; border-radius: 4px; font-size: 12px;">
                    </div>
                    <button id="advance-point-save" type="button" style="width: 100%; background: #198754; color: white; border: 0; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-top: 8px;">Guardar cambios</button>
                </div>
                <div id="map-element-editor"><strong>Elemento seleccionado</strong><label>Nombre</label><input id="map-element-name" type="text"><label>Color</label><input id="map-element-color" type="color" value="#2563c7"><label>Forma</label><select id="map-element-shape"><option value="street">Calle rectangular blanca</option><option value="line">Línea</option><option value="circle">Circular</option><option value="semicircle">Semicírculo</option><option value="rect">Rectangular</option><option value="diamond">Rombo</option></select><label>Ancho / grosor de la calle</label><input id="map-element-width" type="range" min="1" max="35" value="7"><span id="map-element-width-value">7</span><label>Largo de la calle</label><input id="map-element-length" type="range" min="40" max="180" value="100"><span id="map-element-length-value">100%</span><p class="map-editor-help">Arrastra los puntos blancos que aparecen sobre la calle para cambiar su recorrido.</p><label>Alto</label><input id="map-element-height" type="range" min="1" max="35" value="8"><span id="map-element-height-value">8</span><div class="map-control-row"><button id="map-element-copy" type="button">Copiar</button><button id="map-element-paste" type="button">Pegar</button></div><div class="map-control-row"><button id="map-element-front" type="button">Al frente</button><button id="map-element-back" type="button">Atrás</button></div><button id="map-element-delete" type="button">Eliminar elemento</button></div>
                <div class="map-control-row"><label for="map-opacity">Opacidad</label><input id="map-opacity" type="range" min="35" max="100" value="92"><span id="map-opacity-value">92%</span></div>
                <div class="map-control-row"><label for="map-grayscale">Grises</label><input id="map-grayscale" type="range" min="0" max="100" value="0"><span id="map-grayscale-value">0%</span></div>
                <button id="btn-close-map-editor" type="button">Cerrar sin salir del visor</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

    <script>
        let panoramas = [];

        let viewer = null, currentPano = null, selectedHS = null, selectedCircle = null, isEdit = true, draggingHS = null, isDragging = false, rotateDragging = false, rotateStartX = 0, rotateStartRotate = 0;
        let viewerLoadRequest = 0;
        const panoramaImageCache = new Map();
        let hotspotPlacementType = 'arrow';
        let allowHotspotMove = false;
        let routeTargetId = null, routePath = [], routeNextHotspot = null;
        let routeStatusTimeout = null;
        let selectedMinimapElement = null;
        let labelDragState = null;
        let selectedLabel = null;
        let viewerObjectClipboard = null;
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
        let minimapLocationsReady = false;
        let minimapLocationsSaveTimer = null;
        const STORAGE_KEY = 'mapa360.panoramas.v3';
        const PENDING_STORAGE_KEY = 'mapa360.panoramas.pending.v3';
        const PENDING_IMPORT_KEY = 'mapa360.pending-import.entrada-principal.v2';
        const START_IMAGE_KEY = 'mapa360.start-image.v1';
        const DEFAULT_START_IMAGE_PATH = 'imagenes_del_colegio/nueva_puerta/porteria.jpeg';
        const MINIMAP_INDICATOR_KEY = 'mapa360.minimap.indicator.v1';
        const MINIMAP_ADVANCE_KEY = 'mapa360.minimap.advance.v1';
        const MINIMAP_LOCATIONS_FILE = 'data/ubicaciones_puntos_mapa.json';
        const MINIMAP_LOCATIONS_VERSION_KEY = 'mapa360.minimap.locations-import.v1';
        const MINIMAP_LOCATIONS_VERSION = '2026-10-04-puntos-lugar-v3';
        const SAVE_DELAY_MS = 500;
        const MAP_SETTINGS_KEY = 'mapa360.google-map.settings.v1';
        const MAP_COLOR_MIGRATION_KEY = 'mapa360.google-map.color-migration.v1';
        const DEFAULT_MAP_SETTINGS = { width: 300, opacity: 92, grayscale: 0, left: 16, bottom: 16, zoom: 200, viewX: 50, viewY: 50, followIndicator: true };
        let mapSettings = { ...DEFAULT_MAP_SETTINGS };
        const MAP_GUIDE_KEY = 'mapa360.school-guide.v1';
        let mapGuide = { paths: [], places: [], zones: [], layers: { paths: true, places: true, zones: true, user: true }, panoramaPositions: {} };
        let mapPlaceCatalog = [];
        let selectedMapDatabasePlace = null;
        let activeMapTool = null;
        let draftPath = [];
        let selectedMapElement = null;
        let mapOriginalParent = null;
        let draggingPathPoint = null;
        let mapClipboard = null;
        let horariosColegio = [];
        let gradosColegioDesdeBase = [];
        let lugaresSugeridos = [];
        let lugaresDisponibles = [];
        let lugaresBaseDeDatos = [];
        let lugaresCatalogo = [];
        let calendarioDia = '';
        let calendarioHora = '';

        async function cargarLugaresDesdeBaseDeDatos() {
            try {
                const response = await fetch('api.php?action=lugares', { cache: 'no-store' });
                const payload = await response.json();
                const items = Array.isArray(payload?.items) ? payload.items : [];
                lugaresBaseDeDatos = items
                    .map(item => ({
                        id_lugar: item.id_lugar ?? item.id ?? '',
                        panorama_id: item.panorama_id || '',
                        titulo: String(item.titulo || item.nombre || '').trim(),
                        descripcion: String(item.descripcion || '').trim(),
                        salon: String(item.salon || '').trim(),
                        grado: String(item.id_grado || item.grado || '').trim(),
                        profesor: String(item.id_profesor || item.profesor || '').trim()
                    }))
                    .filter(item => item.titulo);
                const target = document.getElementById('quick-nav-target');
                if (target && document.getElementById('quick-nav-category')?.value === 'lugar') {
                    populateQuickNavOptions();
                }
            } catch (error) {
                console.warn('No se pudieron cargar los lugares desde la base de datos', error);
                lugaresBaseDeDatos = [];
            }
        }

        async function cargarCatalogoLugares() {
            try {
                const response = await fetch('api.php?action=catalogo_lugares', { cache: 'no-store' });
                const payload = await response.json();
                lugaresCatalogo = (Array.isArray(payload?.items) ? payload.items : [])
                    .map(item => ({
                        id_lugar: String(item.id_lugar ?? item.id ?? '').trim(),
                        titulo: String(item.titulo || item.nombre || '').trim()
                    }))
                    .filter(item => item.id_lugar && item.titulo);
            } catch (error) {
                console.warn('No se pudo cargar el catálogo de lugares', error);
                lugaresCatalogo = [];
            }
        }

        function normalizarHorarioDeBase(horario) {
            return {
                id_horario: horario.id_horario,
                grado: horario.id_grado,
                dia: horario.dia_semana,
                bloque: horario.num_bloque_clase,
                curso: horario.materia,
                profesor: horario.id_profesor,
                salon: horario.salon,
                hora_fin_sena: horario.hora_fin_sena,
                hora_inicio: horario.hora_inicio,
                hora_fin: horario.hora_fin
            };
        }

        async function completarHorariosFaltantesDesdeBase() {
            try {
                const gradesResponse = await fetch('api.php?action=grados', { cache: 'no-store' });
                const gradesPayload = await gradesResponse.json();
                if (!gradesResponse.ok || !gradesPayload?.ok) return;
                gradosColegioDesdeBase = (gradesPayload.items || [])
                    .map(item => String(item.id_grado || '').trim())
                    .filter(grade => grade && grade !== '-');
                const loadedGrades = new Set(horariosColegio.map(item => String(item.grado || '').trim()));
                const missingGrades = gradosColegioDesdeBase.filter(grade => !loadedGrades.has(grade));
                const missingSchedules = await Promise.all(missingGrades.map(async grade => {
                    try {
                        const response = await fetch(`api.php?action=horarios&grado=${encodeURIComponent(grade)}`, { cache: 'no-store' });
                        const payload = await response.json();
                        return response.ok && payload?.ok ? (payload.items || []).map(normalizarHorarioDeBase) : [];
                    } catch (error) {
                        console.warn(`No se pudieron cargar los horarios del grado ${grade}`, error);
                        return [];
                    }
                }));
                const loadedIds = new Set(horariosColegio.map(item => String(item.id_horario)));
                missingSchedules.flat().forEach(item => {
                    const id = String(item.id_horario || '');
                    if (id && !loadedIds.has(id)) {
                        loadedIds.add(id);
                        horariosColegio.push(item);
                    }
                });
            } catch (error) {
                console.warn('No se pudieron completar los grados desde la base de datos', error);
            }
        }

        async function cargarHorariosColegio() {
            try {
                const response = await fetch('horarios.json', { cache: 'no-store' });
                const payload = await response.json();
                horariosColegio = Array.isArray(payload) ? payload : (payload.horarios || []);
            } catch (error) {
                try {
                    const response = await fetch('api.php?action=horarios', { cache: 'no-store' });
                    const payload = await response.json();
                    horariosColegio = (payload.items || []).map(normalizarHorarioDeBase);
                } catch (fallbackError) {
                    console.warn('No se pudo cargar el listado de horarios', fallbackError);
                }
            }
            await completarHorariosFaltantesDesdeBase();
            configurarHorariosColegio();
        }

        function configurarHorariosColegio() {
                const select = document.getElementById('label-id-horario');
                if (!select) return;
            renderizarOpcionesHorario();
                if (selectedCircle?.idHorario) select.value = selectedCircle.idHorario;
                select.onchange = () => {
                    const h = horariosColegio.find(item => String(item.id_horario) === select.value);
                    if (!h) return;
                    const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value || ''; };
                    const dia = h.dia === 'Miercoles' ? 'Miércoles' : h.dia;
                    const hora = value => (value || '').slice(0, 5);
                    set('label-profesor', h.profesor); set('label-grado', h.grado); set('label-curso', h.curso);
                    set('label-salon', h.salon); set('label-dia', dia); set('label-hora-inicio', hora(h.hora_inicio)); set('label-hora-fin', hora(h.hora_fin));
                };
                configurarAutocompletadoProfesores();
                configurarBusquedaPorSalon();
                if (selectedCircle) actualizarHorarioDelMarcador(selectedCircle);
        }
        function renderizarOpcionesHorario(profesor = '') {
            const select = document.getElementById('label-id-horario');
            if (!select) return;
            const profesorBuscado = profesor.trim().toLowerCase();
            const horariosFiltrados = profesorBuscado
                ? horariosColegio.filter(h => String(h.profesor || '').trim().toLowerCase() === profesorBuscado)
                : horariosColegio;
            select.innerHTML = '<option value="">Selecciona un curso del horario</option>';
            horariosFiltrados.forEach(horario => {
                const option = document.createElement('option');
                option.value = horario.id_horario;
                option.textContent = `${horario.grado} · ${horario.dia} · Bloque ${horario.bloque} · ${horario.curso}`;
                select.appendChild(option);
            });
        }
        async function cargarSugerenciasNombresLugares() {
            const input = document.getElementById('label-profesor');
            const box = document.getElementById('sugerencias-profesores');
            const select = document.getElementById('label-lugar-db');
            if (!input || !box) return;
            try {
                const response = await fetch('api.php?action=catalogo_lugares', { cache: 'no-store' });
                const payload = await response.json();
                if (!response.ok || !payload?.ok) {
                    throw new Error(payload?.error || 'No se pudieron cargar los lugares.');
                }
                const catalogo = (Array.isArray(payload?.items) ? payload.items : []).map(item => ({ ...item, fuente: 'catalogo' }));
                let guardados = [];
                try {
                    const panoramaId = currentPano?.id || '';
                    const url = panoramaId ? `api.php?action=lugares&panorama_id=${encodeURIComponent(panoramaId)}` : 'api.php?action=lugares';
                    const lugaresResponse = await fetch(url, { cache: 'no-store' });
                    const lugaresPayload = await lugaresResponse.json();
                    if (lugaresResponse.ok && lugaresPayload?.ok && Array.isArray(lugaresPayload.items)) {
                        guardados = lugaresPayload.items.map(item => ({ ...item, fuente: 'menu' }));
                    }
                } catch (error) {
                    console.warn('No se pudieron cargar los lugares guardados del menú', error);
                }
                lugaresDisponibles = [...catalogo, ...guardados];
                lugaresSugeridos = [...new Set(lugaresDisponibles.map(item => String(item.titulo || '').trim()).filter(Boolean))];
                if (select) {
                    const selectedId = selectedCircle?.idLugar
                        ? `catalogo:${selectedCircle.idLugar}`
                        : selectedCircle?.idLugar360 ? `menu:${selectedCircle.idLugar360}` : '';
                    select.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = lugaresDisponibles.length
                        ? 'Selecciona un lugar de la base de datos'
                        : 'No hay lugares registrados';
                    select.appendChild(placeholder);
                    lugaresDisponibles.forEach(item => {
                        const option = document.createElement('option');
                        option.value = `${item.fuente}:${item.id_lugar ?? ''}`;
                        option.dataset.source = item.fuente;
                        option.dataset.placeId = String(item.id_lugar ?? '');
                        option.dataset.title = String(item.titulo || '').trim();
                        option.textContent = item.fuente === 'menu'
                            ? `${option.dataset.title || 'Lugar'} · Guardado`
                            : option.dataset.title || `Lugar ${option.dataset.placeId}`;
                        select.appendChild(option);
                    });
                    select.value = selectedId;
                    select.onchange = () => {
                        const option = select.selectedOptions[0];
                        if (!option?.value) return;
                        input.value = option.dataset.title || option.textContent;
                        box.innerHTML = '';
                    };
                }
                box.innerHTML = '';
                if (!lugaresSugeridos.length) {
                    const empty = document.createElement('div');
                    empty.className = 'sugerencia-profesor';
                    empty.textContent = 'Sin nombres guardados';
                    empty.style.opacity = '0.7';
                    box.appendChild(empty);
                    return;
                }
                lugaresSugeridos.slice(0, 10).forEach(nombre => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'sugerencia-profesor';
                    option.textContent = nombre;
                    option.addEventListener('mousedown', event => {
                        event.preventDefault();
                        input.value = nombre;
                        const lugar = lugaresDisponibles.find(item => String(item.titulo || '').trim() === nombre);
                        if (select && lugar) select.value = `${lugar.fuente}:${lugar.id_lugar}`;
                        box.innerHTML = '';
                    });
                    box.appendChild(option);
                });
            } catch (error) {
                console.warn('No se pudieron cargar los nombres desde lugares_360', error);
                lugaresSugeridos = [];
                lugaresDisponibles = [];
                if (select) select.innerHTML = '<option value="">No se pudieron cargar los lugares</option>';
                box.innerHTML = '';
            }
        }

        function getSelectedDatabasePlace(expectedTitle) {
            const option = document.getElementById('label-lugar-db')?.selectedOptions[0];
            const id = Number(option?.dataset.placeId);
            if (!option?.value || option.dataset.title !== expectedTitle || !Number.isSafeInteger(id) || id <= 0) return null;
            return { fuente: option.dataset.source, id };
        }

        function configurarAutocompletadoProfesores() {
            const input = document.getElementById('label-profesor');
            const box = document.getElementById('sugerencias-profesores');
            if (!input || !box || input.dataset.autocompleteReady) return;
            input.dataset.autocompleteReady = 'true';
            const profesores = [...new Set(horariosColegio.map(h => h.profesor).filter(p => p && p !== '-'))].sort((a, b) => a.localeCompare(b, 'es'));
            input.addEventListener('input', () => {
                const query = input.value.trim().toLowerCase();
                const isSimpleMode = document.getElementById('label-alert-form-mode')?.value === 'simple';
                const placeSelect = document.getElementById('label-lugar-db');
                if (isSimpleMode && placeSelect?.selectedOptions[0]?.dataset.title !== input.value.trim()) {
                    placeSelect.value = '';
                }
                box.innerHTML = '';
                if (isSimpleMode) {
                    const nombres = lugaresSugeridos.length ? lugaresSugeridos : [];
                    const matches = !query ? nombres.slice(0, 8) : nombres.filter(nombre => nombre.toLowerCase().includes(query)).slice(0, 8);
                    matches.forEach(nombre => {
                        const option = document.createElement('button');
                        option.type = 'button'; option.className = 'sugerencia-profesor'; option.textContent = nombre;
                        option.addEventListener('mousedown', event => {
                            event.preventDefault();
                            input.value = nombre;
                            box.innerHTML = '';
                        });
                        box.appendChild(option);
                    });
                    if (!matches.length && !query) {
                        const empty = document.createElement('div');
                        empty.className = 'sugerencia-profesor';
                        empty.textContent = 'Sin nombres guardados';
                        empty.style.opacity = '0.7';
                        box.appendChild(empty);
                    }
                    return;
                }
                if (!query) return;
                profesores.filter(p => p.toLowerCase().includes(query)).slice(0, 8).forEach(profesor => {
                    const option = document.createElement('button');
                    option.type = 'button'; option.className = 'sugerencia-profesor'; option.textContent = profesor;
                    option.addEventListener('mousedown', event => {
                        event.preventDefault();
                        input.value = profesor;
                        renderizarOpcionesHorario(profesor);
                        box.innerHTML = '';
                    });
                    box.appendChild(option);
                });
            });
            input.addEventListener('blur', () => setTimeout(() => {
                const isSimpleMode = document.getElementById('label-alert-form-mode')?.value === 'simple';
                if (!isSimpleMode) {
                    renderizarOpcionesHorario(input.value);
                }
                box.innerHTML = '';
            }, 150));
        }
        function configurarBusquedaPorSalon() {
            const salonInput = document.getElementById('label-salon');
            if (!salonInput || salonInput.dataset.roomSearchReady) return;
            salonInput.dataset.roomSearchReady = 'true';
            const completarProfesor = () => {
                const salon = salonInput.value.trim().toLowerCase();
                if (!salon) return;
                const grado = document.getElementById('label-grado')?.value.trim().toLowerCase() || '';
                const dia = document.getElementById('label-dia')?.value.trim().toLowerCase() || '';
                const coincidencias = horariosColegio.filter(horario => String(horario.salon || '').trim().toLowerCase() === salon);
                const horario = coincidencias.find(item => grado && String(item.grado || '').trim().toLowerCase() === grado && dia && String(item.dia || '').trim().toLowerCase() === dia)
                    || coincidencias.find(item => grado && String(item.grado || '').trim().toLowerCase() === grado)
                    || coincidencias.find(item => dia && String(item.dia || '').trim().toLowerCase() === dia)
                    || coincidencias[0];
                if (!horario || !horario.profesor) return;
                const profesorInput = document.getElementById('label-profesor');
                if (profesorInput) {
                    profesorInput.value = horario.profesor;
                    renderizarOpcionesHorario(horario.profesor);
                }
            };
            salonInput.addEventListener('change', completarProfesor);
            salonInput.addEventListener('blur', completarProfesor);
        }

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
            h.direction = h.direction || 'forward';
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
            p.leftYaw = Number.isFinite(Number(p.leftYaw)) ? Number(p.leftYaw) : p.initialYaw;
            p.leftPitch = Number.isFinite(Number(p.leftPitch)) ? Number(p.leftPitch) : p.initialPitch;
            p.rightYaw = Number.isFinite(Number(p.rightYaw)) ? Number(p.rightYaw) : p.initialYaw;
            p.rightPitch = Number.isFinite(Number(p.rightPitch)) ? Number(p.rightPitch) : p.initialPitch;
            p.hotspots = p.hotspots.map(normalizeHotspot);
            p.labels = p.labels.map(label => ({
                id: label.id || `label-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                text: label.text || 'Etiqueta',
                profesor: label.profesor || '',
                curso: label.curso || '',
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
                alertMode: alert.alertMode || 'full',
                profesor: alert.profesor || '',
                curso: alert.curso || '',
                grado: alert.grado || '',
                salon: alert.salon || '',
                dia: alert.dia || '',
                hora: alert.hora || (alert.horaInicio && alert.horaFin ? `${alert.horaInicio} - ${alert.horaFin}` : alert.horaInicio || alert.horaFin || ''),
                horaInicio: alert.horaInicio || '',
                horaFin: alert.horaFin || '',
                titulo: alert.titulo || '',
                descripcion: alert.descripcion || '',
                pitch: Number(alert.pitch) || 0,
                yaw: Number(alert.yaw) || 0,
                x: Number(alert.x) || 50,
                y: Number(alert.y) || 50,
                color: alert.color || '#22c55e',
                bg: alert.bg || 'rgba(101, 35, 18, 0.85)',
                fontSize: Number(alert.fontSize) || 18,
                markerSize: Number(alert.markerSize) || 70
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
            alertPlacementMode = false;
            setLabelFormEditable(true);
            const form = document.getElementById('label-form-panel');
            if (form) {
                form.classList.add('hidden');
                form.classList.remove('circle-form-mode', 'view-form-mode');
            }
            const simpleButton = document.getElementById('btn-add-simple-alert');
            if (simpleButton) {
                simpleButton.classList.remove('active');
                simpleButton.textContent = '❗ Crear nombre simple';
            }
        }

        function setAlertFormMode(mode = 'full') {
            const formPanel = document.getElementById('label-form-panel');
            const modeSelect = document.getElementById('label-alert-form-mode');
            const professorLabel = document.getElementById('label-profesor-label');
            const professorInput = document.getElementById('label-profesor');
            const descriptionInput = document.getElementById('label-descripcion');
            const saveBtn = document.getElementById('btn-add-label');
            const isSimpleMode = mode === 'simple';
            if (formPanel) {
                formPanel.classList.toggle('alert-form-simple', isSimpleMode);
                formPanel.classList.toggle('alert-form-full', !isSimpleMode);
            }
            if (modeSelect) modeSelect.value = isSimpleMode ? 'simple' : 'full';
            if (professorLabel) professorLabel.textContent = isSimpleMode ? 'Nombre del lugar' : 'Profesor';
            if (professorInput) {
                professorInput.placeholder = isSimpleMode ? 'Escribe el nombre del lugar' : 'Escribe el profesor';
                professorInput.title = isSimpleMode ? 'Nombre de la tabla lugares' : 'Profesor';
            }
            if (descriptionInput) {
                // El formulario se limpia únicamente al crear un aviso nuevo.
                // Al abrir un aviso existente, sus datos se cargan después desde ese aviso.
                descriptionInput.closest('label')?.classList.toggle('hidden', isSimpleMode);
            }
            if (isSimpleMode) {
                const input = document.getElementById('label-profesor');
                const box = document.getElementById('sugerencias-profesores');
                if (input && box) {
                    box.innerHTML = '';
                    cargarSugerenciasNombresLugares();
                }
            }
            if (saveBtn) {
                saveBtn.textContent = isSimpleMode ? 'Guardar' : 'Guardar + Info';
            }
        }

        function prepareInfoForm(mode = 'full') {
            if (!currentPano || !viewer) {
                updateSaveStatus('El visor todavía está cargando; inténtalo de nuevo en un momento.', false);
                return;
            }
            selectedLabel = null;
            selectedCircle = null;
            const formPanel = document.getElementById('label-form-panel');
            formPanel.classList.remove('circle-form-mode');
            formPanel.classList.remove('view-form-mode');
            formPanel.querySelector('.label-form-header span').textContent = 'Etiqueta';
            setAlertFormMode(mode);
            setCurrentCalendarDefaults();
            const title = document.getElementById('label-titulo');
            const description = document.getElementById('label-descripcion');
            const alertChk = document.getElementById('label-is-alert');
            const pitch = document.getElementById('label-pitch');
            const yaw = document.getElementById('label-yaw');
            const color = document.getElementById('label-color');
            const markerSize = document.getElementById('label-marker-size');
            const markerSizeValue = document.getElementById('label-marker-size-value');

            // Cada signo nuevo empieza con un formulario independiente.
            // Así nunca hereda profesor, curso, grado, salón u horario del aviso anterior.
            [
                'label-profesor', 'label-curso', 'label-grado', 'label-salon',
                'label-hora', 'label-hora-inicio', 'label-hora-fin',
                'label-id-horario', 'label-titulo', 'label-descripcion',
                'label-pitch', 'label-yaw'
            ].forEach(id => {
                const input = document.getElementById(id);
                if (input) input.value = '';
            });
            const dayInput = document.getElementById('label-dia');
            if (dayInput) dayInput.value = '';
            if (alertChk) alertChk.checked = true;
            if (color) color.value = '#22c55e';
            if (markerSize) markerSize.value = '70';
            if (markerSizeValue) markerSizeValue.textContent = '70 px';
            if (title) title.value = '';
            if (description) description.value = '';
            if (pitch) pitch.value = '';
            if (yaw) yaw.value = '';
            setLabelFormEditable(true);
            alertPlacementMode = true;
            openLabelForm();
            if (title) title.focus();
        }

        function setLabelFormEditable(isEditable) {
            const ids = [
                'label-profesor', 'label-lugar-db', 'label-grado', 'label-salon', 'label-dia', 'label-id-horario',
                'label-hora-inicio', 'label-hora-fin', 'label-hora', 'label-titulo', 'label-descripcion',
                'label-pitch', 'label-yaw', 'label-color', 'label-size', 'label-marker-size', 'label-is-alert'
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
            document.querySelectorAll('.weekly-calendar-day').forEach(button => { button.disabled = !isEditable; });
            const calendarTime = document.getElementById('weekly-calendar-time');
            if (calendarTime) calendarTime.disabled = !isEditable;
            const saveBtn = document.getElementById('btn-add-label');
            if (saveBtn) {
                saveBtn.disabled = !isEditable;
                const formMode = document.getElementById('label-alert-form-mode')?.value || 'full';
                saveBtn.textContent = !isEditable ? 'Solo lectura' : (formMode === 'simple' ? 'Guardar' : 'Guardar + Info');
            }
        }

        function setNoClassFormState(isNoClass, messageText = 'NO HAY CLASE POR EL MOMENTO') {
            const formPanel = document.getElementById('label-form-panel');
            if (!formPanel) return;
            formPanel.classList.toggle('no-class-mode', isNoClass);
            formPanel.classList.toggle('class-data-mode', false);
            formPanel.classList.remove('circle-form-mode', 'view-form-mode');
            const message = document.getElementById('no-class-message');
            if (message) {
                message.textContent = messageText;
                message.style.display = isNoClass ? 'flex' : 'none';
            }
            const header = formPanel.querySelector('.label-form-header span');
            if (header) {
                header.textContent = isNoClass ? 'Datos de la clase' : 'Etiqueta';
            }
            if (isNoClass) {
                setLabelFormEditable(false);
            }
        }

        function setClassDataFormState(isClassData) {
            const formPanel = document.getElementById('label-form-panel');
            if (!formPanel) return;
            formPanel.classList.toggle('class-data-mode', isClassData);
            formPanel.classList.toggle('no-class-mode', false);
            const header = formPanel.querySelector('.label-form-header span');
            if (header) {
                header.textContent = isClassData ? 'Datos de la clase' : 'Etiqueta';
            }
            if (isClassData) {
                setLabelFormEditable(false);
            }
        }

        function openLabelEditorFor(label) {
            if (!label) return;
            selectedLabel = label;
            setNoClassFormState(false);
            document.getElementById('label-form-panel').classList.remove('view-form-mode');
            setLabelFormEditable(true);
            fillLabelFormFromSelection(label);
            openLabelForm();
        }

        function openAlertForm(alert) {
            if (!alert) return;
            selectedLabel = alert;
            setLabelFormEditable(false);
            setAlertFormMode(alert && alert.alertMode === 'simple' ? 'simple' : 'full');
            const professorInput = document.getElementById('label-profesor');
            const cursoInput = document.getElementById('label-curso');
            const gradoInput = document.getElementById('label-grado');
            const salonInput = document.getElementById('label-salon');
            const diaInput = document.getElementById('label-dia');
            const horaInicioInput = document.getElementById('label-hora-inicio');
            const horaFinInput = document.getElementById('label-hora-fin');
            const horaInput = document.getElementById('label-hora');
            const tituloInput = document.getElementById('label-titulo');
            const descripcionInput = document.getElementById('label-descripcion');
            const colorInput = document.getElementById('label-color');
            const sizeInput = document.getElementById('label-size');
            const markerSizeInput = document.getElementById('label-marker-size');
            const markerSizeValue = document.getElementById('label-marker-size-value');
            const alertChk = document.getElementById('label-is-alert');

            if (professorInput) professorInput.value = alert.profesor || '';
            if (cursoInput) cursoInput.value = alert.curso || '';
            if (gradoInput) gradoInput.value = alert.grado || '';
            if (salonInput) salonInput.value = alert.salon || '';
            if (diaInput) diaInput.value = alert.dia || '';
            if (horaInicioInput) horaInicioInput.value = alert.horaInicio || '';
            if (horaFinInput) horaFinInput.value = alert.horaFin || '';
            if (horaInput) horaInput.value = alert.hora || (alert.horaInicio && alert.horaFin ? `${alert.horaInicio} - ${alert.horaFin}` : '');
            if (tituloInput) tituloInput.value = alert.titulo || alert.title || '';
            if (descripcionInput) descripcionInput.value = alert.descripcion || alert.description || '';
            if (colorInput) colorInput.value = alert.color || '#22c55e';
            if (sizeInput) sizeInput.value = String(alert.fontSize || 18);
            if (markerSizeInput) markerSizeInput.value = String(alert.markerSize || 70);
            if (markerSizeValue) markerSizeValue.textContent = `${alert.markerSize || 70} px`;
            if (alertChk) alertChk.checked = true;
            openLabelForm();
        }

        function addAlertAtPosition(pitch, yaw) {
            if (!currentPano || !viewer) return;
            const pitchInput = document.getElementById('label-pitch');
            const yawInput = document.getElementById('label-yaw');
            if (pitchInput) pitchInput.value = String(Number(pitch) || 0);
            if (yawInput) yawInput.value = String(Number(yaw) || 0);
            addLabelFromForm();
        }

        function buildLabelText(label) {
            const explicitName = String(label?.title || label?.titulo || label?.text || '').trim();
            if (explicitName) return explicitName;

            if (label && (label.alertMode === 'simple' || label.type === 'simple-alert')) {
                return String(label.profesor || 'Lugar').trim() || 'Lugar';
            }

            const fallback = String(label?.profesor || label?.text || 'Lugar').trim();
            return fallback || 'Lugar';
        }

        function fillLabelFormFromSelection(label) {
            if (!label) return;
            const professorInput = document.getElementById('label-profesor');
            const cursoInput = document.getElementById('label-curso');
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
            if (cursoInput) cursoInput.value = label.curso || '';
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
                node.className = 'viewer-label' + (selectedLabel === label ? ' selected' : '');
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
                    selectedHS = null;
                    selectedCircle = null;
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

        function setWeeklyCalendar(day, time) {
            const dayInput = document.getElementById('label-dia');
            const timeInput = document.getElementById('weekly-calendar-time');
            const startTimeInput = document.getElementById('label-hora-inicio');
            calendarioDia = day || calendarioDia;
            calendarioHora = time || calendarioHora;
            if (dayInput && day) dayInput.value = day;
            if (timeInput && time) timeInput.value = time;
            if (startTimeInput && time) startTimeInput.value = time;
            document.querySelectorAll('.weekly-calendar-day').forEach(button => button.classList.toggle('active', button.dataset.day === day));
            if (selectedCircle) actualizarHorarioDelMarcador(selectedCircle);
        }

        function horarioEnMinutos(value) {
            const parts = String(value || '').slice(0, 5).split(':').map(Number);
            return parts.length === 2 && parts.every(Number.isFinite) ? parts[0] * 60 + parts[1] : -1;
        }

        function getHoraFinEfectivaHorario(item) {
            const horaFinSena = String(item?.hora_fin_sena || '').trim();
            if (horaFinSena) return horaFinSena;
            const hasta = String(item?.curso || '').match(/\bhasta\s+(\d{1,2}):([0-5]\d)\b/i);
            return hasta ? `${hasta[1].padStart(2, '0')}:${hasta[2]}` : (item?.hora_fin || '');
        }

        function diaNormalizado(value) {
            return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        }

        function profesorTieneClasesRestantes(profesor, dia = calendarioDia, hora = calendarioHora) {
            const nombre = String(profesor || '').trim().toLowerCase();
            if (!nombre) return false;
            const ahora = horarioEnMinutos(hora);
            if (ahora < 0) return false;
            const horariosDelDia = horariosColegio.filter(item => {
                return String(item.profesor || '').trim().toLowerCase() === nombre
                    && diaNormalizado(item.dia) === diaNormalizado(dia);
            });
            if (!horariosDelDia.length) return false;
            return horariosDelDia.some(item => {
                const fin = horarioEnMinutos(getHoraFinEfectivaHorario(item));
                return fin > ahora;
            });
        }

        function actualizarHorarioDelMarcador(marker) {
            if (!marker) return;
            if (marker.type === 'simple-alert' || marker.alertMode === 'simple') {
                setNoClassFormState(false);
                setClassDataFormState(false);
                return;
            }
            if (!calendarioDia || !calendarioHora || !horariosColegio.length) return;
            const markerRoom = String(marker.salon || '').trim().toLowerCase();
            if (!markerRoom) return;
            const selectedMinutes = horarioEnMinutos(calendarioHora);
            const horario = horariosColegio.find(item => {
                const start = horarioEnMinutos(item.hora_inicio);
                const end = horarioEnMinutos(getHoraFinEfectivaHorario(item));
                return String(item.salon || '').trim().toLowerCase() === markerRoom
                    && diaNormalizado(item.dia) === diaNormalizado(calendarioDia)
                    && selectedMinutes >= start && selectedMinutes < end;
            });
            const set = (id, value) => { const input = document.getElementById(id); if (input) input.value = value || ''; };
            if (!horario) {
                const profesorActual = String(document.getElementById('label-profesor')?.value || selectedCircle?.profesor || '').trim();
                if (profesorActual && profesorTieneClasesRestantes(profesorActual, calendarioDia, calendarioHora)) {
                    setNoClassFormState(true, `${profesorActual} está en descanso.`);
                    set('label-curso', '');
                    set('label-grado', '');
                    set('label-id-horario', '');
                    set('label-hora-inicio', '');
                    set('label-hora-fin', '');
                    set('label-dia', calendarioDia);
                    set('weekly-calendar-time', calendarioHora);
                    return;
                }
                const noClassMessage = profesorActual
                    ? `El profesor ${profesorActual} no tiene clase por el momento.`
                    : 'No hay clase por el momento.';
                setNoClassFormState(true, noClassMessage);
                set('label-curso', '');
                set('label-grado', '');
                set('label-id-horario', '');
                set('label-hora-inicio', '');
                set('label-hora-fin', '');
                set('label-dia', calendarioDia);
                set('weekly-calendar-time', calendarioHora);
                return;
            }
            setNoClassFormState(false);
            setClassDataFormState(true);
            const day = horario.dia === 'Miercoles' ? 'Miércoles' : horario.dia;
            set('label-profesor', horario.profesor);
            set('label-curso', horario.curso);
            set('label-grado', horario.grado);
            set('label-salon', horario.salon);
            set('label-dia', day);
            set('label-id-horario', horario.id_horario);
            set('label-hora-inicio', String(horario.hora_inicio || '').slice(0, 5));
            set('label-hora-fin', String(horario.hora_fin || '').slice(0, 5));
            document.querySelectorAll('.weekly-calendar-day').forEach(button => button.classList.toggle('active', button.dataset.day === day));
        }

        function setCurrentCalendarDefaults() {
            const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            const now = new Date();
            const time = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            setWeeklyCalendar(days[now.getDay()], time);
        }

        function openAlertPopup(alert) {
            if (!alert) return;
            selectedHS = null;
            selectedLabel = alert;
            selectedCircle = null;
            const formPanel = document.getElementById('label-form-panel');
            setNoClassFormState(false);
            formPanel.classList.add('view-form-mode');
            const header = formPanel.querySelector('.label-form-header span');
            if (header) header.textContent = 'Información del signo de exclamación';
            setAlertFormMode(alert && alert.alertMode === 'simple' ? 'simple' : 'full');
            setLabelFormEditable(false);
            const professorInput = document.getElementById('label-profesor');
            const cursoInput = document.getElementById('label-curso');
            const gradoInput = document.getElementById('label-grado');
            const salonInput = document.getElementById('label-salon');
            const diaInput = document.getElementById('label-dia');
            const horaInicioInput = document.getElementById('label-hora-inicio');
            const horaFinInput = document.getElementById('label-hora-fin');
            const horaInput = document.getElementById('label-hora');
            const tituloInput = document.getElementById('label-titulo');
            const descripcionInput = document.getElementById('label-descripcion');
            const colorInput = document.getElementById('label-color');
            const sizeInput = document.getElementById('label-size');
            const markerSizeInput = document.getElementById('label-marker-size');
            const markerSizeValue = document.getElementById('label-marker-size-value');
            const alertChk = document.getElementById('label-is-alert');

            if (professorInput) professorInput.value = alert.profesor || '';
            if (cursoInput) cursoInput.value = alert.curso || '';
            if (gradoInput) gradoInput.value = alert.grado || '';
            if (salonInput) salonInput.value = alert.salon || '';
            if (diaInput) diaInput.value = alert.dia || '';
            if (horaInicioInput) horaInicioInput.value = alert.horaInicio || '';
            if (horaFinInput) horaFinInput.value = alert.horaFin || '';
            if (horaInput) horaInput.value = alert.hora || (alert.horaInicio && alert.horaFin ? `${alert.horaInicio} - ${alert.horaFin}` : '');
            if (tituloInput) tituloInput.value = alert.titulo || alert.title || '';
            if (descripcionInput) descripcionInput.value = alert.descripcion || alert.description || '';
            if (colorInput) colorInput.value = alert.color || '#22c55e';
            if (sizeInput) sizeInput.value = String(alert.fontSize || 18);
            if (markerSizeInput) markerSizeInput.value = String(alert.markerSize || 70);
            if (markerSizeValue) markerSizeValue.textContent = `${alert.markerSize || 70} px`;
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
                        const markerSize = Number(alert.markerSize) || 70;
                        const fontSize = Number(alert.fontSize) || 18;
                        el.dataset.alertId = alert.id;
                        el.dataset.pitch = String(pitch);
                        el.dataset.yaw = String(yaw);
                        el.innerHTML = '<span>+</span><span class="info-marker-label">Info</span>';
                        el.title = alert.title || 'Aviso';
                        el.style.cursor = 'pointer';
                        el.style.display = 'flex';
                        el.style.alignItems = 'center';
                        el.style.justifyContent = 'center';
                        el.style.setProperty('width', `${markerSize}px`, 'important');
                        el.style.setProperty('height', `${markerSize}px`, 'important');
                        el.style.setProperty('font-size', `${fontSize}px`, 'important');
                        el.style.setProperty('background', alert.color || '#22c55e', 'important');
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
                empty.textContent = 'Sin puntos + Info';
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

        function getSelectedViewerObject() {
            if (selectedHS && currentPano?.hotspots?.includes(selectedHS)) return { type: 'hotspot', item: selectedHS };
            if (selectedLabel && currentPano?.alerts?.includes(selectedLabel)) return { type: 'alert', item: selectedLabel };
            if (selectedLabel && currentPano?.labels?.includes(selectedLabel)) return { type: 'label', item: selectedLabel };
            return null;
        }

        function copySelectedViewerObject() {
            if (!isEdit) return false;
            const selected = getSelectedViewerObject();
            if (!selected) return false;
            viewerObjectClipboard = { type: selected.type, item: JSON.parse(JSON.stringify(selected.item)) };
            updateSaveStatus('Objeto copiado. Pégalo en este panorama o en otro.', false);
            return true;
        }

        function pasteViewerObject() {
            if (!isEdit || !currentPano || !viewerObjectClipboard) return false;
            const copy = JSON.parse(JSON.stringify(viewerObjectClipboard.item));
            const uniqueId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;

            if (viewerObjectClipboard.type === 'hotspot') {
                copy.sourceImage = currentPano.id;
                currentPano.hotspots = currentPano.hotspots || [];
                currentPano.hotspots.push(copy);
                selectedHS = copy;
                selectedCircle = ['circle', 'exclamation', 'simple-alert'].includes(copy.type) ? copy : null;
                selectedLabel = null;
                renderHS();
                syncMenu();
            } else if (viewerObjectClipboard.type === 'alert') {
                copy.id = `alert-copy-${uniqueId}`;
                copy.sourceImage = currentPano.id;
                currentPano.alerts = currentPano.alerts || [];
                currentPano.alerts.push(copy);
                selectedHS = null;
                selectedCircle = null;
                selectedLabel = copy;
                renderAlerts();
            } else {
                copy.id = `label-copy-${uniqueId}`;
                currentPano.labels = currentPano.labels || [];
                currentPano.labels.push(copy);
                selectedHS = null;
                selectedCircle = null;
                selectedLabel = copy;M
                renderLabels();
            }

            markDirty();
            return true;
        }

        async function guardarCambiosInmediatos(mensaje) {
            try {
                if (saveTimer) {
                    clearTimeout(saveTimer);
                    saveTimer = null;
                }
                const copia = JSON.parse(JSON.stringify(panoramas));
                localStorage.setItem(STORAGE_KEY, JSON.stringify(copia));
                localStorage.setItem(PENDING_STORAGE_KEY, JSON.stringify(copia));
                updateSaveStatus('Guardando cambios...', true);
                const response = await fetch('api.php?action=guardar_panoramas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(copia)
                });
                const resultado = await response.json();
                if (!response.ok || !resultado.ok) {
                    throw new Error(resultado.error || 'El servidor no pudo guardar los cambios.');
                }
                localStorage.removeItem(PENDING_STORAGE_KEY);
                updateSaveStatus(mensaje || 'Guardado correctamente.', false);
                const formStatus = document.getElementById('alert-save-status');
                if (formStatus) formStatus.textContent = mensaje || 'Guardado correctamente.';
                return true;
            } catch (error) {
                console.error('Error al guardar el signo de exclamación:', error);
                updateSaveStatus('No se pudo guardar: ' + error.message, false);
                const formStatus = document.getElementById('alert-save-status');
                if (formStatus) formStatus.textContent = 'No se pudo guardar en el servidor.';
                alert('No se pudo guardar el signo de exclamación.');
                return false;
            }
        }

        async function addLabelFromForm() {
            const formModeSelect = document.getElementById('label-alert-form-mode');
            const alertMode = formModeSelect ? formModeSelect.value : 'full';
            const camposObligatorios = [
                ['label-profesor', 'Profesor'],
                ['label-curso', 'Curso / materia'],
                ['label-grado', 'Grado'],
                ['label-salon', 'Salón'],
                ['label-dia', 'Día'],
                ['label-hora-inicio', 'Hora inicio'],
                ['label-hora-fin', 'Hora finaliza']
            ];
            const isSimpleAlertMode = alertMode === 'simple';
            const camposVacios = isSimpleAlertMode ? [] : camposObligatorios.filter(([id]) => {
                const input = document.getElementById(id);
                return !input || !String(input.value || '').trim();
            });
            if (camposVacios.length > 0) {
                alert(`Campos obligatorios, rellénelos: ${camposVacios.map(([, nombre]) => nombre).join(', ')}.`);
                const primerCampo = document.getElementById(camposVacios[0][0]);
                if (primerCampo) primerCampo.focus();
                return;
            }
            const profesor = document.getElementById('label-profesor').value.trim();
            const curso = document.getElementById('label-curso')?.value.trim() || '';
            const grado = document.getElementById('label-grado').value.trim();
            const salon = document.getElementById('label-salon').value.trim();
            const dia = document.getElementById('label-dia').value || '';
            const horaInicioInput = document.getElementById('label-hora-inicio');
            const horaFinInput = document.getElementById('label-hora-fin');
            const horaInicio = horaInicioInput ? horaInicioInput.value || '' : '';
            const horaFin = horaFinInput ? horaFinInput.value || '' : '';
            const hora = horaInicio && horaFin ? `${horaInicio} - ${horaFin}` : horaInicio || horaFin || '';
            const titulo = document.getElementById('label-titulo').value.trim();
            const descripcion = isSimpleAlertMode ? '' : document.getElementById('label-descripcion')?.value.trim() || '';
            const color = document.getElementById('label-color').value || '#22c55e';
            if (!currentPano || !viewer) {
                updateSaveStatus('El visor todavía está cargando; no se pudo colocar el punto.', false);
                return;
            }
            const pitchInput = document.getElementById('label-pitch');
            const yawInput = document.getElementById('label-yaw');
            const pitchValue = Number(pitchInput && pitchInput.value);
            const yawValue = Number(yawInput && yawInput.value);
            const centerYaw = Number((viewer.getYaw && viewer.getYaw()) || 0);
            const centerPitch = Number((viewer.getPitch && viewer.getPitch()) || 0);
            const pitch = Number.isFinite(pitchValue) ? pitchValue : centerPitch;
            const yaw = Number.isFinite(yawValue) ? yawValue : centerYaw;
            const x = 50;
            const y = 50;
            const background = 'rgba(15, 23, 42, 0.75)';
            const isAlert = true;

            if (selectedCircle) {
                selectedCircle.profesor = profesor;
                selectedCircle.curso = curso;
                selectedCircle.grado = grado;
                selectedCircle.salon = salon;
                selectedCircle.dia = dia;
                selectedCircle.hora = hora;
                selectedCircle.idHorario = document.getElementById('label-id-horario')?.value || '';
                selectedCircle.horaInicio = horaInicio;
                selectedCircle.horaFin = horaFin;
                selectedCircle.titulo = titulo;
                selectedCircle.descripcion = descripcion;
                selectedCircle.title = titulo || profesor || selectedCircle.title || 'Aviso importante';
                selectedCircle.description = descripcion || [curso, profesor, grado, salon ? `Salón ${salon}` : '', dia, horaInicio && horaFin ? `${horaInicio} - ${horaFin}` : hora].filter(Boolean).join(' · ');
                selectedCircle.alertMode = alertMode;
                selectedCircle.color = color || selectedCircle.color || (selectedCircle.type === 'circle' ? 'green' : 'yellow');
                selectedCircle.fontSize = Number(document.getElementById('label-size').value || selectedCircle.fontSize || 18);
                selectedCircle.markerSize = Number(document.getElementById('label-marker-size').value || selectedCircle.markerSize || 70);

                const isPlaceMarker = ['exclamation', 'simple-alert'].includes(selectedCircle.type);
                const markerName = (profesor || titulo || '').trim();
                const selectedPlace = isSimpleAlertMode ? getSelectedDatabasePlace(profesor) : null;
                if (selectedPlace?.fuente === 'catalogo') {
                    selectedCircle.idLugar = selectedPlace.id;
                    delete selectedCircle.idLugar360;
                } else if (selectedPlace?.fuente === 'menu') {
                    selectedCircle.idLugar360 = selectedPlace.id;
                    delete selectedCircle.idLugar;
                } else if (isSimpleAlertMode) {
                    delete selectedCircle.idLugar;
                    delete selectedCircle.idLugar360;
                }
                if (isPlaceMarker && markerName && !selectedPlace) {
                    const saveFn = typeof window.guardarLugarEnBaseDeDatos === 'function' ? window.guardarLugarEnBaseDeDatos : null;
                    if (saveFn) {
                        const ok = await saveFn();
                        if (!ok) return;
                    }
                }

                renderHS();
                const guardado = await guardarCambiosInmediatos('El signo de exclamación se actualizó correctamente.');
                if (!guardado) return;
                selectedCircle = null;
                closeLabelForm();
                alert('El signo de exclamación se guardó correctamente.');
                return;
            }

            if (isSimpleAlertMode) {
                const nombreSimple = profesor.trim();
                if (!nombreSimple) {
                    alert('Escribe un nombre para guardar el lugar simple.');
                    return;
                }
                let selectedPlace = getSelectedDatabasePlace(nombreSimple);
                if (!selectedPlace && typeof window.guardarLugarEnBaseDeDatos === 'function') {
                    const ok = await window.guardarLugarEnBaseDeDatos();
                    if (!ok) return;
                    const catalogPlace = lugaresDisponibles.find(item =>
                        item.fuente === 'catalogo'
                        && String(item.titulo || '').trim().toLowerCase() === nombreSimple.toLowerCase()
                    );
                    if (catalogPlace) selectedPlace = { fuente: 'catalogo', id: Number(catalogPlace.id_lugar) };
                }
                const simpleHotspot = {
                    pitch,
                    yaw,
                    sourceImage: currentPano.id,
                    type: 'simple-alert',
                    targetId: currentPano.id,
                    direction: 'forward',
                    directionLabel: 'Adelante',
                    color,
                    w: 70,
                    h: 70,
                    rotate: 0,
                    tilt: 0,
                    profesor: nombreSimple,
                    title: nombreSimple,
                    alertMode: 'simple'
                };
                if (selectedPlace?.fuente === 'catalogo') simpleHotspot.idLugar = selectedPlace.id;
                if (selectedPlace?.fuente === 'menu') simpleHotspot.idLugar360 = selectedPlace.id;
                currentPano.hotspots = Array.isArray(currentPano.hotspots) ? currentPano.hotspots : [];
                currentPano.hotspots.push(simpleHotspot);
                renderHS();
                const guardado = await guardarCambiosInmediatos('El nombre del lugar se guardó y se colocó en el mapa.');
                if (!guardado) return;
                alertPlacementMode = false;
                closeLabelForm();
                alert('El nombre del lugar se guardó y se colocó en el mapa.');
                return;
            }

            if (isAlert) {
                const alertTitle = titulo || profesor || 'Aviso importante';
                const alertDescription = [curso, profesor, grado, salon ? `Salón ${salon}` : '', dia, horaInicio && horaFin ? `${horaInicio} - ${horaFin}` : hora, descripcion].filter(Boolean).join(' · ');
                if (!alertTitle && !alertDescription) {
                    alert('Selecciona un horario o completa al menos un dato del punto + Info.');
                    return;
                }
                if (typeof window.guardarLugarEnBaseDeDatos === 'function') {
                    const ok = await window.guardarLugarEnBaseDeDatos();
                    if (!ok) return;
                }
                currentPano.alerts = currentPano.alerts || [];
                currentPano.alerts.push({
                    id: `alert-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    title: alertTitle,
                    description: alertDescription || 'Sin información adicional.',
                    alertMode,
                    profesor: profesor,
                    curso: curso,
                    grado: grado,
                    salon: salon,
                    dia: dia,
                    hora: hora,
                    horaInicio: horaInicio,
                    horaFin: horaFin,
                    titulo: titulo,
                    descripcion: descripcion,
                    pitch,
                    yaw,
                    x,
                    y,
                    color,
                    fontSize: Number(document.getElementById('label-size').value || 18),
                    markerSize: Number(document.getElementById('label-marker-size').value || 70),
                    bg: 'rgba(101, 35, 18, 0.85)'
                });
                renderAlerts();
                const guardado = await guardarCambiosInmediatos('El signo de exclamación se guardó correctamente.');
                if (!guardado) return;
                alertPlacementMode = false;
                closeLabelForm();
                alert('El signo de exclamación se guardó correctamente.');
                return;
            }

            if (typeof window.guardarLugarEnBaseDeDatos === 'function') {
                const ok = await window.guardarLugarEnBaseDeDatos();
                if (!ok) return;
            }

            const fontSize = Number(document.getElementById('label-size').value || 12);
            currentPano.labels = currentPano.labels || [];
            const labelText = isSimpleAlertMode ? (profesor || titulo || 'Lugar') : `${profesor || titulo || 'Lugar'}${grado ? ` · ${grado}` : ''}${salon ? ` · Salón ${salon}` : ''}${dia ? ` · ${dia}` : ''}${horaInicio && horaFin ? ` · ${horaInicio} - ${horaFin}` : hora ? ` · ${hora}` : ''}`;
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
            const sourcePanoramas = loadedFromFile ? panoramas : null;
            try {
                const pending = localStorage.getItem(PENDING_STORAGE_KEY);
                if (pending) {
                    const parsedPending = JSON.parse(pending);
                    if (Array.isArray(parsedPending) && parsedPending.length) {
                        panoramas = normalizePanoramas(parsedPending);
                        if (sourcePanoramas && !localStorage.getItem(PENDING_IMPORT_KEY)) {
                            const sourcePanorama = sourcePanoramas.find(item => item.id === 'foto-catalogo-0181');
                            const sourceHotspot = sourcePanorama?.hotspots?.find(item =>
                                item.profesor === 'entrada_principal'
                                && Number(item.pitch) === 15.859890309188858
                                && Number(item.yaw) === -3.5903270130231535
                            );
                            const pendingPanorama = panoramas.find(item => item.id === 'foto-catalogo-0181');
                            if (sourceHotspot && pendingPanorama) {
                                pendingPanorama.hotspots = Array.isArray(pendingPanorama.hotspots) ? pendingPanorama.hotspots : [];
                                const pendingHotspot = pendingPanorama.hotspots.find(item =>
                                    Number(item.pitch) === Number(sourceHotspot.pitch)
                                    && Number(item.yaw) === Number(sourceHotspot.yaw)
                                    && item.profesor === sourceHotspot.profesor
                                );
                                if (pendingHotspot) {
                                    pendingHotspot.type = sourceHotspot.type;
                                    pendingHotspot.title = sourceHotspot.title;
                                    pendingHotspot.alertMode = sourceHotspot.alertMode;
                                    pendingHotspot.idLugar = sourceHotspot.idLugar;
                                } else {
                                    pendingPanorama.hotspots.push(JSON.parse(JSON.stringify(sourceHotspot)));
                                }
                                localStorage.setItem(PENDING_IMPORT_KEY, '1');
                            }
                        }
                    }
                }
            } catch (e) {
                console.warn('No se pudo recuperar la copia pendiente', e);
            }
            panoramas = panoramas.filter(p => {
                const path = String(p?.path || '');
                return !/^images\//i.test(path) && !/^imagenes del colegio\//i.test(path);
            });
            ensureAllPanels();
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(panoramas));
            } catch (e) {
                console.warn('No se pudo guardar la copia local inicial', e);
            }
        }

        async function loadImageCatalog() {
            try {
                const response = await fetch('api.php?action=imagenes', { cache: 'no-store' });
                if (!response.ok) throw new Error('No se pudo consultar la carpeta de imágenes');
                const payload = await response.json();
                const images = Array.isArray(payload.items) ? payload.items : [];
                const panoramasByPath = new Map(panoramas.map(p => [p.path, p]));
                const usedPanoramaIds = new Set(panoramas.map(p => p.id));

                images.forEach((image, index) => {
                    const existing = panoramasByPath.get(image.path) || panoramas.find(p => p.path === image.path);
                    if (existing) {
                        existing.path = image.path;
                        existing.title = `${image.folder} / ${image.title}`;
                        return;
                    }
                    let catalogIndex = index + 1;
                    let catalogId = `foto-catalogo-${String(catalogIndex).padStart(4, '0')}`;
                    while (usedPanoramaIds.has(catalogId)) {
                        catalogIndex += 1;
                        catalogId = `foto-catalogo-${String(catalogIndex).padStart(4, '0')}`;
                    }
                    usedPanoramaIds.add(catalogId);
                    panoramas.push({
                        id: catalogId,
                        title: `${image.folder} / ${image.title}`,
                        path: image.path,
                        hotspots: [],
                        initialYaw: 0,
                        initialPitch: 0,
                        forwardYaw: 0,
                        forwardPitch: 0,
                        backwardYaw: 0,
                        backwardPitch: 0,
                        leftYaw: 0,
                        leftPitch: 0,
                        rightYaw: 0,
                        rightPitch: 0
                    });
                });

                const status = document.getElementById('image-catalog-status');
                if (status) status.textContent = `${images.length} fotos encontradas en imagenes_del_colegio`;
            } catch (error) {
                console.warn('No se pudo cargar el catálogo de imágenes', error);
                const status = document.getElementById('image-catalog-status');
                if (status) status.textContent = 'No se pudo leer la carpeta de imágenes';
            }
        }

        let imageCatalogRefreshPromise = null;

        function refreshImageCatalogOptions() {
            if (!panoramas.length || imageCatalogRefreshPromise) return imageCatalogRefreshPromise;
            imageCatalogRefreshPromise = loadImageCatalog()
                .then(populateImageSelectors)
                .finally(() => { imageCatalogRefreshPromise = null; });
            return imageCatalogRefreshPromise;
        }

        window.addEventListener('focus', refreshImageCatalogOptions);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') refreshImageCatalogOptions();
        });

        function populateImageSelectors() {
            const folderSelect = document.getElementById('select-image-folder');
            const currentSelect = document.getElementById('select-current-image');
            const targetFolderSelect = document.getElementById('select-target-folder');
            const targetSelect = document.getElementById('select-target');
            const catalogPanoramas = panoramas.filter(p => /^imagenes_del_colegio\//i.test(p.path || ''));
            const folders = [...new Set(catalogPanoramas
                .map(p => (p.path || '').split('/')[1])
                .filter(Boolean))]
                .sort((a, b) => a.localeCompare(b, 'es'));

            if (folderSelect) {
                const previousFolder = folderSelect.value;
                folderSelect.innerHTML = '<option value="">Todas las carpetas</option>' + folders
                    .map(folder => `<option value="${folder}">${folder}</option>`)
                    .join('');
                if (folders.includes(previousFolder)) folderSelect.value = previousFolder;
            }

            const renderCurrentImages = () => {
                const selectedFolder = folderSelect?.value || '';
                const folderImages = selectedFolder
                    ? catalogPanoramas.filter(p => (p.path || '').split('/')[1] === selectedFolder)
                    : catalogPanoramas;
                const items = selectedFolder ? folderImages : panoramas;
                currentSelect.innerHTML = items.map(p => `<option value="${p.id}">${p.title}</option>`).join('');
                if (items.some(p => p.id === currentPano?.id)) currentSelect.value = currentPano.id;
            };

            if (currentSelect) {
                renderCurrentImages();
                currentSelect.value = currentPano.id;
                currentSelect.onchange = () => {
                    const selected = panoramas.find(p => p.id === currentSelect.value);
                    if (!selected) return;
                    currentPano = selected;
                    selectedHS = null;
                    init();
                    updateSourceLabel();
                };
            }
            if (folderSelect) folderSelect.onchange = renderCurrentImages;

            const renderTargetImages = () => {
                const selectedFolder = targetFolderSelect?.value || '';
                const items = selectedFolder
                    ? catalogPanoramas.filter(p => (p.path || '').split('/')[1] === selectedFolder)
                    : panoramas;
                const selectedTarget = targetSelect.value;
                targetSelect.innerHTML = items.map(p => `<option value="${p.id}">${p.title}</option>`).join('');
                if (items.some(p => p.id === selectedTarget)) targetSelect.value = selectedTarget;
            };

            if (targetFolderSelect) {
                const previousFolder = targetFolderSelect.value;
                targetFolderSelect.innerHTML = '<option value="">Todas las carpetas</option>' + folders
                    .map(folder => `<option value="${folder}">${folder}</option>`)
                    .join('');
                if (folders.includes(previousFolder)) targetFolderSelect.value = previousFolder;
                targetFolderSelect.onchange = renderTargetImages;
            }

            if (targetSelect) {
                renderTargetImages();
            }
        }

        function saveStartImage() {
            if (!currentPano) return;
            localStorage.setItem(START_IMAGE_KEY, currentPano.id);
            updateSaveStatus('Imagen inicial fijada: ' + currentPano.title, false);
        }

        function getArrivalView(fromPanoId, direction = null) {
            if (direction === 'forward') {
                return { yaw: currentPano.forwardYaw, pitch: currentPano.forwardPitch };
            }
            if (direction === 'backward') {
                return { yaw: currentPano.backwardYaw, pitch: currentPano.backwardPitch };
            }
            if (direction === 'left') {
                return { yaw: currentPano.leftYaw, pitch: currentPano.leftPitch };
            }
            if (direction === 'right') {
                return { yaw: currentPano.rightYaw, pitch: currentPano.rightPitch };
            }
            if (direction === 'down') {
                return { yaw: currentPano.initialYaw, pitch: currentPano.initialPitch };
            }
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

        function preloadPanorama(path) {
            if (!path) return Promise.resolve();
            if (!panoramaImageCache.has(path)) {
                const image = new Image();
                const promise = new Promise(resolve => {
                    image.onload = async () => {
                        try { if (image.decode) await image.decode(); } catch (error) { /* La imagen ya puede usarse aunque decode no esté disponible. */ }
                        resolve();
                    };
                    image.onerror = () => resolve();
                });
                image.src = path;
                panoramaImageCache.set(path, promise);
            }
            return panoramaImageCache.get(path);
        }

        function setPanoramaLoading(isLoading) {
            document.getElementById('panorama-loading')?.classList.toggle('visible', isLoading);
        }

        function syncIndicatorWithPanorama(pano) {
            if (!pano) return;
            const routeStep = routePath.find(step => step && step.fromId === pano.id);
            const startPoints = getMapPointsForPanoramaId(pano.id);
            const nextPoints = routeStep ? getMapPointsForPanoramaId(routeStep.toId) : [];
            let selectedPoint = null;

            if (routeStep && startPoints.length && nextPoints.length) {
                let nearestDistance = Number.POSITIVE_INFINITY;
                startPoints.forEach(startPoint => nextPoints.forEach(nextPoint => {
                    const distance = Math.hypot(startPoint.x - nextPoint.x, startPoint.y - nextPoint.y);
                    if (distance < nearestDistance) {
                        nearestDistance = distance;
                        selectedPoint = startPoint;
                    }
                }));
            } else {
                selectedPoint = minimapAdvancePoints.find(point => point.panoId === pano.id) || startPoints[0];
            }

            if (!selectedPoint) return;
            minimapIndicator = { x: selectedPoint.x, y: selectedPoint.y };
            renderMiniMap();
            updateMapViewport();
        }

        async function init(fromPanoId = null, arrivalDirection = null) {
            if (!currentPano || !currentPano.path) {
                updateSaveStatus('Sin panoramas cargados', false);
                return;
            }
            const panoToLoad = currentPano;
            syncIndicatorWithPanorama(panoToLoad);
            const requestId = ++viewerLoadRequest;
            const arrivalView = getRouteHotspotView(panoToLoad.id) || getArrivalView(fromPanoId, arrivalDirection);
            setPanoramaLoading(true);
            await preloadPanorama(panoToLoad.path);
            if (requestId !== viewerLoadRequest || currentPano !== panoToLoad) return;
            if (viewer) viewer.destroy();
            viewer = pannellum.viewer('panorama-container', {
                "type": "equirectangular",
                "panorama": panoToLoad.path,
                "yaw": Number.isFinite(Number(arrivalView.yaw)) ? Number(arrivalView.yaw) : 0,
                "pitch": Number.isFinite(Number(arrivalView.pitch)) ? Number(arrivalView.pitch) : 0,
                "autoLoad": true,
                // CSS evita la pantalla negra producida por WebGL en algunos equipos.
                "renderer": "css",
                "hfov": 90,
                "showControls": false
            });
            viewer.on('load', () => {
                setPanoramaLoading(false);
                renderHS(); renderAlerts(); if(typeof updateSourceLabel === 'function') updateSourceLabel(); renderMiniMap(); updateMinimap(); setupLeafletMiniMap(); updateLeafletMiniMap();
                focusRouteHotspot(false);
                panoToLoad.hotspots.forEach(hotspot => {
                    const target = panoramas.find(p => p.id === hotspot.targetId);
                    if (target) preloadPanorama(target.path);
                });
            });
            viewer.on('error', (error) => {
                setPanoramaLoading(false);
                console.error('No se pudo cargar la imagen del panorama', panoToLoad.path, error);
                updateSaveStatus('No se pudo cargar esta imagen: ' + panoToLoad.path, false);
            });
            viewer.on('viewchange', () => { requestAnimationFrame(updateRouteLine); requestAnimationFrame(updateMinimap); requestAnimationFrame(updateLeafletMiniMap); });
            const cont = document.getElementById('panorama-container');
            cont.onclick = (e) => {
                if (!isEdit || !alertPlacementMode || e.target.closest('.pnlm-hotspot')) return;
                const c = viewer && viewer.mouseEventToCoords ? viewer.mouseEventToCoords(e) : null;
                if (c) addAlertAtPosition(c[0], c[1]);
            };
            cont.ondblclick = (e) => {
                if (!isEdit || alertPlacementMode) return;
                const c = viewer.mouseEventToCoords(e);
                if (!c) return;
                const placementType = hotspotPlacementType === 'simple-alert' ? 'simple-alert' : hotspotPlacementType;
                addHS(c[0], c[1], placementType);
            };
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

        function openCircleForm(circle, editable = true) {
            const set = (id, value) => { const input = document.getElementById(id); if (input) input.value = value || ''; };
            const formPanel = document.getElementById('label-form-panel');
            setNoClassFormState(false);
            formPanel.classList.add('circle-form-mode');
            formPanel.classList.toggle('view-form-mode', !editable);
            const isSimple = circle.type === 'simple-alert' || circle.alertMode === 'simple';
            formPanel.classList.toggle('class-data-mode', !isSimple && circle.type === 'exclamation');
            setAlertFormMode(isSimple ? 'simple' : 'full');
            const suggestions = document.getElementById('sugerencias-profesores');
            if (suggestions) suggestions.innerHTML = '';
            setLabelFormEditable(editable);
            set('label-profesor', circle.profesor || circle.title || '');
            set('label-curso', circle.curso);
            set('label-grado', circle.grado);
            set('label-salon', circle.salon);
            set('label-dia', circle.dia);
            set('label-id-horario', circle.idHorario);
            set('label-hora-inicio', circle.horaInicio);
            set('label-hora-fin', circle.horaFin);
            if (!isSimple) actualizarHorarioDelMarcador(circle);
            set('label-titulo', circle.titulo || circle.title || '');
            set('label-descripcion', circle.descripcion || circle.description || '');
            document.getElementById('label-is-alert').checked = !!(circle.alertMode || circle.title || circle.titulo || circle.descripcion || circle.description);
            document.getElementById('label-form-panel').querySelector('.label-form-header span').textContent = isSimple ? 'Nombre del lugar' : (circle.type === 'exclamation' ? 'Datos de la clase' : 'Datos del círculo');
            openLabelForm();
        }

        function addHS(p, y, type = 'arrow') {
            const defaultTarget = document.getElementById('select-target').value || panoramas[0].id;
            const isSimpleAlert = type === 'simple-alert';
            const newH = { pitch: p, yaw: y, type, targetId: defaultTarget, sourceImage: currentPano.id, direction: 'forward', color: type === 'circle' ? 'green' : (type === 'exclamation' || isSimpleAlert) ? 'green' : 'white', w: (type === 'exclamation' || isSimpleAlert) ? 70 : 100, h: (type === 'exclamation' || isSimpleAlert) ? 70 : 100, rotate: 0, tilt: 0, idHorario: '', profesor: '', curso: '', grado: '', salon: '', dia: '', hora: '', horaInicio: '', horaFin: '', title: '', alertMode: isSimpleAlert ? 'simple' : 'full' };
            currentPano.hotspots.push(newH);
            selectedHS = newH;
            renderHS();
            syncMenu();
            markDirty();
        }

        function setRouteStatus(message, timeoutMs = 0) {
            const status = document.getElementById('route-status');
            const text = message || '';
            if (status) status.textContent = text;
            if (routeStatusTimeout) clearTimeout(routeStatusTimeout);
            if (timeoutMs > 0) {
                routeStatusTimeout = setTimeout(() => {
                    if (document.getElementById('route-status')) document.getElementById('route-status').textContent = '';
                }, timeoutMs);
            }
        }

        function clearActiveRoute() {
            routeTargetId = null;
            routePath = [];
            routeNextHotspot = null;
            const destination = document.getElementById('route-destination');
            if (destination) destination.value = '';
            setRouteStatus('');
            updateRouteLine();
            if (viewer && currentPano) renderHS();
            renderMiniMap();
            renderMapGuide();
        }

        function isActiveRouteHotspot(hs) {
            if (!hs) return false;
            const currentId = currentPano?.id;
            if (!routePath || !routePath.length) {
                return !!(routeTargetId && hs.targetId === routeTargetId && (!hs.sourceImage || hs.sourceImage === currentId));
            }
            const activeStep = routePath.find(step => step && step.fromId === currentId);
            if (!activeStep) return false;
            if (activeStep.hotspot === hs) return true;
            return activeStep.toId === hs.targetId
                && Math.abs(Number(activeStep.hotspot?.pitch) - Number(hs.pitch)) < 0.001
                && Math.abs(Number(activeStep.hotspot?.yaw) - Number(hs.yaw)) < 0.001;
        }

        function getRouteHotspotView(panoId = currentPano?.id) {
            const step = routePath.find(item => item && item.fromId === panoId);
            if (!step || !step.hotspot) return null;
            const yaw = Number(step.hotspot.yaw);
            const pitch = Number(step.hotspot.pitch);
            return Number.isFinite(yaw) && Number.isFinite(pitch) ? { yaw, pitch } : null;
        }

        function focusRouteHotspot(animated = true) {
            const routeView = getRouteHotspotView();
            if (!viewer || !routeView) return;
            viewer.setYaw(routeView.yaw, animated);
            viewer.setPitch(routeView.pitch, animated);
        }

        function getArrowSvgDataUri(color) {
            const svg = `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='${color}'><path d='M12 2L4.5 20.29L5.21 21L12 18L18.79 21L19.5 20.29L12 2Z'/></svg>`;
            return `url("data:image/svg+xml,${encodeURIComponent(svg)}")`;
        }

        function renderHS() {
            const cfg = viewer && viewer.getConfig ? viewer.getConfig() : null;
            if (cfg && cfg.hotSpots) [...cfg.hotSpots].forEach(h => viewer.removeHotSpot(h.id));

            currentPano.hotspots.forEach((hs, i) => {
                const isArrow = !hs.type || hs.type === 'arrow';
                const isRouteHotspot = isActiveRouteHotspot(hs);
                const isRouteArrow = isRouteHotspot && isArrow;
                const normalizedColor = isRouteHotspot || (isArrow && hs.color === 'green') ? 'blue' : (hs.color || 'white');
                const isExclamationLike = hs.type === 'exclamation' || hs.type === 'simple-alert';
                viewer.addHotSpot({
                    "id": "h"+i, "pitch": hs.pitch, "yaw": hs.yaw, "cssClass": hs.type === 'circle' ? 'circle-hotspot' : isExclamationLike ? 'exclamation-hotspot' : 'custom-arrow',
                    "createTooltipFunc": (el) => {
                        if (selectedHS === hs) el.classList.add('selected');
                        el.innerHTML = '';
                        const inner = document.createElement('div');
                        inner.className = (hs.type === 'circle' ? 'circle-inner' : isExclamationLike ? 'exclamation-inner' : 'arrow-inner') + (selectedHS === hs ? ' selected' : '');
                        if (isExclamationLike) inner.textContent = '!';
                        inner.style.width = hs.w + "px"; inner.style.height = hs.h + "px";
                        inner.style.transform = `rotate(${hs.rotate}deg) rotateX(${hs.tilt}deg)`;
                        const directionNames = { forward: 'Adelante', backward: 'Atrás', down: 'Abajo', right: 'Derecha', left: 'Izquierda' };
                        inner.title = `Dirección: ${directionNames[hs.direction] || 'Adelante'}`;
                        const f = { white: 'brightness(0) invert(1)', red: 'sepia(1) saturate(5) hue-rotate(-50deg)', blue: 'sepia(1) saturate(5) hue-rotate(180deg)', yellow: 'sepia(1) saturate(5) hue-rotate(10deg)', green: 'brightness(1.35) saturate(3) sepia(1) hue-rotate(95deg)' };
                        if (isExclamationLike) {
                            const colors = { white: '#ffffff', red: '#ef4444', blue: '#3b82f6', yellow: '#f59e0b', green: '#2d9f4d' };
                            const markerColor = colors[normalizedColor] || colors.green;
                            inner.style.background = markerColor;
                            inner.style.borderColor = markerColor;
                            inner.style.boxShadow = `0 0 0 3px ${markerColor}59, 0 4px 12px rgba(0, 0, 0, 0.45)`;
                            inner.style.filter = 'none';
                        } else {
                            const isBlueArrow = normalizedColor === 'blue' || normalizedColor === 'green';
                            const arrowColor = isBlueArrow ? '#38bdf8' : '#ffffff';
                            inner.style.backgroundImage = getArrowSvgDataUri(arrowColor);
                            inner.style.backgroundColor = 'transparent';
                            inner.style.filter = isBlueArrow ? 'none' : (f[normalizedColor] || f.white);
                            if (isRouteArrow) {
                                inner.style.setProperty('background-image', getArrowSvgDataUri('#38bdf8'), 'important');
                                inner.style.setProperty('background-color', 'transparent', 'important');
                                inner.style.setProperty('filter', 'none', 'important');
                            }
                        }
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
                        if (isEdit) {
                            selectedHS = hs;
                            selectedLabel = null;
                            selectedCircle = ['circle', 'exclamation', 'simple-alert'].includes(hs.type) ? hs : null;
                            if (selectedCircle) openCircleForm(selectedCircle, true);
                            renderHS();
                            syncMenu();
                        }
                        else {
                            if (['circle', 'exclamation', 'simple-alert'].includes(hs.type)) {
                                selectedCircle = hs;
                                openCircleForm(hs, false);
                                return;
                            }
                            const t = panoramas.find(p => p.id === hs.targetId);
                            if (t) {
                                const plannedStep = routePath[0];
                                const followsPlannedRoute = !!(plannedStep &&
                                    plannedStep.fromId === currentPano.id &&
                                    plannedStep.toId === t.id &&
                                    plannedStep.hotspot === hs);
                                currentPano = t;
                                if (routeTargetId) {
                                    if (currentPano.id === routeTargetId) {
                                        clearActiveRoute();
                                        setRouteStatus('Has llegado a tu destino.', 5000);
                                        renderMiniMap();
                                        updateLeafletMiniMap();
                                        renderMapGuide();
                                    } else {
                                        if (followsPlannedRoute) {
                                            routePath = routePath.slice(1);
                                        } else {
                                            routePath = findRoute(currentPano.id, routeTargetId);
                                        }
                                        routeNextHotspot = routePath[0] || null;
                                        if (routePath.length) {
                                            setRouteStatus(`Ruta: ${[currentPano.id, ...routePath.map(step => step.toId)].join(' → ')}`);
                                        } else {
                                            setRouteStatus('Se ha desviado, pero la ruta sigue buscando el destino correcto.');
                                        }
                                    }
                                }
                                init(hs.sourceImage || currentPano.id, hs.direction);
                                renderMapGuide();
                            }
                        }
                    }
                });
            });
            requestAnimationFrame(updateRouteLine);
        }

        function findRoute(fromId, targetId) {
            if (!targetId || fromId === targetId) return [];

            const graph = new Map();
            panoramas.forEach(pano => {
                const edges = (pano.hotspots || [])
                    .filter(hotspot => hotspot && hotspot.targetId && (!hotspot.type || hotspot.type === 'arrow'))
                    .map(hotspot => {
                        const fromPoints = getMapPointsForPanoramaId(pano.id);
                        const toPoints = getMapPointsForPanoramaId(hotspot.targetId);
                        let mapDistance = Number.POSITIVE_INFINITY;
                        fromPoints.forEach(fromPoint => toPoints.forEach(toPoint => {
                            mapDistance = Math.min(mapDistance, Math.hypot(fromPoint.x - toPoint.x, fromPoint.y - toPoint.y));
                        }));
                        return {
                            toId: hotspot.targetId,
                            hotspot,
                            weight: Number.isFinite(mapDistance) ? mapDistance : (Number(hotspot.distance) || 1)
                        };
                    })
                    .filter(Boolean)
                    .sort((a, b) => (a.weight || 1) - (b.weight || 1));
                graph.set(pano.id, edges);
            });

            const bestCost = new Map();
            const previous = new Map();
            const queue = [{ id: fromId, direction: null, cost: 0 }];
            const startKey = `${fromId}|null`;
            bestCost.set(startKey, 0);

            while (queue.length) {
                queue.sort((a, b) => a.cost - b.cost);
                const current = queue.shift();
                if (!current) break;

                const currentKey = `${current.id}|${current.direction ?? 'null'}`;
                const currentCost = bestCost.get(currentKey) ?? Number.POSITIVE_INFINITY;
                if (current.cost > currentCost + 1e-9) continue;
                if (current.id === targetId) break;

                for (const edge of graph.get(current.id) || []) {
                    const nextDirection = String(edge.hotspot?.direction || 'forward');
                    const baseDistanceWeight = Number(edge.weight) || 1;
                    let turnPenalty = 1 + baseDistanceWeight * 0.8;
                    if (nextDirection === 'backward') turnPenalty += 4;
                    if (nextDirection === 'down') turnPenalty += 2;
                    if (current.direction && current.direction !== nextDirection) turnPenalty += 1.5;
                    if ((nextDirection === 'left' || nextDirection === 'right') && current.direction && current.direction !== nextDirection) turnPenalty += 0.75;

                    const nextCost = currentCost + turnPenalty;
                    const nextKey = `${edge.toId}|${nextDirection}`;
                    if (nextCost < (bestCost.get(nextKey) ?? Number.POSITIVE_INFINITY)) {
                        bestCost.set(nextKey, nextCost);
                        previous.set(nextKey, { fromId: current.id, fromKey: currentKey, hotspot: edge.hotspot, toId: edge.toId });
                        queue.push({ id: edge.toId, direction: nextDirection, cost: nextCost });
                    }
                }
            }

            const targetCandidates = [...bestCost.entries()]
                .filter(([key]) => key.startsWith(`${targetId}|`))
                .sort((a, b) => a[1] - b[1]);
            const bestTarget = targetCandidates[0];
            if (!bestTarget) return [];

            const path = [];
            let cursorKey = bestTarget[0];
            while (cursorKey && cursorKey !== startKey) {
                const step = previous.get(cursorKey);
                if (!step) break;
                path.unshift({ fromId: step.fromId, hotspot: step.hotspot, toId: step.toId });
                cursorKey = step.fromKey;
            }
            return path;
        }

        function getForwardHotspot(pano) {
            const currentNumber = Number((pano.id.match(/\d+/) || [0])[0]);
            return pano.hotspots.find(h => {
                const targetNumber = Number(((h.targetId || '').match(/\d+/) || [0])[0]);
                return targetNumber > currentNumber;
            }) || pano.hotspots[0] || null;
        }

        let keyboardHistory = [];

        function recordKeyboardHistory(currentId, targetId, direction) {
            if (!currentId || !targetId || currentId === targetId) return;
            if (!keyboardHistory.length || keyboardHistory[keyboardHistory.length - 1] !== currentId) {
                keyboardHistory.push(currentId);
            }
            if (direction === 'backward') {
                const previous = keyboardHistory.length > 1 ? keyboardHistory[keyboardHistory.length - 2] : null;
                if (previous === targetId) {
                    keyboardHistory.pop();
                    return;
                }
            }
            if (keyboardHistory[keyboardHistory.length - 1] !== targetId) {
                keyboardHistory.push(targetId);
            }
        }

        function normalizeYawValue(value) {
            const num = Number(value);
            if (!Number.isFinite(num)) return 0;
            let normalized = num % 360;
            if (normalized < 0) normalized += 360;
            return normalized;
        }

        function yawDistance(a, b) {
            const diff = Math.abs(normalizeYawValue(a) - normalizeYawValue(b));
            return Math.min(diff, 360 - diff);
        }

        function resolveKeyboardTravelDirection(inputDirection) {
            if (!currentPano || !viewer || typeof viewer.getYaw !== 'function') return inputDirection;

            const currentYaw = normalizeYawValue(viewer.getYaw());
            const candidates = [
                { direction: 'forward', yaw: normalizeYawValue(currentPano.forwardYaw ?? currentPano.initialYaw ?? 0) },
                { direction: 'right', yaw: normalizeYawValue(currentPano.rightYaw ?? currentPano.initialYaw ?? 0) },
                { direction: 'backward', yaw: normalizeYawValue(currentPano.backwardYaw ?? currentPano.initialYaw ?? 0) },
                { direction: 'left', yaw: normalizeYawValue(currentPano.leftYaw ?? currentPano.initialYaw ?? 0) }
            ];

            const facing = candidates.reduce((best, candidate) => {
                if (!best) return candidate;
                return yawDistance(currentYaw, candidate.yaw) < yawDistance(currentYaw, best.yaw) ? candidate : best;
            }, candidates[0]).direction;

            const relativeMap = {
                forward: { forward: 'forward', backward: 'backward', left: 'left', right: 'right' },
                right: { forward: 'right', backward: 'left', left: 'forward', right: 'backward' },
                backward: { forward: 'backward', backward: 'forward', left: 'right', right: 'left' },
                left: { forward: 'left', backward: 'right', left: 'backward', right: 'forward' }
            };

            return relativeMap[facing]?.[inputDirection] || inputDirection;
        }

        function navigateWithKeyboard(direction) {
            if (!currentPano || !panoramas.length) return;

            const arrowHotspots = (currentPano.hotspots || []).filter(item =>
                item && item.targetId && (!item.type || item.type === 'arrow')
            );
            if (!arrowHotspots.length) return;

            const targetDirection = resolveKeyboardTravelDirection(direction);
            const availableByDirection = new Map(
                arrowHotspots.map(item => [String(item.direction || '').toLowerCase(), item])
            );

            const prioritizedDirections = [];
            const pushDirection = (value) => {
                if (!value) return;
                const normalized = String(value).toLowerCase();
                if (!prioritizedDirections.includes(normalized)) prioritizedDirections.push(normalized);
            };

            pushDirection(targetDirection);
            pushDirection(direction);
            if (direction === 'forward' || direction === 'backward') {
                pushDirection('forward');
                pushDirection('backward');
            }
            if (direction === 'left' || direction === 'right') {
                pushDirection('left');
                pushDirection('right');
            }

            let chosenMatch = null;
            for (const candidateDirection of prioritizedDirections) {
                if (!availableByDirection.has(candidateDirection)) continue;
                chosenMatch = availableByDirection.get(candidateDirection);
                break;
            }

            if (!chosenMatch && direction === 'backward' && keyboardHistory.length > 1) {
                const previousId = keyboardHistory[keyboardHistory.length - 2];
                const previousTarget = panoramas.find(p => p.id === previousId);
                if (previousTarget) {
                    const previousPanoId = currentPano.id;
                    currentPano = previousTarget;
                    routeTargetId = null;
                    routePath = [];
                    routeNextHotspot = null;
                    recordKeyboardHistory(previousPanoId, previousTarget.id, 'backward');
                    init(previousPanoId, 'backward');
                    renderMapGuide();
                }
                return;
            }

            if (!chosenMatch) return;

            const target = panoramas.find(p => p.id === chosenMatch.targetId) || null;
            if (!target) return;

            const previousPanoId = currentPano.id;
            currentPano = target;
            routeTargetId = null;
            routePath = [];
            routeNextHotspot = null;
            recordKeyboardHistory(previousPanoId, target.id, chosenMatch.direction || targetDirection);
            init(previousPanoId, chosenMatch.direction || targetDirection);
            renderMapGuide();
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

        function ensureMapPlaceIds() {
            const usedIds = new Set();
            (Array.isArray(mapGuide.places) ? mapGuide.places : []).forEach((place, index) => {
                if (!place.id || usedIds.has(String(place.id))) {
                    place.id = `map-place-${Date.now()}-${index}-${Math.random().toString(16).slice(2)}`;
                }
                usedIds.add(String(place.id));
            });
        }

        function migrateDatabasePlaceSize(place) {
            if (place.source !== 'catalogo' || Number(place.displaySizeVersion) >= 4) return false;
            place.w = 2.5;
            place.h = 2.5;
            place.displaySizeVersion = 4;
            return true;
        }

        function queueMapLocationsSave() {
            if (!minimapLocationsReady) return;
            ensureMapPlaceIds();
            if (minimapLocationsSaveTimer) clearTimeout(minimapLocationsSaveTimer);
            minimapLocationsSaveTimer = setTimeout(async () => {
                const locations = {
                    indicador: minimapIndicator ? { ...minimapIndicator } : null,
                    puntosAvance: minimapAdvancePoints.map(point => ({ ...point })),
                    puntosLugar: (mapGuide.places || []).map(place => ({ ...place }))
                };
                try {
                    const response = await fetch('api.php?action=guardar_ubicaciones_puntos', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(locations)
                    });
                    const result = await response.json();
                    if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudieron guardar las ubicaciones.');
                } catch (error) {
                    console.warn('No se pudieron guardar las ubicaciones en el servidor', error);
                }
            }, 350);
        }

        async function loadMiniMapState() {
            let syncImportedLocations = false;
            let locationsFileLoaded = false;
            try {
                if (localStorage.getItem(MINIMAP_LOCATIONS_VERSION_KEY) !== MINIMAP_LOCATIONS_VERSION) {
                    const response = await fetch(MINIMAP_LOCATIONS_FILE, { cache: 'no-store' });
                    if (response.ok) {
                        const imported = await response.json();
                        if (imported.indicador && Array.isArray(imported.puntosAvance)) {
                            locationsFileLoaded = true;
                            localStorage.setItem(MINIMAP_INDICATOR_KEY, JSON.stringify(imported.indicador));
                            const savedPoints = JSON.parse(localStorage.getItem(MINIMAP_ADVANCE_KEY) || 'null');
                            const syncedPoints = Array.isArray(savedPoints)
                                ? savedPoints.map(point => {
                                    const importedPoint = imported.puntosAvance.find(item => item.id && item.id === point.id);
                                    return importedPoint
                                        ? { ...point, x: importedPoint.x, y: importedPoint.y, panoId: importedPoint.panoId }
                                        : point;
                                })
                                : [];
                            const savedPointIds = new Set(syncedPoints.map(point => point.id).filter(Boolean));
                            imported.puntosAvance.forEach(point => {
                                if (!point.id || !savedPointIds.has(point.id)) syncedPoints.push(point);
                            });
                            localStorage.setItem(MINIMAP_ADVANCE_KEY, JSON.stringify(syncedPoints));
                            ensureMapPlaceIds();
                            const importedPlaces = Array.isArray(imported.puntosLugar) ? imported.puntosLugar : [];
                            const savedPlaces = Array.isArray(mapGuide.places) ? mapGuide.places : [];
                            const syncedPlaces = savedPlaces.map(place => {
                                const importedPlace = importedPlaces.find(item => item.id && item.id === place.id);
                                if (!importedPlace) return place;
                                const mergedPlace = { ...place, ...importedPlace };
                                if (Number(place.displaySizeVersion) > Number(importedPlace.displaySizeVersion)) {
                                    mergedPlace.w = place.w;
                                    mergedPlace.h = place.h;
                                    mergedPlace.displaySizeVersion = place.displaySizeVersion;
                                }
                                return mergedPlace;
                            });
                            const syncedPlaceIds = new Set(syncedPlaces.map(place => String(place.id)));
                            importedPlaces.forEach((place, index) => {
                                const importedPlace = { ...place };
                                migrateDatabasePlaceSize(importedPlace);
                                if (importedPlace.id && syncedPlaceIds.has(String(importedPlace.id))) return;
                                if (!importedPlace.id) {
                                    importedPlace.id = `map-place-import-${Date.now()}-${index}-${Math.random().toString(16).slice(2)}`;
                                }
                                syncedPlaceIds.add(String(importedPlace.id));
                                syncedPlaces.push(importedPlace);
                            });
                            mapGuide.places = syncedPlaces;
                            localStorage.setItem(MAP_GUIDE_KEY, JSON.stringify(mapGuide));
                            localStorage.setItem(MINIMAP_LOCATIONS_VERSION_KEY, MINIMAP_LOCATIONS_VERSION);
                            syncImportedLocations = true;
                        }
                    }
                }
                const savedIndicator = JSON.parse(localStorage.getItem(MINIMAP_INDICATOR_KEY) || 'null') || mapGuide.minimapIndicator;
                if (savedIndicator && Number.isFinite(Number(savedIndicator.x)) && Number.isFinite(Number(savedIndicator.y))) {
                    minimapIndicator = { x: clampNumber(Number(savedIndicator.x), 0, 100), y: clampNumber(Number(savedIndicator.y), 0, 100) };
                }
                const savedPoints = JSON.parse(localStorage.getItem(MINIMAP_ADVANCE_KEY) || 'null');
                const pointsToLoad = Array.isArray(savedPoints) ? savedPoints : mapGuide.minimapAdvancePoints;
                if (Array.isArray(pointsToLoad)) {
                    minimapAdvancePoints = pointsToLoad.filter(item => item && Number.isFinite(Number(item.x)) && Number.isFinite(Number(item.y))).map(item => ({
                        id: item.id || `advance-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                        x: clampNumber(Number(item.x), 0, 100),
                        y: clampNumber(Number(item.y), 0, 100),
                        panoId: typeof item.panoId === 'string' && item.panoId.trim() ? item.panoId.trim() : 'imagen1'
                    }));
                }
            } catch (error) {
                console.warn('No se pudo cargar el estado del mini mapa', error);
            }
            minimapLocationsReady = true;
            const hasSavedLocations = localStorage.getItem(MINIMAP_INDICATOR_KEY) !== null
                || localStorage.getItem(MINIMAP_ADVANCE_KEY) !== null;
            if (syncImportedLocations || locationsFileLoaded || hasSavedLocations || (mapGuide.places || []).length > 0) {
                queueMapLocationsSave();
            }
        }

        function saveMiniMapState() {
            try {
                localStorage.setItem(MINIMAP_INDICATOR_KEY, JSON.stringify(minimapIndicator));
                localStorage.setItem(MINIMAP_ADVANCE_KEY, JSON.stringify(minimapAdvancePoints));
                mapGuide.minimapIndicator = minimapIndicator;
                mapGuide.minimapAdvancePoints = minimapAdvancePoints;
                ensureMapPlaceIds();
                localStorage.setItem(MAP_GUIDE_KEY, JSON.stringify(mapGuide));
                queueMapLocationsSave();
            } catch (error) {
                console.warn('No se pudo guardar el estado del mini mapa', error);
            }
        }

        function setMiniMapPlacementMode(mode) {
            minimapPlacementMode = minimapPlacementMode === mode ? null : mode;
            document.getElementById('btn-place-indicator')?.classList.toggle('active', minimapPlacementMode === 'indicator');
            document.getElementById('btn-place-advance')?.classList.toggle('active', minimapPlacementMode === 'advance');
            document.getElementById('btn-place-panorama')?.classList.toggle('active', minimapPlacementMode === 'panorama');
            const label = document.getElementById('btn-place-indicator');
            if (label) label.textContent = minimapPlacementMode === 'indicator' ? '📍 Clic para ubicar indicador' : '📍 Colocar indicador';
            const advance = document.getElementById('btn-place-advance');
            if (advance) advance.textContent = minimapPlacementMode === 'advance' ? '🎯 Clic para ubicar punto' : '🎯 Colocar punto avance';
            const panorama = document.getElementById('btn-place-panorama');
            if (panorama) panorama.textContent = minimapPlacementMode === 'panorama' ? '📷 Clic en el mapa para colocar' : '📷 Colocar esta imagen en el mapa';
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
            } else if (minimapPlacementMode === 'panorama') {
                if (!currentPano) return;
                mapGuide.panoramaPositions = mapGuide.panoramaPositions || {};
                mapGuide.panoramaPositions[currentPano.id] = {
                    x,
                    y,
                    title: currentPano.title || currentPano.id,
                    path: currentPano.path
                };
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
            const hasActiveRoute = !!routeTargetId && (routeTargetId === currentPano?.id || routePath.length > 0);
            const activeRoutePanoIds = new Set(hasActiveRoute ? [
                currentPano?.id,
                ...routePath.flatMap(step => [step.fromId, step.toId]),
                routeTargetId
            ].filter(Boolean) : []);
            const targetRoutePoint = hasActiveRoute ? getMapPointsForPanoramaId(routeTargetId)[0] : null;
            const activeRoutePoints = targetRoutePoint && minimapIndicator
                ? buildRouteLinePoints(minimapIndicator, targetRoutePoint)
                : [];

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
            Object.entries(mapGuide.panoramaPositions || {}).forEach(([id, position]) => {
                const panorama = panoramas.find(item => item.id === id);
                if (!panorama) return;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'minimap__node panorama-node' + (currentPano && currentPano.id === id ? ' current' : '');
                button.style.left = `${position.x}%`;
                button.style.top = `${position.y}%`;
                button.title = panorama.title || id;
                button.onclick = () => {
                    currentPano = panorama;
                    selectedHS = null;
                    init();
                    updateSourceLabel();
                    renderMiniMap();
                };
                nodesWrap.appendChild(button);
            });

            minimapAdvancePoints.forEach(point => {
                const advance = document.createElement('button');
                advance.type = 'button';
                advance.className = 'minimap-route-node';
                advance.style.left = point.x + '%';
                advance.style.top = point.y + '%';
                advance.style.width = '12px';
                advance.style.height = '12px';
                const isOnActiveRoute = activeRoutePanoIds.has(point.panoId) || activeRoutePoints.some(routePoint =>
                    Math.abs(routePoint.x - Number(point.x)) < 0.001 && Math.abs(routePoint.y - Number(point.y)) < 0.001
                );
                advance.style.background = isOnActiveRoute ? '#2563eb' : '#f59e0b';
                advance.style.border = '2px solid #fff';
                advance.style.boxShadow = isOnActiveRoute ? '0 0 0 3px rgba(37,99,235,0.4)' : '0 0 0 3px rgba(245,158,11,0.35)';
                advance.title = 'Punto de avance para ' + (point.panoId || '?') + (isOnActiveRoute ? ' (ruta activa)' : '');
                advance.onclick = (event) => {
                    event.stopPropagation();
                    if (isEdit) return;
                    const target = panoramas.find(pano => pano.id === point.panoId);
                    if (!target) return;
                    currentPano = target;
                    init();
                };
                nodesWrap.appendChild(advance);
            });

            if (minimapIndicator) {
                userMarker.style.left = minimapIndicator.x + '%';
                userMarker.style.top = minimapIndicator.y + '%';
            }
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
            updateMapViewport();
            updateLeafletMiniMap();
        }

        function updateMapViewport() {
            const wrapper = document.getElementById('mapa-google-wrapper');
            const surface = document.getElementById('mapa-google-surface');
            if (!wrapper || !surface) return;
            if (wrapper.closest('#map-guide-modal')) {
                surface.classList.remove('map-cropped');
                surface.style.transform = 'none';
                return;
            }
            const zoom = Math.max(100, Number(mapSettings.zoom) || 200);
            const scale = zoom / 100;
            const point = mapSettings.followIndicator ? (minimapIndicator || { x: 50, y: 50 }) : { x: mapSettings.viewX, y: mapSettings.viewY };
            const minOffset = (1 / scale - 1) * 100;
            const left = Math.max(minOffset, Math.min(0, (50 / scale) - Number(point.x)));
            const top = Math.max(minOffset, Math.min(0, (50 / scale) - Number(point.y)));
            surface.style.width = `${zoom}%`;
            surface.style.height = `${zoom}%`;
            surface.style.transform = `translate(${left}%, ${top}%)`;
        }

        function updateRouteLine() {
            const svg = document.getElementById('route-line');
            if (svg) svg.style.display = 'none';
            const pathEl = document.getElementById('route-path');
            const endEl = document.getElementById('route-end');
            if (pathEl) pathEl.setAttribute('d', '');
            if (endEl) endEl.style.display = 'none';
        }

        function showRoute() {
            const input = document.getElementById('route-destination');
            const typed = (input ? (input.value || '').trim().toLowerCase() : '');
            const target = panoramas.find(p => p.id.toLowerCase() === typed.replace(/\s+/g, '') || p.id.toLowerCase() === typed.replace('panel', 'imagen').replace(/\s+/g, '') || p.title.toLowerCase() === typed);
            const status = document.getElementById('route-status');
            if (!target) {
                routeTargetId = null; routePath = []; routeNextHotspot = null; updateRouteLine();
                if (status) setRouteStatus('No encontré esa imagen.');
                renderMiniMap();
                renderMapGuide();
                return;
            }
            routeTargetId = target.id;
            routePath = findRoute(currentPano.id, routeTargetId);
            if (!routePath.length) {
                routeNextHotspot = null;
                updateRouteLine();
                if (currentPano.id === routeTargetId) {
                    clearActiveRoute();
                    setRouteStatus('Has llegado a tu destino.', 5000);
                } else if (status) {
                    setRouteStatus('No hay una ruta con las flechas actuales.');
                }
                renderMiniMap();
                renderMapGuide();
                return;
            }
            routeNextHotspot = routePath[0];
            if (status) setRouteStatus(`Ruta: ${[currentPano.id, ...routePath.map(step => step.toId)].join(' → ')}`);
            syncIndicatorWithPanorama(currentPano);
            focusRouteHotspot(true);
            renderHS();
            renderMiniMap();
            renderMapGuide();
        }

        async function cargarLugaresGuardados() {
            const select = document.getElementById('select-lugares-guardados');
            if (!select) return;
            try {
                const panoramaId = currentPano && currentPano.id ? currentPano.id : '';
                const url = panoramaId ? `api.php?action=lugares&panorama_id=${encodeURIComponent(panoramaId)}` : 'api.php?action=lugares';
                const response = await fetch(url, { cache: 'no-store' });
                const payload = await response.json();
                const items = Array.isArray(payload?.items) ? payload.items : [];
                select.innerHTML = '<option value="">Selecciona un lugar</option>';
                items.forEach(item => {
                    const option = document.createElement('option');
                    option.value = String(item.id_lugar ?? '');
                    const title = String(item.titulo || item.materia || item.salon || 'Lugar').trim();
                    option.textContent = title;
                    select.appendChild(option);
                });
            } catch (error) {
                console.warn('No se pudieron cargar los lugares guardados', error);
                select.innerHTML = '<option value="">No hay lugares guardados</option>';
            }
        }

        async function guardarLugarEnBaseDeDatos() {
            const isSimpleMode = document.getElementById('label-alert-form-mode')?.value === 'simple';
            const titulo = (document.getElementById('label-profesor')?.value.trim() || document.getElementById('label-titulo')?.value.trim() || '').trim();
            const descripcion = document.getElementById('label-descripcion')?.value.trim() || '';
            const horarioIdValue = document.getElementById('label-id-horario')?.value;
            const horarioId = horarioIdValue ? Number(horarioIdValue) : null;
            const pitchInput = document.getElementById('label-pitch');
            const yawInput = document.getElementById('label-yaw');
            const pitch = Number(pitchInput && pitchInput.value ? pitchInput.value : 0);
            const yaw = Number(yawInput && yawInput.value ? yawInput.value : 0);

            if (!currentPano || !currentPano.id) {
                alert('Primero selecciona una imagen del panorama.');
                return false;
            }
            if (!titulo) {
                alert('Escribe un nombre para guardar el lugar.');
                return false;
            }
            if (!isSimpleMode && (!horarioId || !Number.isFinite(horarioId))) {
                alert('Debes seleccionar un horario válido antes de guardar.');
                return false;
            }

            try {
                const response = await fetch('api.php?action=guardar_lugar', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        panorama_id: currentPano.id,
                        titulo,
                        descripcion,
                        id_horario: isSimpleMode ? null : horarioId,
                        pitch,
                        yaw
                    })
                });
                const payload = await response.json();
                if (!response.ok || !payload?.ok) {
                    throw new Error(payload?.error || 'No se pudo guardar el lugar.');
                }
                await cargarLugaresGuardados();
                if (typeof cargarSugerenciasNombresLugares === 'function') {
                    await cargarSugerenciasNombresLugares();
                }
                updateSaveStatus('Lugar guardado correctamente.', false);
                alert('Lugar guardado correctamente.');
                return true;
            } catch (error) {
                console.error('guardarLugarEnBaseDeDatos', error);
                alert(error.message || 'No se pudo guardar el lugar.');
                return false;
            }
        }

        function getDayNameFromCurrentCalendar() {
            const selectedDay = document.getElementById('label-dia')?.value || calendarioDia || '';
            return selectedDay || '';
        }

        function getMinutesFromCurrentCalendar() {
            const selectedTime = document.getElementById('weekly-calendar-time')?.value || calendarioHora || '';
            return getMinutesFromTime(selectedTime);
        }

        function getMinutesFromTime(value) {
            if (!value) return null;
            const match = /^([01]?\d|2[0-3]):([0-5]\d)$/.exec(String(value));
            if (!match) return null;
            return Number(match[1]) * 60 + Number(match[2]);
        }

        function getGradoBreakStatus(grado, hora = calendarioHora || document.getElementById('weekly-calendar-time')?.value || '') {
            const value = String(grado || '').trim();
            const match = value.match(/(\d+)/);
            if (!match) return null;
            const numero = Number(match[1]);
            const ahora = horarioEnMinutos(hora);
            if (!Number.isFinite(ahora) || ahora < 0) return null;

            const descansos = numero >= 10 && numero <= 11
                ? [
                    { inicio: '09:55', fin: '10:25' },
                    { inicio: '12:15', fin: '13:10' }
                ]
                : numero >= 6 && numero <= 9
                    ? [{ inicio: '09:00', fin: '09:30' }]
                    : [];

            for (const descanso of descansos) {
                const inicio = horarioEnMinutos(descanso.inicio);
                const fin = horarioEnMinutos(descanso.fin);
                if (ahora >= inicio && ahora <= fin) {
                    return { inicio: descanso.inicio, fin: descanso.fin };
                }
            }

            return null;
        }

        function getHorarioCoincidentePorGrado(grado, dia = getDayNameFromCurrentCalendar(), hora = calendarioHora || document.getElementById('weekly-calendar-time')?.value || '') {
            const value = String(grado || '').trim();
            if (!value) return null;
            const ahora = horarioEnMinutos(hora);
            if (!Number.isFinite(ahora) || ahora < 0) return null;

            const candidatos = horariosColegio.filter(item => {
                const itemGrado = String(item.grado || '').trim().toLowerCase();
                const itemDia = String(item.dia || '').trim();
                if (itemGrado !== value.toLowerCase()) return false;
                if (dia && diaNormalizado(itemDia) !== diaNormalizado(dia)) return false;
                const start = horarioEnMinutos(item.hora_inicio);
                const end = horarioEnMinutos(getHoraFinEfectivaHorario(item));
                if (!Number.isFinite(start) || !Number.isFinite(end)) return false;
                if (String(item.curso || '').trim().toLowerCase() === 'libre') return false;
                return ahora >= start && ahora < end;
            });
            if (candidatos.length) {
                return candidatos.sort((a, b) => horarioEnMinutos(a.hora_inicio) - horarioEnMinutos(b.hora_inicio))[0];
            }

            return null;
        }

        function getMensajeSinClaseParaGrado(grado, dia, hora) {
            const ahora = horarioEnMinutos(hora);
            if (!dia || ahora < 0) return `Selecciona el día y la hora para comprobar el horario del grado ${grado}.`;
            const gradoBuscado = String(grado || '').trim().toLowerCase();

            const horariosDelDia = horariosColegio.filter(item => {
                const itemGrado = String(item.grado || '').trim().toLowerCase();
                const itemDia = String(item.dia || '').trim();
                if (itemGrado !== gradoBuscado) return false;
                if (dia && diaNormalizado(itemDia) !== diaNormalizado(dia)) return false;
                const start = horarioEnMinutos(item.hora_inicio);
                const end = horarioEnMinutos(getHoraFinEfectivaHorario(item));
                if (!Number.isFinite(start) || !Number.isFinite(end)) return false;
                return true;
            });

            if (!horariosDelDia.length) return `No hay jornada programada para el grado ${grado} el ${dia}.`;

            const starts = horariosDelDia.map(item => horarioEnMinutos(item.hora_inicio));
            const ends = horariosDelDia.map(item => horarioEnMinutos(getHoraFinEfectivaHorario(item)));
            const primeraHora = Math.min(...starts);
            const ultimaHora = Math.max(...ends);
            const formatTime = minutes => `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;

            if (ahora < primeraHora) return `La jornada del grado ${grado} aún no empieza; inicia a las ${formatTime(primeraHora)}.`;
            if (ahora >= ultimaHora) return `La jornada del grado ${grado} ya terminó a las ${formatTime(ultimaHora)}.`;
            return `El grado ${grado} no está en clase el ${dia} a las ${hora}.`;
        }

        function mostrarAlertaHorario(message) {
            const overlay = document.getElementById('schedule-alert-overlay');
            const messageElement = document.getElementById('schedule-alert-message');
            if (!overlay || !messageElement) return;
            messageElement.textContent = message;
            overlay.setAttribute('aria-hidden', 'false');
            overlay.classList.add('open');
            document.getElementById('schedule-alert-close')?.focus();
        }

        function cerrarAlertaHorario() {
            const overlay = document.getElementById('schedule-alert-overlay');
            if (!overlay) return;
            overlay.classList.remove('open');
            overlay.setAttribute('aria-hidden', 'true');
        }

        function findBestSalonMatch(salon, grado = '') {
            const expectedSalon = normalizarNombreSalon(salon).toLowerCase();
            const expectedGrado = String(grado || '').trim().toLowerCase();
            if (!expectedSalon) return null;
            const entries = getQuickNavEntries();
            const exactMatches = entries.filter(entry => {
                const entrySalon = normalizarNombreSalon(entry.salon).toLowerCase();
                if (entrySalon !== expectedSalon) return false;
                if (expectedGrado && String(entry.grado || '').trim().toLowerCase() && String(entry.grado || '').trim().toLowerCase() !== expectedGrado) {
                    return false;
                }
                return true;
            });
            if (exactMatches.length) {
                return exactMatches.sort((a, b) => {
                    const aGrade = String(a.grado || '').trim().toLowerCase() === expectedGrado ? 0 : 1;
                    const bGrade = String(b.grado || '').trim().toLowerCase() === expectedGrado ? 0 : 1;
                    return aGrade - bGrade;
                })[0];
            }
            return entries.find(entry => normalizarNombreSalon(entry.salon).toLowerCase() === expectedSalon) || null;
        }

        function getQuickNavEntries() {
            const entries = [];
            panoramas.forEach(pano => {
                (pano.hotspots || []).forEach(hs => {
                    if (!hs) return;
                    const label = String(hs.profesor || hs.grado || hs.salon || hs.title || hs.titulo || '').trim();
                    if (!label) return;
                    entries.push({
                        id: hs.id || `${pano.id}-${hs.pitch}-${hs.yaw}`,
                        panoId: pano.id,
                        sourceImage: hs.sourceImage || pano.id,
                        type: hs.type,
                        profesor: hs.profesor || '',
                        grado: hs.grado || '',
                        salon: hs.salon || '',
                        title: hs.title || hs.titulo || label,
                        dia: hs.dia || '',
                        horaInicio: hs.horaInicio || '',
                        horaFin: hs.horaFin || ''
                    });
                });
                (pano.labels || []).forEach(label => {
                    if (!label || !label.text) return;
                    entries.push({
                        id: label.id || `${pano.id}-label-${Math.random().toString(16).slice(2)}`,
                        panoId: pano.id,
                        sourceImage: pano.id,
                        type: 'label',
                        profesor: label.profesor || '',
                        grado: label.grado || '',
                        salon: label.salon || '',
                        title: String(label.text || label.titulo || '').trim(),
                        dia: label.dia || '',
                        horaInicio: label.horaInicio || '',
                        horaFin: label.horaFin || ''
                    });
                });
                (pano.alerts || []).forEach(alert => {
                    if (!alert) return;
                    const title = String(alert.title || alert.titulo || alert.profesor || alert.grado || alert.salon || 'Aviso').trim();
                    if (!title) return;
                    const isSimplePlace = alert.alertMode === 'simple' || alert.type === 'simple-alert';
                    entries.push({
                        id: alert.id || `${pano.id}-alert-${Math.random().toString(16).slice(2)}`,
                        panoId: pano.id,
                        sourceImage: pano.id,
                        type: isSimplePlace ? 'simple-alert' : 'alert',
                        profesor: isSimplePlace ? '' : (alert.profesor || ''),
                        grado: isSimplePlace ? '' : (alert.grado || ''),
                        salon: isSimplePlace ? '' : (alert.salon || ''),
                        title: isSimplePlace ? title : title,
                        dia: alert.dia || '',
                        horaInicio: alert.horaInicio || '',
                        horaFin: alert.horaFin || ''
                    });
                });
            });

            lugaresBaseDeDatos.forEach(lugar => {
                const title = String(lugar.titulo || '').trim();
                if (!title) return;
                entries.push({
                    id: `db-lugar-${lugar.id_lugar || title}`,
                    panoId: lugar.panorama_id || panoramas[0]?.id || currentPano?.id || '',
                    sourceImage: lugar.panorama_id || panoramas[0]?.id || currentPano?.id || '',
                    type: 'database-place',
                    profesor: lugar.profesor || '',
                    grado: lugar.grado || '',
                    salon: lugar.salon || '',
                    title,
                    dia: '',
                    horaInicio: '',
                    horaFin: ''
                });
            });

            (mapGuide.places || []).forEach((place, index) => {
                const title = String(place?.name || '').trim();
                if (!title) return;
                const destination = findNearestPanoramaForMapPoint(place);
                if (!destination) return;
                entries.push({
                    id: `map-guide-place-${index}`,
                    panoId: destination.id,
                    sourceImage: destination.id,
                    type: 'map-place',
                    profesor: '',
                    grado: '',
                    salon: /^sal[oó]n\b/i.test(title) ? title : '',
                    title,
                    dia: '',
                    horaInicio: '',
                    horaFin: ''
                });
            });
            return entries;
        }

        function findNearestPanoramaForMapPoint(mapPoint) {
            let nearest = null;
            let nearestDistance = Number.POSITIVE_INFINITY;
            panoramas.forEach(panorama => {
                getMapPointsForPanoramaId(panorama.id).forEach(point => {
                    const distance = Math.hypot(Number(point.x) - Number(mapPoint.x), Number(point.y) - Number(mapPoint.y));
                    if (distance < nearestDistance) {
                        nearest = panorama;
                        nearestDistance = distance;
                    }
                });
            });
            return nearest;
        }

        function normalizarNombreSalon(salon) {
            const value = String(salon || '').trim();
            if (/^\d+$/.test(value)) return `Salón ${value}`;
            return value.replace(/^sal[oó]n\b\s*/i, 'Salón ').trim();
        }

        function getQuickNavEntryValue(category, entry) {
            const value = category === 'grado' ? entry.grado : category === 'salon' ? entry.salon : category === 'profesor' ? entry.profesor : entry.title;
            return category === 'salon' ? normalizarNombreSalon(value) : String(value || '').trim();
        }

        function compararNombresSalon(a, b) {
            const numberA = String(a).match(/^sal[oó]n\s+(\d+)\b/i);
            const numberB = String(b).match(/^sal[oó]n\s+(\d+)\b/i);
            if (numberA && numberB) {
                return Number(numberA[1]) - Number(numberB[1]) || a.localeCompare(b, 'es');
            }
            if (numberA) return -1;
            if (numberB) return 1;
            return a.localeCompare(b, 'es', { sensitivity: 'base' });
        }

        function compararNombresGrado(a, b) {
            const gradeA = String(a).match(/^(\d+)\s*[-–]\s*(\d+)$/);
            const gradeB = String(b).match(/^(\d+)\s*[-–]\s*(\d+)$/);
            if (gradeA && gradeB) {
                return Number(gradeA[1]) - Number(gradeB[1])
                    || Number(gradeA[2]) - Number(gradeB[2])
                    || a.localeCompare(b, 'es');
            }
            if (gradeA) return -1;
            if (gradeB) return 1;
            return a.localeCompare(b, 'es', { sensitivity: 'base' });
        }

        function normalizarClaveLugar(value) {
            const key = diaNormalizado(value).replace(/\s+/g, ' ').trim();
            const aliases = {
                aseo: 'banos y aseo',
                banos: 'banos y aseo',
                'banos y aseo': 'banos y aseo',
                cordinacion: 'coordinacion',
                'sala profesores': 'sala de profesores'
            };
            return aliases[key] || key;
        }

        function populateQuickNavOptions() {
            const category = document.getElementById('quick-nav-category')?.value || 'grado';
            const target = document.getElementById('quick-nav-target');
            if (!target) return;
            if (category === 'lugar') {
                target.innerHTML = '<option value="">Selecciona una opción</option>';
                [...lugaresCatalogo]
                    .sort((a, b) => a.titulo.localeCompare(b.titulo, 'es'))
                    .forEach(lugar => {
                        const option = document.createElement('option');
                        option.value = lugar.titulo;
                        option.textContent = lugar.titulo;
                        target.appendChild(option);
                    });
                return;
            }
            const entries = getQuickNavEntries();
            const values = new Set();

            if (category === 'grado') {
                gradosColegioDesdeBase.forEach(value => values.add(value));
                horariosColegio.forEach(horario => {
                    const value = String(horario.grado || '').trim();
                    if (value && value !== '-') values.add(value);
                });
            }

            if (category === 'salon') {
                horariosColegio.forEach(horario => {
                    const salon = String(horario.salon || '').trim();
                    if (salon && salon !== '-') values.add(normalizarNombreSalon(salon));
                });
            }
            entries.forEach(entry => {
                if (category === 'profesor' && (entry.type === 'database-place' || entry.type === 'simple-alert')) return;
                const value = getQuickNavEntryValue(category, entry);
                if (value && !(category === 'grado' && value === '-')) values.add(value);
            });
            const compare = category === 'salon'
                ? compararNombresSalon
                : category === 'grado'
                    ? compararNombresGrado
                    : (a, b) => a.localeCompare(b, 'es');
            const list = [...values].sort(compare);
            target.innerHTML = '<option value="">Selecciona una opción</option>';
            list.forEach(value => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                target.appendChild(option);
            });
        }

        function actualizarOpcionesGradoDesdeBase() {
            return completarHorariosFaltantesDesdeBase().then(() => {
                configurarHorariosColegio();
                populateQuickNavOptions();
            });
        }

        function buscarUbicacionEnMenuVista() {
            clearActiveRoute();
            const category = document.getElementById('quick-nav-category')?.value || 'grado';
            const target = document.getElementById('quick-nav-target');
            const status = document.getElementById('quick-nav-status');
            const selectedValue = target ? target.value.trim() : '';
            const selectedDay = getDayNameFromCurrentCalendar();
            const selectedMinutes = getMinutesFromCurrentCalendar();
            if (!selectedValue) {
                if (status) status.textContent = 'Elige una opción del menú de vista.';
                return;
            }

            if (category === 'grado') {
                const descanso = getGradoBreakStatus(selectedValue, calendarioHora || document.getElementById('weekly-calendar-time')?.value || '');
                if (descanso) {
                    if (status) status.textContent = `El grado ${selectedValue} está en descanso de ${descanso.inicio} hasta ${descanso.fin}.`;
                    return;
                }

                const horarioGrado = getHorarioCoincidentePorGrado(selectedValue, selectedDay, calendarioHora || document.getElementById('weekly-calendar-time')?.value || '');
                if (horarioGrado) {
                    const salon = String(horarioGrado.salon || '').trim();
                    if (salon) {
                        const salonMatch = findBestSalonMatch(salon, selectedValue);
                        if (salonMatch) {
                            const destino = panoramas.find(p => p.id === salonMatch.panoId) || panoramas.find(p => p.id === salonMatch.sourceImage);
                            if (destino) {
                                clearActiveRoute();
                                currentPano = destino;
                                selectedHS = null;
                                if (status) status.textContent = `Has llegado al salón ${salon} del grado ${selectedValue}.`;
                                init();
                                updateSourceLabel();
                                renderMiniMap();
                                renderMapGuide();
                                return;
                            }
                        }
                        if (status) status.textContent = `El grado ${selectedValue} está programado en el salón ${salon}, pero aún no tiene una ubicación marcada.`;
                        return;
                    }
                }

                clearActiveRoute();
                const horaTexto = calendarioHora || document.getElementById('weekly-calendar-time')?.value || '';
                const message = getMensajeSinClaseParaGrado(selectedValue, selectedDay, horaTexto);
                if (status) status.textContent = message;
                mostrarAlertaHorario(message);
                return;
            }

            const matches = getQuickNavEntries().filter(entry => {
                if (category === 'profesor' && (entry.type === 'database-place' || entry.type === 'simple-alert')) return false;
                const value = getQuickNavEntryValue(category, entry);
                if (!value || value.toLowerCase() !== String(selectedValue).trim().toLowerCase()) return false;
                if (!entry.dia && !entry.horaInicio && !entry.horaFin) return true;
                if (!selectedDay && selectedMinutes === null) return true;
                const entryDay = String(entry.dia || '').trim();
                const start = getMinutesFromTime(entry.horaInicio || '');
                const end = getMinutesFromTime(entry.horaFin || '');
                if (!entryDay && start === null && end === null) return true;
                if (!entryDay) return true;
                if (selectedDay && diaNormalizado(entryDay) !== diaNormalizado(selectedDay)) return false;
                if (selectedMinutes !== null && start !== null && end !== null) {
                    return selectedMinutes >= start && selectedMinutes <= end;
                }
                return true;
            });

            const match = matches[0] || getQuickNavEntries().find(entry => {
                if (category === 'profesor' && (entry.type === 'database-place' || entry.type === 'simple-alert')) return false;
                const value = getQuickNavEntryValue(category, entry);
                return value && value.toLowerCase() === String(selectedValue).trim().toLowerCase();
            });

            if (!match) {
                if (status) status.textContent = 'No se encontró esa opción con la fecha y hora actuales.';
                return;
            }

            const destino = panoramas.find(p => p.id === match.panoId) || panoramas.find(p => p.id === match.sourceImage);
            if (!destino) {
                if (status) status.textContent = 'No se pudo ubicar esa referencia en el mapa.';
                return;
            }
            clearActiveRoute();
            currentPano = destino;
            selectedHS = null;
            if (status) status.textContent = `Has llegado a ${destino.title || destino.id}.`;
            init();
            updateSourceLabel();
            renderMiniMap();
            renderMapGuide();
        }

        function rutaDesdeMenuVista() {
            clearActiveRoute();
            const category = document.getElementById('quick-nav-category')?.value || 'grado';
            const target = document.getElementById('quick-nav-target');
            const status = document.getElementById('quick-nav-status');
            const selectedValue = target ? target.value.trim() : '';
            const selectedDay = getDayNameFromCurrentCalendar();
            const selectedMinutes = getMinutesFromCurrentCalendar();
            if (!selectedValue) {
                if (status) status.textContent = 'Elige una opción para trazar la ruta.';
                return;
            }

            if (category === 'grado') {
                const descanso = getGradoBreakStatus(selectedValue, calendarioHora || document.getElementById('weekly-calendar-time')?.value || '');
                if (descanso) {
                    clearActiveRoute();
                    const message = `El grado ${selectedValue} está en descanso de ${descanso.inicio} hasta ${descanso.fin}.`;
                    if (status) status.textContent = message;
                    mostrarAlertaHorario(message);
                    return;
                }

                const horarioGrado = getHorarioCoincidentePorGrado(selectedValue, selectedDay, calendarioHora || document.getElementById('weekly-calendar-time')?.value || '');
                if (horarioGrado) {
                    const salon = String(horarioGrado.salon || '').trim();
                    if (salon) {
                        const salonMatch = findBestSalonMatch(salon, selectedValue);
                        if (salonMatch) {
                            const destino = panoramas.find(p => p.id === salonMatch.panoId) || panoramas.find(p => p.id === salonMatch.sourceImage);
                            if (destino) {
                                routeTargetId = destino.id;
                                document.getElementById('route-destination').value = destino.id;
                                showRoute();
                                renderMiniMap();
                                updateLeafletMiniMap();
                                renderMapGuide();
                                if (status) status.textContent = `Ruta hacia el salón ${salon} del grado ${selectedValue}.`;
                                return;
                            }
                        }
                        if (status) status.textContent = `El grado ${selectedValue} está programado en el salón ${salon}, pero no hay una ubicación marcada para esa ruta.`;
                        return;
                    }
                }

                clearActiveRoute();
                const horaTexto = calendarioHora || document.getElementById('weekly-calendar-time')?.value || '';
                const message = getMensajeSinClaseParaGrado(selectedValue, selectedDay, horaTexto);
                if (status) status.textContent = message;
                mostrarAlertaHorario(message);
                return;
            }

            const matches = getQuickNavEntries().filter(entry => {
                if (category === 'profesor' && (entry.type === 'database-place' || entry.type === 'simple-alert')) return false;
                const value = getQuickNavEntryValue(category, entry);
                if (!value || value.toLowerCase() !== String(selectedValue).trim().toLowerCase()) return false;
                if (!entry.dia && !entry.horaInicio && !entry.horaFin) return true;
                const entryDay = String(entry.dia || '').trim();
                const start = getMinutesFromTime(entry.horaInicio || '');
                const end = getMinutesFromTime(entry.horaFin || '');
                if (!entryDay) return true;
                if (selectedDay && diaNormalizado(entryDay) !== diaNormalizado(selectedDay)) return false;
                if (selectedMinutes !== null && start !== null && end !== null) {
                    return selectedMinutes >= start && selectedMinutes <= end;
                }
                return true;
            });

            const match = matches[0] || getQuickNavEntries().find(entry => {
                if (category === 'profesor' && (entry.type === 'database-place' || entry.type === 'simple-alert')) return false;
                const value = getQuickNavEntryValue(category, entry);
                return value && value.toLowerCase() === String(selectedValue).trim().toLowerCase();
            });

            if (!match) {
                if (status) status.textContent = 'No se encontró una ubicación para esta búsqueda.';
                return;
            }

            const destino = panoramas.find(p => p.id === match.panoId) || panoramas.find(p => p.id === match.sourceImage);
            if (!destino) {
                if (status) status.textContent = 'No se pudo crear la ruta.';
                return;
            }

            routeTargetId = destino.id;
            document.getElementById('route-destination').value = destino.id;
            showRoute();
            renderMiniMap();
            updateLeafletMiniMap();
            renderMapGuide();
            if (status) status.textContent = `Ruta hacia ${selectedValue} en ${destino.title || destino.id}.`;
        }

        function irAlDestinoSeleccionado() {
            buscarUbicacionEnMenuVista();
        }

        function rutaAlDestinoSeleccionado() {
            rutaDesdeMenuVista();
        }

        function llenarMenuNavegacion() {
            const toggle = document.getElementById('btn-toggle-quick-nav');
            const panel = document.getElementById('quick-nav-panel');
            if (toggle) {
                toggle.onclick = () => {
                    if (!panel) return;
                    const isCollapsed = panel.classList.toggle('collapsed');
                    toggle.textContent = isCollapsed ? '☰ Mostrar menú de vista' : '☰ Ocultar menú de vista';
                    if (!isCollapsed && document.getElementById('quick-nav-category')?.value === 'grado') {
                        actualizarOpcionesGradoDesdeBase();
                    }
                };
            }
            const category = document.getElementById('quick-nav-category');
            if (category) {
                category.onchange = () => {
                    clearActiveRoute();
                    if (category.value === 'grado') {
                        populateQuickNavOptions();
                        actualizarOpcionesGradoDesdeBase();
                        return;
                    }
                    if (category.value === 'lugar') {
                        const pendingLoads = [];
                        if (!lugaresCatalogo.length) pendingLoads.push(cargarCatalogoLugares());
                        if (!lugaresBaseDeDatos.length) pendingLoads.push(cargarLugaresDesdeBaseDeDatos());
                        Promise.all(pendingLoads).then(populateQuickNavOptions);
                    }
                    populateQuickNavOptions();
                };
            }
            populateQuickNavOptions();
            const target = document.getElementById('quick-nav-target');
            if (target) target.onchange = () => {
                clearActiveRoute();
                const status = document.getElementById('quick-nav-status');
                if (status) status.textContent = '';
            };
            const goBtn = document.getElementById('btn-go-to-location');
            if (goBtn) goBtn.onclick = buscarUbicacionEnMenuVista;
            const routeBtn = document.getElementById('btn-route-to-location');
            if (routeBtn) routeBtn.onclick = rutaDesdeMenuVista;
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
                // Igual que las flechas, convertimos cada posición del cursor a pitch/yaw
                // usando el visor 360. El modelo queda actualizado antes de soltar.
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
                    clientY: e.clientY ?? draggedAlert.lastClientY,
                    target: document.getElementById('panorama-container'),
                    currentTarget: document.getElementById('panorama-container')
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
                document.getElementById('select-direction').value = selectedHS.direction || 'forward';
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
                initialViewLabel.textContent = `Adelante: ${Math.round(currentPano.forwardYaw)}° · Atrás: ${Math.round(currentPano.backwardYaw)}° · Izquierda: ${Math.round(currentPano.leftYaw)}° · Derecha: ${Math.round(currentPano.rightYaw)}°`;
            }
        }

        document.getElementById('range-rot').oninput = (e) => { if(selectedHS) { selectedHS.rotate = Number(e.target.value); renderHS(); syncMenu(); markDirty(); } };
        document.getElementById('range-tilt').oninput = (e) => { if(selectedHS) { selectedHS.tilt = Number(e.target.value); renderHS(); syncMenu(); markDirty(); } };
        document.getElementById('range-w').oninput = (e) => { if(selectedHS) { selectedHS.w = Number(e.target.value); renderHS(); syncMenu(); markDirty(); } };
        document.getElementById('range-h').oninput = (e) => { if(selectedHS) { selectedHS.h = Number(e.target.value); renderHS(); syncMenu(); markDirty(); } };
        
        document.getElementById('btn-add').onclick = () => {
            hotspotPlacementType = 'arrow';
            addHS(viewer.getPitch(), viewer.getYaw(), 'arrow');
        };
        document.getElementById('btn-add-circle').onclick = (event) => {
            hotspotPlacementType = hotspotPlacementType === 'circle' ? 'arrow' : 'circle';
            event.currentTarget.classList.toggle('active', hotspotPlacementType === 'circle');
            event.currentTarget.textContent = hotspotPlacementType === 'circle' ? '🟢 Doble clic para colocar círculo' : '🟢 Crear círculo (doble clic)';
        };
        document.getElementById('btn-add-exclamation').onclick = (event) => {
            hotspotPlacementType = hotspotPlacementType === 'exclamation' ? 'arrow' : 'exclamation';
            event.currentTarget.classList.toggle('active', hotspotPlacementType === 'exclamation');
            event.currentTarget.textContent = hotspotPlacementType === 'exclamation' ? '❗ Doble clic para colocar signo' : '❗ Crear exclamación (doble clic)';
        };
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
        document.getElementById('btn-set-left-view').onclick = () => {
            currentPano.leftYaw = viewer.getYaw();
            currentPano.leftPitch = viewer.getPitch();
            syncMenu();
            markDirty();
        };
        document.getElementById('btn-set-right-view').onclick = () => {
            currentPano.rightYaw = viewer.getYaw();
            currentPano.rightPitch = viewer.getPitch();
            syncMenu();
            markDirty();
        };
        document.getElementById('btn-del').onclick = () => { if(selectedHS) { currentPano.hotspots = currentPano.hotspots.filter(h => h !== selectedHS); selectedHS = null; renderHS(); markDirty(); } };
        document.getElementById('select-target').onchange = (e) => { if(selectedHS) { selectedHS.targetId = e.target.value; markDirty(); } };
        document.getElementById('select-direction').onchange = (e) => { if(selectedHS) { selectedHS.direction = e.target.value; renderHS(); syncMenu(); markDirty(); } };
        document.getElementById('select-color').onchange = (e) => { if(selectedHS) { selectedHS.color = e.target.value; renderHS(); markDirty(); } };
                function svgElement(name, attrs = {}) {
            const element = document.createElementNS('http://www.w3.org/2000/svg', name);
            Object.entries(attrs).forEach(([key, value]) => element.setAttribute(key, value));
            return element;
        }

        function mapPointFromEvent(event) {
            const layer = document.getElementById('mapa-google-layer');
            const matrix = layer.getScreenCTM();
            let x;
            let y;
            if (matrix) {
                const point = layer.createSVGPoint();
                point.x = event.clientX;
                point.y = event.clientY;
                const localPoint = point.matrixTransform(matrix.inverse());
                x = localPoint.x;
                y = localPoint.y;
            } else {
                const rect = layer.getBoundingClientRect();
                x = ((event.clientX - rect.left) / rect.width) * 100;
                y = ((event.clientY - rect.top) / rect.height) * 100;
            }
            x = Math.max(0, Math.min(100, x));
            y = Math.max(0, Math.min(100, y));
            if (document.getElementById('map-snap-grid')?.checked) { x = Math.round(x / 5) * 5; y = Math.round(y / 5) * 5; }
            return { x, y };
        }

        function getMapPointsForPanoramaId(id) {
            if (!id) return [];
            const advancePoints = minimapAdvancePoints
                .filter(point => point.panoId === id && Number.isFinite(Number(point.x)) && Number.isFinite(Number(point.y)))
                .map(point => ({ x: Number(point.x), y: Number(point.y) }));
            if (advancePoints.length) {
                return advancePoints.filter((point, index, points) => points.findIndex(other =>
                    Math.abs(other.x - point.x) < 0.001 && Math.abs(other.y - point.y) < 0.001
                ) === index);
            }
            const panoramaPoint = mapGuide.panoramaPositions && mapGuide.panoramaPositions[id];
            if (panoramaPoint && Number.isFinite(Number(panoramaPoint.x)) && Number.isFinite(Number(panoramaPoint.y))) {
                return [{ x: Number(panoramaPoint.x), y: Number(panoramaPoint.y) }];
            }
            return [];
        }

        function buildRouteLinePoints(startPoint, targetPoint) {
            const routeIds = [currentPano?.id, ...routePath.map(step => step.toId)].filter(Boolean);
            if (routeTargetId && routeIds[routeIds.length - 1] !== routeTargetId) routeIds.push(routeTargetId);

            const pointGroups = [[{ x: Number(startPoint.x), y: Number(startPoint.y) }]];
            routeIds.slice(1).forEach(id => {
                const points = getMapPointsForPanoramaId(id);
                if (points.length) pointGroups.push(points);
            });
            const targetIsMapped = pointGroups[pointGroups.length - 1].some(point =>
                Math.abs(point.x - Number(targetPoint.x)) < 0.001 && Math.abs(point.y - Number(targetPoint.y)) < 0.001
            );
            if (!targetIsMapped) pointGroups.push([{ x: Number(targetPoint.x), y: Number(targetPoint.y) }]);

            if (pointGroups.length > 1) {
                const routePoints = [pointGroups[0][0]];
                pointGroups.slice(1).forEach((group, index) => {
                    const previousPoint = routePoints[routePoints.length - 1];
                    const isTargetGroup = index === pointGroups.length - 2;
                    const mappedTarget = isTargetGroup ? group.find(point =>
                        Math.abs(point.x - Number(targetPoint.x)) < 0.001 && Math.abs(point.y - Number(targetPoint.y)) < 0.001
                    ) : null;
                    const nearestPoint = mappedTarget || group.reduce((nearest, point) =>
                        Math.hypot(point.x - previousPoint.x, point.y - previousPoint.y) <
                        Math.hypot(nearest.x - previousPoint.x, nearest.y - previousPoint.y) ? point : nearest
                    );
                    routePoints.push(nearestPoint);
                });
                const cleaned = routePoints.filter((point, index) => index === 0 ||
                    Math.abs(point.x - routePoints[index - 1].x) > 0.001 || Math.abs(point.y - routePoints[index - 1].y) > 0.001
                );
                if (cleaned.length > 1) return cleaned;
            }

            return buildGraphRoutePoints(startPoint, targetPoint);
        }

        function buildGraphRoutePoints(startPoint, targetPoint) {
            const nodes = [];
            const addNode = (id, point) => {
                if (!point || !Number.isFinite(Number(point.x)) || !Number.isFinite(Number(point.y))) return null;
                const x = Number(point.x);
                const y = Number(point.y);
                const key = `${x.toFixed(3)}:${y.toFixed(3)}`;
                const existing = nodes.find(node => node.key === key);
                if (existing) return existing.id;
                nodes.push({ id, key, x, y });
                return id;
            };

            const startId = addNode('start', startPoint);
            minimapAdvancePoints.forEach((point, index) => addNode(`advance:${index}`, { x: Number(point.x), y: Number(point.y) }));
            Object.entries(mapGuide.panoramaPositions || {}).forEach(([id, position]) => addNode(`pano:${id}`, position));
            const targetId = addNode('target', targetPoint);

            if (!nodes.length || startId === null || targetId === null) return [{ ...startPoint }, { ...targetPoint }];
            if (startId === targetId) return [{ ...startPoint }, { ...targetPoint }];

            const distance = (a, b) => Math.hypot(a.x - b.x, a.y - b.y);
            const maxEdgeDistance = 12;
            const graph = new Map(nodes.map(node => [node.id, []]));
            nodes.forEach((node, index) => {
                nodes.slice(index + 1).forEach(other => {
                    const cost = distance(node, other);
                    if (cost <= 0 || cost > maxEdgeDistance) return;
                    graph.get(node.id).push({ id: other.id, cost });
                    graph.get(other.id).push({ id: node.id, cost });
                });
            });
            const dist = new Map(nodes.map(node => [node.id, Number.POSITIVE_INFINITY]));
            const previous = new Map();
            const visited = new Set();
            dist.set(startId, 0);

            while (visited.size < nodes.length) {
                let currentId = null;
                let currentDist = Number.POSITIVE_INFINITY;
                for (const [id, value] of dist.entries()) {
                    if (visited.has(id)) continue;
                    if (value < currentDist) {
                        currentDist = value;
                        currentId = id;
                    }
                }
                if (currentId == null) break;
                if (currentId === targetId) break;
                visited.add(currentId);

                for (const neighbor of graph.get(currentId) || []) {
                    if (visited.has(neighbor.id)) continue;
                    const next = currentDist + neighbor.cost;
                    if (next < (dist.get(neighbor.id) ?? Number.POSITIVE_INFINITY)) {
                        dist.set(neighbor.id, next);
                        previous.set(neighbor.id, currentId);
                    }
                }
            }

            if ((dist.get(targetId) ?? Number.POSITIVE_INFINITY) === Number.POSITIVE_INFINITY) {
                return [{ ...startPoint }, { ...targetPoint }];
            }

            const pathIds = [];
            let cursor = targetId;
            while (cursor) {
                pathIds.unshift(cursor);
                if (cursor === startId) break;
                cursor = previous.get(cursor) ?? null;
            }

            const nodeById = new Map(nodes.map(node => [node.id, node]));
            const pathPoints = pathIds
                .map(id => nodeById.get(id))
                .filter(Boolean)
                .map(node => ({ x: node.x, y: node.y }));

            const cleaned = [pathPoints[0]].filter(Boolean);
            pathPoints.slice(1).forEach(point => {
                const last = cleaned[cleaned.length - 1];
                if (!last || Math.abs(point.x - last.x) > 0.001 || Math.abs(point.y - last.y) > 0.001) {
                    cleaned.push(point);
                }
            });

            if (!cleaned.some(point => Math.abs(point.x - targetPoint.x) < 0.001 && Math.abs(point.y - targetPoint.y) < 0.001)) {
                cleaned.push({ ...targetPoint });
            }
            if (!cleaned.some(point => Math.abs(point.x - startPoint.x) < 0.001 && Math.abs(point.y - startPoint.y) < 0.001)) {
                cleaned.unshift({ ...startPoint });
            }

            return cleaned;
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
                const isDatabasePlace = place.source === 'catalogo';
                const w = Number(place.w) || (isDatabasePlace ? 2.5 : 7), h = Number(place.h) || (isDatabasePlace ? 2.5 : 7);
                const placeColor = place.color || (place.type === 'Aula' ? '#2563c7' : '#f59e0b');
                const shape = place.shape === 'rect' ? svgElement('rect', { x: place.x - w/2, y: place.y - h/2, width: w, height: h, fill: placeColor, stroke: '#fff', 'stroke-width': isDatabasePlace ? .35 : .7 }) : place.shape === 'semicircle' ? svgElement('path', { d: `M ${place.x-w/2} ${place.y+h/2} A ${w/2} ${h/2} 0 0 1 ${place.x+w/2} ${place.y+h/2} L ${place.x-w/2} ${place.y+h/2} Z`, fill: placeColor, stroke: '#fff', 'stroke-width': isDatabasePlace ? .35 : .7 }) : place.shape === 'diamond' ? svgElement('polygon', { points: `${place.x},${place.y-h/2} ${place.x+w/2},${place.y} ${place.x},${place.y+h/2} ${place.x-w/2},${place.y}`, fill: placeColor, stroke: '#fff', 'stroke-width': isDatabasePlace ? .35 : .7 }) : svgElement('circle', { cx: place.x, cy: place.y, r: Math.min(w, h)/2, fill: placeColor, stroke: '#fff', 'stroke-width': isDatabasePlace ? .35 : .7 });
                shape.addEventListener('click', event => { event.stopPropagation(); selectMapElement({ kind: 'place', item: place }); }); content.appendChild(shape);
                if (!isDatabasePlace) {
                    const label = svgElement('text', { x: place.x, y: place.y - h/2 - 1, 'text-anchor': 'middle', 'font-size': 3.2, fill: '#111827', 'font-weight': 'bold' }); label.textContent = place.name; content.appendChild(label);
                }
            });
            Object.entries(mapGuide.panoramaPositions || {}).forEach(([id, position]) => {
                const panorama = panoramas.find(item => item.id === id);
                if (!panorama) return;
                const marker = svgElement('circle', { cx: position.x, cy: position.y, r: 2.2, fill: '#7c3aed', stroke: '#ffffff', 'stroke-width': .8 });
                marker.addEventListener('click', event => {
                    event.stopPropagation();
                    currentPano = panorama;
                    selectedHS = null;
                    init();
                    updateSourceLabel();
                });
                content.appendChild(marker);
                const label = svgElement('text', { x: position.x, y: position.y - 3, 'text-anchor': 'middle', 'font-size': 3, fill: '#4c1d95', 'font-weight': 'bold' });
                label.textContent = panorama.title || id;
                content.appendChild(label);
            });

            const targetId = routeTargetId;
            if (!targetId || !minimapIndicator || (currentPano?.id !== targetId && !routePath.length)) return;

            const targetPoint = getMapPointsForPanoramaId(targetId)[0] || null;

            if (!targetPoint) return;

            const routePoints = buildRouteLinePoints(minimapIndicator, targetPoint);
            if (routePoints.length > 1) {
                const routeLine = svgElement('polyline', {
                    points: routePoints.map(point => `${point.x},${point.y}`).join(' '),
                    fill: 'none',
                    stroke: '#38bdf8',
                    'stroke-width': 1,
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-opacity': '.9',
                    'filter': 'drop-shadow(0 0 1px rgba(56,189,248,0.75))'
                });
                content.appendChild(routeLine);
            }
        }

        function saveMapGuide() {
            try {
                ensureMapPlaceIds();
                localStorage.setItem(MAP_GUIDE_KEY, JSON.stringify(mapGuide));
                queueMapLocationsSave();
            } catch (error) {
                console.warn('No se pudo guardar el mapa guía', error);
            }
        }

        function loadMapGuide() {
            try { const saved = JSON.parse(localStorage.getItem(MAP_GUIDE_KEY) || 'null'); if (saved) mapGuide = { ...mapGuide, ...saved, layers: { ...mapGuide.layers, ...(saved.layers || {}) } }; } catch (error) { console.warn('No se pudo cargar el mapa guía', error); }
            let resizedDatabasePlace = false;
            (Array.isArray(mapGuide.places) ? mapGuide.places : []).forEach(place => {
                if (migrateDatabasePlaceSize(place)) resizedDatabasePlace = true;
            });
            if (resizedDatabasePlace) saveMapGuide();
            renderMapGuide();
        }

        async function loadMapPlaceCatalog() {
            const select = document.getElementById('map-database-place-select');
            if (!select) return;
            const previousValue = select.value;
            try {
                const response = await fetch('api.php?action=catalogo_lugares', { cache: 'no-store' });
                const payload = await response.json();
                if (!response.ok || !payload?.ok) throw new Error(payload?.error || 'No se pudieron cargar los lugares.');
                mapPlaceCatalog = Array.isArray(payload.items) ? payload.items : [];
                select.innerHTML = '<option value="">Selecciona un lugar</option>';
                mapPlaceCatalog.forEach(place => {
                    const option = document.createElement('option');
                    option.value = String(place.id_lugar);
                    option.textContent = String(place.titulo || '').trim();
                    select.appendChild(option);
                });
                if (mapPlaceCatalog.some(place => String(place.id_lugar) === previousValue)) select.value = previousValue;
                if (!mapPlaceCatalog.length) select.innerHTML = '<option value="">No hay lugares en la base</option>';
            } catch (error) {
                mapPlaceCatalog = [];
                select.innerHTML = '<option value="">No se pudieron cargar los lugares</option>';
                console.warn('No se pudo cargar el catálogo para el punto lugar', error);
            }
        }

        function setMapTool(tool) {
            activeMapTool = activeMapTool === tool ? null : tool;
            document.querySelectorAll('#map-tool-path, #map-tool-place, #map-tool-zone, #map-tool-database-place').forEach(button => button.classList.remove('active'));
            if (activeMapTool) document.getElementById(`map-tool-${activeMapTool}`).classList.add('active');
            if (activeMapTool !== 'path' && draftPath.length) finishMapPath();
        }

        function finishMapPath() { if (draftPath.length >= 2) mapGuide.paths.push({ name: `Camino ${mapGuide.paths.length + 1}`, shape: 'street', points: [...draftPath], thickness: 7, lengthScale: 100 }); draftPath = []; renderMapGuide(); saveMapGuide(); }

        function handleMapGuideClick(event) {
            if (minimapPlacementMode === 'panorama') {
                const point = mapPointFromEvent(event);
                if (currentPano) {
                    mapGuide.panoramaPositions = mapGuide.panoramaPositions || {};
                    mapGuide.panoramaPositions[currentPano.id] = {
                        x: point.x,
                        y: point.y,
                        title: currentPano.title || currentPano.id,
                        path: currentPano.path
                    };
                    saveMapGuide();
                    renderMapGuide();
                    renderMiniMap();
                    setMiniMapPlacementMode(null);
                }
                return;
            }
            if (!isEdit || !activeMapTool) return;
            const point = mapPointFromEvent(event);
            if (activeMapTool === 'database-place') {
                if (!selectedMapDatabasePlace) {
                    setMapTool('database-place');
                    return;
                }
                mapGuide.places = Array.isArray(mapGuide.places) ? mapGuide.places : [];
                const place = {
                    id: `database-place-${selectedMapDatabasePlace.id_lugar}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    id_lugar: Number(selectedMapDatabasePlace.id_lugar),
                    source: 'catalogo',
                    name: String(selectedMapDatabasePlace.titulo || '').trim(),
                    type: 'Lugar',
                    shape: 'circle',
                    color: '#38bdf8',
                    w: 2.5,
                    h: 2.5,
                    x: point.x,
                    y: point.y,
                    displaySizeVersion: 4
                };
                mapGuide.places.push(place);
                mapGuide.layers.places = true;
                const placesLayer = document.getElementById('layer-places');
                if (placesLayer) placesLayer.checked = true;
                saveMapGuide();
                selectedMapElement = { kind: 'place', item: place };
                renderMapGuide();
                renderMinimapElementsList();
                setMapTool('database-place');
                selectedMapDatabasePlace = null;
                return;
            }
            if (activeMapTool === 'path') { if (event.shiftKey && draftPath.length) { const previous = draftPath[draftPath.length - 1]; if (Math.abs(point.x - previous.x) >= Math.abs(point.y - previous.y)) point.y = previous.y; else point.x = previous.x; } draftPath.push(point); renderMapGuide(); return; }
            if (activeMapTool === 'place') { const name = prompt('Nombre del aula o lugar:'); if (name) { const type = prompt('Escribe Aula o Lugar:', 'Aula') || 'Lugar';                 const shapeName = (prompt('Forma: círculo, semicírculo, rectángulo o rombo:', 'círculo') || 'círculo').toLowerCase(); const shape = shapeName.includes('semi') ? 'semicircle' : shapeName.includes('rect') ? 'rect' : shapeName.includes('rombo') ? 'diamond' : 'circle'; mapGuide.places.push({ name: name.trim(), type: /^aula$/i.test(type) ? 'Aula' : 'Lugar', shape, w: 7, h: 7, x: point.x, y: point.y });
 renderMapGuide(); saveMapGuide(); } }
            if (activeMapTool === 'zone') { const name = prompt('Nombre del área no transitable:', 'Área restringida'); if (name) {                 const shapeName = (prompt('Forma: rectángulo, círculo, semicírculo o rombo:', 'rectángulo') || 'rectángulo').toLowerCase(); const shape = shapeName.includes('semi') ? 'semicircle' : shapeName.includes('cir') ? 'circle' : shapeName.includes('rombo') ? 'diamond' : 'rect'; mapGuide.zones.push({ name: name.trim(), shape, x: point.x, y: point.y, w: 18, h: 12 });
 renderMapGuide(); saveMapGuide(); } }
        }

        document.getElementById('map-tool-path').onclick = () => setMapTool('path');
        document.getElementById('map-tool-place').onclick = () => setMapTool('place');
        document.getElementById('map-tool-zone').onclick = () => setMapTool('zone');
        document.getElementById('map-tool-database-place').onclick = () => {
            const placeId = Number(document.getElementById('map-database-place-select')?.value);
            selectedMapDatabasePlace = mapPlaceCatalog.find(place => Number(place.id_lugar) === placeId) || null;
            if (!selectedMapDatabasePlace) {
                alert('Selecciona primero un lugar de la base de datos.');
                return;
            }
            setMapTool('database-place');
        };
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
                const linkedPano = panoramas.find(pano => pano.id === point.panoId);
                const panoLabel = linkedPano
                    ? ' [' + (linkedPano.title || linkedPano.path || linkedPano.id) + ']'
                    : point.panoId
                        ? ' [' + point.panoId + ']'
                        : ' [sin imagen]';
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
            const folderSelect = document.getElementById('advance-point-folder');
            const imageSelect = document.getElementById('advance-point-image');
            const xInput = document.getElementById('advance-point-x');
            const yInput = document.getElementById('advance-point-y');
            const saveBtn = document.getElementById('advance-point-save');
            
            if (!editor || !folderSelect || !imageSelect || !xInput || !yInput || !saveBtn) return;

            const catalog = panoramas.filter(pano => /^imagenes_del_colegio\//i.test(pano.path || ''));
            const folders = [...new Set(catalog.map(pano => (pano.path || '').split('/')[1]).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'es'));
            const selectedPano = panoramas.find(pano => pano.id === point.panoId);
            const selectedFolder = selectedPano ? (selectedPano.path || '').split('/')[1] : '';
            folderSelect.innerHTML = '<option value="">Todas las carpetas</option>' + folders.map(folder => `<option value="${folder}">${folder}</option>`).join('');
            folderSelect.value = selectedFolder;

            const renderAdvanceImages = () => {
                const folder = folderSelect.value;
                const images = folder ? catalog.filter(pano => (pano.path || '').split('/')[1] === folder) : catalog;
                imageSelect.innerHTML = images.map(pano => `<option value="${pano.id}">${pano.title}</option>`).join('');
                if (images.some(pano => pano.id === point.panoId)) imageSelect.value = point.panoId;
            };
            folderSelect.onchange = renderAdvanceImages;
            renderAdvanceImages();
            
            editor.style.display = 'block';
            xInput.value = point.x || 0;
            yInput.value = point.y || 0;
            
            saveBtn.onclick = () => {
                point.panoId = imageSelect.value || point.panoId || '';
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
        document.getElementById('map-snap-grid').checked = false;
        document.getElementById('map-snap-grid').addEventListener('change', renderMapGuide);

        function applyMapSettings() {
            const mapa = document.getElementById('mapa-google-overlay');
            if (!mapa) return;
            const wrapper = document.getElementById('mapa-google-wrapper');
            const surface = document.getElementById('mapa-google-surface');
            mapa.style.width = '100%';
            mapa.style.left = 'auto';
            mapa.style.bottom = 'auto';
            if (wrapper && !wrapper.closest('#map-guide-modal')) { wrapper.style.width = `${mapSettings.width}px`; wrapper.style.left = `${mapSettings.left}px`; wrapper.style.bottom = `${mapSettings.bottom}px`; } else if (wrapper) { wrapper.style.width = '100%'; wrapper.style.left = 'auto'; wrapper.style.bottom = 'auto'; }
            if (surface && !wrapper?.closest('#map-guide-modal')) { surface.style.width = `${mapSettings.zoom}%`; surface.style.height = `${mapSettings.zoom}%`; }
            mapa.style.opacity = String(mapSettings.opacity / 100);
            mapa.style.filter = `grayscale(${mapSettings.grayscale}%) contrast(1.18)`;
            const controls = [
                ['map-width', 'map-width-value', mapSettings.width, 'px'],
                ['map-opacity', 'map-opacity-value', mapSettings.opacity, '%'],
                ['map-grayscale', 'map-grayscale-value', mapSettings.grayscale, '%'],
                ['map-left', 'map-left-value', mapSettings.left, 'px'],
                ['map-bottom', 'map-bottom-value', mapSettings.bottom, 'px'],
                ['map-zoom', 'map-zoom-value', mapSettings.zoom, '%'],
                ['map-view-x', 'map-view-x-value', mapSettings.viewX, '%'],
                ['map-view-y', 'map-view-y-value', mapSettings.viewY, '%']
            ];
            controls.forEach(([inputId, valueId, value, unit]) => {
                const input = document.getElementById(inputId);
                const output = document.getElementById(valueId);
                if (input) input.value = value;
                if (output) output.textContent = `${value}${unit}`;
            });
            const followIndicator = document.getElementById('map-follow-indicator');
            if (followIndicator) followIndicator.checked = mapSettings.followIndicator !== false;
            updateMapViewport();
        }

        function loadMapSettings() {
            try {
                const saved = JSON.parse(localStorage.getItem(MAP_SETTINGS_KEY) || 'null');
                if (saved && typeof saved === 'object') mapSettings = { ...DEFAULT_MAP_SETTINGS, ...saved };
                if (localStorage.getItem(MAP_COLOR_MIGRATION_KEY) !== 'done') {
                    mapSettings.grayscale = 0;
                    localStorage.setItem(MAP_SETTINGS_KEY, JSON.stringify(mapSettings));
                    localStorage.setItem(MAP_COLOR_MIGRATION_KEY, 'done');
                }
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
            const surface = document.getElementById('mapa-google-surface');
            if (mapa) mapa.classList.toggle('map-editing', Boolean(isOpen));
            if (wrapper) wrapper.classList.toggle('map-editing', Boolean(isOpen));
            if (layer) layer.classList.toggle('editing', Boolean(isOpen));
            if (panel) panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            if (surface && isOpen) { surface.style.width = '100%'; surface.style.height = '100%'; }
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
            loadMapPlaceCatalog();
            updateMapPanelState(true);
            applyMapSettings();
            renderMinimapElementsList();
        }

        document.getElementById('mapa-google-overlay').addEventListener('click', openMapEditor);
        document.getElementById('btn-open-map-editor').onclick = openMapEditor;
        document.getElementById('map-guide-modal-close').onclick = closeMapGuideModal;
        document.getElementById('map-guide-modal-save').onclick = () => { finishMapPath(); saveMapSettings(); saveMapGuide(); updateSaveStatus('Mapa guía guardado', false); alert('Cambios guardados correctamente.'); };
        document.getElementById('map-guide-modal').addEventListener('click', event => { if (event.target.id === 'map-guide-modal') closeMapGuideModal(); });
        document.getElementById('btn-close-map-editor').onclick = closeMapGuideModal;
        document.getElementById('btn-add-info').onclick = () => prepareInfoForm('full');
        document.getElementById('btn-add-simple-alert').onclick = (event) => {
            hotspotPlacementType = hotspotPlacementType === 'simple-alert' ? 'arrow' : 'simple-alert';
            const isActive = hotspotPlacementType === 'simple-alert';
            event.currentTarget.classList.toggle('active', isActive);
            event.currentTarget.textContent = isActive ? '❗ Doble clic para colocar nombre simple' : '❗ Crear nombre simple';
            if (!isActive) {
                alertPlacementMode = false;
                closeLabelForm();
            }
        };
        document.getElementById('btn-close-label-form').onclick = closeLabelForm;
        const alertFormModeSelect = document.getElementById('label-alert-form-mode');
        if (alertFormModeSelect) {
            alertFormModeSelect.addEventListener('change', (event) => {
                setAlertFormMode(event.target.value === 'simple' ? 'simple' : 'full');
            });
        }
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
        document.getElementById('label-marker-size').oninput = (e) => {
            const sizeValue = document.getElementById('label-marker-size-value');
            if (sizeValue) sizeValue.textContent = `${e.target.value} px`;
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
        document.getElementById('map-element-paste').onclick = () => { if (!mapClipboard) { updateSaveStatus('Primero copia un elemento', false); return; } const copy = JSON.parse(JSON.stringify(mapClipboard.item)); const collection = mapElementCollection(mapClipboard.kind); mapGuide[collection].push(copy); selectedMapElement = { kind: mapClipboard.kind, item: copy }; renderMapGuide(); selectMapElement(selectedMapElement); saveMapGuide(); updateSaveStatus('Elemento pegado con la misma información', false); };
        function reorderSelectedElement(direction) { if (!selectedMapElement) return; const collection = mapElementCollection(selectedMapElement.kind); const items = mapGuide[collection]; const index = items.indexOf(selectedMapElement.item); const target = direction === 'front' ? items.length - 1 : 0; items.splice(index, 1); items.splice(target, 0, selectedMapElement.item); renderMapGuide(); saveMapGuide(); }
        document.getElementById('map-element-front').onclick = () => reorderSelectedElement('front');
        document.getElementById('map-element-back').onclick = () => reorderSelectedElement('back');
        document.getElementById('map-element-delete').onclick = () => { if (!selectedMapElement) return; const collection = mapElementCollection(selectedMapElement.kind); mapGuide[collection] = mapGuide[collection].filter(item => item !== selectedMapElement.item); selectedMapElement = null; document.getElementById('map-element-editor').classList.remove('visible'); renderMapGuide(); saveMapGuide(); };
        [
            ['map-opacity', 'opacity', 'map-opacity-value', '%'],
            ['map-grayscale', 'grayscale', 'map-grayscale-value', '%'],
            ['map-zoom', 'zoom', 'map-zoom-value', '%'],
            ['map-view-x', 'viewX', 'map-view-x-value', '%'],
            ['map-view-y', 'viewY', 'map-view-y-value', '%'],
        ].forEach(([inputId, setting, outputId, unit]) => {
            document.getElementById(inputId).addEventListener('input', (event) => {
                mapSettings[setting] = Number(event.target.value);
                document.getElementById(outputId).textContent = `${mapSettings[setting]}${unit}`;
                applyMapSettings();
                updateMapPanelState(true);
            });
        });

        document.getElementById('map-follow-indicator').addEventListener('change', (event) => {
            mapSettings.followIndicator = event.target.checked;
            applyMapSettings();
            updateMapViewport();
            saveMapSettings();
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
            openMapEditor();
        };
        document.getElementById('btn-copy-viewer-object').onclick = () => {
            if (!copySelectedViewerObject()) updateSaveStatus('Selecciona un objeto del panorama para copiarlo.', false);
        };
        document.getElementById('btn-paste-viewer-object').onclick = () => {
            if (!pasteViewerObject()) updateSaveStatus('Copia primero un objeto del panorama.', false);
        };
        document.getElementById('schedule-alert-close').onclick = cerrarAlertaHorario;
        document.getElementById('schedule-alert-overlay').addEventListener('click', event => {
            if (event.target.id === 'schedule-alert-overlay') cerrarAlertaHorario();
        });
        document.addEventListener('keydown', (event) => {
            if (document.getElementById('schedule-alert-overlay')?.classList.contains('open')) {
                if (event.key === 'Escape') cerrarAlertaHorario();
                event.preventDefault();
                return;
            }
            const element = event.target;
            const isTyping = element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement || element.isContentEditable;
            if (isTyping) return;
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'c') {
                if (copySelectedViewerObject()) event.preventDefault();
                return;
            }
            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'v') {
                if (pasteViewerObject()) event.preventDefault();
                return;
            }
            const key = event.key.length === 1 ? event.key.toLowerCase() : event.key;
            const rawDirection = {
                w: 'forward', ArrowUp: 'forward', d: 'right', ArrowRight: 'right',
                s: 'backward', ArrowDown: 'backward', a: 'left', ArrowLeft: 'left'
            }[key];
            if (rawDirection === undefined) return;
            event.preventDefault();
            navigateWithKeyboard(rawDirection);
        });
        document.getElementById('btn-show-route').textContent = 'Mostrar ruta azul';
        document.getElementById('btn-show-route').onclick = showRoute;
        document.getElementById('route-destination').onkeydown = (e) => { if (e.key === 'Enter') showRoute(); };
        document.getElementById('btn-place-indicator').onclick = () => setMiniMapPlacementMode('indicator');
        document.getElementById('btn-place-advance').onclick = () => setMiniMapPlacementMode('advance');
        document.getElementById('btn-place-panorama').onclick = () => setMiniMapPlacementMode('panorama');
        document.getElementById('btn-set-start-image').onclick = saveStartImage;
        document.getElementById('mapa-google-wrapper').addEventListener('click', placeMiniMapPoint);
        document.getElementById('mapa-google-layer').addEventListener('click', placeMiniMapPoint, true);
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
                p.leftYaw = Number(p.leftYaw) || 0;
                p.leftPitch = Number(p.leftPitch) || 0;
                p.rightYaw = Number(p.rightYaw) || 0;
                p.rightPitch = Number(p.rightPitch) || 0;
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
                    alertMode: alert.alertMode || 'full',
                    profesor: alert.profesor || '',
                    curso: alert.curso || '',
                    grado: alert.grado || '',
                    salon: alert.salon || '',
                    dia: alert.dia || '',
                    hora: alert.hora || (alert.horaInicio && alert.horaFin ? `${alert.horaInicio} - ${alert.horaFin}` : alert.horaInicio || alert.horaFin || ''),
                    horaInicio: alert.horaInicio || '',
                    horaFin: alert.horaFin || '',
                    titulo: alert.titulo || '',
                    descripcion: alert.descripcion || '',
                    pitch: Number(alert.pitch) || 0,
                    yaw: Number(alert.yaw) || 0,
                    x: Number(alert.x) || 50,
                    y: Number(alert.y) || 50,
                    color: alert.color || '#22c55e',
                    bg: alert.bg || 'rgba(101, 35, 18, 0.85)',
                    fontSize: Number(alert.fontSize) || 18,
                    markerSize: Number(alert.markerSize) || 70
                })) : [];
                if (p.hotspots && p.hotspots.length) {
                    p.hotspots = p.hotspots.map(h => ({
                        pitch: h.pitch,
                        yaw: h.yaw,
                        sourceImage: p.id,
                        type: h.type || 'arrow',
                        targetId: h.targetId || h.target || h.targetImage || null,
                        direction: h.direction || 'forward',
                        directionLabel: ({ forward: 'Adelante', backward: 'Atrás', down: 'Abajo', right: 'Derecha', left: 'Izquierda' })[h.direction || 'forward'],
                        color: h.color,
                        w: h.w,
                        h: h.h,
                        rotate: h.rotate,
                        tilt: h.tilt,
                        profesor: h.profesor || '',
                        curso: h.curso || '',
                        grado: h.grado || '',
                        salon: h.salon || '',
                        dia: h.dia || '',
                        hora: h.hora || '',
                        idHorario: h.idHorario || '',
                        horaInicio: h.horaInicio || '',
                        horaFin: h.horaFin || ''
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

        function downloadMapPointLocations() {
            downloadJSON({
                indicador: minimapIndicator ? { ...minimapIndicator } : null,
                puntosAvance: minimapAdvancePoints.map(point => ({ ...point })),
                puntosLugar: (mapGuide.places || []).map(place => ({ ...place }))
            }, 'ubicaciones_puntos_mapa.json');
        }

        document.getElementById('btn-download-map-points').onclick = downloadMapPointLocations;

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

            const saveToServer = async () => {
                if (showConfirm && !confirm('Esto sobrescribirá data/colegio_santander.json en el proyecto. ¿Deseas continuar?')) {
                    updateSaveStatus('Guardado local', false);
                    return;
                }
                try {
                    const response = await fetch('api.php?action=guardar_panoramas', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(copy)
                    });
                    const result = await response.json();
                    if (!response.ok || !result.ok) throw new Error(result.error || 'Error al guardar');
                    localStorage.removeItem(PENDING_STORAGE_KEY);
                    updateSaveStatus('Guardado en servidor', false);
                    if (!silent) alert('Panoramas guardados en el servidor.');
                } catch (error) {
                    console.error(error);
                    updateSaveStatus('Guardado local', false);
                    if (!silent && confirm('No fue posible guardar en el servidor. ¿Deseas descargar el JSON en su lugar?')) {
                        downloadJSON(copy);
                        alert('Se descargó colegio_santander_export.json. Puedes copiarlo sobre data/colegio_santander.json.');
                    }
                }
            };

            saveToServer();
        }

        document.getElementById('btn-export').onclick = () => {
            saveMapSettings();
            saveMapGuide();
            downloadJSON(buildExportCopy(), 'colegio_santander_coordenadas.json');
            savePanoramas({ showConfirm: true, silent: false });
        };

        document.getElementById('label-id-horario').addEventListener('change', (event) => {
            const option = event.target.options[event.target.selectedIndex];
            if (!option?.dataset.horario) return;
            try {
                const horario = JSON.parse(option.dataset.horario);
                const cursoInput = document.getElementById('label-curso');
                if (cursoInput && horario.materia) cursoInput.value = horario.materia;
            } catch (error) {
                console.warn('No se pudo completar el curso desde el horario', error);
            }
        });
        document.querySelectorAll('.weekly-calendar-day').forEach(button => {
            button.addEventListener('click', () => {
                clearActiveRoute();
                setWeeklyCalendar(button.dataset.day, document.getElementById('weekly-calendar-time').value);
            });
        });
        document.getElementById('weekly-calendar-time').addEventListener('change', (event) => {
            clearActiveRoute();
            setWeeklyCalendar(calendarioDia, event.target.value);
        });
        setCurrentCalendarDefaults();

        const labelSource = document.getElementById('label-source');
        function updateSourceLabel() {
            if (labelSource) labelSource.textContent = currentPano.title || currentPano.id;
            syncMenu();
        }

                loadMapSettings();
        loadMapGuide();
                loadMiniMapState().then(() => {
                    return cargarHorariosColegio().then(() => loadPanoramas());
                }).then(loadImageCatalog).then(() => {
            if (!Array.isArray(panoramas) || !panoramas.length) {
                updateSaveStatus('No hay datos del colegio', false);
                return;
            }

            document.getElementById('route-images').innerHTML = panoramas
                .filter(p => /^imagen\d+$/.test(p.id))
                .sort((a, b) => Number(a.id.replace('imagen', '')) - Number(b.id.replace('imagen', '')))
                .map(p => `<option value="${p.id.replace('imagen', 'imagen ')}">${p.title}</option>`)
                .join('');
            const fixedStartImage = panoramas.find(p => p && p.path === DEFAULT_START_IMAGE_PATH);
            const firstRealImage = panoramas.find(p => p && /^imagenes_del_colegio\//i.test(p.path || ''));
            currentPano = fixedStartImage || firstRealImage || panoramas[0];
            populateImageSelectors();
            cargarLugaresGuardados();
            if (!currentPano) {
                updateSaveStatus('No hay panoramas válidos', false);
                return;
            }
            currentPano.labels = currentPano.labels || [];
            currentPano.alerts = currentPano.alerts || [];
            if (window.matchMedia('(max-width: 700px), (max-height: 500px) and (orientation: landscape)').matches) {
                isEdit = false;
                document.body.classList.replace('edit-mode', 'view-mode');
                document.getElementById('btn-mode-view').classList.add('active');
                document.getElementById('btn-mode-edit').classList.remove('active');
            }
            updateSaveStatus('Guardado local', false);
            setupLeafletMiniMap();
            init(null, currentPano.path === DEFAULT_START_IMAGE_PATH ? 'forward' : null);
            renderLabels();
            updateSourceLabel();
            llenarMenuNavegacion();
            updateLeafletMiniMap();

        });
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>