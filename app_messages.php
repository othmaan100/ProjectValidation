<?php
session_start();
include_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dept_id = $_SESSION['department'];

// Handle Sending Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message_text']);
    
    if (!empty($message_text)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, academic_session) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message_text, $current_session]);
        header("Location: app_messages.php?contact=$receiver_id");
        exit();
    }
}

// Fetch Contacts based on role
$contacts = [];
if ($role === 'dpc') {
    // DPC sees all supervisors in their department
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE department = ? AND role = 'sup' ORDER BY name ASC");
    $stmt->execute([$dept_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($role === 'sup') {
    // Supervisor sees the DPC of their department and their assigned students
    // Get DPC
    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE department = ? AND role = 'dpc'");
    $stmt->execute([$dept_id]);
    $dpc = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get assigned students
    $stmt = $conn->prepare("SELECT s.id, s.name, 'stu' as role 
                            FROM supervision sp 
                            JOIN students s ON sp.student_id = s.id 
                            WHERE sp.supervisor_id = ? AND sp.status = 'active'");
    $stmt->execute([$user_id]);
    $stus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $contacts = array_merge($dpc, $stus);
} elseif ($role === 'stu') {
    // Student sees their supervisor
    $stmt = $conn->prepare("SELECT su.id, su.name, 'sup' as role 
                            FROM supervision sp 
                            JOIN supervisors su ON sp.supervisor_id = su.id 
                            WHERE sp.student_id = ? AND sp.status = 'active'");
    $stmt->execute([$user_id]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$selected_contact_id = isset($_GET['contact']) ? intval($_GET['contact']) : (count($contacts) > 0 ? $contacts[0]['id'] : null);
$messages = [];
$selected_contact_name = "Select a contact";

if ($selected_contact_id) {
    // Check if this contact is allowed (simple verification)
    $is_allowed = false;
    foreach ($contacts as $c) {
        if ($c['id'] == $selected_contact_id) {
            $is_allowed = true;
            $selected_contact_name = $c['name'];
            break;
        }
    }
    
    if ($is_allowed) {
        // Mark as read
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
        $stmt->execute([$user_id, $selected_contact_id]);
        
        // Fetch messages between these two
        $stmt = $conn->prepare("SELECT * FROM messages 
                                WHERE (sender_id = ? AND receiver_id = ?) 
                                OR (sender_id = ? AND receiver_id = ?) 
                                ORDER BY sent_at ASC");
        $stmt->execute([$user_id, $selected_contact_id, $selected_contact_id, $user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In-App Messages | Project Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .msg-container { display: grid; grid-template-columns: 300px 1fr; gap: 0; height: calc(100vh - 160px); background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 25px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .contacts-panel { background: #f8fafc; border-right: 1px solid #e2e8f0; overflow-y: auto; }
        .contact-item { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit; }
        .contact-item:hover { background: #edf2f7; }
        .contact-item.active { background: #ebf4ff; border-left: 4px solid #4e73df; }
        .contact-avatar { width: 40px; height: 40px; background: #cbd5e0; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .contact-info { flex: 1; }
        .contact-name { font-weight: 600; font-size: 14px; }
        .contact-role { font-size: 11px; text-transform: uppercase; color: #a0aec0; }
        
        .chat-panel { display: flex; flex-direction: column; }
        .chat-header { padding: 15px 25px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; background: white; }
        .chat-history { flex: 1; padding: 25px; overflow-y: auto; background: #f1f5f9; display: flex; flex-direction: column; gap: 15px; }
        .message { max-width: 70%; padding: 12px 18px; border-radius: 15px; font-size: 14px; line-height: 1.5; position: relative; }
        .message.sent { align-self: flex-end; background: #4e73df; color: white; border-bottom-right-radius: 2px; }
        .message.received { align-self: flex-start; background: white; color: #334155; border-bottom-left-radius: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .msg-time { font-size: 10px; opacity: 0.7; margin-top: 5px; display: block; text-align: right; }
        
        .chat-input-area { padding: 20px; background: white; border-top: 1px solid #e2e8f0; }
        .chat-form { display: flex; gap: 10px; }
        .chat-input { flex: 1; padding: 12px 20px; border: 2px solid #e2e8f0; border-radius: 10px; font-family: inherit; resize: none; height: 45px; }
        .chat-input:focus { outline: none; border-color: #4e73df; }
        .btn-send { background: #4e73df; color: white; border: none; width: 45px; height: 45px; border-radius: 10px; cursor: pointer; transition: 0.2s; }
        .btn-send:hover { background: #224abe; transform: scale(1.05); }
        
        .unread-dot { width: 8px; height: 8px; background: #e74a3b; border-radius: 50%; }
    </style>
</head>
<body style="background: #f7fafc;">
    <?php include_once __DIR__ . '/includes/header.php'; ?>
    </div> <!-- Close header container -->

    <div class="container" style="max-width: 1200px; padding: 20px;">
        <div class="msg-container">
            <!-- Contacts -->
            <div class="contacts-panel">
                <div style="padding: 20px; font-weight: 800; color: #4e73df; border-bottom: 2px solid #e2e8f0;">
                    <i class="fas fa-comments"></i> DISCUSSIONS
                </div>
                <?php if (empty($contacts)): ?>
                    <div style="padding: 20px; color: #a0aec0; text-align: center; font-size: 14px;">No contacts available.</div>
                <?php else: ?>
                    <?php foreach ($contacts as $c): ?>
                        <?php 
                            // Check for unread from this specific contact
                            $stmt_unread = $conn->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
                            $stmt_unread->execute([$c['id'], $user_id]);
                            $c_unread = $stmt_unread->fetchColumn();
                        ?>
                        <a href="app_messages.php?contact=<?= $c['id'] ?>" class="contact-item <?= ($selected_contact_id == $c['id']) ? 'active' : '' ?>">
                            <div class="contact-avatar"><?= substr($c['name'], 0, 1) ?></div>
                            <div class="contact-info">
                                <div class="contact-name"><?= htmlspecialchars($c['name']) ?></div>
                                <div class="contact-role"><?= $c['role'] ?></div>
                            </div>
                            <?php if($c_unread > 0): ?>
                                <div class="unread-dot"></div>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Chat -->
            <div class="chat-panel">
                <?php if ($selected_contact_id): ?>
                    <div class="chat-header">
                        <div class="contact-avatar"><?= substr($selected_contact_name, 0, 1) ?></div>
                        <div>
                            <div style="font-weight: 700; color: #1e293b;"><?= htmlspecialchars($selected_contact_name) ?></div>
                            <div style="font-size: 12px; color: #10b981;"><i class="fas fa-circle" style="font-size: 8px;"></i> Online</div>
                        </div>
                    </div>
                    
                    <div class="chat-history" id="chatHistory">
                        <?php if (empty($messages)): ?>
                            <div style="text-align: center; margin-top: 100px; color: #a0aec0;">
                                <i class="fas fa-paper-plane" style="font-size: 40px; margin-bottom: 15px; opacity: 0.3;"></i>
                                <p>No messages yet. Start a conversation!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <div class="message <?= ($m['sender_id'] == $user_id) ? 'sent' : 'received' ?>">
                                    <?= nl2br(htmlspecialchars($m['message_text'])) ?>
                                    <span class="msg-time"><?= date('h:i A', strtotime($m['sent_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="chat-input-area">
                        <form method="POST" class="chat-form">
                            <input type="hidden" name="receiver_id" value="<?= $selected_contact_id ?>">
                            <textarea name="message_text" class="chat-input" placeholder="Type your message here..." required></textarea>
                            <button type="submit" name="send_message" class="btn-send">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="flex: 1; display: flex; align-items: center; justify-content: center; flex-direction: column; background: #f8fafc;">
                        <i class="fas fa-comments" style="font-size: 80px; color: #e2e8f0; margin-bottom: 20px;"></i>
                        <h2 style="color: #64748b;">Select a contact to start messaging</h2>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Scroll to bottom of chat history
        const chatHistory = document.getElementById('chatHistory');
        if (chatHistory) {
            chatHistory.scrollTop = chatHistory.scrollHeight;
        }
    </script>
</body>
</html>
