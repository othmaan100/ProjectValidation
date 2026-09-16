<?php
/**
 * Super Admin Account Creator
 *
 * Instructions:
 * 1. Upload this file to the root of your project directory on the live server.
 * 2. Navigate to this file in your browser (e.g. https://yourdomain.com/create_super_admin.php).
 * 3. Confirm the success message.
 * 4. Delete this file immediately after it runs successfully for security.
 */

require_once __DIR__ . '/includes/db.php';

$username = 'speradmin';
$password = '1234';

echo "<h1>Super Admin Account Creator</h1>";

try {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        echo "<p style='color:orange;'>A user with username '" . htmlspecialchars($username) . "' already exists. No changes made.</p>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role, name, is_active) VALUES (?, ?, 'admin', ?, 1)");
        $stmt->execute([$username, $hashed_password, 'Super Admin']);

        echo "<p style='color:green;'><strong>Success!</strong> Super admin account created.</p>";
        echo "<p>Username: <code>" . htmlspecialchars($username) . "</code><br>Password: <code>" . htmlspecialchars($password) . "</code></p>";
        echo "<p><strong>Important:</strong> Log in and change this password immediately, then delete this file from the server.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
