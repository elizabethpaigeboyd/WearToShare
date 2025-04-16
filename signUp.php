<?php
// include the database connection file
include '/home/epboyd/db.php';

// function to create a random code of 8 letters/numbers
// this will be used for later when adding friends
function generateFriendCode() {
  return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
}

// check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        echo "All fields are required.";
        exit;
    }

    // get unique friendship code
    $friendship_code = generateFriendCode();

    // use bcrypt for password hashing
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, friendship_code) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $friendship_code);

    if ($stmt->execute()) {
        echo "User registered successfully!";
        header('Location: login.php');
    } else {
        echo "Error: " . $stmt->error;
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
    <title>Create Account</title>
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
 <h1>Create Your Account</h1>
 <body>
    <form method="POST" action="signUp.php">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Sign Up</button>
</form>

<p>Already have an account? Log in here: <a href="login.php"><button>Log in</button></a></p>
 </body>
 </html>

