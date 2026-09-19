<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';
include_once __DIR__ . '/../includes/functions.php';

// Authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

$dpc_id = $_SESSION['user_id'];

// Fetch the DPC's department info
$stmt = $conn->prepare("SELECT u.department as dept_id, d.department_name FROM users u JOIN departments d ON u.department = d.id WHERE u.id = ?");
$stmt->execute([$dpc_id]);
$dpc_info = $stmt->fetch(PDO::FETCH_ASSOC);
$dept_id = $dpc_info['dept_id'];
$dept_name = $dpc_info['department_name'];

$active_session = $current_session;

// Maximum achievable score per category, on the same final scale used in the
// result reports. External is stored raw out of 100 in defense_scores (that's
// what panels/examiners enter), so it's converted to /30 for display here and
// converted back to raw before being saved.
$score_max = [
    'proposal' => 10,
    'internal' => 20,
    'external' => 30,
    'supervisor' => 40,
];

$response = ['success' => false, 'message' => ''];

// Handle AJAX correction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    try {
        $source = $_POST['source'] ?? '';
        $id = intval($_POST['id'] ?? 0);
        $score = $_POST['score'] ?? '';
        $comments = trim($_POST['comments'] ?? '');

        if ($source === 'panel') {
            $stmt = $conn->prepare("
                SELECT ds.id, dp.panel_type, s.department, s.session
                FROM defense_scores ds
                JOIN students s ON ds.student_id = s.id
                JOIN defense_panels dp ON ds.panel_id = dp.id
                WHERE ds.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || (int)$row['department'] !== (int)$dept_id || $row['session'] !== $active_session) {
                throw new Exception("Score record not found in your department for this session.");
            }

            $max = $score_max[$row['panel_type']] ?? 100;
            if (!is_numeric($score) || $score < 0 || $score > $max) {
                throw new Exception("Score must be between 0 and $max for a " . ucfirst($row['panel_type']) . " defense.");
            }

            // External is displayed and corrected on its /30 final scale, but
            // defense_scores stores the raw /100 value (what examiners actually
            // enter) - convert back before saving so later reports keep working.
            $score_to_store = ($row['panel_type'] === 'external') ? ($score / 30 * 100) : $score;

            $stmt = $conn->prepare("UPDATE defense_scores SET score = ?, comments = ? WHERE id = ?");
            $stmt->execute([$score_to_store, $comments, $id]);
        } elseif ($source === 'supervisor') {
            $stmt = $conn->prepare("
                SELECT sa.id, s.department, s.session
                FROM supervisor_assessments sa
                JOIN students s ON sa.student_id = s.id
                WHERE sa.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || (int)$row['department'] !== (int)$dept_id || $row['session'] !== $active_session) {
                throw new Exception("Score record not found in your department for this session.");
            }

            $max = $score_max['supervisor'];
            if (!is_numeric($score) || $score < 0 || $score > $max) {
                throw new Exception("Score must be between 0 and $max.");
            }

            $stmt = $conn->prepare("UPDATE supervisor_assessments SET score = ?, comments = ? WHERE id = ?");
            $stmt->execute([$score, $comments, $id]);
        } else {
            throw new Exception("Invalid score source.");
        }

        $response['success'] = true;
        $response['message'] = "Score corrected successfully.";
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Fetch every individual panel-based score for this department/session (not
// aggregated, so each entry is separately editable)
$stmt = $conn->prepare("
    SELECT ds.id, s.id as student_id, s.name as student_name, s.reg_no,
           dp.panel_type, dp.panel_name, u.name as scorer_name, ds.score, ds.comments
    FROM defense_scores ds
    JOIN students s ON ds.student_id = s.id
    JOIN defense_panels dp ON ds.panel_id = dp.id
    JOIN student_panel_assignments spa ON spa.student_id = s.id AND spa.panel_id = dp.id
    LEFT JOIN users u ON ds.supervisor_id = u.id
    WHERE s.department = ? AND s.session = ? AND spa.academic_session = ?
    ORDER BY FIELD(dp.panel_type, 'proposal', 'internal', 'external'), dp.panel_name, s.name
");
$stmt->execute([$dept_id, $active_session, $active_session]);
$panel_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch every supervisor assessment score for this department/session
$stmt = $conn->prepare("
    SELECT sa.id, s.id as student_id, s.name as student_name, s.reg_no,
           u.name as scorer_name, sa.score, sa.comments
    FROM supervisor_assessments sa
    JOIN students s ON sa.student_id = s.id
    LEFT JOIN users u ON sa.supervisor_id = u.id
    WHERE s.department = ? AND s.session = ? AND sa.academic_session = ?
    ORDER BY s.name
");
$stmt->execute([$dept_id, $active_session, $active_session]);
$supervisor_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped = ['proposal' => [], 'internal' => [], 'external' => []];
foreach ($panel_scores as $row) {
    $grouped[$row['panel_type']][] = $row;
}

$stage_labels = [
    'proposal' => 'Proposal Defense',
    'internal' => 'Internal Defense',
    'external' => 'External Defense',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correct Scores - <?= htmlspecialchars($dept_name) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4338ca; --success: #059669; --danger: #dc2626; --bg-body: #f1f5f9; --text-main: #1e293b; --text-muted: #64748b; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; }
        .container { max-width: 1200px; margin: 10px auto 40px auto; padding: 0 20px; }

        .header { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 24px; font-weight: 700; color: var(--primary); margin: 0; }

        .card { background: white; padding: 30px; border-radius: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .card h2 { font-size: 18px; margin: 0 0 20px; padding-bottom: 10px; border-bottom: 2px solid #f1f5f9; color: var(--primary); }

        .btn { padding: 10px 18px; border: none; border-radius: 10px; font-family: inherit; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: var(--primary); color: white; }

        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 14px; font-size: 12px; color: var(--text-muted); text-transform: uppercase; border-bottom: 2px solid #f1f5f9; }
        td { padding: 14px; border-bottom: 1px solid #f1f5f9; font-size: 14px; vertical-align: middle; }

        .empty-note { text-align: center; padding: 40px; color: var(--text-muted); }

        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background: white; margin: 60px auto; padding: 30px; border-radius: 20px; width: 500px; max-width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; color: var(--primary); font-size: 18px; }
        .close { font-size: 28px; cursor: pointer; color: #999; }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; font-size: 14px; }
        input[type="number"], textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-family: inherit; font-size: 16px; box-sizing: border-box; }
        textarea { height: 100px; resize: none; }
        input:focus, textarea:focus { border-color: var(--primary); outline: none; }

        .submit-btn { width: 100%; padding: 14px; background: var(--success); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; }
        .submit-btn:hover { background: #047857; }

        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { background: white; padding: 15px 25px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); margin-bottom: 10px; border-left: 5px solid var(--primary); }
    </style>
</head>
<body>
    <?php include_once __DIR__ . '/../includes/header.php'; ?>

    <div class="container">
        <?= csrf_field() ?>
        <div class="header">
            <div>
                <h1><i class="fas fa-pen-to-square"></i> Correct Scores</h1>
                <p style="color: var(--text-muted);">Session: <?= htmlspecialchars($active_session) ?> | Dept: <?= htmlspecialchars($dept_name) ?></p>
            </div>
            <a href="dpc_view_assessments.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Assessments</a>
        </div>

        <?php foreach ($stage_labels as $type => $label): ?>
            <div class="card">
                <h2><i class="fas fa-graduation-cap"></i> <?= $label ?> (Max <?= $score_max[$type] ?>)</h2>
                <?php if (empty($grouped[$type])): ?>
                    <div class="empty-note">No <?= strtolower($label) ?> scores recorded yet for this session.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Panel</th>
                                <th>Scored By</th>
                                <th style="text-align: center;">Score</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grouped[$type] as $row): ?>
                                <?php
                                    // External is stored raw out of 100 but shown/corrected on its /30 final scale
                                    $display_score = $row['score'];
                                    if ($type === 'external' && $display_score !== null) {
                                        $display_score = round($display_score / 100 * 30, 2);
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['student_name']) ?></strong><br>
                                        <small style="color: var(--text-muted);"><?= htmlspecialchars($row['reg_no']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['panel_name']) ?></td>
                                    <td><?= htmlspecialchars($row['scorer_name'] ?: 'Unknown') ?></td>
                                    <td style="text-align: center;"><strong><?= $display_score !== null ? $display_score . ' / ' . $score_max[$type] : '--' ?></strong></td>
                                    <td style="text-align: right;">
                                        <button type="button" class="btn btn-outline correct-btn"
                                            data-source="panel"
                                            data-id="<?= $row['id'] ?>"
                                            data-max="<?= $score_max[$type] ?>"
                                            data-title="<?= htmlspecialchars($row['student_name'] . ' - ' . $label, ENT_QUOTES) ?>"
                                            data-score="<?= htmlspecialchars($display_score ?? '', ENT_QUOTES) ?>"
                                            data-comments="<?= htmlspecialchars($row['comments'] ?: '', ENT_QUOTES) ?>"
                                        ><i class="fas fa-edit"></i> Correct</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="card">
            <h2><i class="fas fa-user-tie"></i> Supervisor Assessment (Max <?= $score_max['supervisor'] ?>)</h2>
            <?php if (empty($supervisor_scores)): ?>
                <div class="empty-note">No supervisor assessment scores recorded yet for this session.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Supervisor</th>
                            <th style="text-align: center;">Score</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($supervisor_scores as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['student_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= htmlspecialchars($row['reg_no']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['scorer_name'] ?: 'Unknown') ?></td>
                                <td style="text-align: center;"><strong><?= $row['score'] !== null ? $row['score'] . ' / ' . $score_max['supervisor'] : '--' ?></strong></td>
                                <td style="text-align: right;">
                                    <button type="button" class="btn btn-outline correct-btn"
                                        data-source="supervisor"
                                        data-id="<?= $row['id'] ?>"
                                        data-max="<?= $score_max['supervisor'] ?>"
                                        data-title="<?= htmlspecialchars($row['student_name'] . ' - Supervisor Assessment', ENT_QUOTES) ?>"
                                        data-score="<?= htmlspecialchars($row['score'] ?? '', ENT_QUOTES) ?>"
                                        data-comments="<?= htmlspecialchars($row['comments'] ?: '', ENT_QUOTES) ?>"
                                    ><i class="fas fa-edit"></i> Correct</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Correction Modal -->
    <div id="correctModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Correct Score</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="correctForm">
                <input type="hidden" name="ajax" value="1">
                <input type="hidden" name="source" id="modalSource">
                <input type="hidden" name="id" id="modalId">

                <div class="form-group">
                    <label for="score">Corrected Score (Max <span id="modalMax">100</span>)</label>
                    <input type="number" step="0.01" min="0" name="score" id="modalScore" required>
                </div>

                <div class="form-group">
                    <label for="comments">Comments</label>
                    <textarea name="comments" id="modalComments" placeholder="Optional reason for the correction..."></textarea>
                </div>

                <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Save Correction</button>
            </form>
        </div>
    </div>

    <div id="toast-container"></div>

    <script>
        const modal = document.getElementById("correctModal");

        document.querySelectorAll('.correct-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                openCorrectModal(
                    this.dataset.source,
                    this.dataset.id,
                    this.dataset.max,
                    this.dataset.title,
                    this.dataset.score,
                    this.dataset.comments
                );
            });
        });

        function openCorrectModal(source, id, max, title, score, comments) {
            document.getElementById("modalSource").value = source;
            document.getElementById("modalId").value = id;
            document.getElementById("modalTitle").innerText = "Correct: " + title;
            document.getElementById("modalMax").innerText = max;
            document.getElementById("modalScore").max = max;
            document.getElementById("modalScore").value = score || '';
            document.getElementById("modalComments").value = comments || '';
            modal.style.display = "block";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function (event) {
            if (event.target == modal) closeModal();
        };

        function showToast(msg, success = true) {
            const t = document.createElement('div');
            t.className = 'toast';
            t.style.borderLeftColor = success ? 'var(--success)' : 'var(--danger)';
            t.innerHTML = `<i class="fas fa-${success ? 'check-circle' : 'exclamation-circle'}" style="color:${success ? 'var(--success)' : 'var(--danger)'}; margin-right:10px;"></i> ${msg}`;
            document.getElementById('toast-container').appendChild(t);
            setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 500); }, 3000);
        }

        document.getElementById('correctForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            const token = document.querySelector('input[name="csrf_token"]').value;
            fd.append('csrf_token', token);
            try {
                const res = await fetch('dpc_correct_scores.php', { method: 'POST', body: fd });
                const data = await res.json();
                showToast(data.message, data.success);
                if (data.success) setTimeout(() => location.reload(), 1000);
            } catch (err) {
                showToast('A connection error occurred.', false);
            }
        });
    </script>

    <?php include_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
