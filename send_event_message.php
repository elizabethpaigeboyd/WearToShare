<?php
include '/home/epboyd/db.php';
session_start();

$user_id = $_SESSION['user_id'];
$event_id = intval($_POST['event_id']); 
$message = trim($_POST['message']);

if (empty($message)) {
    die("Message cannot be empty.");
}

$stmt = $conn->prepare("INSERT INTO event_messages (event_id, user_id, message, sent_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $event_id, $user_id, $message);

if ($stmt->execute()) {
    echo "Message sent.";
} else {
    die("Error sending message: " . $stmt->error);
}
?>

