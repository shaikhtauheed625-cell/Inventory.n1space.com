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
    <title>Login — N1 Solution Inventory</title>
    <meta name="description" content="Sign in to N1 Solution IT Inventory Management System — track assets, manage stock, and monitor warranties.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple-deep:  #4C1D95;
            --purple-main:  #6C3BFF;
            --purple-mid:   #8B5CF6;
            --purple-light: #A78BFA;
            --purple-glow:  rgba(108,59,255,0.35);
            --glass-bg:     rgba(255,255,255,0.06);
            --glass-border: rgba(255,255,255,0.12);
            --text-primary: #F1F0FF;
            --text-muted:   rgba(241,240,255,0.55);
            --dark-base:    #0D0B1E;
            --dark-card:    #110F26;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--dark-base);
            overflow: hidden;
        }

        /* ── Particles Canvas ── */
        #particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; }

        /* ── Layout ── */
        .page-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* ══════════════════════════════
           LEFT PANEL — Hero Illustration
        ══════════════════════════════ */
        .hero-panel {
            flex: 1.1;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 48px;
        }

        .hero-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('assets/login_hero.png') center center / cover no-repeat;
            filter: brightness(0.75) saturate(1.2);
            z-index: 0;
        }

        /* Purple gradient overlay on top of image */
        .hero-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                135deg,
                rgba(76, 29, 149, 0.55) 0%,
                rgba(13, 11, 30, 0.45) 50%,
                rgba(13, 11, 30, 0.80) 100%
            );
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(108,59,255,0.25);
            border: 1px solid rgba(139,92,246,0.4);
            backdrop-filter: blur(12px);
            border-radius: 100px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--purple-light);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .hero-badge span.dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #4ADE80;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 0 rgba(74,222,128,0.6); }
            50%       { box-shadow: 0 0 0 5px rgba(74,222,128,0); }
        }

        .hero-title {
            font-size: clamp(28px, 3.5vw, 48px);
            font-weight: 800;
            color: #fff;
            line-height: 1.15;
            margin-bottom: 16px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.5);
        }

        .hero-title .accent {
            background: linear-gradient(90deg, #A78BFA, #6C3BFF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 15px;
            color: rgba(255,255,255,0.65);
            max-width: 440px;
            line-height: 1.7;
            margin-bottom: 36px;
        }

        /* Floating stat chips */
        .stat-chips {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .chip {
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            backdrop-filter: blur(12px);
            border-radius: 12px;
            padding: 10px 16px;
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            animation: float-chip 4s ease-in-out infinite;
        }

        .chip:nth-child(2) { animation-delay: 0.8s; }
        .chip:nth-child(3) { animation-delay: 1.6s; }

        @keyframes float-chip {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-5px); }
        }

        .chip i { color: var(--purple-light); font-size: 14px; }
        .chip strong { color: #fff; }

        /* ══════════════════════════════
           RIGHT PANEL — Login Form
        ══════════════════════════════ */
        .login-panel {
            width: 480px;
            min-width: 420px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 48px;
            background: rgba(13, 11, 30, 0.92);
            backdrop-filter: blur(30px);
            border-left: 1px solid rgba(139,92,246,0.15);
            position: relative;
            overflow: hidden;
        }

        /* Glow blob behind form */
        .login-panel::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(108,59,255,0.18) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-panel::after {
            content: '';
            position: absolute;
            bottom: -100px;
            left: -100px;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(139,92,246,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-box {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        /* Logo */
        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 36px;
        }

        .logo-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--purple-main), var(--purple-mid));
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px var(--purple-glow);
            font-size: 20px;
            color: #fff;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .logo-sub {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .form-heading {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            letter-spacing: -0.02em;
        }

        .form-sub {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        /* Error */
        .error-box {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 10px;
            padding: 12px 16px;
            color: #FCA5A5;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25%      { transform: translateX(-6px); }
            75%      { transform: translateX(6px); }
        }

        /* Form fields */
        .field-group {
            margin-bottom: 20px;
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 8px;
        }

        .field-wrap {
            position: relative;
        }

        .field-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(139,92,246,0.6);
            font-size: 14px;
            pointer-events: none;
            transition: color 0.2s;
        }

        .field-input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 14px 16px 14px 44px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }

        .field-input::placeholder { color: rgba(255,255,255,0.25); }

        .field-input:focus {
            background: rgba(108,59,255,0.08);
            border-color: var(--purple-main);
            box-shadow: 0 0 0 3px rgba(108,59,255,0.15);
        }

        .field-input:focus + .field-icon,
        .field-wrap:focus-within .field-icon {
            color: var(--purple-light);
        }

        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            font-size: 14px;
            background: none;
            border: none;
            padding: 4px;
            transition: color 0.2s;
        }
        .toggle-pass:hover { color: var(--purple-light); }

        /* Options row */
        .options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .remember-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .remember-wrap input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--purple-main);
            cursor: pointer;
        }

        .remember-label {
            font-size: 13px;
            color: var(--text-muted);
            user-select: none;
        }

        .forgot-link {
            font-size: 13px;
            color: var(--purple-light);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: #fff; }

        /* Submit button */
        .btn-login {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--purple-main) 0%, var(--purple-mid) 100%);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            letter-spacing: 0.02em;
            position: relative;
            overflow: hidden;
            transition: transform 0.15s, box-shadow 0.3s;
            box-shadow: 0 4px 24px rgba(108,59,255,0.4);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(108,59,255,0.55); }
        .btn-login:hover::before { left: 100%; }
        .btn-login:active { transform: translateY(0); }

        .btn-login .btn-text { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 8px; }

        /* Footer */
        .login-footer {
            margin-top: 32px;
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }

        .login-footer a { color: var(--purple-light); text-decoration: none; }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }
        .divider span { font-size: 11px; color: var(--text-muted); white-space: nowrap; text-transform: uppercase; letter-spacing: 0.08em; }

        /* Trust badges */
        .trust-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-top: 24px;
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: var(--text-muted);
        }
        .trust-item i { font-size: 12px; color: var(--purple-light); }

        /* Responsive */
        @media (max-width: 900px) {
            .hero-panel { display: none; }
            .login-panel { width: 100%; min-width: unset; border-left: none; padding: 32px 24px; }
        }
    </style>
