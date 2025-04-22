<?php
require '/home/epboyd/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "SELECT username, nickname FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$nickname = $user['nickname'] ?? null;
$username = $user['username'] ?? 'User'; 

$display_name = !empty($nickname) ? $nickname : $username; 
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
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="user-info">
      <h2>
        Hi, <?php echo htmlspecialchars($display_name); ?>! What can we help you with?
      </h2>
    </div>

    <div class="faq-section">
  <div class="faq-card">
    <details>
      <summary>What is Wear to Share?</summary>
      <div class="answer">
        Wear to Share is a fun and collaborative platform where users can exchange outfit inspiration, chat about fashion, and vote in daily style polls.
      </div>
    </details>
  </div>

  <div class="faq-card">
    <details>
      <summary>How do I add friends?</summary>
      <div class="answer">
        Every user has a 'friendship code' unique to them! You can find this code on your profile page below your bio. To add a friend, navigate to the 
        'Friends' page and input their friendship code. You can invite friends by sharing your code with them personally or sending them a link from your profile page.
      </div>
    </details>
  </div>

  <div class="faq-card">
    <details>
      <summary>Is Wear to Share free to use?</summary>
      <div class="answer">
        Yes! Wear to Share is completely free. We believe style inspo should be shared, not sold.
      </div>
    </details>
  </div>

  <div class="faq-card">
    <details>
      <summary>What are Style Polls?</summary>
      <div class="answer">
        Style Polls let you vote on looks, trends, or fashion dilemmas. You can even create your own polls to get fashion feedback! 
        Simply navigate to one of your groups and find the 'Polls' section. From there, you can create as many polls as your heart desires!
      </div>
    </details>
  </div>

  <div class="faq-card">
    <details>
      <summary>Can I message other users?</summary>
      <div class="answer">
        Absolutely! Our chat feature makes it easy to connect with others and trade outfit tips or fashion thoughts.
        Navigate to a group or event to start the conversation!
      </div>
    </details>
  </div>
</div>

  </div>
</body>

</html>

