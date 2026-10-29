<?php

$student = $student ?? [];
$errors = $errors ?? [];
$courses = $courses ?? [];
$csrfToken = $csrfToken ?? '';
$formAction = $formAction ?? '';
$submitLabel = $submitLabel ?? 'Save student';
?>
<?php if (isset($errors['form'])): ?>
    <div class="flash flash--error" role="alert"><?= e($errors['form']) ?></div>
<?php endif; ?>

<form class="card student-form" action="<?= e($formAction) ?>" method="post">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <div class="form-grid">
        <div class="form-field">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" minlength="2" maxlength="60"
                   autocomplete="given-name" required value="<?= e($student['first_name'] ?? '') ?>">
            <?php if (isset($errors['first_name'])): ?>
                <p class="field-error"><?= e($errors['first_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" type="text" minlength="2" maxlength="60"
                   autocomplete="family-name" required value="<?= e($student['last_name'] ?? '') ?>">
            <?php if (isset($errors['last_name'])): ?>
                <p class="field-error"><?= e($errors['last_name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" maxlength="254"
                   autocomplete="email" required value="<?= e($student['email'] ?? '') ?>">
            <?php if (isset($errors['email'])): ?>
                <p class="field-error"><?= e($errors['email']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="phone">Phone <span class="optional">(optional)</span></label>
            <input id="phone" name="phone" type="tel" inputmode="numeric"
                   autocomplete="tel" pattern="0[789][01][0-9]{8}"
                   value="<?= e($student['phone'] ?? '') ?>">
            <?php if (isset($errors['phone'])): ?>
                <p class="field-error"><?= e($errors['phone']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="course">Course</label>
            <select id="course" name="course" required>
                <option value="">Select a course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= e($course) ?>" <?= ($student['course'] ?? '') === $course ? 'selected' : '' ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['course'])): ?>
                <p class="field-error"><?= e($errors['course']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-field">
            <label for="enrolled_on">Enrolled on</label>
            <input id="enrolled_on" name="enrolled_on" type="date"
                   max="<?= e(date('Y-m-d')) ?>" required value="<?= e($student['enrolled_on'] ?? '') ?>">
            <?php if (isset($errors['enrolled_on'])): ?>
                <p class="field-error"><?= e($errors['enrolled_on']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-actions">
        <a class="button button--secondary" href="index.php">Cancel</a>
        <button class="button" type="submit"><?= e($submitLabel) ?></button>
    </div>
</form>
