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
    <meta name="description" content="Sign in to N1 Solution IT Inventory Management System">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --cyan:      #00E5FF;
            --cyan-glow: rgba(0,229,255,0.4);
            --dark:      #020B12;
            --glass:     rgba(2,11,18,0.78);
            --gborder:   rgba(0,229,255,0.18);
            --text:      #E0F7FA;
            --muted:     rgba(224,247,250,0.5);
        }

        html, body {
            width: 100%; height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            overflow: hidden;
        }

        /* ── Full-screen background — character visible on left ── */
        .bg {
            position: fixed; inset: 0;
            background: url('assets/robot_bg.png') center center / cover no-repeat;
            z-index: 0;
        }

        /*  Gradient: transparent left → dark right (card area)
            Left half: barely any overlay so the character shines through
            Right half: dark so the glass card is readable             */
        .bg-grad {
            position: fixed; inset: 0; z-index: 1;
            background: linear-gradient(
                100deg,
                rgba(2,11,18,0.10)  0%,
                rgba(2,11,18,0.15) 30%,
                rgba(2,11,18,0.65) 58%,
                rgba(2,11,18,0.93) 70%,
                rgba(2,11,18,0.97) 100%
            );
        }

        /* Subtle scanlines */
        .scanlines {
            position: fixed; inset: 0; z-index: 2; pointer-events: none;
            background: repeating-linear-gradient(
                0deg, transparent, transparent 2px,
                rgba(0,229,255,0.013) 2px, rgba(0,229,255,0.013) 4px
            );
            animation: scan 10s linear infinite;
        }
        @keyframes scan { to { background-position: 0 100px; } }

        /* Particles canvas */
        #cv { position: fixed; inset: 0; z-index: 3; pointer-events: none; }

        /* ── Page wrapper ── */
        .page {
            position: relative; z-index: 10;
            width: 100%; height: 100vh;
            display: flex;
            align-items: center;
            justify-content: flex-end;   /* card on right */
            padding-right: 6vw;
        }

        /* ── Optional left text block (low opacity so char shows) ── */
        .left-block {
            position: absolute;
            left: 5vw; bottom: 10vh;
            z-index: 11;
        }

        .badge {
            display: inline-flex; align-items: center; gap: 8px;
            border: 1px solid rgba(0,229,255,0.35); border-radius: 100px;
            padding: 5px 14px; font-size: 11px; font-weight: 600;
            color: var(--cyan); letter-spacing: .12em; text-transform: uppercase;
            background: rgba(0,229,255,0.06); margin-bottom: 14px;
        }
        .dot {
            width: 6px; height: 6px; border-radius: 50%; background: var(--cyan);
            animation: pdot 1.8s infinite;
        }
        @keyframes pdot {
            0%,100% { box-shadow: 0 0 0 0 var(--cyan-glow); }
            50%      { box-shadow: 0 0 0 6px rgba(0,229,255,0); }
        }

        .left-title {
            font-size: clamp(28px, 3.5vw, 52px); font-weight: 900;
            color: #fff; line-height: 1.05; letter-spacing: -.03em;
            text-shadow: 0 2px 20px rgba(0,0,0,0.8);
        }
        .left-title .accent {
            color: var(--cyan);
            text-shadow: 0 0 30px var(--cyan-glow);
        }

        /* ── Login Card ── */
        .login-card {
            width: 390px;
            background: var(--glass);
            border: 1px solid var(--gborder);
            border-radius: 22px;
            padding: 44px 40px;
            backdrop-filter: blur(32px);
            -webkit-backdrop-filter: blur(32px);
            box-shadow:
                0 0 0 1px rgba(0,229,255,0.06),
                0 8px 60px rgba(0,0,0,0.6),
                0 0 80px rgba(0,229,255,0.05);
            position: relative; overflow: hidden;
            flex-shrink: 0;
        }

        /* Top glow line */
        .login-card::before {
            content: '';
            position: absolute; top: 0; left: 15%; right: 15%; height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            opacity: 0.5;
        }

        /* Top-right corner glow */
        .login-card::after {
            content: '';
            position: absolute; top: -80px; right: -80px;
            width: 220px; height: 220px; border-radius: 50%;
            background: radial-gradient(circle, rgba(0,229,255,0.07) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Logo */
        .card-logo {
            display: flex; align-items: center; gap: 11px; margin-bottom: 30px;
        }
        .logo-icon {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, rgba(0,229,255,0.18), rgba(0,229,255,0.04));
            border: 1px solid rgba(0,229,255,0.28);
            display: flex; align-items: center; justify-content: center;
            color: var(--cyan); font-size: 16px;
        }
        .logo-name { font-size: 16px; font-weight: 700; color: #fff; }
        .logo-sub  { font-size: 10px; color: var(--muted); letter-spacing: .07em; text-transform: uppercase; }

        .card-h2 { font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 5px; letter-spacing: -.02em; }
        .card-p  { font-size: 13px; color: var(--muted); margin-bottom: 28px; }

        /* Error */
        .err-box {
            background: rgba(255,70,70,0.1); border: 1px solid rgba(255,70,70,0.25);
            border-radius: 10px; padding: 11px 14px; color: #FF8080;
            font-size: 12.5px; display: flex; align-items: center; gap: 8px;
            margin-bottom: 18px; animation: shake .35s ease;
        }
        @keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)} }

        /* Fields */
        .field { margin-bottom: 17px; }
        .field-lbl {
            display: block; font-size: 11px; font-weight: 600;
            color: var(--muted); text-transform: uppercase;
            letter-spacing: .08em; margin-bottom: 7px;
        }
        .field-wrap { position: relative; }
        .field-ico {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: rgba(0,229,255,0.35); font-size: 13px; pointer-events: none;
            transition: color .2s;
        }
        .field-input {
            width: 100%;
            background: rgba(0,229,255,0.04);
            border: 1px solid rgba(0,229,255,0.12);
            border-radius: 11px;
            padding: 13px 14px 13px 40px;
            font-size: 14px; font-family: 'Inter', sans-serif;
            color: var(--text); outline: none; caret-color: var(--cyan);
            transition: all .2s;
        }
        .field-input::placeholder { color: rgba(224,247,250,0.2); }
        .field-input:focus {
            background: rgba(0,229,255,0.07);
            border-color: rgba(0,229,255,0.5);
            box-shadow: 0 0 0 3px rgba(0,229,255,0.1);
        }
        .field-wrap:focus-within .field-ico { color: var(--cyan); }

        .eye-btn {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--muted);
            cursor: pointer; font-size: 13px; padding: 4px;
            transition: color .2s;
        }
        .eye-btn:hover { color: var(--cyan); }

        /* Options row */
        .opts-row {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 24px;
        }
        .rem { display: flex; align-items: center; gap: 7px; cursor: pointer; }
        .rem input { accent-color: var(--cyan); width: 14px; height: 14px; }
        .rem span  { font-size: 12.5px; color: var(--muted); user-select: none; }
        .forgot { font-size: 12.5px; color: var(--cyan); text-decoration: none; font-weight: 500; transition: opacity .2s; }
        .forgot:hover { opacity: .7; }

        /* Submit button */
        .btn-sign {
            width: 100%; padding: 14px; border: none; border-radius: 11px;
            background: linear-gradient(135deg, #00E5FF, #00B4D8);
            color: #020B12; font-size: 14px; font-weight: 700;
            font-family: 'Inter', sans-serif; cursor: pointer;
            letter-spacing: .03em; position: relative; overflow: hidden;
            transition: transform .15s, box-shadow .25s;
            box-shadow: 0 4px 24px rgba(0,229,255,0.3);
        }
        .btn-sign::before {
            content: '';
            position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left .45s;
        }
        .btn-sign:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,229,255,0.45); }
        .btn-sign:hover::before { left: 100%; }
        .btn-sign:active { transform: translateY(0); }
        .btn-inner { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 8px; }

        /* Trust badges */
        .trust-row {
            display: flex; align-items: center; justify-content: center;
            gap: 18px; margin-top: 22px;
        }
        .trust-item {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: rgba(224,247,250,0.25);
        }
        .trust-item i { color: rgba(0,229,255,0.4); font-size: 11px; }

        /* ── Submitting overlay ── */
        #sub-overlay {
            position: fixed; inset: 0; z-index: 200;
            background: rgba(2,11,18,0.96);
            display: none; flex-direction: column;
            align-items: center; justify-content: center; gap: 22px;
        }
        #sub-overlay.show { display: flex; }

        .ov-ring {
            width: 120px; height: 120px; border-radius: 50%;
            border: 2px solid rgba(0,229,255,0.3);
            box-shadow: 0 0 40px rgba(0,229,255,0.2), inset 0 0 40px rgba(0,229,255,0.05);
            display: flex; align-items: center; justify-content: center;
            animation: spinRing 3s linear infinite;
        }
        @keyframes spinRing { to { transform: rotate(360deg); } }

        .ov-icon { font-size: 40px; color: var(--cyan); animation: iconPulse 1s ease-in-out infinite; }
        @keyframes iconPulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.15)} }

        .ov-status { font-size: 15px; font-weight: 600; color: var(--cyan); letter-spacing: .05em; }
        .ov-dots::after { content: ''; animation: dots 1.2s steps(4,end) infinite; }
        @keyframes dots { 0%{content:''} 25%{content:'.'} 50%{content:'..'} 75%{content:'...'} }

        .prog-track { width: 260px; height: 3px; background: rgba(0,229,255,0.1); border-radius: 100px; overflow: hidden; }
        .prog-fill  { height: 100%; width: 0; background: linear-gradient(90deg,var(--cyan),rgba(0,229,255,0.4)); border-radius: 100px; box-shadow: 0 0 10px var(--cyan-glow); transition: width .3s ease; }

        /* Responsive */
        @media (max-width: 680px) {
            .left-block { display: none; }
            .page { justify-content: center; padding: 20px; }
            .login-card { width: 100%; max-width: 400px; padding: 36px 28px; }
        }
    </style>
