<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Add student';
$student = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'course' => '',
    'enrolled_on' => '',
];
$errors = [];
$courses = [];
$databaseUnavailable = false;
$csrfToken = csrfToken();
$formAction = 'create.php';
$submitLabel = 'Add student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($student as $field => $default) {
        $value = $_POST[$field] ?? $default;
        $student[$field] = is_string($value)
            ? ($field === 'course' ? $value : trim($value))
            : '';
    }
}

try {
    $pdo = db();
    $courses = studentCourses($pdo);

    if ($courses === []) {
        $databaseUnavailable = true;
        http_response_code(503);
    }
} catch (PDOException $exception) {
    $databaseUnavailable = true;
    http_response_code(503);
}

if (!$databaseUnavailable && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? null;

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    } else {
        try {
            $errors = validateStudent($pdo, $student, $courses);
        } catch (PDOException $exception) {
            $errors['form'] = 'We could not validate the student details. Please try again.';
        }

        if ($errors === []) {
            try {
                $insertStatement = $pdo->prepare(
                    'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                     VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
                );
                $insertStatement->execute([
                    'first_name' => $student['first_name'],
                    'last_name' => $student['last_name'],
                    'email' => $student['email'],
                    'phone' => $student['phone'] === '' ? null : $student['phone'],
                    'course' => $student['course'],
                    'enrolled_on' => $student['enrolled_on'],
                ]);

                flash('success', 'Student added');
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
<?php else: ?>
    <section class="page-heading">
        <div>
            <h1>Add student</h1>
            <p>Enter the student's details below.</p>
        </div>
    </section>

    <?php require __DIR__ . '/partials/student-form.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
