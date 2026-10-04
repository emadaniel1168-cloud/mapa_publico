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
                    'SELECT id_profesor, especialidad
                     FROM profesores
                     ORDER BY id_profesor
                     LIMIT 10'
                );
                jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            }

            $stmt = $pdo->prepare(
                'SELECT id_profesor, especialidad
                 FROM profesores
                 WHERE id_profesor LIKE :q
                 ORDER BY id_profesor
                 LIMIT 10'
            );
            $stmt->execute(['q' => $q . '%']);

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
                    h.dia_semana,
                    h.num_bloque_clase,
                    h.materia,
                    h.id_profesor,
                    h.salon,
                    h.hora_fin_sena,
                    b.hora_inicio,
                    COALESCE(h.hora_fin_sena, b.hora_fin) AS hora_fin
                 FROM horarios h
                 INNER JOIN grados g ON g.id_grado = h.id_grado
                 INNER JOIN bloques_horarios b
                    ON b.jornada = g.jornada
                   AND b.num_bloque = CAST(h.num_bloque_clase AS CHAR)
                 WHERE 1 = 1';

            $params = [];

            if ($profesor !== '') {
                $sql .= ' AND h.id_profesor = :profesor';
                $params['profesor'] = $profesor;
            }
            if ($grado !== '') {
                $sql .= ' AND h.id_grado = :grado';
                $params['grado'] = $grado;
            }
            if ($dia !== '') {
                $sql .= ' AND h.dia_semana = :dia';
                $params['dia'] = $dia;
            }

            $sql .= ' ORDER BY h.id_grado, h.dia_semana, h.num_bloque_clase LIMIT 1000';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            break;

        case 'grados':
            $stmt = $pdo->query(
                'SELECT id_grado, jornada FROM grados ORDER BY id_grado'
            );
            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            break;

        case 'catalogo_lugares':
            $stmt = $pdo->query(
                'SELECT id AS id_lugar, nombre AS titulo FROM lugares ORDER BY nombre'
            );
            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);
            break;

        case 'lugares':
            $panoramaId = trim((string)($_GET['panorama_id'] ?? ''));

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
                    h.dia_semana,
                    h.materia,
                    h.id_profesor,
                    h.salon,
                    b.hora_inicio,
                    b.hora_fin
                 FROM lugares_360 l
                 LEFT JOIN horarios h ON h.id_horario = l.id_horario
                 LEFT JOIN grados g ON g.id_grado = h.id_grado
                 LEFT JOIN bloques_horarios b
                    ON b.jornada = g.jornada
                   AND b.num_bloque = CAST(h.num_bloque_clase AS CHAR)
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
                    'SELECT id_horario FROM horarios WHERE id_horario = :id_horario'
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
