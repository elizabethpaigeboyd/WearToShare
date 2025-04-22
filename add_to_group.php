<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '/home/epboyd/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$group_id = intval($_POST['group_id'] ?? 0);
$friend_id = intval($_POST['friend_id'] ?? 0);

// check for admin
$stmt = $conn->prepare("SELECT user_role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$role_result = $stmt->get_result();
$user_role = $role_result->fetch_assoc()['user_role'] ?? null;

if ($user_role !== 'admin') {
    die("Only admins can add members to the group.");
}

// check if friend_id is valid
$stmt = $conn->prepare("
    SELECT 1 FROM friendships 
    WHERE status = 'accepted' 
    AND (
        (user_id = ? AND friend_id = ?) 
        OR (friend_id = ? AND user_id = ?)
    )
");
$stmt->bind_param("iiii", $user_id, $friend_id, $user_id, $friend_id);
$stmt->execute();
$friend_result = $stmt->get_result();

if ($friend_result->num_rows === 0) {
    die("You can only add friends to the group.");
}

// check if already in group
$stmt = $conn->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $friend_id);
$stmt->execute();
$exists_result = $stmt->get_result();
if ($exists_result->num_rows > 0) {
    die("User is already in the group.");
}

// add to group
$stmt = $conn->prepare("
    INSERT INTO group_members (group_id, user_id, user_role) 
    VALUES (?, ?, 'participant')
");
$stmt->bind_param("ii", $group_id, $friend_id);
if ($stmt->execute()) {
    header("Location: group.php?group_id=$group_id");
    exit;
} else {
    die("Error adding user to group.");
}
?>
