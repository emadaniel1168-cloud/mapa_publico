<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

try {
    $action = $_GET['action'] ?? '';

    if ($action === 'imagenes') {
        $root = __DIR__ . DIRECTORY_SEPARATOR . 'imagenes_del_colegio';
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $items = [];

        if (is_dir($root)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) continue;
                if (strtolower($file->getFilename()) === 'mapa_google.jpeg') continue;
                $extension = strtolower($file->getExtension());
                if (!in_array($extension, $allowedExtensions, true)) continue;

                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen(__DIR__) + 1));
                $relativeFolder = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPath(), strlen($root) + 1));
                $items[] = [
                    'path' => $relativePath,
                    'title' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'folder' => $relativeFolder,
                ];
            }
        }

        usort($items, static fn(array $a, array $b): int => strnatcasecmp($a['path'], $b['path']));
        jsonResponse(['ok' => true, 'items' => $items]);
    }

    if ($action === 'guardar_ubicaciones_puntos') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
        }

        $data = requestJson();
        if (!is_array($data['puntosAvance'] ?? null) || !is_array($data['puntosLugar'] ?? null)) {
            jsonResponse(['ok' => false, 'error' => 'Las listas de puntos no son válidas'], 422);
        }
        if (isset($data['indicador']) && $data['indicador'] !== null && !is_array($data['indicador'])) {
            jsonResponse(['ok' => false, 'error' => 'El indicador no es válido'], 422);
        }

        $locations = [
            'indicador' => $data['indicador'] ?? null,
            'puntosAvance' => $data['puntosAvance'],
            'puntosLugar' => $data['puntosLugar'],
        ];
        $dataPath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ubicaciones_puntos_mapa.json';
        $json = json_encode($locations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            jsonResponse(['ok' => false, 'error' => 'No se pudieron codificar las ubicaciones'], 500);
        }

        if (is_file($dataPath) && file_get_contents($dataPath) !== $json) {
            $backupPath = dirname($dataPath) . DIRECTORY_SEPARATOR . 'ubicaciones_puntos_mapa.backup.' . date('YmdHis') . '.json';
            if (!copy($dataPath, $backupPath)) {
                jsonResponse(['ok' => false, 'error' => 'No se pudo crear la copia de ubicaciones'], 500);
            }
        }

        $tempPath = $dataPath . '.tmp';
        if (file_put_contents($tempPath, $json, LOCK_EX) === false || !rename($tempPath, $dataPath)) {
            if (is_file($tempPath)) unlink($tempPath);
            jsonResponse(['ok' => false, 'error' => 'No se pudieron guardar las ubicaciones'], 500);
        }

        jsonResponse(['ok' => true, 'message' => 'Ubicaciones guardadas correctamente']);
    }

    if ($action === 'guardar_panoramas') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
        }

        $panoramas = requestJson();
        if ($panoramas === [] || array_keys($panoramas) !== range(0, count($panoramas) - 1)) {
            jsonResponse(['ok' => false, 'error' => 'La lista de panoramas está vacía o no es válida'], 422);
        }

        $dataPath = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'colegio_santander.json';
        $json = json_encode($panoramas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            jsonResponse(['ok' => false, 'error' => 'No se pudieron codificar los datos'], 500);
        }

        if (is_file($dataPath)) {
            $backupPath = dirname($dataPath) . DIRECTORY_SEPARATOR . 'colegio_santander.backup.' . date('YmdHis') . '.json';
            if (!copy($dataPath, $backupPath)) {
                jsonResponse(['ok' => false, 'error' => 'No se pudo crear la copia de seguridad'], 500);
            }
        }

        $tempPath = $dataPath . '.tmp';
        if (file_put_contents($tempPath, $json, LOCK_EX) === false || !rename($tempPath, $dataPath)) {
            if (is_file($tempPath)) {
                unlink($tempPath);
            }
            jsonResponse(['ok' => false, 'error' => 'No se pudo escribir el archivo de panoramas'], 500);
        }

        jsonResponse(['ok' => true, 'message' => 'Panoramas guardados correctamente']);
    }

    $pdo = db();

    switch ($action) {
        case 'profesores':
            $q = trim((string)($_GET['q'] ?? ''));

            if ($q === '') {
                $stmt = $pdo->query(
                    'SELECT id_profesor, nombre_completo
                     FROM profesores
                     ORDER BY nombre_completo'
                );
                jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            }

            $stmt = $pdo->prepare(
                'SELECT id_profesor, nombre_completo
                 FROM profesores
                 WHERE nombre_completo LIKE :q
                 ORDER BY nombre_completo'
            );
            $stmt->execute(['q' => '%' . $q . '%']);

            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            break;

        case 'horarios':
            $profesor = trim((string)($_GET['profesor'] ?? ''));
            $grado = trim((string)($_GET['grado'] ?? ''));
            $dia = trim((string)($_GET['dia'] ?? ''));

            $sql =
                'SELECT
                    h.id_horario,
                    h.id_grado,
                          d.nombre AS dia_semana,
                          b.numero_bloque AS num_bloque_clase,
                          m.nombre AS materia,
                          p.nombre_completo AS id_profesor,
                          p.nombre_completo AS profesor,
                          h.id_profesor AS profesor_id,
                          s.nombre AS salon,
                          NULL AS hora_fin_sena,
                    b.hora_inicio,
                          b.hora_fin
                      FROM horario_semanal h
                      INNER JOIN dias_semana d ON d.id_dia = h.id_dia
                      INNER JOIN bloques_horarios b ON b.id_bloque = h.id_bloque
                      INNER JOIN materias m ON m.id_materia = h.id_materia
                      LEFT JOIN profesores p ON p.id_profesor = h.id_profesor
                      LEFT JOIN salones s ON s.id_salon = h.id_salon
                      WHERE h.estado <> \'CANCELADA\'';

            $params = [];

            if ($profesor !== '') {
                $sql .= ' AND (p.nombre_completo = :profesor_nombre OR CAST(h.id_profesor AS CHAR) = :profesor_id)';
                $params['profesor_nombre'] = $profesor;
                $params['profesor_id'] = $profesor;
            }
            if ($grado !== '') {
                $sql .= ' AND h.id_grado = :grado';
                $params['grado'] = $grado;
            }
            if ($dia !== '') {
                $sql .= ' AND d.nombre = :dia';
                $params['dia'] = $dia;
            }

            $sql .= ' ORDER BY h.id_grado, d.orden_dia, b.numero_bloque LIMIT 5000';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            break;

        case 'grados':
            $stmt = $pdo->query(
                'SELECT id_grado, id_jornada AS jornada
                 FROM grados
                 ORDER BY id_grado'
            );
            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            break;

        case 'catalogo_lugares':
            $stmt = $pdo->query(
                'SELECT id_lugar, nombre AS titulo
                 FROM lugares
                 WHERE activo = 1
                 ORDER BY nombre'
            );
            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            break;

        case 'lugares':
            $panoramaId = trim((string)($_GET['panorama_id'] ?? ''));
            $tableExists = $pdo->query(
                "SELECT COUNT(*) FROM information_schema.tables
                 WHERE table_schema = DATABASE() AND table_name = 'lugares_360'"
            )->fetchColumn();
            if ((int)$tableExists === 0) {
                jsonResponse(['ok' => true, 'items' => []]);
            }

            $sql =
                'SELECT
                    l.id_lugar,
                    l.panorama_id,
                    l.titulo,
                    l.descripcion,
                    l.pitch,
                    l.yaw,
                    h.id_horario,
                    h.id_grado,
                          d.nombre AS dia_semana,
                          m.nombre AS materia,
                          p.nombre_completo AS id_profesor,
                          s.nombre AS salon,
                    b.hora_inicio,
                    b.hora_fin
                 FROM lugares_360 l
                      LEFT JOIN horario_semanal h ON h.id_horario = l.id_horario
                      LEFT JOIN dias_semana d ON d.id_dia = h.id_dia
                      LEFT JOIN bloques_horarios b ON b.id_bloque = h.id_bloque
                      LEFT JOIN materias m ON m.id_materia = h.id_materia
                      LEFT JOIN profesores p ON p.id_profesor = h.id_profesor
                      LEFT JOIN salones s ON s.id_salon = h.id_salon
                 WHERE l.activo = 1';

            $params = [];

            if ($panoramaId !== '') {
                $sql .= ' AND l.panorama_id = :panorama_id';
                $params['panorama_id'] = $panoramaId;
            }

            $sql .= ' ORDER BY l.id_lugar';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            break;

        case 'guardar_lugar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['ok' => false, 'error' => 'Método no permitido'], 405);
            }

            $data = requestJson();
            $panoramaId = requiredString($data, 'panorama_id');
            $titulo = requiredString($data, 'titulo');
            $descripcion = trim((string)($data['descripcion'] ?? ''));
            $idHorarioRaw = $data['id_horario'] ?? null;
            $idHorario = $idHorarioRaw === null || $idHorarioRaw === '' ? null : filter_var($idHorarioRaw, FILTER_VALIDATE_INT);
            $pitch = (float)($data['pitch'] ?? 0);
            $yaw = (float)($data['yaw'] ?? 0);

            if ($idHorario === null) {
                $find = $pdo->prepare(
                    'SELECT id FROM lugares WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(:nombre)) LIMIT 1'
                );
                $find->execute(['nombre' => $titulo]);
                $idLugar = $find->fetchColumn();
                if ($idLugar === false) {
                    $insert = $pdo->prepare('INSERT INTO lugares (nombre) VALUES (:nombre)');
                    $insert->execute(['nombre' => $titulo]);
                    $idLugar = (int)$pdo->lastInsertId();
                }

                jsonResponse([
                    'ok' => true,
                    'id_lugar' => (int)$idLugar,
                    'message' => 'Lugar agregado al catálogo'
                ], 201);
            }

            if ($pitch < -90 || $pitch > 90 || $yaw < -360 || $yaw > 360) {
                jsonResponse(['ok' => false, 'error' => 'Coordenadas del hotspot inválidas'], 422);
            }

            if ($idHorario !== null && $idHorario <= 0) {
                jsonResponse(['ok' => false, 'error' => 'Debes seleccionar un horario válido'], 422);
            }

            if ($idHorario !== null) {
                $check = $pdo->prepare(
                    'SELECT id_horario FROM horario_semanal WHERE id_horario = :id_horario'
                );
                $check->execute(['id_horario' => $idHorario]);

                if (!$check->fetch()) {
                    jsonResponse(['ok' => false, 'error' => 'El horario seleccionado no existe'], 422);
                }
            }

            $stmt = $pdo->prepare(
                'INSERT INTO lugares_360
                    (panorama_id, titulo, descripcion, id_horario, pitch, yaw)
                 VALUES
                    (:panorama_id, :titulo, :descripcion, :id_horario, :pitch, :yaw)'
            );
            $stmt->execute([
                'panorama_id' => $panoramaId,
                'titulo' => $titulo,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'id_horario' => $idHorario,
                'pitch' => $pitch,
                'yaw' => $yaw,
            ]);

            jsonResponse([
                'ok' => true,
                'id_lugar' => (int)$pdo->lastInsertId(),
                'message' => 'Lugar guardado correctamente'
            ], 201);
            break;

        default:
            jsonResponse([
                'ok' => false,
                'error' => 'Acción no válida',
                'actions' => ['profesores', 'horarios', 'grados', 'catalogo_lugares', 'lugares', 'guardar_lugar']
            ], 404);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    jsonResponse([
        'ok' => false,
        'error' => 'Error de base de datos. Revisa la configuración de conexión.'
    ], 500);
} catch (Throwable $e) {
    error_log($e->getMessage());
    jsonResponse(['ok' => false, 'error' => 'Error interno del servidor'], 500);
}
