<?php
/**
 * Shared topic-validation pipeline, used by department_project_coordinator/dpc_topic_validation.php,
 * hod/hod_topic_validation.php, and supervisor/sup_topic_validation.php so the three pages stay
 * behaviorally identical instead of drifting apart as hand-copied logic.
 *
 * Pipeline implemented by tv_validate_full():
 *   1. Gather candidate topics to compare against, staged:
 *        a. Topics already APPROVED this academic session in the student's own department.
 *        b. Only if (a) is empty, fall back to this faculty's past_projects archive.
 *      The top ~15 candidates by local string-overlap are kept (regardless of how low that
 *      overlap score is) so the AI step below still gets a shortlist to reason over even when
 *      the duplicate is worded completely differently from the original.
 *   2. One AI call judges, over that shortlist: which (if any) are substantively the same idea
 *      as the proposed topic (catching synonyms/rephrasing a plain string-match would miss),
 *      and whether the topic is relevant to the student's department.
 *
 * tv_validate_quick() is the cheap, local-only, no-AI version used for the instant pre-approve
 * check, so clicking "Approve" doesn't trigger a paid API call on every click.
 *
 * Nothing in this module blocks approval/rejection - every result is advisory only, surfaced to
 * the validator in a report. The approve/reject decision stays entirely manual.
 */

const TV_CANDIDATE_LIMIT = 15;
const TV_QUICK_MATCH_THRESHOLD = 75.0;

function tv_clean_text($text) {
    if (!$text) return "";
    $common = ['the', 'a', 'an', 'of', 'for', 'in', 'on', 'at', 'to', 'using', 'based', 'study', 'design', 'implementation', 'system'];
    $text = strtolower(trim($text));
    return preg_replace('/\b(' . implode('|', $common) . ')\b/i', '', $text);
}

/**
 * Resolves everything the pipeline needs from a topic_id: the topic text and the
 * owning student's department/faculty/session - so every caller (dpc/hod/sup) gets
 * identical, correctly-scoped context regardless of who is doing the validating.
 */
