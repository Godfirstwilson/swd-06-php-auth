<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Students';
$databaseUnavailable = false;

try {
    $statement = db()->query(
        'SELECT id, first_name, last_name, email, course, enrolled_on
         FROM students
         ORDER BY last_name ASC, first_name ASC'
    );
    $students = $statement->fetchAll();
} catch (PDOException $exception) {
    http_response_code(503);
    $databaseUnavailable = true;
    $students = [];
}

require __DIR__ . '/partials/header.php';
?>

<?php if ($databaseUnavailable): ?>
    <section class="card error-card" role="alert">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn't connect to the student records database. Please make sure MySQL is running, then try again.</p>
    </section>
<?php else: ?>
    <section class="page-heading">
        <div>
            <h1>Students</h1>
            <p>View and manage student records.</p>
        </div>
        <a class="button" href="create.php">Add student</a>
    </section>

    <section class="card" aria-label="Student list">
        <?php if ($students === []): ?>
            <div class="empty-state">
                <strong>No students yet</strong>
                Add a student to get started.
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="student-table">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Course</th>
                            <th scope="col">Enrolled</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="student-name">
                                    <?= e($student['first_name']) ?> <?= e($student['last_name']) ?>
                                </td>
                                <td><?= e($student['email']) ?></td>
                                <td><?= e($student['course']) ?></td>
                                <td><?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="edit.php?id=<?= (int) $student['id'] ?>">Edit</a>
                                        <a class="action-delete" href="delete.php?id=<?= (int) $student['id'] ?>">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
