<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

try {
    $action = $_GET['action'] ?? '';
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
                    b.hora_inicio,
                    b.hora_fin
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

            $sql .= ' ORDER BY h.id_grado, h.dia_semana, h.num_bloque_clase LIMIT 100';

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

        case 'lugares':
            $panoramaId = trim((string)($_GET['panorama_id'] ?? ''));

            if ($panoramaId === '') {
                jsonResponse(['ok' => false, 'error' => 'Falta panorama_id'], 422);
            }

            $stmt = $pdo->prepare(
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
                 INNER JOIN horarios h ON h.id_horario = l.id_horario
                 INNER JOIN grados g ON g.id_grado = h.id_grado
                 INNER JOIN bloques_horarios b
                    ON b.jornada = g.jornada
                   AND b.num_bloque = CAST(h.num_bloque_clase AS CHAR)
                 WHERE l.panorama_id = :panorama_id
                   AND l.activo = 1
                 ORDER BY l.id_lugar'
            );
            $stmt->execute(['panorama_id' => $panoramaId]);

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
            $idHorario = filter_var($data['id_horario'] ?? null, FILTER_VALIDATE_INT);
            $pitch = (float)($data['pitch'] ?? 0);
            $yaw = (float)($data['yaw'] ?? 0);

            if (!$idHorario) {
                jsonResponse(['ok' => false, 'error' => 'Debes seleccionar un horario válido'], 422);
            }

            if ($pitch < -90 || $pitch > 90 || $yaw < -360 || $yaw > 360) {
                jsonResponse(['ok' => false, 'error' => 'Coordenadas del hotspot inválidas'], 422);
            }

            /* Validación fuerte: el id_horario debe existir en la BD. */
            $check = $pdo->prepare(
                'SELECT id_horario FROM horarios WHERE id_horario = :id_horario'
            );
            $check->execute(['id_horario' => $idHorario]);

            if (!$check->fetch()) {
                jsonResponse(['ok' => false, 'error' => 'El horario seleccionado no existe'], 422);
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
                'actions' => ['profesores', 'horarios', 'grados', 'lugares', 'guardar_lugar']
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
