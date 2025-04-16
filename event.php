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
    SELECT e.event_name, e.event_date, e.event_location, e.event_description, u.username AS organizer_name 
    FROM events e
    JOIN users u ON e.organizer_id = u.user_id
    WHERE e.event_id = ?
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    die("<p style='color: red;'>Group not found.</p>");
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

    <h2>Event Chat</h2>
    <div class="chat-box" id="chatBox">
        <!-- messages will load here -->
    </div>

    <form id="chatForm">
        <input type="hidden" id="event_id" value="<?php echo $event_id; ?>">
        <input type="text" id="chatMessage" placeholder="Type a message..." required>
        <button type="submit">Send</button>
    </form>
</div>

<script>
function loadMessages() {
    const eventId = <?php echo $event_id; ?>;
    fetch("fetch_event_messages.php?event_id=" + eventId)
        .then(response => response.text())
        .then(data => {
            document.getElementById("chatBox").innerHTML = data;
        });
}

loadMessages();

document.getElementById("chatForm").addEventListener("submit", function(event) {
    event.preventDefault();
    const eventId = document.getElementById("event_id").value;
    const message = document.getElementById("chatMessage").value;

    fetch("event_chat_handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `event_id=${eventId}&message=${encodeURIComponent(message)}`
    })
    .then(response => response.text())
    .then(() => {
        loadMessages();
        document.getElementById("chatMessage").value = "";
    });
});
</script>

</body>
</html>