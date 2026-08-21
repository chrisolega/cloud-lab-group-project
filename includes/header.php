<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $title; ?> - Campus Navigator</title>
<link rel="stylesheet" href="<?php echo $base; ?>css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">
<script>
(function () {
var saved = localStorage.getItem('theme');
var theme = saved ? saved : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
document.documentElement.setAttribute('data-theme', theme);
})();
</script>
</head>
<body>
<nav class="nav">
<a href="<?php echo $base; ?>index.php" class="brand">Campus<span>Navigator</span></a>
<div class="navlinks">
<button type="button" class="theme-toggle" onclick="toggleSiteTheme()" aria-label="Toggle dark mode" title="Toggle dark mode">
<svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
<svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path></svg>
</button>
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
