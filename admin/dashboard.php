<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

need_admin();

$base = '../';
$title = 'Admin Dashboard';

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$schools = $db->query('SELECT COUNT(*) FROM schools')->fetchColumn();
$managers = $db->query('SELECT COUNT(*) FROM managers')->fetchColumn();
$students = $db->query('SELECT COUNT(*) FROM students')->fetchColumn();
$events = $db->query('SELECT COUNT(*) FROM events')->fetchColumn();

/*
|--------------------------------------------------------------------------
| School Breakdown
|--------------------------------------------------------------------------
*/

$q = $db->query('
    SELECT 
        s.name,
        s.short_name,
        (SELECT COUNT(*) FROM managers m WHERE m.school_id = s.id) AS mc,
        (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id) AS sc,
        (SELECT COUNT(*) FROM events e WHERE e.school_id = s.id) AS ec
    FROM schools s
    ORDER BY s.id
');

$rows = $q->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Total activity for progress bars
|--------------------------------------------------------------------------
*/

$totalActivity = $managers + $students + $events;

include '../includes/header.php';
?>

<style>
/* =========================================================
   ADMIN DASHBOARD
   ========================================================= */

.admin-dashboard {
    --ad-primary: #2563eb;
    --ad-primary-dark: #1d4ed8;
    --ad-success: #16a34a;
    --ad-warning: #f59e0b;
    --ad-purple: #7c3aed;
    --ad-danger: #dc2626;

    --ad-bg: #f5f7fb;
    --ad-card: #ffffff;
    --ad-text: #111827;
    --ad-muted: #6b7280;
    --ad-border: #e5e7eb;

    min-height: 100vh;
    background: var(--ad-bg);
    color: var(--ad-text);
    padding: 25px;
    border-radius: 18px;
    transition: background 0.3s ease, color 0.3s ease;
}

/* Dark Mode */

.admin-dashboard.dark-mode {
    --ad-bg: #0f172a;
    --ad-card: #1e293b;
    --ad-text: #f8fafc;
    --ad-muted: #94a3b8;
    --ad-border: #334155;

    background: #0f172a;
}

/* Header */

.ad-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.ad-title-area h1 {
    margin: 0 0 7px;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.ad-title-area p {
    margin: 0;
    color: var(--ad-muted);
    font-size: 14px;
}

.ad-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Buttons */

.ad-btn {
    border: 1px solid var(--ad-border);
    background: var(--ad-card);
    color: var(--ad-text);
    padding: 10px 15px;
    border-radius: 10px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.ad-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.ad-btn.primary {
    background: var(--ad-primary);
    color: #fff;
    border-color: var(--ad-primary);
}

.ad-btn.primary:hover {
    background: var(--ad-primary-dark);
}

/* Statistics */

.ad-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}

.ad-stat-card {
    background: var(--ad-card);
    border: 1px solid var(--ad-border);
    border-radius: 16px;
    padding: 22px;
    position: relative;
    overflow: hidden;
    transition: all 0.25s ease;
}

.ad-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
}

.ad-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ad-stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.ad-stat-icon.blue {
    background: #dbeafe;
}

.ad-stat-icon.green {
    background: #dcfce7;
}

.ad-stat-icon.purple {
    background: #ede9fe;
}

.ad-stat-icon.orange {
    background: #fef3c7;
}

.ad-stat-number {
    font-size: 31px;
    font-weight: 800;
    margin-top: 18px;
}

.ad-stat-label {
    color: var(--ad-muted);
    font-size: 14px;
    margin-top: 4px;
}

/* Dashboard Grid */

.ad-main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 22px;
    margin-bottom: 25px;
}

/* Cards */

.ad-card {
    background: var(--ad-card);
    border: 1px solid var(--ad-border);
    border-radius: 16px;
    padding: 22px;
}

.ad-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.ad-card-header h2 {
    margin: 0;
    font-size: 19px;
}

.ad-card-header p {
    margin: 5px 0 0;
    color: var(--ad-muted);
    font-size: 13px;
}

/* Search */

.ad-search {
    position: relative;
}

.ad-search input {
    width: 230px;
    box-sizing: border-box;
    padding: 10px 13px 10px 38px;
    border: 1px solid var(--ad-border);
    border-radius: 10px;
    background: var(--ad-card);
    color: var(--ad-text);
    outline: none;
}

.ad-search input:focus {
    border-color: var(--ad-primary);
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}

