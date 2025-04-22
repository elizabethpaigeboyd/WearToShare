<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// connect to the database
include '/home/epboyd/db.php';
session_start();

// check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// get user ID from session
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_name'])) {
    $event_name = trim($_POST['event_name']);
    $event_date = str_replace("T", " ", trim($_POST['event_date'])); // fix datetime format
    $event_location = trim($_POST['event_location']);
    $event_description = trim($_POST['event_description']);

    if (!empty($event_name) && !empty($event_date)) {        
        // Insert the event into the events table
        $stmt = $conn->prepare("INSERT INTO events (event_name, event_date, event_location, event_description, organizer_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $event_name, $event_date, $event_location, $event_description, $user_id);
        $stmt->execute(); 

        // Get the ID of the newly created event
        $event_id = $conn->insert_id;
        $stmt->close();

        // Insert the user as an admin into event_participants
        $stmt = $conn->prepare("INSERT INTO event_participants (event_id, user_id, user_role) VALUES (?, ?, 'admin')");
        $stmt->bind_param("ii", $event_id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Redirect back to the user's event page
        header("Location: https://turing2.cs.olemiss.edu/~epboyd/WearToShare/my-events.php");
        exit();
    }
}

// get the user's events
$stmt = $conn->prepare("SELECT event_id, event_name, event_date, event_location FROM events WHERE organizer_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$events = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/profile.css" />
    <link
      href="https://fonts.googleapis.com/css?family=Montserrat:100"
      rel="stylesheet"
    />
    <title>My Events</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>

<div class="main-content">
<h2>My Events</h2>
<div class="event-links">
<?php
if (!empty($events)): 
    foreach ($events as $event): 
        echo "<form action='event.php' method='get' style='display:inline;'>
                <button type='submit' name='event_id' value='" . $event['event_id'] . "' class='event-button'>" . htmlspecialchars($event['event_name']) . "</button>
              </form>";
    endforeach;
else:
    echo "<p class='no-events'>You have no events yet.</p>";
endif;

// formatted event date 
if (isset($_POST['event_date'])) {
    $dateTime = str_replace("T", " ", $_POST['event_date']);  
    $formattedTime = date("m/d/Y g:i A", strtotime($dateTime));
    echo "<p>Formatted Time: " . htmlspecialchars($formattedTime) . "</p>";
}
?>
</div>

<hr>

<h2>Create an Event</h2>
<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="event-form">
    <div class="form-group">
        <label for="event_name">Event Name:</label>
        <input type="text" id="event_name" name="event_name" required>
    </div>
    
    <div class="form-group">
        <label for="event_date">Event Date:</label>
        <input type="datetime-local" id="event_date" name="event_date" required>
    </div>
    
    <div class="form-group">
        <label for="event_location">Location:</label>
        <input type="text" id="event_location" name="event_location">
    </div>
    
    <div class="form-group">
        <label for="event_description">Description:</label>
        <textarea id="event_description" name="event_description"></textarea>
    </div>
    
    <div class="form-group">
        <input type="submit" value="Create Event" class="submit-button">
    </div>
</form>
</div>

<script>
document.querySelector("form").addEventListener("submit", function() {
    console.log("Form submitted");
});
</script>

</body>
</html>
