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
            --cyan:       #00E5FF;
            --cyan-dim:   rgba(0,229,255,0.15);
            --cyan-glow:  rgba(0,229,255,0.4);
            --dark:       #020B12;
            --glass-bg:   rgba(2,11,18,0.72);
            --glass-border: rgba(0,229,255,0.18);
            --text:       #E0F7FA;
            --muted:      rgba(224,247,250,0.45);
        }

        html, body {
            height: 100%;
            font-family: 'Inter', sans-serif;
            background: var(--dark);
            overflow: hidden;
        }

        /* ══════════════ FULL BG ══════════════ */
        .bg-scene {
            position: fixed;
            inset: 0;
            background: url('assets/robot_bg.png') center center / cover no-repeat;
            z-index: 0;
        }

        /* Dark overlay — heavier on the left so form is readable */
        .bg-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(
                90deg,
                rgba(2,11,18,0.88) 0%,
                rgba(2,11,18,0.60) 40%,
                rgba(2,11,18,0.10) 100%
            );
            z-index: 1;
        }

        /* Animated scanlines */
        .scanlines {
            position: fixed;
            inset: 0;
            z-index: 2;
            background: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0,229,255,0.015) 2px,
                rgba(0,229,255,0.015) 4px
            );
            pointer-events: none;
            animation: scanMove 8s linear infinite;
        }
        @keyframes scanMove {
            0%   { background-position: 0 0; }
            100% { background-position: 0 100px; }
        }

        /* Canvas particles */
        #particles {
            position: fixed;
            inset: 0;
            z-index: 3;
            pointer-events: none;
        }

        /* ══════════════ PAGE LAYOUT ══════════════ */
        .page {
            position: relative;
            z-index: 10;
            height: 100vh;
            display: flex;
            align-items: center;
            padding: 0 6vw;
        }

        /* ══════════════ LEFT TEXT BLOCK ══════════════ */
        .hero-text {
            flex: 1;
            max-width: 500px;
        }

        .system-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(0,229,255,0.3);
            border-radius: 100px;
            padding: 5px 14px;
            font-size: 11px;
            font-weight: 600;
            color: var(--cyan);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 28px;
            background: rgba(0,229,255,0.06);
        }
        .system-badge .live-dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--cyan);
            animation: pulseDot 1.8s infinite;
        }
        @keyframes pulseDot {
            0%,100% { box-shadow: 0 0 0 0 var(--cyan-glow); }
            50%      { box-shadow: 0 0 0 6px rgba(0,229,255,0); }
        }

        .hero-title {
            font-size: clamp(36px, 5vw, 68px);
            font-weight: 900;
            color: #fff;
            line-height: 1.05;
            letter-spacing: -0.03em;
            margin-bottom: 20px;
        }

        .hero-title .line2 {
            color: var(--cyan);
            display: block;
            text-shadow: 0 0 40px var(--cyan-glow);
        }

        .hero-sub {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.75;
            margin-bottom: 40px;
            max-width: 360px;
        }

        /* Stat row */
        .stat-row {
            display: flex;
            gap: 28px;
        }
        .stat {
            display: flex;
            flex-direction: column;
        }
        .stat-num {
            font-size: 22px;
            font-weight: 800;
            color: var(--cyan);
            letter-spacing: -0.03em;
        }
        .stat-lbl {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 2px;
        }

        /* ══════════════ LOGIN CARD ══════════════ */
        .login-card {
            width: 400px;
            margin-left: auto;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 44px 40px;
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            box-shadow:
                0 0 60px rgba(0,229,255,0.08),
                0 0 120px rgba(0,229,255,0.04),
                inset 0 1px 0 rgba(0,229,255,0.1);
            position: relative;
            overflow: hidden;
        }

        /* Top glow strip */
        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 20%; right: 20%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--cyan), transparent);
            opacity: 0.6;
        }

        /* Corner glow */
        .login-card::after {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,229,255,0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .card-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 32px;
        }
        .card-logo-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(0,229,255,0.2), rgba(0,229,255,0.05));
            border: 1px solid rgba(0,229,255,0.3);
            display: flex; align-items: center; justify-content: center;
            color: var(--cyan);
            font-size: 16px;
        }
        .card-logo-name { font-size: 16px; font-weight: 700; color: #fff; }
        .card-logo-sub  { font-size: 10px; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase; }

        .card-title { font-size: 22px; font-weight: 700; color: #fff; margin-bottom: 5px; letter-spacing: -0.02em; }
        .card-sub   { font-size: 13px; color: var(--muted); margin-bottom: 28px; }

        /* Error */
        .err-box {
            background: rgba(255,80,80,0.1);
            border: 1px solid rgba(255,80,80,0.25);
            border-radius: 10px;
            padding: 11px 14px;
            color: #FF8080;
            font-size: 12.5px;
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 18px;
            animation: shake 0.35s ease;
        }
        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25%      { transform: translateX(-5px); }
            75%      { transform: translateX(5px); }
        }

        /* Fields */
        .field { margin-bottom: 18px; }
        .field-lbl {
            display: block;
            font-size: 11px; font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 7px;
        }
        .field-wrap { position: relative; }
        .field-ico {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            color: rgba(0,229,255,0.4);
            font-size: 13px; pointer-events: none;
            transition: color 0.2s;
        }
        .field-input {
            width: 100%;
            background: rgba(0,229,255,0.04);
            border: 1px solid rgba(0,229,255,0.12);
            border-radius: 10px;
            padding: 13px 14px 13px 40px;
            font-size: 14px; font-family: 'Inter', sans-serif;
            color: var(--text);
            outline: none;
            transition: all 0.2s;
            caret-color: var(--cyan);
        }
        .field-input::placeholder { color: rgba(224,247,250,0.2); }
        .field-input:focus {
            background: rgba(0,229,255,0.07);
            border-color: rgba(0,229,255,0.5);
            box-shadow: 0 0 0 3px rgba(0,229,255,0.1);
        }
        .field-wrap:focus-within .field-ico { color: var(--cyan); }

        .eye-btn {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: var(--muted); cursor: pointer; font-size: 13px;
            transition: color 0.2s;
        }
        .eye-btn:hover { color: var(--cyan); }

        /* Options */
        .opts-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .rem-wrap {
            display: flex; align-items: center; gap: 7px; cursor: pointer;
        }
        .rem-wrap input { accent-color: var(--cyan); width: 14px; height: 14px; cursor: pointer; }
        .rem-lbl { font-size: 12.5px; color: var(--muted); user-select: none; }
        .forgot { font-size: 12.5px; color: var(--cyan); text-decoration: none; font-weight: 500; transition: opacity 0.2s; }
        .forgot:hover { opacity: 0.7; }

        /* Submit */
        .btn-submit {
            width: 100%;
            padding: 14px;
            border: none; border-radius: 10px;
            background: linear-gradient(135deg, rgba(0,229,255,0.9), rgba(0,180,220,0.9));
            color: #020B12;
            font-size: 14px; font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            letter-spacing: 0.03em;
            position: relative; overflow: hidden;
            transition: transform 0.15s, box-shadow 0.25s;
            box-shadow: 0 4px 24px rgba(0,229,255,0.3);
        }
        .btn-submit::before {
            content: '';
            position: absolute; top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.45s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,229,255,0.45); }
        .btn-submit:hover::before { left: 100%; }
        .btn-submit:active { transform: translateY(0); }
        .btn-inner { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: 8px; }

        /* Footer */
        .card-footer-txt {
            margin-top: 24px; text-align: center;
            font-size: 11px; color: rgba(224,247,250,0.25);
        }

        /* ══════════════ ROBOT TYPING OVERLAY ══════════════ */
        #robot-overlay {
            position: fixed; inset: 0; z-index: 100;
            background: rgba(2,11,18,0.96);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 32px;
        }
        #robot-overlay.active { display: flex; }

        .robot-scene {
            position: relative;
            width: 340px; height: 260px;
        }

        /* Robot SVG body */
        .robot-svg {
            width: 140px;
            position: absolute;
            bottom: 60px; left: 50%;
            transform: translateX(-50%);
            filter: drop-shadow(0 0 20px var(--cyan-glow));
            animation: robotBob 1.2s ease-in-out infinite;
        }
        @keyframes robotBob {
            0%,100% { transform: translateX(-50%) translateY(0); }
            50%      { transform: translateX(-50%) translateY(-6px); }
        }

        /* Arms animate while typing */
        .robot-arm-l, .robot-arm-r {
            transform-origin: top center;
        }
        .robot-arm-l { animation: typeArmL 0.18s ease-in-out infinite alternate; }
        .robot-arm-r { animation: typeArmR 0.18s ease-in-out infinite alternate; }
        @keyframes typeArmL {
            0%   { transform: rotate(-8deg); }
            100% { transform: rotate(8deg); }
        }
        @keyframes typeArmR {
            0%   { transform: rotate(8deg); }
            100% { transform: rotate(-8deg); }
        }

        /* Eyes blink */
        .robot-eye { animation: blinkEye 3s infinite; }
        @keyframes blinkEye {
            0%,92%,100% { ry: 6; }
            94%,98%     { ry: 1; }
        }

        /* Keyboard */
        .keyboard {
            position: absolute;
            bottom: 0; left: 50%;
            transform: translateX(-50%);
            width: 220px; height: 55px;
            background: rgba(0,229,255,0.07);
            border: 1px solid rgba(0,229,255,0.2);
            border-radius: 10px;
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 3px;
            padding: 6px;
            backdrop-filter: blur(10px);
        }
        .key {
            background: rgba(0,229,255,0.08);
            border: 1px solid rgba(0,229,255,0.15);
            border-radius: 3px;
            transition: background 0.05s, box-shadow 0.05s;
        }
        .key.active {
            background: rgba(0,229,255,0.4);
            box-shadow: 0 0 8px rgba(0,229,255,0.6);
        }

        /* Glow ring behind robot */
        .glow-ring {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 220px; height: 220px;
            border-radius: 50%;
            border: 2px solid rgba(0,229,255,0.2);
            box-shadow: 0 0 40px rgba(0,229,255,0.1), inset 0 0 40px rgba(0,229,255,0.05);
            animation: ringPulse 2s ease-in-out infinite;
        }
        @keyframes ringPulse {
            0%,100% { transform: translate(-50%,-50%) scale(1);   opacity: 0.6; }
            50%      { transform: translate(-50%,-50%) scale(1.05); opacity: 1; }
        }

        /* Status text */
        .login-status {
            font-size: 15px; font-weight: 600;
            color: var(--cyan);
            letter-spacing: 0.05em;
            text-align: center;
        }
        .login-status .dots::after {
            content: '';
            animation: loadDots 1.5s steps(4, end) infinite;
        }
        @keyframes loadDots {
            0%   { content: ''; }
            25%  { content: '.'; }
            50%  { content: '..'; }
            75%  { content: '...'; }
            100% { content: ''; }
        }

        /* Progress bar */
        .prog-track {
            width: 280px; height: 3px;
            background: rgba(0,229,255,0.1);
            border-radius: 100px;
            overflow: hidden;
        }
        .prog-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--cyan), rgba(0,229,255,0.4));
            border-radius: 100px;
            width: 0%;
            transition: width 0.3s ease;
            box-shadow: 0 0 10px var(--cyan-glow);
        }

        /* Responsive */
        @media (max-width: 820px) {
            .hero-text { display: none; }
            .login-card { margin: 0 auto; }
            .page { justify-content: center; padding: 20px; }
        }
    </style>
