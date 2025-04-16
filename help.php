<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Help/FAQs</title>
    <link rel="stylesheet" href="css/profile.css" />
    <link
      href="https://fonts.googleapis.com/css?family=Montserrat:100"
      rel="stylesheet"
    />
  </head>
  <body>

    <div class="main-content">
      <header>
        <div class="user-info">
          <h2>
            Hi, <?php echo htmlspecialchars($username); ?>! What can we help you with?
          </h2>

          <h3>Question</h3>
          <p>   Answer</p>
        </div>
      </header>

      <p>Didn't find your answer? No problem! Send us a message here: </p>
    </div>
  </body>
</html>