.ad-search span {
    position: absolute;
    left: 13px;
    top: 9px;
}

/* Table */

.ad-table-wrapper {
    overflow-x: auto;
}

.ad-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

.ad-table th {
    text-align: left;
    padding: 13px 12px;
    color: var(--ad-muted);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--ad-border);
}

.ad-table td {
    padding: 15px 12px;
    border-bottom: 1px solid var(--ad-border);
    font-size: 14px;
}

.ad-table tbody tr {
    transition: background 0.2s ease;
}

.ad-table tbody tr:hover {
    background: rgba(37,99,235,0.05);
}

.ad-school-name {
    font-weight: 700;
}

.ad-school-short {
    color: var(--ad-muted);
    font-size: 12px;
    margin-top: 3px;
}

/* Number badges */

.ad-number {
    display: inline-flex;
    min-width: 34px;
    height: 30px;
    padding: 0 8px;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(37,99,235,0.1);
    color: var(--ad-primary);
    font-weight: 700;
}

/* Activity */

.ad-activity-item {
    margin-bottom: 22px;
}

.ad-activity-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 7px;
    font-size: 13px;
}

.ad-activity-info strong {
    font-weight: 700;
}

.ad-progress {
    height: 8px;
    background: var(--ad-border);
    border-radius: 20px;
    overflow: hidden;
}

.ad-progress-bar {
    height: 100%;
    border-radius: 20px;
    width: 0;
    transition: width 1s ease;
}

.ad-progress-blue {
    background: var(--ad-primary);
}

.ad-progress-green {
    background: var(--ad-success);
}

.ad-progress-purple {
    background: var(--ad-purple);
}

/* Quick actions */

.ad-quick-actions {
    display: grid;
    gap: 10px;
}

.ad-quick-action {
    text-decoration: none;
    color: var(--ad-text);
    border: 1px solid var(--ad-border);
    border-radius: 11px;
    padding: 13px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
}

.ad-quick-action:hover {
    border-color: var(--ad-primary);
    transform: translateX(3px);
}

