<?php
/**
 * Bulk Student Password Reset
 *
 * Sets the password for every user with role = 'stu' to a single
 * known value, hashed properly (not stored in plain text).
 *
 * Instructions:
 * 1. Upload this file to the root of your project directory on the live server.
 * 2. Navigate to this file in your browser (e.g. https://yourdomain.com/reset_student_passwords.php).
 * 3. Confirm the success message and row count.
 * 4. Delete this file immediately after it runs successfully for security.
 */

require_once __DIR__ . '/includes/db.php';

$new_password = '1234';

echo "<h1>Bulk Student Password Reset</h1>";

try {
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE role = 'stu'");
    $stmt->execute([$hashed_password]);

    $affected = $stmt->rowCount();

    echo "<p style='color:green;'><strong>Success!</strong> Updated password for {$affected} student account(s).</p>";
    echo "<p>New password for every student: <code>" . htmlspecialchars($new_password) . "</code></p>";
    echo "<p><strong>Important:</strong> Ask students to change this password after logging in, then delete this file from the server.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