</head>
<body>

<!-- Background — character image visible -->
<div class="bg"></div>
<div class="bg-grad"></div>
<div class="scanlines"></div>
<canvas id="cv"></canvas>

<!-- Submitting overlay -->
<div id="sub-overlay">
    <div class="ov-ring">
        <i class="fas fa-cube ov-icon"></i>
    </div>
    <div class="ov-status">Authenticating<span class="ov-dots"></span></div>
    <div class="prog-track"><div class="prog-fill" id="prog-fill"></div></div>
</div>

<!-- Page -->
<div class="page">

    <!-- Bottom-left brand text -->
    <div class="left-block">
        <div class="badge"><span class="dot"></span>System Online</div>
        <h1 class="left-title">
            IT ASSET<br>
            <span class="accent">CONTROL</span>
        </h1>
    </div>

    <!-- Login card -->
    <div class="login-card">
        <div class="card-logo">
            <div class="logo-icon"><i class="fas fa-cube"></i></div>
            <div>
                <div class="logo-name">N1 Solution</div>
                <div class="logo-sub">Inventory System</div>
            </div>
        </div>

        <h2 class="card-h2">Welcome back</h2>
        <p class="card-p">Sign in to access your dashboard</p>

        <?php if ($error): ?>
            <div class="err-box">
                <i class="fas fa-triangle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="login-form" autocomplete="on">
            <div class="field">
                <label class="field-lbl" for="username">Username</label>
                <div class="field-wrap">
                    <input type="text" name="username" id="username" class="field-input"
                        placeholder="Enter username" required autofocus autocomplete="username">
                    <i class="fas fa-user field-ico"></i>
                </div>
            </div>

            <div class="field">
                <label class="field-lbl" for="password">Password</label>
                <div class="field-wrap">
                    <input type="password" name="password" id="password" class="field-input"
                        placeholder="Enter password" required autocomplete="current-password">
                    <i class="fas fa-lock field-ico"></i>
                    <button type="button" class="eye-btn" id="eye-btn">
                        <i class="fas fa-eye" id="eye-ico"></i>
                    </button>
                </div>
            </div>

            <div class="opts-row">
                <label class="rem">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
                <a href="#" class="forgot">Forgot password?</a>
            </div>

            <button type="submit" class="btn-sign" id="login-btn">
                <span class="btn-inner">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Sign In
                </span>
            </button>
        </form>

        <div class="trust-row">
            <div class="trust-item"><i class="fas fa-lock"></i> SSL Encrypted</div>
            <div class="trust-item"><i class="fas fa-shield-halved"></i> Enterprise</div>
            <div class="trust-item"><i class="fas fa-clock"></i> 24/7 Uptime</div>
        </div>
    </div>
