<?php
include_once __DIR__ .'/../includes/auth.php';
include_once __DIR__ .'/../includes/db.php';
include __DIR__ . '/../includes/functions.php'; // Include functions.php

if ($_SESSION['role'] !== 'dpc') {
    header("Location: " . PROJECT_ROOT);
    exit();
}

// Fetch all topics (pending, approved, and rejected)
$stmt = $conn->prepare("SELECT * FROM project_topics");
$stmt->execute();
$topics = $stmt->fetchAll();

// Handle topic validation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['validate_topic'])) {
        // Validate the topic
        $topic_id = $_POST['topic_id'];

        // Fetch the topic from the database
        $stmt = $conn->prepare("SELECT topic FROM project_topics WHERE id = ?");
        $stmt->execute([$topic_id]);
        $topic = $stmt->fetchColumn();

        // Validate topic using the updated function
        $validation_explanation = validate_topic_with_chatgpt($topic);

        // Store the validation result in the session
        $_SESSION['validation_result'][$topic_id] = $validation_explanation;
    } elseif (isset($_POST['approve_topic'])) {
        // Approve the topic
        $topic_id = $_POST['topic_id'];
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'approved' WHERE id = ?");
        $stmt->execute([$topic_id]);

        // Send feedback to the student
        send_feedback_to_student($topic_id, 'approved');
        echo "Topic approved!";
    } elseif (isset($_POST['reject_topic'])) {
        // Reject the topic
        $topic_id = $_POST['topic_id'];
        $stmt = $conn->prepare("UPDATE project_topics SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$topic_id]);

        // Send feedback to the student
        send_feedback_to_student($topic_id, 'rejected');
        echo "Topic rejected!";
    }
    header("Refresh:2"); // Refresh the page after 2 seconds
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validate Topics</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* Style for buttons */
        .action-buttons {
            display: flex;
            gap: 5px; /* Space between buttons */
        }

        .action-buttons button {
            padding: 5px 10px; /* Smaller padding */
            font-size: 12px; /* Smaller font size */
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        /* Style for disabled buttons */
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Style for validation explanation */
        .validation-explanation {
            margin-top: 5px;
            font-size: 12px;
            color: #555;
        }

        /* Style for status badges */
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #ffcc00;
            color: #000;
        }

        .status-approved {
            background-color: #4CAF50;
            color: #fff;
        }

        .status-rejected {
            background-color: #f44336;
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include_once __DIR__ .'/../includes/header.php'; ?>
    <div class="container">
        <h1>Validate Topics</h1>
        <table>
            <tr>
                <th>Topic</th>
                <th>Student Registration Number</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?php echo $topic['topic']; ?></td>
                    <td><?php echo $topic['student_reg_no']; ?></td>
                    <td>
                        <!-- Display Status Badge -->
                        <span class="status-badge 
                            <?php
                                if ($topic['status'] == 'pending') echo 'status-pending';
                                elseif ($topic['status'] == 'approved') echo 'status-approved';
                                elseif ($topic['status'] == 'rejected') echo 'status-rejected';
                            ?>">
                            <?php echo ucfirst($topic['status']); ?>
                        </span>
                    </td>
                    <td>
                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <!-- Validate Button (only for pending topics) -->
                            <?php if ($topic['status'] == 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <button type="submit" name="validate_topic">Validate</button>
                                </form>
                            <?php endif; ?>

                            <!-- Approve Button (only for pending topics) -->
                            <?php if ($topic['status'] == 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <button type="submit" name="approve_topic" <?php echo isset($_SESSION['validation_result'][$topic['id']]) ? '' : 'disabled'; ?>>Approve</button>
                                </form>
                            <?php endif; ?>

                            <!-- Reject Button (only for pending topics) -->
                            <?php if ($topic['status'] == 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                    <button type="submit" name="reject_topic" <?php echo isset($_SESSION['validation_result'][$topic['id']]) ? '' : 'disabled'; ?>>Reject</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- Display Validation Result -->
                        <?php if (isset($_SESSION['validation_result'][$topic['id']])): ?>
                            <div class="validation-explanation">
                                <?php echo $_SESSION['validation_result'][$topic['id']]; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php include_once __DIR__ .'/../includes/footer.php'; ?>
</body>
</html>

