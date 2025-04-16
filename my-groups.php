<?php 
// error messages
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '/home/epboyd/db.php'; // connect to database
session_start();

// check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id']; // get user id

// checking the form submission 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_name'])) {
    $group_name = trim($_POST['group_name']);

    // if group name isn't empty...
    if (!empty($group_name)) {        
        $stmt = $conn->prepare("SELECT 1 FROM groups WHERE group_name = ?"); // check for duplicate group name
        if (!$stmt) {
            die("<p style='color: red;'>Prepare failed: " . $conn->error . "</p>");
        }
        $stmt->bind_param("s", $group_name);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            die("<p style='color: red;'>Group name already exists. Choose another.</p>");
        }

        // insert group name into a new group
        $stmt = $conn->prepare("INSERT INTO groups (group_name) VALUES (?)");
        if (!$stmt) {
            die("<p style='color: red;'>Prepare failed: " . $conn->error . "</p>");
        }
        $stmt->bind_param("s", $group_name);
        
        if (!$stmt->execute()) {
            die("<p style='color: red;'>Error creating group: " . $stmt->error . "</p>");
        }

        $new_group_id = $conn->insert_id; 
        echo "<p>New group created with ID: " . $new_group_id . "</p>";
        
        $user_role = 'admin'; // set the creator of the group as the admin

        // check if the user is already a member
        $stmt = $conn->prepare("SELECT 1 FROM group_members WHERE user_id = ? AND group_id = ?");
        $stmt->bind_param("ii", $user_id, $new_group_id);
        $stmt->execute();
        $result = $stmt->get_result();

        
        if ($result->num_rows === 0) { // insert if it doesn't already exist
            $stmt = $conn->prepare("INSERT INTO group_members (user_id, group_id, user_role) VALUES (?, ?, ?)");
            if (!$stmt) {
                die("<p style='color: red;'>Prepare failed: " . $conn->error . "</p>");
            }
            $stmt->bind_param("iis", $user_id, $new_group_id, $user_role);
            if (!$stmt->execute()) {
                die("<p style='color: red;'>Error adding user to group: " . $stmt->error . "</p>");
            }
            echo "<p>User added to group as admin</p>";
        } else {
            echo "<p style='color: red;'>User is already a member of this group.</p>";
        }
        $_SESSION['message'] = "Group created successfully!";
        // refresh the page so that the updated group will show
        header("Location: https://turing2.cs.olemiss.edu/~epboyd/WearToShare/my-groups.php");
        exit();
    } else {
        $message = "<p style='color: red;'>Group name cannot be empty.</p>";
    }
}

// get the user's groups
$stmt = $conn->prepare("
SELECT groups.group_id, groups.group_name 
FROM groups 
JOIN group_members ON groups.group_id = group_members.group_id 
WHERE group_members.user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$groups = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>My Groups</title>
</head>
<body>
    <?php include 'sidebar.php'?>

<div class="main-content">
    <h2>My Groups</h2>

<!-- show the user's groups -->
<div class="group-links">
<?php
    if (!empty($groups)): 
        foreach ($groups as $group): 
            echo "<a href='group.php?group_id=" . $group['group_id'] . "' class='group-button'>
        <button>" . htmlspecialchars($group['group_name']) . "</button>
      </a>";
        endforeach;
    else:
        echo "You are not in any groups yet.";
    endif;
?>
</div>
<br><br>
<hr>

<h2>Create a Group</h2>
<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <label for="group_name">Group Name:</label>
    <input type="text" id="group_name" name="group_name" required>
    <input type="submit" value="Create Group">
</form>
</div>
</body>

<script>
document.getElementById('createGroupForm').addEventListener('submit', function(e) {
    console.log('Form submitted');
});
</script>

</html>