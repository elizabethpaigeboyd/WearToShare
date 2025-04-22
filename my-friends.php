<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '/home/epboyd/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$original_sender = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sending a friend request
    if (isset($_POST['friendship_code'])) {
        $code = trim($_POST['friendship_code']);

        $stmt = $conn->prepare("SELECT user_id FROM users WHERE friendship_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $friend = $result->fetch_assoc();

        if (!$friend) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'No user found with that friendship code.'];
        } else {
            $friend_id = $friend['user_id'];

            if ($friend_id == $user_id) {
                $_SESSION['message'] = ['type' => 'warning', 'text' => "You can't send a friend request to yourself!"];
            } else {
                $check = $conn->prepare("SELECT * FROM friendships WHERE 
                    (user_id = ? AND friend_id = ?) OR 
                    (user_id = ? AND friend_id = ?)");
                $check->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
                $check->execute();
                $existing = $check->get_result();

                if ($existing->num_rows > 0) {
                    $_SESSION['message'] = ['type' => 'warning', 'text' => 'You already have a pending or existing friendship with this user.'];
                } else {
                    if ($user_id > $friend_id) {
                        [$user_id, $friend_id] = [$friend_id, $user_id];
                    }

                    $insert = $conn->prepare("INSERT INTO friendships (user_id, friend_id, status, requested_by) VALUES (?, ?, 'pending', ?)");
                    $insert->bind_param("iii", $user_id, $friend_id, $original_sender);

                    if ($insert->execute()) {
                        $_SESSION['message'] = ['type' => 'success', 'text' => 'Friend request sent successfully!'];
                    } else {
                        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to send request: ' . $insert->error];
                    }
                }
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Approving a friend request
    if (isset($_POST['approve'], $_POST['friend_id'])) {
        $friend_id = $_POST['friend_id'];
        $stmt = $conn->prepare("UPDATE friendships SET status = 'accepted' 
            WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))");
        $stmt->bind_param("iiii", $friend_id, $user_id, $user_id, $friend_id);
        $stmt->execute();
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Friend request approved!'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Rejecting a friend request
    if (isset($_POST['reject'], $_POST['friend_id'])) {
        $friend_id = $_POST['friend_id'];

        $userA = min($user_id, $friend_id);
        $userB = max($user_id, $friend_id);

        $stmt = $conn->prepare("UPDATE friendships 
            SET status = 'blocked' 
            WHERE user_id = ? AND friend_id = ?");
        $stmt->bind_param("ii", $userA, $userB);
        $stmt->execute();

        $_SESSION['message'] = ['type' => 'error', 'text' => 'Friend request rejected (blocked).'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// get accepted friends
$query = "
    SELECT u.username 
    FROM friendships f 
    JOIN users u ON 
        (f.friend_id = u.user_id AND f.user_id = ?) OR 
        (f.user_id = u.user_id AND f.friend_id = ?)
    WHERE f.status = 'accepted' AND (f.user_id = ? OR f.friend_id = ?)
";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$friends = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// get pending requests (received)
$sql = "
    SELECT u.user_id, u.username
    FROM friendships f
    JOIN users u ON f.requested_by = u.user_id
    WHERE 
        f.status = 'pending'
        AND ((f.user_id = ? OR f.friend_id = ?) AND f.requested_by != ?)
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert <?php echo $_SESSION['message']['type']; ?>">
                <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

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
