<?php

$pageTitle = $pageTitle ?? 'Student Records';
$flashMessage = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Student Records</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="index.php">Student Records</a>
            <span class="header-label">Administration</span>
        </div>
    </header>

    <main class="container main-content">
        <?php if ($flashMessage !== null): ?>
            <div class="flash flash--<?= e($flashMessage['type'] ?? 'info') ?>" role="status">
                <?= e($flashMessage['msg'] ?? '') ?>
            </div>
        <?php endif; ?>
