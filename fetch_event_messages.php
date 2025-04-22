<?php
include '/home/epboyd/db.php';

$event_id = intval($_GET['event_id']);

$stmt = $conn->prepare("
    SELECT u.username, em.message, em.sent_at
    FROM event_messages em
    JOIN users u ON em.user_id = u.user_id
    WHERE em.event_id = ?
    ORDER BY em.sent_at ASC
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "<p><strong>" . htmlspecialchars($row['username']) . ":</strong> " .
         htmlspecialchars($row['message']) . 
         " <small>(" . $row['sent_at'] . ")</small></p>";
}
?>
