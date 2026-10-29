<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST', true, 405);
    exit;
}

$studentId = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($studentId === false) {
    http_response_code(404);
}

$pageTitle = 'Edit student';
$student = [];
$errors = [];
$courses = [];
$databaseUnavailable = false;
$studentNotFound = $studentId === false;
$csrfToken = csrfToken();
$formAction = $studentId === false ? '' : 'edit.php?id=' . $studentId;
$submitLabel = 'Save changes';
$pdo = null;

if (!$studentNotFound) {
    try {
        $pdo = db();
        $courses = studentCourses($pdo);
        $statement = $pdo->prepare(
            'SELECT id, first_name, last_name, email, phone, course, enrolled_on
             FROM students
             WHERE id = :id'
        );
        $statement->execute(['id' => $studentId]);
        $student = $statement->fetch();

        if ($student === false || $courses === []) {
            $studentNotFound = $student === false;
            $databaseUnavailable = $courses === [];
            http_response_code($databaseUnavailable ? 503 : 404);
        }
    } catch (PDOException $exception) {
        $databaseUnavailable = true;
        http_response_code(503);
    }
}

if (!$databaseUnavailable && !$studentNotFound && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['first_name', 'last_name', 'email', 'phone', 'course', 'enrolled_on'] as $field) {
        $value = $_POST[$field] ?? '';
        $student[$field] = is_string($value)
            ? ($field === 'course' ? $value : trim($value))
            : '';
    }

    $submittedToken = $_POST['csrf_token'] ?? null;

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    } else {
        try {
            $errors = validateStudent($pdo, $student, $courses, $studentId);
        } catch (PDOException $exception) {
            $errors['form'] = 'We could not validate the student details. Please try again.';
        }

        if ($errors === []) {
            try {
                $statement = $pdo->prepare(
                    'UPDATE students
                     SET first_name = :first_name,
                         last_name = :last_name,
                         email = :email,
                         phone = :phone,
                         course = :course,
                         enrolled_on = :enrolled_on
                     WHERE id = :id'
                );
                $statement->execute([
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'phone' => $student['phone'] === '' ? null : $student['phone'],
                    'course' => $student['course'],
                    'enrolled_on' => $student['enrolled_on'],
                    'id' => $studentId,
                ]);

                flash('success', 'Student updated');
                redirect('index.php');
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) === 1062) {
                    $errors['email'] = 'This email address is already in use.';
                } else {
                    $errors['form'] = 'We could not save this student. Please try again.';
                }
            }
        }
    }
}

require __DIR__ . '/partials/header.php';
?>

<?php if ($databaseUnavailable): ?>
    <section class="card error-card" role="alert">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn't load the student database or its course options. Please check that MySQL is running and the students table is configured, then try again.</p>
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
            <h1>Edit student</h1>
            <p>Update the student's details below.</p>
        </div>
    </section>

    <?php require __DIR__ . '/partials/student-form.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
