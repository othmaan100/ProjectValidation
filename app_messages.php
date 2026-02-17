<?php
session_start();
include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = $_SESSION['department'] ?? null;

// Fallback if department is not in session
if (empty($dept_id)) {
    $stmt = $conn->prepare("SELECT department FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $dept_id = $stmt->fetchColumn();
    $_SESSION['department'] = $dept_id;
}

$current_session = $_SESSION['session'] ?? '2023/2024'; // Fallback if not set

// Handle Sending Message or Broadcast
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message_text = trim($_POST['message_text']);
    $broadcast_type = $_POST['broadcast_type'] ?? null;
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : null;

    if (!empty($message_text)) {
        if ($broadcast_type) {
            $recipients = [];
            if ($role === 'dpc') {
                if ($broadcast_type === 'all_sups') {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE department = ? AND role = 'sup'");
                    $stmt->execute([$dept_id]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } elseif ($broadcast_type === 'all_stus') {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE department = ? AND role = 'stu'");
                    $stmt->execute([$dept_id]);
                    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
            } elseif ($role === 'sup' && $broadcast_type === 'my_stus') {
                $stmt = $conn->prepare("SELECT student_id FROM supervision WHERE supervisor_id = ? AND status = 'active'");
                $stmt->execute([$user_id]);
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            if (!empty($recipients)) {
                $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, academic_session) VALUES (?, ?, ?, ?)");
                foreach ($recipients as $rid) {
                    $stmt->execute([$user_id, $rid, "[BROADCAST]: " . $message_text, $current_session]);
                }
                header("Location: app_messages.php?broadcast_sent=1");
                exit();
            }
        } elseif ($receiver_id) {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, academic_session) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $receiver_id, $message_text, $current_session]);
            header("Location: app_messages.php?contact=$receiver_id");
            exit();
        }
    }
}

