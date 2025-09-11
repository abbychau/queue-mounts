<?php
// Public login page for the admin panel
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in -> redirect to panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    
    header("Location: /");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded credentials
    $expectedUser = 'abby';
    $expectedPass = 'core-admin-pressure-2223';

    if ($username === $expectedUser && $password === $expectedPass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['username'] = $username;
        header("Location: /");
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.staticfile.org/twitter-bootstrap/4.5.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.staticfile.org/bootswatch/4.5.2/darkly/bootstrap.min.css">
    <style>
        body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .card { min-width: 320px; max-width: 420px; width: 100%; }
    </style>
</head>
<body>
    <div class="card shadow">
        <div class="card-body">
            <h4 class="card-title mb-3">Admin Login</h4>
            <?php if ($error) { ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
