<?php
require_once __DIR__ . '/evaluation_questions.php';

/**
 * Flags a user as owing a system evaluation. Safe to call repeatedly -
 * it is a no-op once the user has already submitted.
 */
function mark_evaluation_due($conn, $user_id) {
    if (empty($user_id)) return;
    try {
        $stmt = $conn->prepare("UPDATE users SET evaluation_due = 1 WHERE id = ? AND evaluation_submitted = 0");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("mark_evaluation_due failed: " . $e->getMessage());
    }
}

/**
 * Returns the evaluation gate state for the given user, or null if the
 * role has no evaluation instrument (e.g. admin, library, external examiner).
 */
function evaluation_gate_state($conn, $user_id, $system_role) {
    $role_key = evaluation_role_key($system_role);
    if (!$role_key) return null;

    $stmt = $conn->prepare("SELECT evaluation_due, evaluation_submitted, evaluation_remind_count FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;

    return [
        'role_key'      => $role_key,
        'due'           => (bool)$row['evaluation_due'],
        'submitted'     => (bool)$row['evaluation_submitted'],
        'remind_count'  => (int)$row['evaluation_remind_count'],
        'max_reminders' => 2,
    ];
}

/**
 * Renders the sidebar "Evaluation" nav link with a Pending badge or a
 * completed checkmark, depending on gate state. Returns '' for roles
 * with no evaluation instrument.
 */
function render_evaluation_nav_link($eval_gate) {
    if (!$eval_gate) return '';

    $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : '/';
    $active = (strpos($_SERVER['PHP_SELF'], '/evaluation/') !== false) ? 'active' : '';

    if ($eval_gate['submitted']) {
        $badge = '<i class="fa-solid fa-circle-check" style="margin-left:auto; color:#1cc88a;" title="Evaluation submitted"></i>';
    } elseif ($eval_gate['due']) {
        $badge = '<span style="margin-left:auto; background:#fff3cd; color:#856404; padding:2px 9px; border-radius:10px; font-size:10px; font-weight:700;">PENDING</span>';
    } else {
        $badge = '';
    }

    return '<a href="' . htmlspecialchars($root) . 'evaluation/index.php" class="' . $active . '" style="display:flex; align-items:center; gap:10px;">'
        . '<i class="fa-solid fa-clipboard-question"></i> Evaluation ' . $badge
        . '</a>';
}

/**
 * Filenames that must never be blocked by the evaluation gate, even
 * when an evaluation is due, so the user is never fully locked out.
 */
function evaluation_gate_exempt($current_page) {
    $exempt_substrings = [
        'change_password',
        'profile',
        'evaluation', // the module's own pages (evaluation/index.php, submit.php, defer.php)
    ];
    foreach ($exempt_substrings as $needle) {
        if (strpos($current_page, $needle) !== false) return true;
    }
    // index.php covers login/logout/home
    if ($current_page === 'index.php') return true;

    return false;
}
