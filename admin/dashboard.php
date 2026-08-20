<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
need_admin();
$base = '../';
$title = 'Admin Dashboard';
$schools = $db->query('SELECT COUNT(*) FROM schools')->fetchColumn();
$managers = $db->query('SELECT COUNT(*) FROM managers')->fetchColumn();
$students = $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$events = $db->query('SELECT COUNT(*) FROM events')->fetchColumn();
$q = $db->query('SELECT s.name, s.short_name,
(SELECT COUNT(*) FROM managers m WHERE m.school_id = s.id) AS mc,
(SELECT COUNT(*) FROM students st WHERE st.school_id = s.id) AS sc,
(SELECT COUNT(*) FROM events e WHERE e.school_id = s.id) AS ec
FROM schools s ORDER BY s.id');
$rows = $q->fetchAll();
include '../includes/header.php';
?>
<h1>System Overview</h1>
<p class="lead">Totals across the whole Campus Navigator system.</p>
<div class="stats">
<div class="stat"><div class="num"><?php echo $schools; ?></div><div class="lbl">Schools</div></div>
<div class="stat"><div class="num"><?php echo $managers; ?></div><div class="lbl">Managers</div></div>
<div class="stat"><div class="num"><?php echo $students; ?></div><div class="lbl">Students</div></div>
<div class="stat"><div class="num"><?php echo $events; ?></div><div class="lbl">Events</div></div>
</div>
<h2>Breakdown per school</h2>
<table>
<tr><th>School</th><th>Managers</th><th>Students</th><th>Events</th></tr>
<?php foreach ($rows as $r) { ?>
<tr>
<td><?php echo htmlspecialchars($r['name']); ?> (<?php echo htmlspecialchars($r['short_name']); ?>)</td>
<td><?php echo $r['mc']; ?></td>
<td><?php echo $r['sc']; ?></td>
<td><?php echo $r['ec']; ?></td>
</tr>
<?php } ?>
</table>
<?php include '../includes/footer.php'; ?>
