<?php
// error messages
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '/home/epboyd/db.php'; // connect to database
session_start();

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if fields are filled
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo "All fields are required.";
        exit;
    }

    // Check if new passwords match
    if ($new_password !== $confirm_password) {
        echo "New passwords do not match.";
        exit;
    }

    // Fetch current hash from DB
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($stored_hash);
    $stmt->fetch();
    $stmt->close();

    // Verify current password
    if (!password_verify($current_password, $stored_hash)) {
        echo "Current password is incorrect.";
        exit;
    }

    // Hash and update new password
    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $update->bind_param("si", $new_hash, $user_id);

    if ($update->execute()) {
        echo "Password changed successfully!";
    } else {
        echo "Error updating password: " . $update->error;
    }

    $update->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="css/profile.css">
    <link
      href="https://fonts.googleapis.com/css?family=Montserrat:100"
      rel="stylesheet"
    />
</head>
<body>
    <?php include 'sidebar.php';?>

    <div class="main-content">
        <h2>Change Password</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <label>Current Password:</label><br>
        <input type="password" name="current_password" required><br><br>

        <label>New Password:</label><br>
        <input type="password" name="new_password" required><br><br>

        <label>Confirm New Password:</label><br>
        <input type="password" name="confirm_password" required><br><br>

        <input type="submit" value="Change Password">
        </form>
    </div>
</body>
</html>

