<?php
require '/home/epboyd/db.php';
session_start();

// check for log-in & redirect if not logged in yet
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// get username and nickname from the database
$query = "SELECT username, nickname FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$nickname = $user['nickname'] ?? null; // set nickname if available
$username = $user['username'] ?? 'User'; // default to user if error and no name available

$display_name = !empty($nickname) ? $nickname : $username; // choose display name based on if nickname is empty or not
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile</title>
    <link rel="stylesheet" href="css/profile.css" />
    <link
      href="https://fonts.googleapis.com/css?family=Montserrat:100"
      rel="stylesheet"
    />
  </head>
  <body>
    <?php include 'sidebar.php'?>

    <div class="main-content">
      <header>
        <div class="user-info">
          <h2>
            Welcome back,
            <?php echo htmlspecialchars($display_name); ?>!
          </h2>
        </div>
      </header>
      <h2>Quick Links</h2>
<div class="quick-access-cards">
    <div class="groupsCard">
        <a href="my-groups.php"><h2>Groups</h2></a>
    </div>

    <div class="closetCard">
        <a href="closet.php"><h2>Closet</h2></a>
    </div>

    <div class="eventsCard">
        <a href="my-events.php"><h2>Events</h2></a>
    </div>

    <div class="friendsCard">
        <a href="my-friends.php"><h2>Friends</h2></a>
    </div>
</div>

<h2>Upcoming Events</h2>


  </body>
</html>
