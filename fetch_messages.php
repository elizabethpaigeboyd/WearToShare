<?php
include '/home/epboyd/db.php';

$group_id = intval($_GET['group_id']);

$stmt = $conn->prepare("
    SELECT users.username, chat_messages.message, chat_messages.sent_at 
    FROM chat_messages 
    JOIN users ON chat_messages.user_id = users.user_id 
    WHERE chat_messages.group_id = ?
    ORDER BY chat_messages.sent_at ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<p><strong>" . htmlspecialchars($row['username']) . ":</strong> " . 
         htmlspecialchars($row['message']) . " <small>(" . $row['sent_at'] . ")</small></p>";
}
?>
