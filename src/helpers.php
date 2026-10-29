<?php

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['_flash'] = [
        'type' => $type,
        'msg' => $msg,
    ];
}

function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $message = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);

    return is_array($message) ? $message : null;
}

function csrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function enumValues(string $columnType): array
{
    if (!preg_match('/^enum\((.*)\)$/i', $columnType, $matches)) {
        return [];
    }

    $definition = $matches[1];
    $length = strlen($definition);
    $values = [];
    $index = 0;

    while ($index < $length) {
        while ($index < $length && (ctype_space($definition[$index]) || $definition[$index] === ',')) {
            $index++;
        }

        if ($index >= $length || $definition[$index] !== "'") {
            return [];
        }

        $index++;
        $value = '';
        $closed = false;

        while ($index < $length) {
            $character = $definition[$index];

            if ($character === '\\') {
                if ($index + 1 >= $length) {
                    return [];
                }

                $escaped = $definition[$index + 1];
                $escapeMap = [
                    '0' => "\0",
                    'b' => "\x08",
                    'n' => "\n",
                    'r' => "\r",
                    't' => "\t",
                    'Z' => "\x1a",
                    '\\' => '\\',
                    "'" => "'",
                    '"' => '"',
                ];
                $value .= $escapeMap[$escaped] ?? $escaped;
                $index += 2;
                continue;
            }

            if ($character === "'") {
                $index++;
                $closed = true;
                break;
            }

            $value .= $character;
            $index++;
        }

        if (!$closed) {
            return [];
        }

        $values[] = $value;
    }

    return $values;
}

function studentCourses(PDO $pdo): array
{
    $statement = $pdo->query(
        "SELECT COLUMN_TYPE
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'students'
           AND COLUMN_NAME = 'course'
         LIMIT 1"
    );
    $columnType = $statement->fetchColumn();

    return is_string($columnType) ? enumValues($columnType) : [];
}

function validateStudent(PDO $pdo, array $student, array $courses, ?int $ignoreStudentId = null): array
{
    $errors = [];
    $namePattern = '/^(?=.*\p{L})[\p{L} -]{2,60}$/u';

    foreach (['first_name', 'last_name'] as $field) {
        if (!preg_match($namePattern, $student[$field])) {
            $errors[$field] = 'Enter 2–60 characters using letters, spaces, or hyphens.';
        }
    }

    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($student['phone'] !== '' && !preg_match('/\A0[789][01]\d{8}\z/', $student['phone'])) {
        $errors['phone'] = 'Enter a valid 11-digit phone number or leave this blank.';
    }

    if (!in_array($student['course'], $courses, true)) {
        $errors['course'] = 'Select one of the available courses.';
    }

    $enrolledDate = DateTimeImmutable::createFromFormat('!Y-m-d', $student['enrolled_on']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $validDate = $enrolledDate !== false
        && $enrolledDate->format('Y-m-d') === $student['enrolled_on']
        && ($dateErrors === false || ($dateErrors['warning_count'] === 0 && $dateErrors['error_count'] === 0));

    if (!$validDate || $enrolledDate > new DateTimeImmutable('today')) {
        $errors['enrolled_on'] = 'Enter a valid enrollment date that is not in the future.';
    }

    if (!isset($errors['email'])) {
        $query = 'SELECT id FROM students WHERE email = :email';
        $parameters = ['email' => $student['email']];

        if ($ignoreStudentId !== null) {
            $query .= ' AND id <> :student_id';
            $parameters['student_id'] = $ignoreStudentId;
        }

        $statement = $pdo->prepare($query . ' LIMIT 1');
        $statement->execute($parameters);

        if ($statement->fetchColumn() !== false) {
            $errors['email'] = 'This email address is already in use.';
        }
    }

    return $errors;
}
