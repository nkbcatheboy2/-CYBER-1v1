<?php
// auth.php
require_once 'config.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $phone = trim($_POST['phone']);
    $pass = $_POST['password'];

    if ($action === 'register') {
        $name = trim($_POST['name']);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, phone, password, wallet_balance) VALUES (?, ?, ?, 100.00)");
            $stmt->execute([$name, $phone, password_hash($pass, PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header("Location: index.php");
            exit;
        } catch (PDOException $e) { $msg = "Phone already exists!"; }
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $u = $stmt->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            $_SESSION['user_id'] = $u['id'];
            header("Location: index.php");
            exit;
        } else { $msg = "Wrong phone or password!"; }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login | 1v1 Game Arena</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    <div class="glass-card" style="width: 380px; text-align: center;">
        <h2 class="brand-font" style="color: var(--gold); margin-bottom: 15px;">⚡ 1v1 ARENA</h2>
        <?php if($msg): ?><p style="color: var(--crimson); font-size: 14px; margin-bottom: 10px;"><?= $msg ?></p><?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" id="action-type" value="login">
            <div id="name-box" style="display: none;">
                <input type="text" name="name" class="input-field" placeholder="Full Name">
            </div>
            <input type="text" name="phone" class="input-field" placeholder="Phone Number" required>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
            <button type="submit" class="btn-gold" style="width: 100%;" id="btn-text">Login</button>
        </form>
        <p style="margin-top: 15px; font-size: 13px; color: var(--text-muted);">
            <a href="javascript:void(0)" onclick="toggle()" style="color: var(--gold);" id="toggle-btn">New here? Sign Up (Get ₹100 Free)</a>
        </p>
    </div>
    <script>
        function toggle() {
            const act = document.getElementById('action-type');
            if (act.value === 'login') {
                act.value = 'register';
                document.getElementById('name-box').style.display = 'block';
                document.getElementById('btn-text').innerText = 'Register & Claim ₹100';
                document.getElementById('toggle-btn').innerText = 'Already registered? Login';
            } else {
                act.value = 'login';
                document.getElementById('name-box').style.display = 'none';
                document.getElementById('btn-text').innerText = 'Login';
                document.getElementById('toggle-btn').innerText = 'New here? Sign Up (Get ₹100 Free)';
            }
        }
    </script>
</body>
</html>