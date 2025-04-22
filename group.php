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

// get group_id from URL
$group_id = intval($_GET['group_id']);

// get group info
$stmt = $conn->prepare("SELECT group_name FROM groups WHERE group_id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$group_result = $stmt->get_result();
$group = $group_result->fetch_assoc();

if (!$group) {
    die("<p style='color: red;'>Group not found.</p>");
}

// get all group members
$stmt = $conn->prepare("
    SELECT users.username, group_members.user_role 
    FROM group_members 
    JOIN users ON group_members.user_id = users.user_id 
    WHERE group_members.group_id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = $members_result->fetch_all(MYSQLI_ASSOC);

// admin check
$stmt = $conn->prepare("SELECT user_role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$role_result = $stmt->get_result();
$user_role = $role_result->fetch_assoc()['user_role'] ?? null;

// admin can add friends to the group
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
            SELECT user_id FROM group_members WHERE group_id = ?
        )
    ");
    $stmt->bind_param("iii", $user_id, $user_id, $group_id);
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
    <title><?php echo htmlspecialchars($group['group_name']); ?></title>
    <style>
        /* chat box-specific styling...had trouble with it in main css file*/
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
    <!-- include the menu -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h2><?php echo htmlspecialchars($group['group_name']); ?></h2>
        <hr>
        <!-- display all members -->
        <h2>Group Members</h2>
        <ul>
            <?php foreach ($members as $member): ?>
                <li><?php echo htmlspecialchars($member['username']); ?></li>
            <?php endforeach; ?>
        </ul>
        
        <?php if ($user_role === 'admin'): ?>
            <h2>Add a Friend to Group</h2>
            <form method="POST" action="add_to_group.php">
                <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                <select name="friend_id" required>
                    <option value="">-- Select a friend --</option>
                    <?php foreach ($potential_friends as $friend): ?>
                        <option value="<?php echo $friend['user_id']; ?>">
                            <?php echo htmlspecialchars($friend['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Add to Group</button>
                </form>
        <?php endif; ?>


        <h2>Chat</h2>
        <div class="chat-box" id="chatBox">
            <!-- messages load here -->
        </div>

        <form id="chatForm">
            <input type="hidden" id="group_id" value="<?php echo $group_id; ?>">
            <input type="text" id="chatMessage" placeholder="Type a message..." required>
            <button type="submit">Send</button>
        </form>
        <h2>Style Polls</h2>
        <?php include 'group_polls.php'; ?>
    </div>

<script>
function loadMessages() {
    const groupId = <?php echo $group_id; ?>;
    fetch("fetch_messages.php?group_id=" + groupId)
        .then(response => response.text())
        .then(data => {
            document.getElementById("chatBox").innerHTML = data;
        });
}

loadMessages(); // show messages when page opens

document.getElementById("chatForm").addEventListener("submit", function(event) {
    event.preventDefault();
    const groupId = document.getElementById("group_id").value;
    const message = document.getElementById("chatMessage").value;

    fetch("chat_handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `group_id=${groupId}&message=${encodeURIComponent(message)}`
    })
    .then(response => response.text())
    .then(() => {
        loadMessages(); // refresh chat after sending
        document.getElementById("chatMessage").value = "";
    });
});
</script>

</body>
</html>
