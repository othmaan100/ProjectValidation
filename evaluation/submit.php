<?php
session_start();
include_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

function fail($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    fail('Your session has expired. Please log in again.', 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Invalid request method.', 405);
}

$system_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$role_key = evaluation_role_key($system_role);

if (!$role_key) {
    fail('Your role does not have a system evaluation questionnaire.');
}

// Duplicate-submission guard - server side, not just UI
$stmt = $conn->prepare("SELECT evaluation_submitted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if ((int)$stmt->fetchColumn() === 1) {
    fail('You have already submitted this evaluation.');
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    fail('Malformed submission.');
}

$years_experience = trim($payload['years_experience'] ?? '');
$answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];

if ($years_experience === '') {
    fail('Please state your years/period of experience with the system.');
}

$definitions = evaluation_questionnaire_definitions();
$questionnaire = $definitions[$role_key];

// Re-derive every required question from the authoritative definition -
// never trust client-supplied question text/section labels.
$rows_to_insert = [];
foreach ($questionnaire['sections'] as $section) {
    foreach ($section['questions'] as $q) {
        $no = $q['no'];
        $raw_value = isset($answers[$no]) ? trim((string)$answers[$no]) : '';

        if ($raw_value === '') {
            fail("Question {$no} ({$section['title']}) is required.");
        }

        if ($section['type'] === 'likert') {
            if (!in_array($raw_value, ['1', '2', '3', '4', '5'], true)) {
                fail("Question {$no} has an invalid rating value.");
            }
            $rows_to_insert[] = [$section['title'], $no, $q['text'], (int)$raw_value, null];
        } elseif ($section['type'] === 'choice') {
            if (!in_array($raw_value, $q['options'], true)) {
                fail("Question {$no} has an invalid option.");
            }
            $rows_to_insert[] = [$section['title'], $no, $q['text'], null, $raw_value];
        } else { // text
            $rows_to_insert[] = [$section['title'], $no, $q['text'], null, $raw_value];
        }
    }
}

// Respondent info (Department/Faculty/Session captured from the account,
// years of experience captured as its own response row)
$stmt = $conn->prepare("
    SELECT u.department AS department_id, d.department_name,
           u.faculty_id, f.faculty AS faculty_name, u.session
    FROM users u
    LEFT JOIN departments d ON u.department = d.id
    LEFT JOIN faculty f ON (u.faculty_id = f.id OR d.faculty_id = f.id)
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$academic_session = $profile['session'] ?: $current_session;

array_unshift($rows_to_insert, ['Respondent Information', 'RI1', 'Years/Period of experience with the system', null, $years_experience]);

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO evaluations (user_id, respondent_role, role, questionnaire_version, department_id, department_name, faculty_id, faculty_name, academic_session)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id, $system_role, $role_key, $questionnaire['version'],
        $profile['department_id'] ?? null, $profile['department_name'] ?? null,
        $profile['faculty_id'] ?? null, $profile['faculty_name'] ?? null,
        $academic_session,
    ]);
    $evaluation_id = $conn->lastInsertId();

    $stmt = $conn->prepare("
        INSERT INTO evaluation_responses (evaluation_id, section, question_no, question_text, answer_value, answer_text)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($rows_to_insert as $row) {
        $stmt->execute([$evaluation_id, $row[0], $row[1], $row[2], $row[3], $row[4]]);
    }

    $stmt = $conn->prepare("UPDATE users SET evaluation_submitted = 1, evaluation_due = 0 WHERE id = ?");
    $stmt->execute([$user_id]);

    $conn->commit();
} catch (PDOException $e) {
    $conn->rollBack();
    // Unique key (user_id, questionnaire_version) blocks a genuine double-submit race
    if ($e->getCode() == 23000) {
        fail('You have already submitted this evaluation.');
    }
    error_log('Evaluation submit failed: ' . $e->getMessage());
    fail('Could not save your evaluation. Please try again.', 500);
}

unset($_SESSION['evaluation_reminded_this_login']);

$dashboards = [
    'stu' => 'student/index.php',
    'sup' => 'supervisor/index.php',
    'dpc' => 'department_project_coordinator/index.php',
    'fpc' => 'faculty_project_coordinator/index.php',
    'hod' => 'hod/index.php',
];
$redirect = PROJECT_ROOT . ($dashboards[$system_role] ?? 'index.php') . '?evaluation=submitted';

echo json_encode(['success' => true, 'redirect' => $redirect]);