</head>
<body>

<!-- Background -->
<div class="bg-scene"></div>
<div class="bg-overlay"></div>
<div class="scanlines"></div>
<canvas id="particles"></canvas>

<!-- ══════════ ROBOT TYPING OVERLAY ══════════ -->
<div id="robot-overlay">
    <div class="robot-scene">
        <div class="glow-ring"></div>

        <!-- Robot SVG -->
        <svg class="robot-svg" viewBox="0 0 100 140" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Head -->
            <rect x="25" y="8" width="50" height="40" rx="8" fill="#0A2535" stroke="#00E5FF" stroke-width="1.5"/>
            <!-- Visor -->
            <rect x="30" y="14" width="40" height="22" rx="5" fill="rgba(0,229,255,0.1)" stroke="rgba(0,229,255,0.4)" stroke-width="1"/>
            <!-- Eyes -->
            <ellipse class="robot-eye" cx="40" cy="25" rx="5" ry="6" fill="#00E5FF" opacity="0.9"/>
            <ellipse class="robot-eye" cx="60" cy="25" rx="5" ry="6" fill="#00E5FF" opacity="0.9"/>
            <!-- Antenna -->
            <line x1="50" y1="8" x2="50" y2="0" stroke="#00E5FF" stroke-width="1.5"/>
            <circle cx="50" cy="0" r="2.5" fill="#00E5FF" opacity="0.8"/>
            <!-- Neck -->
            <rect x="42" y="48" width="16" height="8" rx="3" fill="#0A2535" stroke="#00E5FF" stroke-width="1"/>
            <!-- Body -->
            <rect x="18" y="56" width="64" height="52" rx="10" fill="#071C2C" stroke="#00E5FF" stroke-width="1.5"/>
            <!-- Chest panel -->
            <rect x="30" y="64" width="40" height="26" rx="5" fill="rgba(0,229,255,0.06)" stroke="rgba(0,229,255,0.2)" stroke-width="1"/>
            <!-- Chest LEDs -->
            <circle cx="38" cy="75" r="3" fill="#00E5FF" opacity="0.8"/>
            <circle cx="50" cy="75" r="3" fill="#00E5FF" opacity="0.4"/>
            <circle cx="62" cy="75" r="3" fill="#00E5FF" opacity="0.6"/>
            <!-- Left arm -->
            <g class="robot-arm-l">
                <rect x="2" y="58" width="14" height="36" rx="6" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
                <!-- Left hand -->
                <rect x="2" y="92" width="14" height="10" rx="4" fill="#071C2C" stroke="#00E5FF" stroke-width="1"/>
            </g>
            <!-- Right arm -->
            <g class="robot-arm-r">
                <rect x="84" y="58" width="14" height="36" rx="6" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
                <!-- Right hand -->
                <rect x="84" y="92" width="14" height="10" rx="4" fill="#071C2C" stroke="#00E5FF" stroke-width="1"/>
            </g>
            <!-- Legs -->
            <rect x="28" y="108" width="18" height="28" rx="7" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
            <rect x="54" y="108" width="18" height="28" rx="7" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
        </svg>

        <!-- Keyboard -->
        <div class="keyboard" id="keyboard">
            <!-- Keys generated by JS -->
        </div>
    </div>

    <div class="login-status">Authenticating<span class="dots"></span></div>
    <div class="prog-track"><div class="prog-fill" id="prog-fill"></div></div>
