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

// get user id
$user_id = $_SESSION['user_id'];

// get user details
$query = "SELECT username, email, nickname, bio FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $new_username = $_POST['username'];
        $new_email = $_POST['email'];
        $new_nickname = $_POST['nickname'];
        $new_bio = $_POST['bio'];

        $update_query = "UPDATE users SET username=?, email=?, nickname=?, bio=? WHERE user_id=?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssssi", $new_username, $new_email, $new_nickname, $new_bio, $user_id);

        if ($stmt->execute()) {
            header("Location: https://turing2.cs.olemiss.edu/~epboyd/WearToShare/profile.php");
            exit();
        } else {
            echo "Error updating profile.";
        }
    }
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
        <h2>My Profile</h2>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="profile-form">
            <label class="profile-label">Username:</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required class="profile-input"><br>

            <label class="profile-label">Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="profile-input"><br>

            <label class="profile-label">Nickname:</label>
            <input type="text" name="nickname" value="<?php echo htmlspecialchars(isset($user['nickname']) ? $user['nickname'] : ''); ?>" class="profile-input"><br>

            <label class="profile-label">Bio:</label>
            <textarea name="bio" class="profile-textarea"><?php echo htmlspecialchars(isset($user['bio']) ? $user['bio'] : ''); ?></textarea><br>

            <button type="submit" name="update_profile" class="profile-button">Update Profile</button><br><br>
            <button name="changepw-button"id="changepw-button" class="changepw-button">Change Password</button>
        </form>
    </div>
<script>
    const button = document.getElementById('changepw-button'); 
    button.addEventListener('click', function(event) {
        event.preventDefault(); 
        window.location.href = 'changePassword.php';
    });
</script>
</body>
</html>

