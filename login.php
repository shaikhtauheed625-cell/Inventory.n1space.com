<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - N1 Solution Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: radial-gradient(circle at center, #1e293b 0%, #0b1120 100%);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }
        .login-card .card {
            background: rgba(17, 24, 39, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <div class="login-card animate-fade-in">
        <div class="card p-5">
            <div class="text-center mb-5">
                <div class="bg-primary bg-opacity-10 d-inline-block p-3 rounded-4 mb-3" style="box-shadow: 0 0 20px rgba(0,210,255,0.2)">
                    <i class="fas fa-cube text-primary fa-2x"></i>
                </div>
                <h1 class="navbar-brand d-block mb-1" style="font-size: 2.2rem;">N1 SOLUTION</h1>
                <p class="text-muted small fw-bold text-uppercase tracking-wider">Premium Inventory System</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-25 text-danger small"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus placeholder="Enter your username">
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="Enter your password">
                </div>
                <div class="d-grid mb-4">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-lg">Login to Dashboard</button>
                </div>
            </form>
            


        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