function tv_load_topic_context(PDO $conn, int $topicId) {
    $stmt = $conn->prepare("
        SELECT pt.topic, s.id AS student_id, s.reg_no, s.department AS department_id,
               s.session, d.department_name, d.faculty_id
        FROM project_topics pt
        JOIN students s ON pt.student_id = s.id
        LEFT JOIN departments d ON s.department = d.id
        WHERE pt.id = ?
    ");
    $stmt->execute([$topicId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

/**
 * Staged candidate retrieval: current-session approved topics in the student's department
 * first; only falls back to the faculty's past_projects archive if that pool is empty.
 * Always returns up to TV_CANDIDATE_LIMIT candidates ranked by local similarity, even if
 * their raw score is low, so the AI step has something to reason over.
 */
function tv_gather_candidates(PDO $conn, string $topic, int $currentTopicId, array $ctx) {
    $cleanInput = tv_clean_text($topic);
    $scored = [];

    $stmt = $conn->prepare("
        SELECT pt.topic, s.reg_no
        FROM project_topics pt
        JOIN students s ON pt.student_id = s.id
        WHERE pt.id != ? AND pt.status = 'approved' AND s.department = ? AND s.session = ?
    ");
    $stmt->execute([$currentTopicId, $ctx['department_id'], $ctx['session']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $source = 'current_session_approved';

    if (empty($rows)) {
        $stmt = $conn->prepare("SELECT topic, reg_no FROM past_projects WHERE faculty_id = ?");
        $stmt->execute([$ctx['faculty_id']]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $source = 'past_projects';
    }

    foreach ($rows as $row) {
        similar_text($cleanInput, tv_clean_text($row['topic']), $perc);
        $scored[] = ['topic' => $row['topic'], 'reg_no' => $row['reg_no'], 'score' => round($perc, 1)];
    }

    usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

    if (empty($rows)) $source = 'none';

    return ['source' => $source, 'candidates' => array_slice($scored, 0, TV_CANDIDATE_LIMIT)];
}

/**
 * Fast, local-only, no AI call - used for the instant warning shown before "Approve" submits.
 */
function tv_validate_quick(PDO $conn, int $topicId) {
    $ctx = tv_load_topic_context($conn, $topicId);
    if (!$ctx) return ['status' => 'error', 'message' => 'Topic not found.'];

    $gathered = tv_gather_candidates($conn, $ctx['topic'], $topicId, $ctx);
    $sourceLabel = $gathered['source'] === 'current_session_approved' ? 'Approved This Session' : 'Past Project';

    $matches = [];
    foreach ($gathered['candidates'] as $c) {
        if ($c['score'] > TV_QUICK_MATCH_THRESHOLD) {
            $matches[] = [
                'topic' => $c['topic'],
                'source' => $sourceLabel . ' (Reg No: ' . $c['reg_no'] . ')',
                'score' => $c['score'],
            ];
        }
    }

    return empty($matches) ? ['status' => 'clear'] : ['status' => 'match', 'matches' => $matches];
}

/**
 * Builds the shared system/user prompt content used by both AI backends, so the OpenAI
 * and Claude calls judge duplication/relevance against the exact same instructions.
 */
function tv_build_ai_prompt($topic, array $candidates, $departmentName) {
    $system = "You are an academic project-topic validator for a university. Respond ONLY with a JSON object, no other text, shaped exactly like: "
        . '{"matches": [{"index": 1, "is_duplicate": true, "reason": "one short sentence"}], "department_relevant": true, "department_reason": "one short sentence"}. '
        . "Mark is_duplicate true only when a candidate addresses substantively the same research problem or idea as the proposed topic, even if worded completely "
        . "differently (synonyms, rephrasing, different terminology for the same concept) - not merely because they share a general subject area. "
        . "Include one entry in \"matches\" per candidate listed, in the same order. If no candidates are listed, return an empty matches array. "
        . "For department_relevant, judge whether the proposed topic's subject matter plausibly belongs to the stated department's field of study "
        . "(e.g. a biochemistry topic is not relevant for a Computer Science student; an algorithms topic is).";

    $userLines = ["Student Department: " . ($departmentName ?: 'Unknown'), "Proposed Topic: \"$topic\"", ""];
    if (empty($candidates)) {
        $userLines[] = "No existing topics are available for comparison.";
    } else {
        $userLines[] = "Existing topics to compare against:";
        foreach (array_values($candidates) as $i => $c) {
            $userLines[] = ($i + 1) . '. "' . $c['topic'] . '"';
        }
    }

    return ['system' => $system, 'user' => implode("\n", $userLines)];
}

/**
 * Pulls the first {...} JSON object out of an AI response, tolerating markdown code-fence
 * wrapping (```json ... ```) that some models add despite being told not to.
 */
function tv_extract_json($content) {
    if (!$content) return null;
    $content = trim($content);
    if (strpos($content, '```') === 0) {
        $content = preg_replace('/^```(?:json)?/', '', $content);
        $content = preg_replace('/```$/', '', $content);
        $content = trim($content);
    }
    $parsed = json_decode($content, true);
    if (is_array($parsed) && isset($parsed['matches'])) return $parsed;

    // Last resort: grab the outermost {...} span in case of stray prose around it.
    if (preg_match('/\{.*\}/s', $content, $m)) {
        $parsed = json_decode($m[0], true);
        if (is_array($parsed) && isset($parsed['matches'])) return $parsed;
    }
    return null;
}

/**
 * OpenAI chat-completions backend. Returns null on any failure (network, non-200,
 * unparseable response) - callers must treat null as "this backend is unavailable",
 * never as a block on approval.
 */
function tv_call_openai($topic, array $candidates, $departmentName) {
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) return null;

    $prompt = tv_build_ai_prompt($topic, $candidates, $departmentName);
    $data = [
        "model" => "gpt-4o-mini",
        "response_format" => ["type" => "json_object"],
        "messages" => [
            ["role" => "system", "content" => $prompt['system']],
            ["role" => "user", "content" => $prompt['user']],
        ],
        "max_tokens" => 500,
        "temperature" => 0.2,
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_API_KEY],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError || $httpCode !== 200) {
        // Surface OpenAI's actual error body (e.g. insufficient_quota vs. rate_limit_exceeded
        // vs. invalid_api_key) rather than just the HTTP code, since 429 alone is ambiguous
        // between "billing/quota exhausted" and "too many requests".
        $apiError = null;
        if ($response) {
            $decoded = json_decode($response, true);
            $apiError = $decoded['error']['code'] ?? $decoded['error']['type'] ?? null;
        }
        error_log("tv_call_openai failed: http=$httpCode curl_error=$curlError api_error=" . ($apiError ?? 'n/a') . " body=" . substr((string)$response, 0, 300));
        return null;
    }

    $res = json_decode($response, true);
    $content = $res['choices'][0]['message']['content'] ?? null;
    return tv_extract_json($content);
}

/**
 * Anthropic (Claude) Messages API backend - used only as a fallback when tv_call_openai()
 * fails, so validation keeps working (e.g. during an OpenAI billing/quota outage) as long
 * as ANTHROPIC_API_KEY is configured in includes/config.php.
 */
function tv_call_claude($topic, array $candidates, $departmentName) {
    if (!defined('ANTHROPIC_API_KEY') || empty(ANTHROPIC_API_KEY) || strpos(ANTHROPIC_API_KEY, 'REPLACE-WITH') !== false) return null;

    $prompt = tv_build_ai_prompt($topic, $candidates, $departmentName);
    $data = [
        "model" => "claude-haiku-5-5",
        "system" => $prompt['system'],
        "messages" => [
            ["role" => "user", "content" => $prompt['user']],
        ],
        "max_tokens" => 500,
        "temperature" => 0.2,
    ];

    $ch = curl_init("https://api.anthropic.com/v1/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError || $httpCode !== 200) {
        $apiError = null;
        if ($response) {
            $decoded = json_decode($response, true);
            $apiError = $decoded['error']['type'] ?? null;
        }
        error_log("tv_call_claude failed: http=$httpCode curl_error=$curlError api_error=" . ($apiError ?? 'n/a') . " body=" . substr((string)$response, 0, 300));
        return null;
    }

    $res = json_decode($response, true);
    $content = $res['content'][0]['text'] ?? null;
    return tv_extract_json($content);
}

/**
 * Tries OpenAI first, then falls back to Claude if OpenAI is unavailable for any reason
 * (quota exhausted, rate-limited, outage, missing key). Returns null only if both backends
 * fail or neither is configured - callers must treat that as "AI step skipped", never as
 * a block on approval.
 */
function tv_call_ai($topic, array $candidates, $departmentName) {
    $result = tv_call_openai($topic, $candidates, $departmentName);
    if ($result !== null) return $result;
    return tv_call_claude($topic, $candidates, $departmentName);
}

/**
 * Full pipeline: local staged candidates -> one AI call (semantic duplicate judgment +
 * department relevance). Returns a ready-to-display HTML report string. Every piece of
 * dynamic text (topic titles, reg numbers, AI-authored reasons) is escaped here, so
 * callers should echo the returned HTML directly rather than re-escaping it.
 */
function tv_validate_full(PDO $conn, int $topicId) {
    $ctx = tv_load_topic_context($conn, $topicId);
    if (!$ctx) return ['status' => 'error', 'html' => '<span style="color:#c62828;">Topic not found.</span>'];

    $gathered = tv_gather_candidates($conn, $ctx['topic'], $topicId, $ctx);
    $candidates = $gathered['candidates'];
    $sourceLabel = [
        'current_session_approved' => 'topics already approved this session in ' . htmlspecialchars($ctx['department_name'] ?? 'the department'),
        'past_projects' => 'the faculty\'s past project archive',
        'none' => null,
    ][$gathered['source']];

    $ai = tv_call_ai($ctx['topic'], $candidates, $ctx['department_name'] ?? '');

    $html = '';

    // --- Similarity section ---
    $flagged = [];
    if ($ai && !empty($ai['matches'])) {
        foreach ($ai['matches'] as $m) {
            $idx = (int)($m['index'] ?? 0) - 1;
            if (!empty($m['is_duplicate']) && isset($candidates[$idx])) {
                $flagged[] = [
                    'topic' => $candidates[$idx]['topic'],
                    'reg_no' => $candidates[$idx]['reg_no'],
                    'reason' => $m['reason'] ?? '',
                ];
            }
        }
    }

    if (!empty($flagged)) {
        $html .= '<div style="margin-bottom:6px;"><strong>&#9888;&#65039; Possible duplicate(s) found</strong> (compared against ' . $sourceLabel . '):</div>';
        $html .= '<ul style="margin:0 0 8px 18px; padding:0;">';
        foreach ($flagged as $f) {
            $html .= '<li>"' . htmlspecialchars($f['topic']) . '" (Reg No: ' . htmlspecialchars($f['reg_no']) . ') &mdash; ' . htmlspecialchars($f['reason']) . '</li>';
        }
        $html .= '</ul>';
    } elseif ($sourceLabel === null) {
        $html .= '<div>&#9989; No comparable topics found in ' . htmlspecialchars($ctx['department_name'] ?? 'the department') . ' or the faculty archive.</div>';
    } elseif ($ai) {
        $html .= '<div>&#9989; No duplicate found among ' . $sourceLabel . '.</div>';
    } else {
        // AI unavailable - fall back to showing the raw local shortlist so the validator
        // still gets useful information, clearly labelled as unverified by AI.
        $localHigh = array_filter($candidates, fn($c) => $c['score'] > TV_QUICK_MATCH_THRESHOLD);
        if (!empty($localHigh)) {
            $html .= '<div style="margin-bottom:6px;"><strong>&#9888;&#65039; Local text overlap found</strong> (AI check unavailable - review manually):</div><ul style="margin:0 0 8px 18px; padding:0;">';
            foreach ($localHigh as $c) {
                $html .= '<li>"' . htmlspecialchars($c['topic']) . '" (Reg No: ' . htmlspecialchars($c['reg_no']) . ') &mdash; ' . $c['score'] . '% text overlap</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<div>&#9989; No high text-overlap found among ' . $sourceLabel . ' (AI check unavailable for deeper comparison).</div>';
        }
    }

    // --- Department relevance section ---
    if ($ai && isset($ai['department_relevant'])) {
        $relevant = (bool)$ai['department_relevant'];
        $icon = $relevant ? '&#9989;' : '&#9888;&#65039;';
        $verdict = $relevant ? 'Relevant' : 'May not be relevant';
        $html .= '<div style="margin-top:4px;">' . $icon . ' <strong>Department Relevance (' . htmlspecialchars($ctx['department_name'] ?? 'N/A') . '):</strong> '
            . $verdict . ' &mdash; ' . htmlspecialchars($ai['department_reason'] ?? '') . '</div>';
    } else {
        $html .= '<div style="margin-top:4px; color:#888;">Department relevance check unavailable (AI offline).</div>';
    }

    return ['status' => 'ok', 'html' => $html];
}