// Fetch Contacts based on role
$contacts = [];
if ($role === 'dpc') {
    // DPC sees all supervisors in their department
    $stmt = $conn->prepare("SELECT id, name, 'sup' as role FROM users WHERE department = ? AND role = 'sup' ORDER BY name ASC");
    $stmt->execute([$dept_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // AND any student who has messaged the DPC or received a message from the DPC
    $stmt = $conn->prepare("SELECT DISTINCT u.id, u.name, 'stu' as role 
                            FROM users u 
                            JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
                            WHERE u.role = 'stu' AND u.department = ? 
                            AND (m.sender_id = ? OR m.receiver_id = ?)
                            ORDER BY u.name ASC");
    $stmt->execute([$dept_id, $user_id, $user_id]);
    $active_stus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Merge and filter duplicates
    foreach ($active_stus as $stu) {
        $found = false;
        foreach ($contacts as $c) {
            if ($c['id'] == $stu['id']) { $found = true; break; }
        }
        if (!$found) $contacts[] = $stu;
    }

} elseif ($role === 'sup') {
    // Supervisor sees the DPC of their department and their assigned students
    $stmt = $conn->prepare("SELECT id, name, 'dpc' as role FROM users WHERE department = ? AND role = 'dpc'");
    $stmt->execute([$dept_id]);
    $dpc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get assigned students
    $stmt = $conn->prepare("SELECT id, name, 'stu' as role 
                            FROM users 
                            WHERE id IN (SELECT student_id FROM supervision WHERE supervisor_id = ? AND status = 'active')");
    $stmt->execute([$user_id]);
    $stus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $contacts = array_merge($dpc, $stus);
} elseif ($role === 'stu') {
    // Student sees their supervisor AND the DPC of their department
    $stmt = $conn->prepare("SELECT id, name, 'sup' as role 
                            FROM users 
                            WHERE id IN (SELECT supervisor_id FROM supervision WHERE student_id = ? AND status = 'active')");
    $stmt->execute([$user_id]);
    $sups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get DPC
    $stmt = $conn->prepare("SELECT id, name, 'dpc' as role FROM users WHERE department = ? AND role = 'dpc'");
    $stmt->execute([$dept_id]);
    $dpcs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $contacts = array_merge($sups, $dpcs);
}

$selected_contact_id = isset($_GET['contact']) ? intval($_GET['contact']) : null;
$selected_broadcast = isset($_GET['broadcast']) ? $_GET['broadcast'] : null;
$messages = [];
$selected_name = "Select a contact";

if ($selected_contact_id) {
    foreach ($contacts as $c) {
        if ($c['id'] == $selected_contact_id) {
            $selected_name = $c['name'];
            break;
        }
    }
    
    // Mark as read
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
    $stmt->execute([$user_id, $selected_contact_id]);
    
    // Fetch messages
    $stmt = $conn->prepare("SELECT * FROM messages 
                            WHERE (sender_id = ? AND receiver_id = ?) 
                            OR (sender_id = ? AND receiver_id = ?) 
                            ORDER BY sent_at ASC");
    $stmt->execute([$user_id, $selected_contact_id, $selected_contact_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($selected_broadcast) {
    if ($selected_broadcast === 'all_sups') $selected_name = "Broadcast to All Supervisors";
    elseif ($selected_broadcast === 'all_stus') $selected_name = "Broadcast to All Students";
    elseif ($selected_broadcast === 'my_stus') $selected_name = "Broadcast to My Students";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #224abe;
            --bg-light: #f8fafc;
            --border: #e2e8f0;
            --text-main: #334155;
            --text-muted: #64748b;
        }

        .msg-wrapper {
            display: flex;
            height: calc(100vh - 120px);
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin: 10px 0;
        }

        /* Sidebar Styles */
        .msg-sidebar {
            width: 350px;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            background: white;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            background: var(--bg-light);
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contacts-list {
            flex: 1;
            overflow-y: auto;
        }

        .broadcast-section {
            padding: 10px;
            background: #fff;
            border-bottom: 1px solid var(--border);
        }

        .broadcast-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            background: rgba(78, 115, 223, 0.05);
            color: var(--primary);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 8px;
            transition: 0.2s;
            border: 1px dashed var(--primary);
        }

        .broadcast-btn:hover {
            background: rgba(78, 115, 223, 0.1);
        }

        .contact-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: 0.2s;
        }

        .contact-card:hover { background: #f8fafc; }
        .contact-card.active { 
            background: #ebf4ff; 
            border-left: 4px solid var(--primary);
        }

        .avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary), #6366f1);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            flex-shrink: 0;
        }

        .contact-info { flex: 1; min-width: 0; }
        .contact-name { font-weight: 700; font-size: 15px; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .contact-role { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        /* Main Chat Area */
        .msg-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f1f5f9;
            transition: all 0.3s ease;
        }

        .chat-header {
            padding: 15px 25px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            display: none;
            color: var(--text-muted);
            font-size: 18px;
            padding: 5px;
        }

        .chat-body {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .msg-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 14px;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .msg-bubble.sent {
            align-self: flex-end;
            background: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .msg-bubble.received {
            align-self: flex-start;
            background: white;
            color: var(--text-main);
            border-bottom-left-radius: 4px;
        }

        .msg-meta {
            font-size: 10px;
            margin-top: 4px;
            display: block;
            opacity: 0.8;
            text-align: right;
        }

        .chat-footer {
            padding: 20px;
            background: white;
            border-top: 1px solid var(--border);
        }

        .input-group {
            display: flex;
            gap: 12px;
        }

        .msg-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid var(--border);
            border-radius: 10px;
            resize: none;
            height: 48px;
            font-family: inherit;
        }

        .msg-input:focus { outline: none; border-color: var(--primary); }

        .btn-send {
            width: 48px;
            height: 48px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: 0.2s;
        }

        .btn-send:hover { background: var(--primary-dark); transform: scale(1.05); }

        .unread-badge {
            background: #e74a3b;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 800;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .msg-wrapper { height: calc(100vh - 100px); margin: 0; border-radius: 0; }
            .msg-sidebar { width: 100%; border-right: none; }
            .msg-main { width: 100%; position: absolute; top: 0; left: 0; height: 100%; z-index: 100; transform: translateX(100%); }
            
            .msg-wrapper.chat-open .msg-sidebar { display: none; }
            .msg-wrapper.chat-open .msg-main { transform: translateX(0); position: relative; }
            
            .back-btn { display: block; }
        }
    </style>
</head>
<body style="background: #f1f5f9;">
    <?php include_once __DIR__ . '/includes/header.php'; ?>
    </div> <!-- Close header container -->

    <div class="container" style="max-width: 1400px; padding: 10px;">
        <?php if(isset($_GET['broadcast_sent'])): ?>
            <div style="background: #10b981; color: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-check-circle"></i> Broadcast message sent successfully!
            </div>
        <?php endif; ?>

        <div class="msg-wrapper <?= ($selected_contact_id || $selected_broadcast) ? 'chat-open' : '' ?>">
            <!-- Sidebar -->
            <div class="msg-sidebar">
                <div class="sidebar-header">
                    <h3><i class="fas fa-comments"></i> In-App Chat</h3>
                </div>

                <div class="broadcast-section">
                    <?php if ($role === 'dpc'): ?>
                        <a href="app_messages.php?broadcast=all_sups" class="broadcast-btn">
                            <i class="fas fa-bullhorn"></i> Broadcast to All Supervisors
                        </a>
                        <a href="app_messages.php?broadcast=all_stus" class="broadcast-btn">
                            <i class="fas fa-bullhorn"></i> Broadcast to All Students
                        </a>
                    <?php elseif ($role === 'sup'): ?>
                        <a href="app_messages.php?broadcast=my_stus" class="broadcast-btn">
                            <i class="fas fa-bullhorn"></i> Broadcast to My Students
                        </a>
                    <?php endif; ?>
                </div>

                <div class="contacts-list">
                    <?php if (empty($contacts)): ?>
                        <div style="padding: 40px 20px; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-user-slash" style="font-size: 30px; margin-bottom: 10px; opacity: 0.5;"></i>
                            <p>No contacts found.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($contacts as $c): ?>
                            <?php 
                                $stmt_u = $conn->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
                                $stmt_u->execute([$c['id'], $user_id]);
                                $unread = $stmt_u->fetchColumn();
                            ?>
                            <a href="app_messages.php?contact=<?= $c['id'] ?>" class="contact-card <?= ($selected_contact_id == $c['id']) ? 'active' : '' ?>">
                                <div class="avatar"><?= substr($c['name'], 0, 1) ?></div>
                                <div class="contact-info">
                                    <div class="contact-name"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="contact-role"><?= $c['role'] ?></div>
                                </div>
                                <?php if($unread > 0): ?>
                                    <span class="unread-badge"><?= $unread ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Chat -->
            <div class="msg-main">
                <?php if ($selected_contact_id || $selected_broadcast): ?>
                    <div class="chat-header">
                        <a href="app_messages.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
                        <div class="avatar" style="width: 40px; height: 40px; font-size: 14px;">
                            <?= ($selected_broadcast) ? '<i class="fas fa-bullhorn"></i>' : substr($selected_name, 0, 1) ?>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; color: var(--text-main);"><?= htmlspecialchars($selected_name) ?></div>
                            <div style="font-size: 12px; color: #10b981;">
                                <?php if($selected_broadcast): ?>
                                    System Broadcast Channel
                                <?php else: ?>
                                    <i class="fas fa-circle" style="font-size: 8px;"></i> Active
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="chat-body" id="chatBody">
                        <?php if ($selected_broadcast): ?>
                            <div style="text-align: center; padding: 40px;">
                                <div style="width: 80px; height: 80px; background: rgba(78, 115, 223, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 30px;">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <h3 style="margin-bottom: 10px;">New Broadcast</h3>
                                <p style="color: var(--text-muted); font-size: 14px; max-width: 300px; margin: 0 auto;">
                                    Your message will be sent individually to each recipient in this category.
                                </p>
                            </div>
                        <?php elseif (empty($messages)): ?>
                            <div style="text-align: center; margin-top: 100px; color: var(--text-muted);">
                                <i class="far fa-paper-plane" style="font-size: 50px; margin-bottom: 15px; opacity: 0.2;"></i>
                                <p>No messages yet. Say hello!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <div class="msg-bubble <?= ($m['sender_id'] == $user_id) ? 'sent' : 'received' ?>">
                                    <?= nl2br(htmlspecialchars($m['message_text'])) ?>
                                    <span class="msg-meta"><?= date('h:i A', strtotime($m['sent_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="chat-footer">
                        <form method="POST" class="input-group">
                            <?php if($selected_broadcast): ?>
                                <input type="hidden" name="broadcast_type" value="<?= $selected_broadcast ?>">
                            <?php else: ?>
                                <input type="hidden" name="receiver_id" value="<?= $selected_contact_id ?>">
                            <?php endif; ?>
                            
                            <textarea name="message_text" class="msg-input" placeholder="Write a message..." required></textarea>
                            <button type="submit" name="send_message" class="btn-send">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; padding: 20px;">
                        <div style="width: 150px; height: 150px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                            <i class="fas fa-comments" style="font-size: 60px; color: var(--primary); opacity: 0.3;"></i>
                        </div>
                        <h2 style="color: var(--text-main); font-weight: 800; margin-bottom: 10px;">Welcome to Project Pro Messaging</h2>
                        <p style="color: var(--text-muted); max-width: 400px;">Connect with your supervisors, students, or coordinators instantly. Select a contact from the list to begin.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const chatBody = document.getElementById('chatBody');
        if (chatBody) {
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        // Auto-focus input when contact selected
        const msgInput = document.querySelector('.msg-input');
        if (msgInput) msgInput.focus();
    </script>
</body>
</html>