</div>

<!-- ══════════ MAIN PAGE ══════════ -->
<div class="page">

    <!-- Left hero text -->
    <div class="hero-text">
        <div class="system-badge">
            <span class="live-dot"></span>
            System Online
        </div>

        <h1 class="hero-title">
            IT ASSET
            <span class="line2">CONTROL</span>
        </h1>

        <p class="hero-sub">
            Enterprise-grade inventory management.<br>
            Track every device, warranty and stock level<br>
            in real-time across your entire organisation.
        </p>

        <div class="stat-row">
            <div class="stat">
                <span class="stat-num">100%</span>
                <span class="stat-lbl">Asset Visibility</span>
            </div>
            <div class="stat">
                <span class="stat-num">Real-time</span>
                <span class="stat-lbl">Stock Updates</span>
            </div>
            <div class="stat">
                <span class="stat-num">24 / 7</span>
                <span class="stat-lbl">Uptime</span>
            </div>
        </div>
    </div>

    <!-- Login card -->
    <div class="login-card" id="login-card">
        <div class="card-logo">
            <div class="card-logo-icon"><i class="fas fa-cube"></i></div>
            <div>
                <div class="card-logo-name">N1 Solution</div>
                <div class="card-logo-sub">Inventory System</div>
            </div>
        </div>

        <h2 class="card-title">Welcome back</h2>
        <p class="card-sub">Sign in to access your dashboard</p>

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
                <label class="rem-wrap">
                    <input type="checkbox" name="remember" id="remember">
                    <span class="rem-lbl">Remember me</span>
                </label>
                <a href="#" class="forgot">Forgot password?</a>
            </div>

            <button type="submit" class="btn-submit" id="login-btn">
                <span class="btn-inner" id="btn-inner">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Sign In
                </span>
            </button>
        </form>

        <p class="card-footer-txt">&copy; <?php echo date('Y'); ?> N1 Solution. All rights reserved.</p>
    </div>