</div>

<script>
/* ── Particles ── */
const cv = document.getElementById('cv');
const cx = cv.getContext('2d');
function rsz(){ cv.width = innerWidth; cv.height = innerHeight; }
rsz();
window.addEventListener('resize', rsz);
const pts = Array.from({length:50}, () => ({
    x: Math.random()*innerWidth, y: Math.random()*innerHeight,
    r: Math.random()*1.3+.3,
    dx:(Math.random()-.5)*.28, dy:(Math.random()-.5)*.28,
    a: Math.random()*.3+.08
}));
(function ap(){
    cx.clearRect(0,0,cv.width,cv.height);
    pts.forEach(p => {
        cx.beginPath(); cx.arc(p.x,p.y,p.r,0,Math.PI*2);
        cx.fillStyle=`rgba(0,229,255,${p.a})`; cx.fill();
        p.x+=p.dx; p.y+=p.dy;
        if(p.x<0||p.x>cv.width)  p.dx*=-1;
        if(p.y<0||p.y>cv.height) p.dy*=-1;
    });
    requestAnimationFrame(ap);
})();

/* ── Submit overlay ── */
document.getElementById('login-form').addEventListener('submit', () => {
    const ov = document.getElementById('sub-overlay');
    ov.classList.add('show');
    const fill = document.getElementById('prog-fill');
    let p = 0;
    const pi = setInterval(() => {
        p += Math.random() * 8 + 2;
        if (p >= 95) { p = 95; clearInterval(pi); }
        fill.style.width = p + '%';
    }, 200);
});

/* ── Password toggle ── */
document.getElementById('eye-btn').addEventListener('click', () => {
    const pw  = document.getElementById('password');
    const ico = document.getElementById('eye-ico');
    if (pw.type === 'password') {
        pw.type = 'text';
        ico.classList.replace('fa-eye','fa-eye-slash');
    } else {
        pw.type = 'password';
        ico.classList.replace('fa-eye-slash','fa-eye');
    }
});
</script>
</body>
</html>