.ad-quick-left {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ad-quick-icon {
    width: 35px;
    height: 35px;
    border-radius: 9px;
    background: rgba(37,99,235,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Empty state */

.ad-empty {
    text-align: center;
    padding: 35px;
    color: var(--ad-muted);
}

/* Last updated */

.ad-updated {
    color: var(--ad-muted);
    font-size: 12px;
    margin-top: 20px;
    text-align: right;
}

/* Responsive */

@media (max-width: 1000px) {
    .ad-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .ad-main-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 650px) {
    .admin-dashboard {
        padding: 15px;
        border-radius: 0;
    }

    .ad-title-area h1 {
        font-size: 24px;
    }

    .ad-stats {
        grid-template-columns: 1fr;
    }

    .ad-search input {
        width: 100%;
    }

    .ad-search {
        width: 100%;
    }

    .ad-card {
        padding: 16px;
    }

    .ad-topbar {
        align-items: stretch;
    }

    .ad-actions {
        width: 100%;
    }

    .ad-actions .ad-btn {
        flex: 1;
        justify-content: center;
    }
}
</style>

<div class="admin-dashboard" id="adminDashboard">

    <!-- TOP BAR -->
    <div class="ad-topbar">

        <div class="ad-title-area">
            <h1>Admin Dashboard</h1>
            <p>Welcome back. Here's an overview of your Campus Navigator system.</p>
        </div>

        <div class="ad-actions">

            <button type="button" class="ad-btn" onclick="toggleAdminTheme()">
                <span id="themeIcon">🌙</span>
                <span id="themeText">Dark Mode</span>
            </button>

            <button type="button" class="ad-btn primary" onclick="refreshDashboard()">
                ↻ Refresh
            </button>

        </div>

    </div>


    <!-- STATISTICS -->
    <div class="ad-stats">

        <div class="ad-stat-card">
            <div class="ad-stat-top">
                <div>
                    <div class="ad-stat-label">Total Schools</div>
                </div>

                <div class="ad-stat-icon blue">🏫</div>
            </div>

            <div class="ad-stat-number counter"
                 data-target="<?php echo (int)$schools; ?>">
                0
            </div>

            <div class="ad-stat-label">
                Registered schools
            </div>
        </div>


        <div class="ad-stat-card">
            <div class="ad-stat-top">
                <div>
                    <div class="ad-stat-label">Managers</div>
                </div>

                <div class="ad-stat-icon green">👨‍💼</div>
            </div>

            <div class="ad-stat-number counter"
                 data-target="<?php echo (int)$managers; ?>">
                0
            </div>

            <div class="ad-stat-label">
                System managers
            </div>
        </div>


        <div class="ad-stat-card">
            <div class="ad-stat-top">
                <div>
                    <div class="ad-stat-label">Students</div>
                </div>

                <div class="ad-stat-icon purple">🎓</div>
            </div>

            <div class="ad-stat-number counter"
                 data-target="<?php echo (int)$students; ?>">
                0
            </div>

            <div class="ad-stat-label">
                Registered students
            </div>
        </div>


        <div class="ad-stat-card">
            <div class="ad-stat-top">
                <div>
                    <div class="ad-stat-label">Events</div>
                </div>

                <div class="ad-stat-icon orange">📅</div>
            </div>

            <div class="ad-stat-number counter"
                 data-target="<?php echo (int)$events; ?>">
                0
            </div>

            <div class="ad-stat-label">
                Campus events
            </div>
        </div>

    </div>


    <!-- MAIN CONTENT -->
    <div class="ad-main-grid">

        <!-- SCHOOL BREAKDOWN -->
        <div class="ad-card">

            <div class="ad-card-header">

                <div>
                    <h2>School Overview</h2>
                    <p>Breakdown of managers, students and events by school.</p>
                </div>

                <div class="ad-search">
                    <span>🔍</span>

                    <input
                        type="text"
                        id="schoolSearch"
                        placeholder="Search schools..."
                        onkeyup="searchSchools()"
                        autocomplete="off"
                    >
                </div>

            </div>


            <div class="ad-table-wrapper">

                <table class="ad-table" id="schoolTable">

                    <thead>
                        <tr>
                            <th>School</th>
                            <th>Managers</th>
                            <th>Students</th>
                            <th>Events</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($rows) > 0): ?>

                        <?php foreach ($rows as $r): ?>

                            <tr>

                                <td>
                                    <div class="ad-school-name">
                                        <?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>

                                    <div class="ad-school-short">
                                        <?php echo htmlspecialchars($r['short_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>

                                <td>
                                    <span class="ad-number">
                                        <?php echo (int)$r['mc']; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="ad-number">
                                        <?php echo (int)$r['sc']; ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="ad-number">
                                        <?php echo (int)$r['ec']; ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4">
                                <div class="ad-empty">
                                    🏫 No schools found in the system.
                                </div>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- QUICK ACTIONS / ACTIVITY -->
        <div>

            <div class="ad-card" style="margin-bottom: 22px;">

                <div class="ad-card-header">
                    <div>
                        <h2>System Activity</h2>
                        <p>Current distribution of system records.</p>
                    </div>
                </div>


                <?php
                $studentPercent = $totalActivity > 0
                    ? round(($students / $totalActivity) * 100)
                    : 0;

                $managerPercent = $totalActivity > 0
                    ? round(($managers / $totalActivity) * 100)
                    : 0;

                $eventPercent = $totalActivity > 0
                    ? round(($events / $totalActivity) * 100)
                    : 0;
                ?>


                <div class="ad-activity-item">

                    <div class="ad-activity-info">
                        <strong>Students</strong>
                        <span><?php echo $studentPercent; ?>%</span>
                    </div>

                    <div class="ad-progress">
                        <div
                            class="ad-progress-bar ad-progress-blue"
                            data-width="<?php echo $studentPercent; ?>%"
                        ></div>
                    </div>

                </div>


                <div class="ad-activity-item">

                    <div class="ad-activity-info">
                        <strong>Managers</strong>
                        <span><?php echo $managerPercent; ?>%</span>
                    </div>

                    <div class="ad-progress">
                        <div
                            class="ad-progress-bar ad-progress-green"
                            data-width="<?php echo $managerPercent; ?>%"
                        ></div>
                    </div>

                </div>


                <div class="ad-activity-item">

                    <div class="ad-activity-info">
                        <strong>Events</strong>
                        <span><?php echo $eventPercent; ?>%</span>
                    </div>

                    <div class="ad-progress">
                        <div
                            class="ad-progress-bar ad-progress-purple"
                            data-width="<?php echo $eventPercent; ?>%"
                        ></div>
                    </div>

                </div>

            </div>


            <div class="ad-card">

                <div class="ad-card-header">
                    <div>
                        <h2>Quick Access</h2>
                        <p>Useful administration actions.</p>
                    </div>
                </div>

                <div class="ad-quick-actions">

                    <a href="#" class="ad-quick-action">
                        <div class="ad-quick-left">
                            <div class="ad-quick-icon">🏫</div>
                            <span>Manage Schools</span>
                        </div>

                        <span>›</span>
                    </a>


                    <a href="#" class="ad-quick-action">
                        <div class="ad-quick-left">
                            <div class="ad-quick-icon">👨‍💼</div>
                            <span>Manage Managers</span>
                        </div>

                        <span>›</span>
                    </a>


                    <a href="#" class="ad-quick-action">
                        <div class="ad-quick-left">
                            <div class="ad-quick-icon">🎓</div>
                            <span>Manage Students</span>
                        </div>

                        <span>›</span>
                    </a>


                    <a href="#" class="ad-quick-action">
                        <div class="ad-quick-left">
                            <div class="ad-quick-icon">📅</div>
                            <span>Manage Events</span>
                        </div>

                        <span>›</span>
                    </a>

                </div>

            </div>

        </div>

    </div>


    <div class="ad-updated">
        Last dashboard refresh:
        <strong id="lastUpdated"></strong>
    </div>

</div>


<script>
/*
|--------------------------------------------------------------------------
| Dark / Light Mode
|--------------------------------------------------------------------------
*/

const adminDashboard = document.getElementById('adminDashboard');
const themeIcon = document.getElementById('themeIcon');
const themeText = document.getElementById('themeText');

function applyAdminTheme(theme) {

    if (theme === 'dark') {

        adminDashboard.classList.add('dark-mode');

        themeIcon.textContent = '☀️';
        themeText.textContent = 'Light Mode';

    } else {

        adminDashboard.classList.remove('dark-mode');

        themeIcon.textContent = '🌙';
        themeText.textContent = 'Dark Mode';
    }
}


function toggleAdminTheme() {

    const currentTheme =
        adminDashboard.classList.contains('dark-mode')
            ? 'dark'
            : 'light';

    const newTheme =
        currentTheme === 'dark'
            ? 'light'
            : 'dark';

    localStorage.setItem('adminTheme', newTheme);

    applyAdminTheme(newTheme);
}


/*
|--------------------------------------------------------------------------
| Load saved theme
|--------------------------------------------------------------------------
*/

const savedAdminTheme =
    localStorage.getItem('adminTheme') || 'light';

applyAdminTheme(savedAdminTheme);


/*
|--------------------------------------------------------------------------
| Animated Statistics
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.counter').forEach(function(counter) {

    const target = parseInt(counter.getAttribute('data-target')) || 0;

    let current = 0;

    const duration = 800;

    const increment =
        target > 0
            ? Math.max(1, Math.ceil(target / 40))
            : 1;

    const timer = setInterval(function() {

        current += increment;

        if (current >= target) {

            current = target;
            clearInterval(timer);
        }

        counter.textContent =
            current.toLocaleString();

    }, duration / 40);

});


/*
|--------------------------------------------------------------------------
| Search Schools
|--------------------------------------------------------------------------
*/

function searchSchools() {

    const input =
        document.getElementById('schoolSearch');

    const filter =
        input.value.toLowerCase().trim();

    const table =
        document.getElementById('schoolTable');

    const rows =
        table.querySelectorAll('tbody tr');

    rows.forEach(function(row) {

        const text =
            row.textContent.toLowerCase();

        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }

    });

}


/*
|--------------------------------------------------------------------------
| Refresh Dashboard
|--------------------------------------------------------------------------
*/

function refreshDashboard() {

    const button =
        document.querySelector('.ad-btn.primary');

    button.innerHTML = '⟳ Refreshing...';

    button.disabled = true;

    setTimeout(function() {
        window.location.reload();
    }, 500);

}


/*
|--------------------------------------------------------------------------
| Last Updated
|--------------------------------------------------------------------------
*/

function updateLastUpdated() {

    const element =
        document.getElementById('lastUpdated');

    const now = new Date();

    element.textContent =
        now.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
}

updateLastUpdated();


/*
|--------------------------------------------------------------------------
| Animate Progress Bars
|--------------------------------------------------------------------------
*/

document.querySelectorAll('.ad-progress-bar').forEach(function(bar) {

    const width =
        bar.getAttribute('data-width');

    setTimeout(function() {

        bar.style.width = width;

    }, 200);

});
</script>


<?php include '../includes/footer.php'; ?>