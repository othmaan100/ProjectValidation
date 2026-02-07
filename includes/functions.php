<?php

function validate_topic_with_chatgpt($topic) {
    global $conn;

    // Step 1: Check for exact matches in past_projects table
    $stmt = $conn->prepare("SELECT topic FROM past_projects WHERE topic = ?");
    $stmt->execute([$topic]);
    $exact_match = $stmt->fetchColumn();

    if ($exact_match) {
        return "Error: This topic already exists in past projects.";
    }

    // Step 2: Check for similar topics using string similarity
    $stmt = $conn->prepare("SELECT topic FROM past_projects");
    $stmt->execute();
    $past_topics = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($past_topics as $past_topic) {
        $similarity = calculateSimilarity($topic, $past_topic);
        if ($similarity >= 80) { // Adjust threshold as needed
            return "Warning: Similar topic found in past projects: $past_topic";
        }
    }

    // Step 3: Validate the topic using ChatGPT API
    $api_key = getenv('OPENAI_API_KEY'); // Retrieve API key from environment
    $url = "https://api.openai.com/v1/completions";
    $data = [
        "model" => "text-davinci-003",
        "prompt" => "Validate the following project topic: '$topic'. Is it original and relevant? Provide a short explanation (1-2 sentences) for your answer.",
        "max_tokens" => 100,
        "temperature" => 0.7
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $api_key"
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code == 429) {
        return "Error: API rate limit exceeded. Please try again later.";
    } elseif ($http_code == 401) {
        return "Error: Invalid API key. Please check your OpenAI API key.";
    } elseif ($http_code != 200) {
        return "Error: Unable to validate the topic. HTTP Code: $http_code";
    }

    curl_close($ch);

    $response_data = json_decode($response, true);
    if (isset($response_data['choices'][0]['text'])) {
        return trim($response_data['choices'][0]['text']);
    }

    return "Error: Unable to validate the topic. Please try again.";
}

function send_feedback_to_student($topic_id, $decision, $reason = '') {
    global $conn;

    $stmt = $conn->prepare("SELECT student_reg_no FROM project_topics WHERE id = ?");
    $stmt->execute([$topic_id]);
    $student_reg_no = $stmt->fetchColumn();

    $feedback_message = "Your project topic has been $decision.";
    if ($reason) {
        $feedback_message .= " Reason: $reason";
    }

    $stmt = $conn->prepare("INSERT INTO feedback (student_reg_no, message) VALUES (?, ?)");
    $stmt->execute([$student_reg_no, $feedback_message]);

    // Send email notification
    send_email_notification($student_reg_no, $feedback_message);
}

function display_error($message) {
    echo "<div class='alert alert-danger'>$message</div>";
}

function upload_file($file, $target_dir) {
    $allowed_types = ['application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        return "Error: Only PDF files are allowed.";
    }

    if ($file['size'] > $max_size) {
        return "Error: File size exceeds the limit of 5MB.";
    }

    $target_file = $target_dir . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    } else {
        return "Error: Unable to upload the file.";
    }
}

function calculateSimilarity($str1, $str2) {
    similar_text(strtolower($str1), strtolower($str2), $similarity);
    return $similarity;
}

// CSRF Protection Functions
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}