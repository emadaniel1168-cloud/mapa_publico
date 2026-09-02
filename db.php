<?php

declare(strict_types=1);

/*
 * Configuración para XAMPP.
 * Cambia estos valores si tu instalación utiliza otros datos.
 * Nunca publiques este archivo con una contraseña real en un repositorio público.
 */
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'colegio_santander';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST
        . ';port=' . DB_PORT
        . ';dbname=' . DB_NAME
        . ';charset=utf8mb4';

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestJson(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);

    if (!is_array($data)) {
        jsonResponse(['ok' => false, 'error' => 'JSON inválido'], 400);
    }

    return $data;
}

function requiredString(array $data, string $key): string
{
    $value = trim((string)($data[$key] ?? ''));

    if ($value === '') {
        jsonResponse(['ok' => false, 'error' => "El campo {$key} es obligatorio"], 422);
    }

    return $value;
}