</div>

<script>
/* ── Particles ── */
const canvas = document.getElementById('particles');
const ctx = canvas.getContext('2d');
function resizeCanvas() { canvas.width = innerWidth; canvas.height = innerHeight; }
resizeCanvas();
window.addEventListener('resize', resizeCanvas);

const pts = Array.from({length:50}, () => ({
    x: Math.random()*innerWidth, y: Math.random()*innerHeight,
    r: Math.random()*1.5+0.3,
    dx:(Math.random()-.5)*.35, dy:(Math.random()-.5)*.35,
    a: Math.random()*.4+.1
}));

(function animPts(){
    ctx.clearRect(0,0,canvas.width,canvas.height);
    pts.forEach(p=>{
        ctx.beginPath();
        ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
        ctx.fillStyle=`rgba(0,229,255,${p.a})`;
        ctx.fill();
        p.x+=p.dx; p.y+=p.dy;
        if(p.x<0||p.x>canvas.width)  p.dx*=-1;
        if(p.y<0||p.y>canvas.height) p.dy*=-1;
    });
    requestAnimationFrame(animPts);
})();

/* ── Build keyboard keys ── */
const kb = document.getElementById('keyboard');
for(let i=0;i<30;i++){
    const k = document.createElement('div');
    k.className='key';
    k.id='k'+i;
    kb.appendChild(k);
}

