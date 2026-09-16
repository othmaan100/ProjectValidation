<?php
include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$format = ($_GET['format'] ?? 'wide') === 'long' ? 'long' : 'wide';
$definitions = evaluation_questionnaire_definitions();
$role_labels = ['student' => 'Student', 'supervisor' => 'Supervisor', 'coordinator' => 'Coordinator'];

$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

$header_fill = [
    'fillType' => Fill::FILL_SOLID,
    'startColor' => ['rgb' => '667EEA'],
];

if ($format === 'wide') {
    // One sheet per role, one row per respondent, one column per question
    foreach ($role_labels as $role_key => $label) {
        $questionnaire = $definitions[$role_key];

        // Flatten the canonical question list for this instrument
        $question_cols = []; // question_no => header label
        foreach ($questionnaire['sections'] as $section) {
            foreach ($section['questions'] as $q) {
                $question_cols[$q['no']] = '[' . $section['key'] . $q['no'] . '] ' . mb_strimwidth($q['text'], 0, 60, '...');
            }
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($label);

        $meta_headers = ['Evaluation ID', 'Respondent Name', 'Username', 'Respondent Role', 'Department', 'Faculty', 'Academic Session', 'Submitted At', 'Years/Period of Experience'];
        $col = 1;
        foreach ($meta_headers as $h) { $sheet->setCellValueByColumnAndRow($col, 1, $h); $col++; }
        $question_start_col = $col;
        foreach ($question_cols as $no => $label_text) { $sheet->setCellValueByColumnAndRow($col, 1, $label_text); $col++; }

        $header_range = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col - 1) . '1';
        $sheet->getStyle($header_range)->getFill()->applyFromArray($header_fill);
        $sheet->getStyle($header_range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');

        $stmt = $conn->prepare("
            SELECT e.id, u.name, u.username, e.respondent_role, e.department_name, e.faculty_name, e.academic_session, e.submitted_at
            FROM evaluations e JOIN users u ON u.id = e.user_id
            WHERE e.role = ? ORDER BY e.submitted_at ASC
        ");
        $stmt->execute([$role_key]);
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $row_num = 2;
        foreach ($evaluations as $ev) {
            $resp_stmt = $conn->prepare("SELECT question_no, answer_value, answer_text FROM evaluation_responses WHERE evaluation_id = ?");
            $resp_stmt->execute([$ev['id']]);
            $answers = [];
            $years_experience = '';
            foreach ($resp_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if ($r['question_no'] === 'RI1') { $years_experience = $r['answer_text']; continue; }
                $answers[$r['question_no']] = $r['answer_value'] !== null ? $r['answer_value'] : $r['answer_text'];
            }

            $col = 1;
            $meta_values = [$ev['id'], $ev['name'], $ev['username'], strtoupper($ev['respondent_role']), $ev['department_name'], $ev['faculty_name'], $ev['academic_session'], $ev['submitted_at'], $years_experience];
            foreach ($meta_values as $v) { $sheet->setCellValueByColumnAndRow($col, $row_num, $v); $col++; }
            foreach ($question_cols as $no => $label_text) {
                $sheet->setCellValueByColumnAndRow($col, $row_num, $answers[$no] ?? '');
                $col++;
            }
            $row_num++;
        }

        foreach (range(1, $question_start_col - 1) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
    }
} else {
    // Long format: one row per question-response pair, per role sheet
    foreach ($role_labels as $role_key => $label) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($label);

        $headers = ['Evaluation ID', 'Respondent Name', 'Username', 'Respondent Role', 'Department', 'Faculty', 'Academic Session', 'Submitted At', 'Section', 'Question No', 'Question Text', 'Answer Value (1-5)', 'Answer Text'];
        $sheet->fromArray($headers, null, 'A1');
        $header_range = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($header_range)->getFill()->applyFromArray($header_fill);
        $sheet->getStyle($header_range)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');

        $stmt = $conn->prepare("
            SELECT e.id, u.name, u.username, e.respondent_role, e.department_name, e.faculty_name, e.academic_session, e.submitted_at
            FROM evaluations e JOIN users u ON u.id = e.user_id
            WHERE e.role = ? ORDER BY e.submitted_at ASC
        ");
        $stmt->execute([$role_key]);
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $row_num = 2;
        foreach ($evaluations as $ev) {
            $resp_stmt = $conn->prepare("SELECT section, question_no, question_text, answer_value, answer_text FROM evaluation_responses WHERE evaluation_id = ? ORDER BY id ASC");
            $resp_stmt->execute([$ev['id']]);
            foreach ($resp_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $sheet->fromArray([
                    $ev['id'], $ev['name'], $ev['username'], strtoupper($ev['respondent_role']),
                    $ev['department_name'], $ev['faculty_name'], $ev['academic_session'], $ev['submitted_at'],
                    $r['section'], $r['question_no'], $r['question_text'], $r['answer_value'], $r['answer_text'],
                ], null, 'A' . $row_num);
                $row_num++;
            }
        }

        foreach (range(1, count($headers)) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
    }
}

$spreadsheet->setActiveSheetIndex(0);

$filename = 'evaluation_results_' . $format . '_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
