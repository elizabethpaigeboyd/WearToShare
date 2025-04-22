<?php
include '/home/epboyd/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['poll_id']) || !isset($_POST['option_id'])) {
    header("Location: group.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$poll_id = intval($_POST['poll_id']);
$option_id = intval($_POST['option_id']);

// check if user already voted
$stmt = $conn->prepare("SELECT * FROM poll_votes WHERE user_id = ? AND poll_id = ?");
$stmt->bind_param("ii", $user_id, $poll_id);
$stmt->execute();
$result = $stmt->get_result();

// if not, insert into poll_votes table
if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO poll_votes (poll_id, user_id, option_id) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $poll_id, $user_id, $option_id);
    $stmt->execute();
}

header("Location: group.php?group_id=" . $_GET['group_id']);
exit;
?>
