<?php
include '/home/epboyd/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$event_id = $_GET['event_id'] ?? null;

if (!$user_id || !$event_id) {
    echo "<p>Error: Missing user or event info.</p>";
    return;
}

// === Handle voting submission ===
if (isset($_POST['vote']) && isset($_POST['poll_id'], $_POST['option_id'])) {
    $poll_id = $_POST['poll_id'];
    $option_id = $_POST['option_id'];

    $check = $conn->prepare("SELECT * FROM poll_votes WHERE poll_id = ? AND user_id = ?");
    $check->bind_param("ii", $poll_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        $voteStmt = $conn->prepare("INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)");
        $voteStmt->bind_param("iii", $poll_id, $option_id, $user_id);
        $voteStmt->execute();
    }

    header("Location: event.php?event_id=$event_id");
    exit;
}

// === Handle poll creation ===
if (isset($_POST['create_poll']) && isset($_POST['question'], $_POST['image_a'], $_POST['image_b'])) {
    $question = $_POST['question'];
    $imageA = $_POST['image_a'];
    $imageB = $_POST['image_b'];

    $conn->begin_transaction();

    try {
        $insertPoll = $conn->prepare("INSERT INTO polls (event_id, question, created_by_user_id, group_id) VALUES (?, ?, ?, NULL)");
        $insertPoll->bind_param("isi", $event_id, $question, $user_id);
        $insertPoll->execute();
        $poll_id = $conn->insert_id;

        $textA = 'Option A';
        $textB = 'Option B';

        $opt1 = $conn->prepare("INSERT INTO poll_options (poll_id, option_text, image_url) VALUES (?, ?, ?)");
        $opt1->bind_param("iss", $poll_id, $textA, $imageA);
        $opt1->execute();

        $opt2 = $conn->prepare("INSERT INTO poll_options (poll_id, option_text, image_url) VALUES (?, ?, ?)");
        $opt2->bind_param("iss", $poll_id, $textB, $imageB);
        $opt2->execute();

        $conn->commit();

        header("Location: event.php?event_id=$event_id");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p>Error creating poll: " . $e->getMessage() . "</p>";
    }
}

// === Display ALL polls for the event ===
$stmt = $conn->prepare("
    SELECT p.*, u.username 
    FROM polls p 
    JOIN users u ON p.created_by_user_id = u.user_id 
    WHERE p.event_id = ? 
    ORDER BY p.poll_id DESC
");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$pollResult = $stmt->get_result();

if ($pollResult->num_rows > 0) {
    while ($poll = $pollResult->fetch_assoc()) {
        $poll_id = $poll['poll_id'];
        $question = htmlspecialchars($poll['question']);
        $creator = htmlspecialchars($poll['username']);

        echo "<div style='margin-bottom: 40px; border-bottom: 1px solid #ddd; padding-bottom: 20px;'>";
        echo "<h3>$question <span style='font-size: 0.9em; color: #666;'>($creator)</span></h3>";
        echo "<div style='display: flex; gap: 20px;'>";

        // Check if user has voted
        $checkVote = $conn->prepare("SELECT * FROM poll_votes WHERE poll_id = ? AND user_id = ?");
        $checkVote->bind_param("ii", $poll_id, $user_id);
        $checkVote->execute();
        $voteResult = $checkVote->get_result();
        $hasVoted = $voteResult->num_rows > 0;

        // Get poll options with vote counts
        $optionsStmt = $conn->prepare("
            SELECT o.*, COUNT(v.vote_id) AS vote_count 
            FROM poll_options o 
            LEFT JOIN poll_votes v ON o.option_id = v.option_id 
            WHERE o.poll_id = ? 
            GROUP BY o.option_id
        ");
        $optionsStmt->bind_param("i", $poll_id);
        $optionsStmt->execute();
        $options = $optionsStmt->get_result();

        while ($opt = $options->fetch_assoc()) {
            echo "<div style='text-align: center;'>
                <img src='" . htmlspecialchars($opt['image_url']) . "' style='width: 200px; height: auto; border: 1px solid #ccc;'><br>
                <p>{$opt['vote_count']} vote(s)</p>";

            if ($hasVoted) {
                echo "<button disabled>Voted</button>";
            } else {
                echo "<form method='post'>
                    <input type='hidden' name='vote' value='1'>
                    <input type='hidden' name='poll_id' value='{$poll_id}'>
                    <input type='hidden' name='option_id' value='{$opt['option_id']}'>
                    <button type='submit'>Vote</button>
                </form>";
            }

            echo "</div>";
        }

        echo "</div></div>";
    }
} else {
    echo "<p>No polls yet for this event.</p>";
}

// === Always show create poll form ===
?>
<h3>Create a New Poll</h3>
<form method="post">
    <input type="hidden" name="create_poll" value="1">
    <label>Poll Question: <input type="text" name="question" required></label><br><br>
    <label>Image URL for Option A: <input type="text" name="image_a" required></label><br><br>
    <label>Image URL for Option B: <input type="text" name="image_b" required></label><br><br>
    <button type="submit">Create Poll</button>
</form>
