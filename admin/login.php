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

.password-box {
    position: relative;
}

.password-box input {
    padding-right: 46px;
    box-sizing: border-box;
    width: 100%;
}

.show-password {
    position: absolute;
    right: 6px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: none;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
}

.show-password:hover {
    background: var(--surface-alt);
    color: var(--text);
}

.show-password svg {
    width: 18px;
    height: 18px;
}

.show-password .icon-eye-off {
    display: none;
}

.show-password.is-visible .icon-eye {
    display: none;
}

.show-password.is-visible .icon-eye-off {
    display: block;
}

.strength {
    height: 5px;
    background: var(--border);
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
                aria-label="Show password"
            >
                <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
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

    const hidden = password.type === 'password';

    password.type = hidden ? 'text' : 'password';
    button.setAttribute(
        'aria-label',
        hidden ? 'Hide password' : 'Show password'
    );
    button.classList.toggle('is-visible', hidden);

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