</head>
<body>

<canvas id="particles"></canvas>

<div class="page-wrapper">

    <!-- ═══════════ LEFT: Hero Panel ═══════════ -->
    <div class="hero-panel">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="dot"></span>
                Live System — All Assets Online
            </div>

            <h1 class="hero-title">
                Smarter IT Asset<br>
                <span class="accent">Management</span>
            </h1>

            <p class="hero-desc">
                Track laptops, servers, peripherals and warranties in real-time.
                One platform for your entire IT inventory — built for enterprise teams.
            </p>

            <div class="stat-chips">
                <div class="chip">
                    <i class="fas fa-laptop"></i>
                    <div><strong>1,240+</strong> Assets Tracked</div>
                </div>
                <div class="chip">
                    <i class="fas fa-shield-halved"></i>
                    <div><strong>99.9%</strong> Uptime</div>
                </div>
                <div class="chip">
                    <i class="fas fa-boxes-stacked"></i>
                    <div><strong>Real-time</strong> Stock</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════ RIGHT: Login Panel ═══════════ -->
    <div class="login-panel">
        <div class="login-box">

            <!-- Logo -->
            <div class="logo-wrap">
                <div class="logo-icon"><i class="fas fa-cube"></i></div>
                <div>
                    <div class="logo-text">N1 Solution</div>
                    <div class="logo-sub">Inventory Management</div>
                </div>
            </div>

            <h2 class="form-heading">Welcome back</h2>
            <p class="form-sub">Sign in to access your dashboard</p>

            <!-- Error -->
            <?php if ($error): ?>
                <div class="error-box" id="error-alert">
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" id="login-form" autocomplete="on">
                <div class="field-group">
                    <label class="field-label" for="username">Username</label>
                    <div class="field-wrap">
                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="field-input"
                            placeholder="Enter your username"
                            required
                            autofocus
                            autocomplete="username"
                        >
                        <i class="fas fa-user field-icon"></i>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label" for="password">Password</label>
                    <div class="field-wrap">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="field-input"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <i class="fas fa-lock field-icon"></i>
                        <button type="button" class="toggle-pass" id="toggle-pass" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="pass-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="options-row">
                    <label class="remember-wrap">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="remember-label">Remember me</span>
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login" id="login-btn">
                    <span class="btn-text">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        Sign In to Dashboard
                    </span>
                </button>
            </form>

            <div class="divider"><span>Secured by N1 Solution</span></div>

            <div class="trust-row">
                <div class="trust-item"><i class="fas fa-lock"></i> SSL Encrypted</div>
                <div class="trust-item"><i class="fas fa-shield-halved"></i> Enterprise Grade</div>
                <div class="trust-item"><i class="fas fa-clock"></i> 24/7 Uptime</div>
            </div>

            <div class="login-footer">
                &copy; <?php echo date('Y'); ?> N1 Solution. All rights reserved.
            </div>

        </div>
    </div>
</div>

<script>
// ── Particle System ──
const canvas = document.getElementById('particles');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

const particles = Array.from({ length: 60 }, () => ({
    x: Math.random() * canvas.width,
    y: Math.random() * canvas.height,
    r: Math.random() * 1.8 + 0.4,
    dx: (Math.random() - 0.5) * 0.4,
    dy: (Math.random() - 0.5) * 0.4,
    alpha: Math.random() * 0.5 + 0.1,
}));

function drawParticles() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    particles.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(139,92,246,${p.alpha})`;
        ctx.fill();
        p.x += p.dx; p.y += p.dy;
        if (p.x < 0 || p.x > canvas.width)  p.dx *= -1;
        if (p.y < 0 || p.y > canvas.height) p.dy *= -1;
    });
    requestAnimationFrame(drawParticles);
}
drawParticles();
window.addEventListener('resize', () => { canvas.width = window.innerWidth; canvas.height = window.innerHeight; });

// ── Password Toggle ──
document.getElementById('toggle-pass').addEventListener('click', () => {
    const pwd = document.getElementById('password');
    const eye = document.getElementById('pass-eye');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        eye.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        eye.classList.replace('fa-eye-slash', 'fa-eye');
    }
});

// ── Login button loading state ──
document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('login-btn');
    btn.innerHTML = '<span class="btn-text"><i class="fas fa-spinner fa-spin"></i> Signing in...</span>';
    btn.disabled = true;
    setTimeout(() => { btn.disabled = false; btn.innerHTML = '<span class="btn-text"><i class="fas fa-arrow-right-to-bracket"></i> Sign In to Dashboard</span>'; }, 4000);
});
</script>
</body>
</html>
