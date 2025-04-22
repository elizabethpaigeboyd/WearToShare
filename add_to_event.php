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
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;  // Sanitize and ensure numeric value
$friend_id = isset($_POST['friend_id']) ? intval($_POST['friend_id']) : 0;  // Sanitize and ensure numeric value

// Validate inputs
if ($event_id <= 0 || $friend_id <= 0) {
    die("<p style='color: red;'>Invalid event or friend ID.</p>");
}

// Check if the current user is the organizer of the event
$stmt = $conn->prepare("SELECT organizer_id FROM events WHERE event_id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event || $event['organizer_id'] !== $user_id) {
    die("<p style='color: red;'>You are not the organizer of this event or the event does not exist.</p>");
}

// Check if the user is already participating in the event
$stmt = $conn->prepare("SELECT 1 FROM event_participants WHERE event_id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $friend_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    die("<p style='color: red;'>This user is already participating in the event.</p>");
}

// Add the friend to the event
$stmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id, is_confirmed, attendance_status) VALUES (?, ?, 1, 'going')");
$stmt->bind_param("ii", $event_id, $friend_id);

if ($stmt->execute()) {
    // Set a success message to be displayed after redirect
    $_SESSION['message'] = "Friend successfully added to the event!";
} else {
    // Set an error message to be displayed after redirect
    $_SESSION['message'] = "Error adding friend to the event.";
}

// Redirect back to the event page with message
header("Location: event.php?event_id=" . $event_id);
exit;
?>
