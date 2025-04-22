<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// database connection
include '/home/epboyd/db.php'; 
session_start();

// check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// get event_id from URL
$event_id = intval($_GET['event_id']);

$stmt = $conn->prepare("
    SELECT e.event_name, e.event_date, e.event_location, e.event_description, e.organizer_id, u.username AS organizer_name 
    FROM events e
    JOIN users u ON e.organizer_id = u.user_id
    WHERE e.event_id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    die("<p style='color: red;'>Event not found.</p>");
}

// get all event attendees from event_participants table
$stmt = $conn->prepare("
    SELECT u.user_id, u.username, ep.attendance_status 
    FROM event_participants ep
    JOIN users u ON ep.user_id = u.user_id
    WHERE ep.event_id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$attendees_result = $stmt->get_result();
$attendees = $attendees_result->fetch_all(MYSQLI_ASSOC);

// admin check
$stmt = $conn->prepare("SELECT user_role FROM event_participants WHERE event_id = ? AND user_id = ?");
$stmt->bind_param("ii", $event_id, $user_id);
$stmt->execute();
$role_result = $stmt->get_result();
$user_role = $role_result->fetch_assoc()['user_role'] ?? null;

// admin can add friends to the event
$potential_friends = [];
if ($user_role === 'admin') {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.username 
        FROM users u
        JOIN friendships f ON (
            (f.user_id = ? AND f.friend_id = u.user_id) 
            OR (f.friend_id = ? AND f.user_id = u.user_id)
        )
        WHERE f.status = 'accepted'
        AND u.user_id NOT IN (
            SELECT ep.user_id FROM event_participants ep WHERE ep.event_id = ?
        )
    ");

    $stmt->bind_param("iii", $user_id, $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $potential_friends = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/profile.css" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:100" rel="stylesheet"/>
    <title><?php echo htmlspecialchars($event['event_name']); ?></title>
    <style>
        .chat-box {
            width: 100%;
            height: 300px;
            overflow-y: scroll;
            border: 1px solid #ccc;
            padding: 10px;
            background: #f9f9f9;
        }
        .chat-box p {
            margin: 5px 0;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>

    <hr>

    <p><strong>Date:</strong> <?php echo date("F j, Y, g:i a", strtotime($event['event_date'])); ?></p>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($event['event_location']); ?></p>
    <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer_name']); ?></p>
    <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></p>

    <hr>

    <!-- display all attendees -->
    <h2>Event Attendees</h2>
    <ul>
        <?php foreach ($attendees as $attendee): ?>
            <li><?php echo htmlspecialchars($attendee['username']); ?> 
                (Status: <?php echo htmlspecialchars($attendee['attendance_status']); ?>)
            </li>
        <?php endforeach; ?>
    </ul>
    
    <?php if ($user_role === 'admin'): ?>
        <h2>Add a Friend to Event</h2>
        <form method="POST" action="add_to_event.php">
            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
            <select name="friend_id" required>
                <option value="">-- Select a friend --</option>
                <?php foreach ($potential_friends as $friend): ?>
                    <option value="<?php echo $friend['user_id']; ?>">
                        <?php echo htmlspecialchars($friend['username']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Add to Event</button>
        </form>
    <?php endif; ?>

    <hr>

    <h2>Chat</h2>
    <div class="chat-box" id="eventChatBox">
        <!-- Messages will be loaded here -->
    </div>

    <form id="eventChatForm">
        <input type="hidden" id="event_id" value="<?php echo $event_id; ?>">
        <input type="text" id="eventMessage" placeholder="Type your message..." required>
        <button type="submit">Send</button>
    </form>

    <h2>Style Polls</h2>
    <?php include 'event_polls.php'; ?>
</div>

<script>
function loadEventMessages() {
    const eventId = <?php echo $event_id; ?>;
    fetch("fetch_event_messages.php?event_id=" + eventId)
        .then(res => res.text())
        .then(data => {
            document.getElementById("eventChatBox").innerHTML = data;
        });
}

loadEventMessages();

document.getElementById("eventChatForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const eventId = document.getElementById("event_id").value;
    const message = document.getElementById("eventMessage").value;

    fetch("send_event_message.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `event_id=${eventId}&message=${encodeURIComponent(message)}`
    })
    .then(() => {
        document.getElementById("eventMessage").value = "";
        loadEventMessages();
    });
});
</script>

</body>
</html>
