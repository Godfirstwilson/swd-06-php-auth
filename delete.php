<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST', true, 405);
    exit;
}

$studentId = filter_var(
    $_POST['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($studentId === false) {
    http_response_code(400);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? null;

if (!is_string($submittedToken) || !hash_equals(csrfToken(), $submittedToken)) {
    http_response_code(403);
    exit;
}

try {
    $statement = db()->prepare('DELETE FROM students WHERE id = :id');
    $statement->execute(['id' => $studentId]);

    if ($statement->rowCount() === 0) {
        flash('error', 'Student not found.');
    } else {
        flash('success', 'Student deleted');
    }

    redirect('index.php');
} catch (PDOException $exception) {
    http_response_code(503);
    $pageTitle = 'Student records unavailable';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="card error-card" role="alert">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn't update the student records. Please check that MySQL is running, then try again.</p>
        <p><a href="index.php">Back to students</a></p>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
}
