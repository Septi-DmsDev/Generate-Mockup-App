<?php
session_start();
require "db.php";

// Jika sudah login -> langsung ke user_index.php
if (isset($_SESSION["user"])) {
    header("Location: user_index.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST["username"] ?? '');
    $password = trim($_POST["password"] ?? '');

    // Ambil user dari database
    $q = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' LIMIT 1");

    if ($q && mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);

        // Cek password hash
        if (password_verify($password, $user['password'])) {

            $_SESSION["user"] = $user; 
            header("Location: user_index.php");
            exit;

        } else {
            $error = "Password salah!";
        }

    } else {
        $error = "Username tidak ditemukan!";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login - Overlay App</title>

<style>
body {
    background: #eef1f7;
    font-family: Arial;
}
.login-box {
    width: 350px;
    margin: 80px auto;
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    text-align: center;
}
input {
    width: 93%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
}
button {
    width: 100%;
    padding: 10px;
    background: #2879ff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
button:hover {
    background: #1f63d4;
}
.error {
    color: red;
    margin-bottom: 10px;
}
</style>

</head>
<body>

<div class="login-box">
    <h2>Login</h2>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">

        <input type="text" name="username" placeholder="Username" required>

        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">Masuk</button>

    </form>
</div>

</body>
</html>
