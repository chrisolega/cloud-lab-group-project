<?php
require_once '../includes/db.php';

$base = '../';
$title = 'Admin Login';
$err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    $q = $db->prepare('SELECT * FROM admins WHERE username = ?');
    $q->execute(array($user));
    $a = $q->fetch();

    if ($a && password_verify($pass, $a['password'])) {

        $_SESSION['admin_id'] = $a['id'];
        $_SESSION['admin_name'] = $a['username'];

        header('Location: dashboard.php');
        exit;

    } else {

        $err = 'Wrong username or password.';
    }
}

include '../includes/header.php';
?>

<style>

.login-box {
    max-width: 450px;
    margin: 50px auto;
}

.login-box.dark {
    background: #1f2937;
    color: white;
}

.login-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.theme-btn {
    border: none;
    padding: 8px 10px;
    border-radius: 8px;
    cursor: pointer;
}

.password-box {
    position: relative;
}

.password-box input {
    padding-right: 70px;
    box-sizing: border-box;
    width: 100%;
}

.show-password {
    position: absolute;
    right: 10px;
    top: 9px;
    border: none;
    background: none;
    cursor: pointer;
}

.strength {
    height: 5px;
    background: #ddd;
    border-radius: 5px;
    margin-top: 7px;
}

.strength-bar {
    height: 100%;
    width: 0;
    border-radius: 5px;
    transition: width .3s;
}

</style>


<div class="card narrow login-box" id="loginBox">

    <div class="login-top">

        <div>
            <h1>Admin Sign In</h1>
            <p class="lead">
                For system administrators only.
            </p>
        </div>

        <button
            type="button"
            class="theme-btn"
            onclick="toggleTheme()"
        >
            🌙
        </button>

    </div>


    <?php if ($err) { ?>

        <div class="msg bad">
            <?php echo htmlspecialchars($err); ?>
        </div>

    <?php } ?>


    <form method="post" id="loginForm">

        <label>Username</label>

        <input
            type="text"
            name="username"
            placeholder="Enter username"
            autocomplete="username"
            required
        >


        <label>Password</label>

        <div class="password-box">

            <input
                type="password"
                name="password"
                id="password"
                placeholder="Enter password"
                autocomplete="current-password"
                required
            >

            <button
                type="button"
                class="show-password"
                onclick="togglePassword()"
                id="passwordButton"
            >
                👁️ Show
            </button>

        </div>


        <div class="strength">
            <div
                class="strength-bar"
                id="strengthBar"
            ></div>
        </div>

        <small id="strengthText"></small>


        <br>

        <button
            type="submit"
            class="btn"
            id="loginButton"
        >
            Sign in
        </button>

    </form>

</div>


<script>

/* Show / Hide Password */

function togglePassword() {

    const password =
        document.getElementById('password');

    const button =
        document.getElementById('passwordButton');

    if (password.type === 'password') {

        password.type = 'text';
        button.textContent = '🙈 Hide';

    } else {

        password.type = 'password';
        button.textContent = '👁️ Show';

    }

}


/* Password Strength */

document.getElementById('password')
.addEventListener('input', function () {

    const password = this.value;

    const bar =
        document.getElementById('strengthBar');

    const text =
        document.getElementById('strengthText');

    let strength = 0;

    if (password.length >= 6) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;

    if (password.length === 0) {

        bar.style.width = '0';
        text.textContent = '';

    } else if (strength <= 1) {

        bar.style.width = '25%';
        text.textContent = 'Weak password';

    } else if (strength <= 3) {

        bar.style.width = '65%';
        text.textContent = 'Medium password';

    } else {

        bar.style.width = '100%';
        text.textContent = 'Strong password';

    }

});


/* Dark / Light Mode */

function toggleTheme() {

    document.body.classList.toggle('dark');

    const box =
        document.getElementById('loginBox');

    box.classList.toggle('dark');

    if (document.body.classList.contains('dark')) {

        localStorage.setItem('theme', 'dark');

    } else {

        localStorage.setItem('theme', 'light');

    }

}


/* Remember Theme */

if (localStorage.getItem('theme') === 'dark') {

    document.body.classList.add('dark');

    document.getElementById('loginBox')
        .classList.add('dark');

}


/* Loading State */

document.getElementById('loginForm')
.addEventListener('submit', function () {

    const button =
        document.getElementById('loginButton');

    button.disabled = true;

    button.textContent = 'Signing in...';

});

</script>


<?php include '../includes/footer.php'; ?>
