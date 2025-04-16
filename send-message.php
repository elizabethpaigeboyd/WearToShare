<?php
include '/home/epboyd/db.php'; 
session_start();

$user_id = $_SESSION['user_id'];
$group_id = intval($_POST['group_id']);
$message = trim($_POST['message']);

if (empty($message)) {
    die("Message cannot be empty.");
}

// insert message into chat_messages table
$stmt = $conn->prepare("INSERT INTO chat_messages (group_id, user_id, message, sent_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $group_id, $user_id, $message);

if ($stmt->execute()) {
    header("Location: group.php?group_id=$group_id");
    exit();
} else {
    die("Error sending message: " . $stmt->error);
}
?>
