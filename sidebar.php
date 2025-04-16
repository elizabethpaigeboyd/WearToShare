<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
  <ul>
    <li class="menu-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
      <a href="dashboard.php">Dashboard</a>
    </li>
    <li class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
      <a href="profile.php">Profile</a>
    </li>
    <li class="menu-item <?php echo ($current_page == 'my-groups.php') ? 'active' : ''; ?>">
      <a href="my-groups.php">Groups</a>
    </li>
    <li class="menu-item <?php echo ($current_page == 'my-events.php') ? 'active' : ''; ?>">
      <a href="my-events.php">Events</a>
    </li>
    <li class="menu-item <?php echo ($current_page == 'my-friends.php') ? 'active' : ''; ?>">
      <a href="my-friends.php">Friends</a>
    </li>
    <li class="menu-item <?php echo ($current_page == 'help.php') ? 'active' : ''; ?>">
      <a href="help.php">Help/FAQs</a>
    </li>
    <li class="menu-item">
      <a href="logout.php">Log Out</a>
    </li>
  </ul>
</div>
