<?php

require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/Database.php';

$pageTitle = 'Students';
$csrfToken = csrfToken();
$databaseUnavailable = false;
$filterError = false;
$students = [];
$courses = [];
$totalStudents = 0;
$totalPages = 1;
$page = 1;
$perPage = 10;

$searchParameter = $_GET['q'] ?? '';
$selectedCourseParameter = $_GET['course'] ?? '';
$search = is_string($searchParameter) ? trim($searchParameter) : '';
$selectedCourse = is_string($selectedCourseParameter) ? $selectedCourseParameter : '';
$requestedPage = filter_var(
    $_GET['page'] ?? 1,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($requestedPage === false) {
    http_response_code(400);
    $filterError = true;
}

if (!is_string($searchParameter) || !is_string($selectedCourseParameter)) {
    http_response_code(400);
    $filterError = true;
}

try {
    $pdo = db();
    $courses = studentCourses($pdo);

    if ($selectedCourse !== '' && !in_array($selectedCourse, $courses, true)) {
        http_response_code(400);
        $filterError = true;
        $selectedCourse = '';
    }

    if (!$filterError) {
        $conditions = [];
        $filterParameters = [];

        if ($search !== '') {
            $conditions[] = '(first_name LIKE :first_name
                OR last_name LIKE :last_name
                OR email LIKE :email)';
            $searchPattern = '%' . $search . '%';
            $filterParameters['first_name'] = $searchPattern;
            $filterParameters['last_name'] = $searchPattern;
            $filterParameters['email'] = $searchPattern;
        }

        if ($selectedCourse !== '') {
            $conditions[] = 'course = :course';
            $filterParameters['course'] = $selectedCourse;
        }

        $whereClause = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);
        $countStatement = $pdo->prepare('SELECT COUNT(*) FROM students' . $whereClause);
        $countStatement->execute($filterParameters);
        $totalStudents = (int) $countStatement->fetchColumn();
        $totalPages = max(1, (int) ceil($totalStudents / $perPage));
        $page = min($requestedPage, $totalPages);
        $offset = ($page - 1) * $perPage;

        $statement = $pdo->prepare(
            'SELECT id, first_name, last_name, email, course, enrolled_on
             FROM students' . $whereClause . '
             ORDER BY last_name ASC, first_name ASC
             LIMIT :limit OFFSET :offset'
        );

        foreach ($filterParameters as $name => $value) {
            $statement->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $students = $statement->fetchAll();
    }
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

    <?php if ($filterError): ?>
        <div class="flash flash--error" role="alert">The search, page number, or course filter is invalid.</div>
    <?php endif; ?>

    <form class="card student-filters" action="index.php" method="get">
        <div class="filter-field">
            <label for="q">Search</label>
            <input id="q" name="q" type="search" value="<?= e($search) ?>"
                   placeholder="Name or email">
        </div>
        <div class="filter-field">
            <label for="course">Course</label>
            <select id="course" name="course">
                <option value="">All courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>" <?= $selectedCourse === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-actions">
            <button class="button" type="submit">Apply filters</button>
            <a class="button button--secondary" href="index.php">Clear</a>
        </div>
    </form>

    <section class="card" aria-label="Student list">
        <?php if ($students === []): ?>
            <div class="empty-state">
                <strong><?= $totalStudents === 0 ? 'No students found' : 'No students on this page' ?></strong>
                <?php if ($search !== '' || $selectedCourse !== ''): ?>
                    Try changing or clearing the filters.
                <?php else: ?>
                    Add a student to get started.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-summary">
                Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalStudents) ?>
                of <?= $totalStudents ?> students
            </div>
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
                                    <a href="show.php?id=<?= (int) $student['id'] ?>">
                                        <?= e($student['first_name']) ?> <?= e($student['last_name']) ?>
                                    </a>
                                </td>
                                <td><?= e($student['email']) ?></td>
                                <td><?= e($student['course']) ?></td>
                                <td><?= e((new DateTimeImmutable($student['enrolled_on']))->format('d M Y')) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="show.php?id=<?= (int) $student['id'] ?>">View</a>
                                        <a href="edit.php?id=<?= (int) $student['id'] ?>">Edit</a>
                                        <form class="delete-form" action="delete.php" method="post"
                                              data-confirm="<?= e('Delete ' . $student['first_name'] . ' ' . $student['last_name'] . '?') ?>"
                                              onsubmit="return confirm(this.dataset.confirm)">
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                                            <button class="action-delete" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Student list pages">
            <?php if ($page > 1): ?>
                <a class="pagination-link"
                   href="index.php?<?= e(http_build_query(['q' => $search, 'course' => $selectedCourse, 'page' => $page - 1])) ?>">
                    Previous
                </a>
            <?php endif; ?>

            <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                <a class="pagination-link<?= $pageNumber === $page ? ' is-current' : '' ?>"
                   href="index.php?<?= e(http_build_query(['q' => $search, 'course' => $selectedCourse, 'page' => $pageNumber])) ?>"
                   <?= $pageNumber === $page ? 'aria-current="page"' : '' ?>>
                    <?= $pageNumber ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a class="pagination-link"
                   href="index.php?<?= e(http_build_query(['q' => $search, 'course' => $selectedCourse, 'page' => $page + 1])) ?>">
                    Next
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
