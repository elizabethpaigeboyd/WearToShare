<?php
// error messages
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '/home/epboyd/db.php';
session_start();

// check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// approve or reject friend requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friend_id'])) {
    $friend_id = intval($_POST['friend_id']);

    if (isset($_POST['approve'])) {
        $stmt = $conn->prepare("UPDATE friendships SET status = 'accepted' 
            WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 'pending'");
        $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
    } elseif (isset($_POST['reject'])) {
        $stmt = $conn->prepare("UPDATE friendships SET status = 'rejected' 
            WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND status = 'pending'");
        $stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
    }

    if ($stmt->execute()) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "<p style='color: red;'>Error updating friend request: " . $stmt->error . "</p>";
    }
}

// send a friend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friendship_code'])) {
    $friendship_code = trim($_POST['friendship_code']);

    if (!empty($friendship_code)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE friendship_code = ?");
        $stmt->bind_param("s", $friendship_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo "<p style='color: red;'>Invalid friendship code.</p>";
        } else {
            $friend = $result->fetch_assoc();
            $friend_user_id = $friend['user_id'];

            // prevent self-friending
            if ($friend_user_id === $user_id) {
                echo "<p style='color: red;'>You can't send a friend request to yourself.</p>";
            } else {
                $stmt = $conn->prepare("SELECT friendship_id FROM friendships 
                    WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?) 
                    AND status IN ('accepted', 'pending')");
                $stmt->bind_param("iiii", $user_id, $friend_user_id, $friend_user_id, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    echo "<p style='color: red;'>You are already friends or have a pending request with this user.</p>";
                } else {
                    // Ensure user_id is less than friend_id
                    if ($user_id > $friend_user_id) {
                        // Swap user_id and friend_id
                        list($user_id, $friend_user_id) = array($friend_user_id, $user_id);
                    }

                    $stmt = $conn->prepare("INSERT INTO friendships (user_id, friend_id, status) VALUES (?, ?, 'pending')");
                    $stmt->bind_param("ii", $user_id, $friend_user_id);

                    if ($stmt->execute()) {
                        echo "<p>Friend request sent successfully!</p>";
                    } else {
                        echo "<p style='color: red;'>Error sending friend request: " . $stmt->error . "</p>";
                    }

                }

            }
        }
    }
}

// get accepted friends
$stmt = $conn->prepare("
    SELECT DISTINCT users.user_id, users.friendship_code, users.username 
    FROM friendships 
    JOIN users ON users.user_id = CASE 
        WHEN friendships.user_id = ? THEN friendships.friend_id 
        ELSE friendships.user_id 
    END
    WHERE (friendships.user_id = ? OR friendships.friend_id = ?) 
    AND friendships.status = 'accepted'
");
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$friends = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// get pending requests
// Get pending friend requests where the logged-in user is the recipient (Person B)
$stmt = $conn->prepare("
    SELECT users.user_id, users.friendship_code, users.username 
    FROM friendships 
    JOIN users ON (friendships.user_id = users.user_id OR friendships.friend_id = users.user_id) 
    WHERE friendships.status = 'pending' 
    AND ((friendships.user_id = ? AND friendships.friend_id = ?) OR (friendships.user_id = ? AND friendships.friend_id = ?))
    AND users.user_id != ? 
");
$stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$pending_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="css/profile.css"/>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:100" rel="stylesheet"/>
    <title>My Friends</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2>My Friends</h2>
        <div class="friend-links">
            <?php if (!empty($friends)): ?>
                <?php foreach ($friends as $friend): ?>
                    <p><?php echo htmlspecialchars($friend['username']); ?></p>
                <?php endforeach; ?>
            <?php else: ?>
                <p>You don't have any friends yet.</p>
            <?php endif; ?>
        </div>

        <hr>

        <h2>Pending Requests</h2>
        <div class="pending-requests">
            <?php if (!empty($pending_requests)): ?>
                <?php foreach ($pending_requests as $request): ?>
                    <div class="request">
                        <p><?php echo htmlspecialchars($request['username']); ?></p>
                        <form method="POST" action="">
                            <input type="hidden" name="friend_id" value="<?php echo $request['user_id']; ?>">
                            <button type="submit" name="approve">Approve</button>
                            <button type="submit" name="reject">Reject</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No pending friend requests.</p>
            <?php endif; ?>
        </div>


        <hr>

        <h2>Send a Friend Request</h2>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <label for="friendship_code">Friendship Code:</label>
            <input type="text" id="friendship_code" name="friendship_code" required>
            <input type="submit" value="Send Friend Request">
        </form>
    </div>
</body>
</html>
