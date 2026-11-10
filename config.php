<?php
declare(strict_types=1);

if (!defined('APP_ENTRY')) {
    http_response_code(404);
    exit;
}

const DB_HOST = 'localhost';
const DB_NAME = 'phonebook';
const DB_USER = 'root';
const DB_PASSWORD = '';

function get_db_connection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $connection = new PDO($dsn, DB_USER, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connection;
}

function h(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_is_valid(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && is_string($_POST['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function empty_contact_values(): array
{
    return [
        'surname' => '',
        'name' => '',
        'lastname' => '',
        'gender' => '',
        'date' => '',
        'phone' => '',
        'location' => '',
        'email' => '',
        'comment' => '',
    ];
}

function contact_values_from_row(array $row): array
{
    return [
        'surname' => (string) ($row['surname'] ?? ''),
        'name' => (string) ($row['name'] ?? ''),
        'lastname' => (string) ($row['lastname'] ?? ''),
        'gender' => (string) ($row['gender'] ?? ''),
        'date' => (string) ($row['birth_date'] ?? ''),
        'phone' => (string) ($row['phone'] ?? ''),
        'location' => (string) ($row['address'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'comment' => (string) ($row['comment'] ?? ''),
    ];
}

function validate_contact_input(array $input): array
{
    $values = empty_contact_values();
    foreach ($values as $key => $_) {
        if (isset($input[$key]) && is_scalar($input[$key])) {
            $values[$key] = trim((string) $input[$key]);
        }
    }

    $errors = [];
    if ($values['surname'] === '') {
        $errors[] = 'Укажите фамилию.';
    }
    if ($values['name'] === '') {
        $errors[] = 'Укажите имя.';
    }
    if ($values['gender'] !== '' && !in_array($values['gender'], ['мужской', 'женский'], true)) {
        $errors[] = 'Выберите корректный вариант пола.';
    }
    if ($values['email'] !== '' && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Укажите корректный адрес электронной почты.';
    }
    if ($values['date'] !== '') {
        $date = DateTime::createFromFormat('!Y-m-d', $values['date']);
        $dateErrors = DateTime::getLastErrors();
        if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $date->format('Y-m-d') !== $values['date']) {
            $errors[] = 'Укажите корректную дату рождения.';
        }
    }

    $data = [
        'surname' => $values['surname'],
        'name' => $values['name'],
        'lastname' => $values['lastname'],
        'gender' => $values['gender'] !== '' ? $values['gender'] : null,
        'birth_date' => $values['date'] !== '' ? $values['date'] : null,
        'phone' => $values['phone'],
        'address' => $values['location'],
        'email' => $values['email'] !== '' ? $values['email'] : null,
        'comment' => $values['comment'],
    ];

    return ['data' => $data, 'values' => $values, 'errors' => $errors];
}

function format_birth_date(?string $date): string
{
    if ($date === null || $date === '') {
        return '—';
    }

    $timestamp = strtotime($date);
    return $timestamp === false ? h($date) : date('d.m.Y', $timestamp);
}

function first_letter(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (preg_match('/^./u', $value, $matches) === 1) {
        return $matches[0] . '.';
    }

    return substr($value, 0, 1) . '.';
}
