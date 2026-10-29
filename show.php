<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$studentId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$student = false;
$databaseUnavailable = false;
$studentNotFound = $studentId === false;

if ($studentNotFound) {
    http_response_code(404);
} else {
    try {
        $statement = db()->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on, created_at, updated_at
             FROM students
             WHERE id = :id'
        );
        $statement->execute(['id' => $studentId]);
        $student = $statement->fetch();

        if ($student === false) {
            $studentNotFound = true;
            http_response_code(404);
        }
    } catch (PDOException $exception) {
        $databaseUnavailable = true;
        http_response_code(503);
    }
}

$pageTitle = $studentNotFound ? 'Student not found' : 'Student details';

require __DIR__ . '/partials/header.php';
?>

<?php if ($databaseUnavailable): ?>
    <section class="card error-card" role="alert">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn't load the student record. Please check that MySQL is running, then try again.</p>
    </section>
<?php elseif ($studentNotFound): ?>
    <section class="card error-card" role="alert">
        <h1>Student not found</h1>
        <p>This student record may have been removed. Return to the student list and try again.</p>
        <p><a href="index.php">Back to students</a></p>
    </section>
<?php else: ?>
    <section class="page-heading">
        <div>
            <h1><?= e($student['first_name']) ?> <?= e($student['last_name']) ?></h1>
            <p>Student record details.</p>
        </div>
        <a class="button" href="edit.php?id=<?= (int) $student['id'] ?>">Edit student</a>
    </section>

    <dl class="card detail-list">
        <div class="detail-row">
            <dt>Email</dt>
            <dd><a href="mailto:<?= e($student['email']) ?>"><?= e($student['email']) ?></a></dd>
        </div>
        <div class="detail-row">
            <dt>Phone</dt>
            <dd><?= $student['phone'] !== null && $student['phone'] !== '' ? e($student['phone']) : 'Not provided' ?></dd>
        </div>
        <div class="detail-row">
            <dt>Course</dt>
            <dd><?= e($student['course']) ?></dd>
        </div>
        <div class="detail-row">
            <dt>Enrolled on</dt>
            <dd><?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?></dd>
        </div>
        <div class="detail-row">
            <dt>Created</dt>
            <dd><?= e((new DateTimeImmutable($student['created_at']))->format('d M Y, H:i')) ?></dd>
        </div>
        <div class="detail-row">
            <dt>Last updated</dt>
            <dd><?= e((new DateTimeImmutable($student['updated_at']))->format('d M Y, H:i')) ?></dd>
        </div>
    </dl>

    <p class="back-link"><a href="index.php">← Back to students</a></p>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
