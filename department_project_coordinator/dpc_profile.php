<?php
session_start();
include 'includes/auth.php';
include __DIR__ . '/includes/db.php';

if ($_SESSION['role'] !== 'dpc') {
    header("Location: /projectval/");
    exit();
}

// Fetch the current DPC's details
$dpcId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$dpcId]);
$dpc = $stmt->fetch();

// Handle form submission to update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $title = $_POST['title'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // Update the DPC's profile
    $stmt = $conn->prepare("UPDATE users SET title = ?, name = ?, phone = ?, email = ? WHERE id = ?");
    $stmt->execute([$title, $name, $phone, $email, $dpcId]);

    echo "Profile updated successfully!";
    header("Refresh:1");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPC Profile</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h1>Update Your Profile</h1>

        <!-- Profile Update Form -->
        <form method="POST">
            <input type="text" name="title" placeholder="Title" value="<?php echo $dpc['title'] ?? ''; ?>">
            <input type="text" name="name" placeholder="Full Name" value="<?php echo $dpc['name'] ?? ''; ?>" required>
            <input type="text" name="phone" placeholder="Phone Number" value="<?php echo $dpc['phone'] ?? ''; ?>">
            <input type="email" name="email" placeholder="Email Address" value="<?php echo $dpc['email'] ?? ''; ?>" required>
            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
