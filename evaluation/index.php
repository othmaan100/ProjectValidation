<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$system_role = $_SESSION['role'];
$role_key = evaluation_role_key($system_role);

// Roles without an evaluation instrument (admin, lib, ext, guest) don't belong here
if (!$role_key) {
    header("Location: " . PROJECT_ROOT . "index.php");
    exit();
}

$dashboards = [
    'stu' => 'student/index.php',
    'sup' => 'supervisor/index.php',
    'dpc' => 'department_project_coordinator/index.php',
    'fpc' => 'faculty_project_coordinator/index.php',
    'hod' => 'hod/index.php',
];
$dashboard_url = PROJECT_ROOT . ($dashboards[$system_role] ?? 'index.php');

// Already submitted - nothing to do here
$stmt = $conn->prepare("SELECT evaluation_submitted FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ((int)$stmt->fetchColumn() === 1) {
    header("Location: " . $dashboard_url . "?evaluation=already_submitted");
    exit();
}

$definitions = evaluation_questionnaire_definitions();
$questionnaire = $definitions[$role_key];

// Respondent info (auto-filled from account, matches "already captured" fields)
$stmt = $conn->prepare("
    SELECT u.name, u.department AS department_id, d.department_name,
           u.faculty_id, f.faculty AS faculty_name, u.session
    FROM users u
    LEFT JOIN departments d ON u.department = d.id
    LEFT JOIN faculty f ON (u.faculty_id = f.id OR d.faculty_id = f.id)
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$academic_session = $profile['session'] ?: $current_session;

$total_steps = 1 + count($questionnaire['sections']); // + respondent info step
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Evaluation | Project Validation System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --success: #1cc88a; --danger: #e74a3b; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; padding: 30px 15px 60px; }
        .eval-container { max-width: 820px; margin: 0 auto; }
        .eval-card { background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 35px; }
        .eval-header h1 { margin: 0 0 5px; color: var(--primary); font-size: 24px; }
        .eval-header p { margin: 0; color: #636e72; font-size: 14px; }
        .eval-note { background: #f0f7ff; border: 1px solid #d0e1fd; border-radius: 12px; padding: 14px 18px; font-size: 13px; color: #2d3436; margin-top: 18px; line-height: 1.6; }

        .eval-progress-track { background: #eef1f8; border-radius: 20px; height: 10px; margin-top: 22px; overflow: hidden; }
        .eval-progress-bar { background: linear-gradient(90deg, var(--primary), var(--secondary)); height: 100%; width: 0%; transition: width 0.3s; }
        .eval-step-label { text-align: center; font-size: 12px; font-weight: 700; color: #636e72; text-transform: uppercase; letter-spacing: 0.5px; margin: 10px 0 25px; }

        .eval-step { display: none; }
        .eval-step.active { display: block; animation: fadeIn 0.25s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .eval-step h2 { font-size: 18px; color: #2d3436; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #f1f2f6; }

        .form-row { margin-bottom: 18px; }
        .form-row label.field-label { display: block; font-weight: 600; color: #2d3436; margin-bottom: 6px; font-size: 14px; }
        .form-row input[type=text], .form-row select {
            width: 100%; padding: 12px 14px; border: 2px solid #eef1f8; border-radius: 10px; font-size: 14px; font-family: inherit; background: #f8faff;
        }
        .form-row input[readonly] { background: #f1f2f6; color: #636e72; }

        .question-block { border: 1px solid #f1f2f6; border-radius: 14px; padding: 16px 18px; margin-bottom: 14px; background: #fbfbfd; }
        .question-text { font-weight: 600; color: #2d3436; font-size: 14px; margin-bottom: 12px; line-height: 1.5; }
        .question-no { display: inline-block; background: var(--primary); color: #fff; font-size: 11px; font-weight: 700; border-radius: 6px; padding: 2px 8px; margin-right: 8px; }

        .likert-scale { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
        .likert-option { text-align: center; }
        .likert-option label { display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: 11px; color: #636e72; cursor: pointer; }
        .likert-option input { width: 18px; height: 18px; cursor: pointer; }

        .choice-options { display: flex; flex-direction: column; gap: 8px; }
        .choice-options label { display: flex; align-items: center; gap: 10px; font-size: 14px; color: #2d3436; cursor: pointer; background: #fff; border: 1px solid #eef1f8; border-radius: 10px; padding: 10px 14px; }
        .choice-options input { width: 16px; height: 16px; }

        textarea { width: 100%; min-height: 100px; padding: 12px 14px; border: 2px solid #eef1f8; border-radius: 10px; font-family: inherit; font-size: 14px; resize: vertical; background: #f8faff; }

        .eval-nav { display: flex; justify-content: space-between; margin-top: 30px; gap: 12px; }
        .btn { padding: 12px 26px; border: none; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: #5a67d8; }
        .btn-secondary { background: #eef1f8; color: #2d3436; }
        .btn-success { background: var(--success); color: #fff; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .eval-error { background: #ffebee; color: #c62828; border-radius: 10px; padding: 12px 16px; font-size: 13px; margin-bottom: 16px; display: none; }

        @media (max-width: 600px) {
            .eval-card { padding: 22px; }
            .likert-scale { grid-template-columns: repeat(5, 1fr); gap: 4px; }
            .likert-option label { font-size: 9px; }
        }
    </style>
</head>
<body>
    <div class="eval-container">
        <div class="eval-card">
            <div class="eval-header">
                <h1><i class="fas fa-clipboard-check"></i> <?= htmlspecialchars($questionnaire['title']) ?></h1>
                <p>Target Respondent: <?= htmlspecialchars($questionnaire['target']) ?> &middot; A Hybrid AI-Driven Project Topics Validation System</p>
                <div class="eval-note">
                    This questionnaire is designed to obtain structured feedback from system users for the evaluation and continuous improvement of the Project Topics Validation System.
                    For the rating sections, select one response for each statement: 1 = Strongly Disagree, 2 = Disagree, 3 = Neutral, 4 = Agree, 5 = Strongly Agree.
                </div>
            </div>

            <div class="eval-progress-track"><div class="eval-progress-bar" id="progressBar"></div></div>
            <p class="eval-step-label" id="stepLabel">Section 1 of <?= $total_steps ?></p>

            <div class="eval-error" id="evalError"></div>

            <form id="evalForm" novalidate>
                <!-- Step 0: Respondent Information -->
                <div class="eval-step active" data-step="0">
                    <h2>Respondent Information</h2>
                    <div class="form-row">
                        <label class="field-label">Department</label>
                        <input type="text" value="<?= htmlspecialchars($profile['department_name'] ?? 'N/A') ?>" readonly>
                    </div>
                    <div class="form-row">
                        <label class="field-label">Faculty</label>
                        <input type="text" value="<?= htmlspecialchars($profile['faculty_name'] ?? 'N/A') ?>" readonly>
                    </div>
                    <div class="form-row">
                        <label class="field-label">Academic Session</label>
                        <input type="text" value="<?= htmlspecialchars($academic_session ?: 'N/A') ?>" readonly>
                    </div>
                    <div class="form-row">
                        <label class="field-label">Years/Period of experience with the system <span style="color:var(--danger)">*</span></label>
                        <input type="text" name="years_experience" id="years_experience" placeholder="e.g. 6 months, 1 session" required>
                    </div>
                </div>

                <?php foreach ($questionnaire['sections'] as $s_idx => $section): ?>
                    <div class="eval-step" data-step="<?= $s_idx + 1 ?>">
                        <h2><?= htmlspecialchars($section['title']) ?></h2>

                        <?php foreach ($section['questions'] as $q): ?>
                            <div class="question-block">
                                <div class="question-text"><span class="question-no"><?= htmlspecialchars($q['no']) ?></span><?= htmlspecialchars($q['text']) ?></div>

                                <?php if ($section['type'] === 'likert'): ?>
                                    <div class="likert-scale">
                                        <?php
                                        $labels = [1 => 'Strongly Disagree', 2 => 'Disagree', 3 => 'Neutral', 4 => 'Agree', 5 => 'Strongly Agree'];
                                        foreach ($labels as $val => $label): ?>
                                            <div class="likert-option">
                                                <label>
                                                    <input type="radio" name="q_<?= htmlspecialchars($q['no']) ?>" value="<?= $val ?>" required>
                                                    <span><?= $val ?><br><?= $label ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($section['type'] === 'choice'): ?>
                                    <div class="choice-options">
                                        <?php foreach ($q['options'] as $opt): ?>
                                            <label>
                                                <input type="radio" name="q_<?= htmlspecialchars($q['no']) ?>" value="<?= htmlspecialchars($opt) ?>" required>
                                                <?= htmlspecialchars($opt) ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <textarea name="q_<?= htmlspecialchars($q['no']) ?>" required placeholder="Type your answer..."></textarea>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div class="eval-nav">
                    <button type="button" class="btn btn-secondary" id="prevBtn" disabled>Back</button>
                    <button type="button" class="btn btn-primary" id="nextBtn">Next <i class="fas fa-arrow-right"></i></button>
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display:none;"><i class="fas fa-check"></i> Submit Evaluation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const steps = Array.from(document.querySelectorAll('.eval-step'));
        const totalSteps = steps.length;
        let current = 0;

        const progressBar = document.getElementById('progressBar');
        const stepLabel = document.getElementById('stepLabel');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const evalError = document.getElementById('evalError');

        function showError(msg) {
            evalError.textContent = msg;
            evalError.style.display = 'block';
            evalError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        function clearError() { evalError.style.display = 'none'; }

        function render() {
            steps.forEach((s, i) => s.classList.toggle('active', i === current));
            progressBar.style.width = (((current + 1) / totalSteps) * 100) + '%';
            stepLabel.textContent = 'Section ' + (current + 1) + ' of ' + totalSteps;
            prevBtn.disabled = current === 0;
            nextBtn.style.display = current === totalSteps - 1 ? 'none' : 'inline-block';
            submitBtn.style.display = current === totalSteps - 1 ? 'inline-block' : 'none';
        }

        function validateStep(index) {
            const step = steps[index];
            const radioGroups = {};
            step.querySelectorAll('input[type=radio][required]').forEach(r => { radioGroups[r.name] = radioGroups[r.name] || []; radioGroups[r.name].push(r); });
            for (const name in radioGroups) {
                if (!radioGroups[name].some(r => r.checked)) { showError('Please answer every question before continuing.'); return false; }
            }
            const texts = step.querySelectorAll('textarea[required], input[type=text][required]');
            for (const t of texts) {
                if (!t.value.trim()) { showError('Please fill in all required fields before continuing.'); return false; }
            }
            clearError();
            return true;
        }

        nextBtn.addEventListener('click', () => {
            if (!validateStep(current)) return;
            current = Math.min(current + 1, totalSteps - 1);
            render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        prevBtn.addEventListener('click', () => {
            current = Math.max(current - 1, 0);
            render();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.getElementById('evalForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!validateStep(current)) return;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const formData = new FormData(this);
            const answers = {};
            for (const [key, value] of formData.entries()) {
                if (key.startsWith('q_')) answers[key.substring(2)] = value;
            }

            try {
                const res = await fetch('submit.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        years_experience: formData.get('years_experience'),
                        answers: answers
                    })
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.message || 'Something went wrong. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Evaluation';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            } catch (err) {
                showError('Network error. Please check your connection and try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Submit Evaluation';
            }
        });

        render();
    </script>
</body>
</html>