/* ── Random key flicker ── */
let keyInterval;
function startTyping(){
    keyInterval = setInterval(()=>{
        // reset all
        document.querySelectorAll('.key').forEach(k=>k.classList.remove('active'));
        // light up 1-3 random keys
        const count = Math.floor(Math.random()*3)+1;
        for(let i=0;i<count;i++){
            const idx = Math.floor(Math.random()*30);
            const k = document.getElementById('k'+idx);
            if(k) k.classList.add('active');
        }
    }, 120);
}
function stopTyping(){
    clearInterval(keyInterval);
    document.querySelectorAll('.key').forEach(k=>k.classList.remove('active'));
}

/* ── Login animation ── */
document.getElementById('login-form').addEventListener('submit', function(e){
    // Show overlay
    const overlay = document.getElementById('robot-overlay');
    overlay.classList.add('active');
    startTyping();

    // Progress bar
    const fill = document.getElementById('prog-fill');
    let prog = 0;
    const progInt = setInterval(()=>{
        prog += Math.random()*8 + 2;
        if(prog>=95){ prog=95; clearInterval(progInt); }
        fill.style.width = prog+'%';
    }, 180);

    // Let form submit naturally after brief animation
    // We don't preventDefault — just show the overlay then submit
    // The PHP redirect will happen after server response
});

/* ── Password toggle ── */
document.getElementById('eye-btn').addEventListener('click',()=>{
    const p=document.getElementById('password');
    const ico=document.getElementById('eye-ico');
    if(p.type==='password'){ p.type='text'; ico.classList.replace('fa-eye','fa-eye-slash'); }
    else { p.type='password'; ico.classList.replace('fa-eye-slash','fa-eye'); }
});
</script>
</body>
</html>
