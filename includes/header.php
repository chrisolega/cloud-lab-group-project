<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $title; ?> - Campus Navigator</title>
<link rel="stylesheet" href="<?php echo $base; ?>css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">
</head>
<body>
<nav class="nav">
<a href="<?php echo $base; ?>index.php" class="brand">Campus<span>Navigator</span></a>
<div class="navlinks">
<?php if (isset($_SESSION['student_id'])) { ?>
<span class="who">Student: <?php echo htmlspecialchars($_SESSION['student_index']); ?></span>
<a href="<?php echo $base; ?>student/logout.php" class="btn small">Sign out</a>
<?php } else if (isset($_SESSION['manager_id'])) { ?>
<span class="who">Manager: <?php echo htmlspecialchars($_SESSION['manager_name']); ?></span>
<a href="<?php echo $base; ?>manager/dashboard.php">Events</a>
<a href="<?php echo $base; ?>manager/students.php">Students</a>
<a href="<?php echo $base; ?>manager/logout.php" class="btn small">Sign out</a>
<?php } else if (isset($_SESSION['admin_id'])) { ?>
<span class="who">Admin: <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
<a href="<?php echo $base; ?>admin/dashboard.php">Dashboard</a>
<a href="<?php echo $base; ?>admin/managers.php">Managers</a>
<a href="<?php echo $base; ?>admin/students.php">Students</a>
<a href="<?php echo $base; ?>admin/events.php">Events</a>
<a href="<?php echo $base; ?>admin/logout.php" class="btn small">Sign out</a>
<?php } else { ?>
<a href="<?php echo $base; ?>index.php">Home</a>
<a href="<?php echo $base; ?>manager/login.php">Manager</a>
<a href="<?php echo $base; ?>admin/login.php">Admin</a>
<?php } ?>
</div>
</nav>
<main class="wrap">
