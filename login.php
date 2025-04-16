<?php
include '/home/epboyd/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        echo "Both fields are required.";
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $db_username, $db_password);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $db_username;
            echo "Login successful! Welcome " . $db_username;
            header('Location: dashboard.php');
            exit;
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found with that username.";
    }

    $stmt->close();
    $conn->close();
}
?>

 <!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/signUp.css" />
    <link
      href="https://fonts.googleapis.com/css?family=Montserrat:100"
      rel="stylesheet"
    />
 </head>
 <header>
      <nav class="navbar">
        <div class="logo">
          <img src="images/logo.png" alt="Wear to Share logo" />
        </div>
        <ul class="nav-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="about.html">About</a></li>
          <li><a href="contact.html">Contact Us</a></li>
        </ul>
        <div class="nav-icons">
          <a href="dashboard.php">
            <img src="images/menu-icon.png" alt="Menu" />
          </a>
        </div>
      </nav>
    </header>
 <body>
     <h1>Log In</h1>

    <form method="POST" action="login.php">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Log In</button>
</form>
    <p>Don't have an account? Sign up here: <a href="signUp.php"><button>Sign Up</button></a></p>

 </body>
 </html>