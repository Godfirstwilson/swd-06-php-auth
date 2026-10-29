<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    header('Allow: GET, POST', true, 405);
    exit;
}

$confirmed = ($_GET['confirm'] ?? null) === 'yes';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$confirmed) {
    http_response_code(400);
}

$pageTitle = 'Seed sample students';
$csrfToken = csrfToken();
$errors = [];
$courses = [];
$databaseUnavailable = false;
$inserted = 0;
$alreadyPresent = 0;

if ($confirmed) {
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
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $confirmed && !$databaseUnavailable) {
    $submittedToken = $_POST['csrf_token'] ?? null;

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $errors['form'] = 'Your form session expired. Please try again.';
    } else {
        $firstNames = [
            'Adebayo', 'Chiamaka', 'Tunde', 'Ngozi', 'Fatima',
            'Ifeoma', 'Emeka', 'Zainab', 'Oluwaseun', 'Chinedu',
        ];
        $lastNames = [
            'Afolayan', 'Okafor', 'Bello', 'Adeyemi', 'Nwosu',
            'Ibrahim', 'Eze', 'Balogun', 'Abubakar', 'Okonkwo',
        ];

        try {
            $pdo->beginTransaction();
            $existsStatement = $pdo->prepare(
                'SELECT id FROM students WHERE email = :email LIMIT 1'
            );
            $insertStatement = $pdo->prepare(
                'INSERT INTO students (first_name, last_name, email, phone, course, enrolled_on)
                 VALUES (:first_name, :last_name, :email, :phone, :course, :enrolled_on)'
            );

            for ($index = 0; $index < 50; $index++) {
                $number = $index + 1;
                $email = sprintf('seed.student.%02d@example.com', $number);
                $existsStatement->execute(['email' => $email]);

                if ($existsStatement->fetchColumn() !== false) {
                    $alreadyPresent++;
                    continue;
                }

                $insertStatement->execute([
                    'first_name' => $firstNames[$index % count($firstNames)],
                    'last_name' => $lastNames[($index * 3 + intdiv($index, 10)) % count($lastNames)],
                    'email' => $email,
                    'phone' => sprintf('08%d%08d', $index % 2, 10000000 + $index),
                    'course' => $courses[$index % count($courses)],
                    'enrolled_on' => (new DateTimeImmutable('today'))
                        ->sub(new DateInterval('P' . ($index * 13) . 'D'))
                        ->format('Y-m-d'),
                ]);
                $inserted++;
            }

            $pdo->commit();
            flash(
                'success',
                sprintf(
                    'Seed data ready: %d students added; %d were already present.',
                    $inserted,
                    $alreadyPresent
                )
            );
            redirect('index.php');
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $inserted = 0;
            $alreadyPresent = 0;
            $errors['form'] = 'We could not insert the sample students. Check the students table and try again.';
        }
    }
}

require __DIR__ . '/partials/header.php';
?>

<section class="page-heading">
    <div>
        <h1>Seed sample students</h1>
        <p>Insert 50 clearly fictional Nigerian-named sample records for pagination testing.</p>
    </div>
</section>

<?php if (isset($errors['form'])): ?>
    <div class="flash flash--error" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

<?php if ($databaseUnavailable): ?>
    <section class="card error-card" role="alert">
        <h1>Student records are temporarily unavailable</h1>
        <p>We couldn't load the student database or its course options. Please check that MySQL is running and the students table is configured, then try again.</p>
    </section>
<?php elseif (!$confirmed): ?>
    <section class="card error-card">
        <h1>Confirmation required</h1>
        <p>To review the seed action, open <a href="seed.php?confirm=yes">seed.php?confirm=yes</a>. No records are added from this page.</p>
    </section>
<?php else: ?>
    <section class="card error-card">
        <h2>Ready to add sample records</h2>
        <p>This safely adds up to 50 sample students. Existing seed email addresses are skipped, so you can run it again without duplicating those records.</p>
        <form action="seed.php?confirm=yes" method="post" class="seed-actions">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <button class="button" type="submit">Insert 50 sample students</button>
            <a class="button button--secondary" href="index.php">Cancel</a>
        </form>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
