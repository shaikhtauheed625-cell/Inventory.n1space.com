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
            --glass:     rgba(2,11,18,0.75);
            --gborder:   rgba(0,229,255,0.18);
            --text:      #E0F7FA;
            --muted:     rgba(224,247,250,0.45);
        }

        html, body { height: 100%; font-family: 'Inter', sans-serif; background: var(--dark); overflow: hidden; }

        /* BG */
        .bg-scene   { position:fixed; inset:0; background:url('assets/robot_bg.png') center/cover no-repeat; z-index:0; }
        .bg-overlay { position:fixed; inset:0; background:linear-gradient(90deg,rgba(2,11,18,.92) 0%,rgba(2,11,18,.65) 45%,rgba(2,11,18,.15) 100%); z-index:1; }
        .scanlines  {
            position:fixed; inset:0; z-index:2; pointer-events:none;
            background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,229,255,.015) 2px,rgba(0,229,255,.015) 4px);
            animation:scan 8s linear infinite;
        }
        @keyframes scan { 0%{background-position:0 0} 100%{background-position:0 100px} }
        #cv { position:fixed; inset:0; z-index:3; pointer-events:none; }

        /* ── Page grid ── */
        .page {
            position:relative; z-index:10;
            height:100vh; display:flex; align-items:center;
            padding:0 5vw; gap:40px;
        }

        /* ── LEFT: robot + text ── */
        .left-col {
            flex:1; display:flex; flex-direction:column;
            align-items:flex-start; gap:28px;
        }

        /* Stat badge */
        .badge {
            display:inline-flex; align-items:center; gap:8px;
            border:1px solid rgba(0,229,255,.3); border-radius:100px;
            padding:5px 14px; font-size:11px; font-weight:600;
            color:var(--cyan); letter-spacing:.12em; text-transform:uppercase;
            background:rgba(0,229,255,.06);
        }
        .dot { width:6px; height:6px; border-radius:50%; background:var(--cyan); animation:pdot 1.8s infinite; }
        @keyframes pdot { 0%,100%{box-shadow:0 0 0 0 var(--cyan-glow)} 50%{box-shadow:0 0 0 6px rgba(0,229,255,0)} }

        .hero-title {
            font-size:clamp(32px,4.5vw,62px); font-weight:900;
            color:#fff; line-height:1.05; letter-spacing:-.03em;
        }
        .hero-title .accent { color:var(--cyan); display:block; text-shadow:0 0 40px var(--cyan-glow); }

        /* ── ROBOT SECTION ── */
        .robot-wrap {
            position:relative; width:260px; height:300px;
            display:flex; align-items:flex-end; justify-content:center;
        }

        /* Glow ring always visible */
        .robot-ring {
            position:absolute; top:50%; left:50%;
            transform:translate(-50%,-52%);
            width:210px; height:210px; border-radius:50%;
            border:2px solid rgba(0,229,255,.25);
            box-shadow:0 0 50px rgba(0,229,255,.12), inset 0 0 50px rgba(0,229,255,.06);
            animation:ringPulse 3s ease-in-out infinite;
        }
        @keyframes ringPulse {
            0%,100%{transform:translate(-50%,-52%) scale(1); opacity:.6}
            50%    {transform:translate(-50%,-52%) scale(1.04); opacity:1}
        }

        /* Robot SVG – always on screen */
        #robot-svg {
            position:relative; z-index:2; width:130px;
            filter:drop-shadow(0 0 18px rgba(0,229,255,.5));
            animation:bobIdle 3s ease-in-out infinite;
            transition:filter .3s;
        }
        @keyframes bobIdle {
            0%,100%{transform:translateY(0)}
            50%    {transform:translateY(-8px)}
        }

        /* Typing state — faster bob + brighter */
        #robot-svg.typing {
            animation:bobType .5s ease-in-out infinite;
            filter:drop-shadow(0 0 28px rgba(0,229,255,.85));
        }
        @keyframes bobType {
            0%,100%{transform:translateY(0)}
            50%    {transform:translateY(-4px)}
        }

        /* Arms – idle vs typing */
        .arm-l { transform-origin:20px 0; animation:armIdleL 3s ease-in-out infinite; }
        .arm-r { transform-origin:0px 0;  animation:armIdleR 3s ease-in-out infinite; }

        @keyframes armIdleL { 0%,100%{transform:rotate(-4deg)} 50%{transform:rotate(4deg)} }
        @keyframes armIdleR { 0%,100%{transform:rotate(4deg)}  50%{transform:rotate(-4deg)} }

        /* Fast typing arm animation */
        #robot-svg.typing .arm-l { animation:armTypeL .18s ease-in-out infinite alternate; }
        #robot-svg.typing .arm-r { animation:armTypeR .18s ease-in-out infinite alternate; }
        @keyframes armTypeL { 0%{transform:rotate(-18deg) translateY(0)} 100%{transform:rotate(12deg) translateY(4px)} }
        @keyframes armTypeR { 0%{transform:rotate(18deg) translateY(0)}  100%{transform:rotate(-12deg) translateY(4px)} }

        /* Blink */
        .eye { animation:blink 4s infinite; }
        @keyframes blink { 0%,90%,100%{ry:5} 93%,97%{ry:.8} }

        /* Eye glow on typing */
        #robot-svg.typing .eye { fill:#00FFFF; filter:url(#eyeGlow); }

        /* Keyboard below robot */
        .robot-kb {
            position:absolute; bottom:0; left:50%;
            transform:translateX(-50%);
            width:200px; height:50px;
            background:rgba(0,229,255,.05);
            border:1px solid rgba(0,229,255,.15);
            border-radius:10px;
            display:grid;
            grid-template-columns:repeat(10,1fr);
            grid-template-rows:repeat(3,1fr);
            gap:3px; padding:5px;
            backdrop-filter:blur(8px);
        }
        .key {
            background:rgba(0,229,255,.06);
            border:1px solid rgba(0,229,255,.12);
            border-radius:3px;
            transition:background .06s, box-shadow .06s;
        }
        .key.lit {
            background:rgba(0,229,255,.45);
            box-shadow:0 0 8px rgba(0,229,255,.7);
        }

        /* Stat chips */
        .stat-row { display:flex; gap:24px; }
        .stat-num { font-size:20px; font-weight:800; color:var(--cyan); letter-spacing:-.02em; }
        .stat-lbl { font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-top:1px; }

        /* ── RIGHT: Login card ── */
        .login-card {
            width:390px; min-width:340px;
            background:var(--glass);
            border:1px solid var(--gborder);
            border-radius:20px;
            padding:42px 38px;
            backdrop-filter:blur(28px);
            box-shadow:0 0 60px rgba(0,229,255,.07), inset 0 1px 0 rgba(0,229,255,.1);
            position:relative; overflow:hidden;
        }
        .login-card::before {
            content:''; position:absolute;
            top:0; left:20%; right:20%; height:1px;
            background:linear-gradient(90deg,transparent,var(--cyan),transparent);
            opacity:.5;
        }
        .card-logo { display:flex; align-items:center; gap:10px; margin-bottom:28px; }
        .logo-icon {
            width:36px; height:36px; border-radius:9px;
            background:linear-gradient(135deg,rgba(0,229,255,.2),rgba(0,229,255,.05));
            border:1px solid rgba(0,229,255,.3);
            display:flex; align-items:center; justify-content:center;
            color:var(--cyan); font-size:15px;
        }
        .logo-name { font-size:15px; font-weight:700; color:#fff; }
        .logo-sub  { font-size:10px; color:var(--muted); letter-spacing:.07em; text-transform:uppercase; }

        .card-h2 { font-size:21px; font-weight:700; color:#fff; margin-bottom:4px; letter-spacing:-.02em; }
        .card-p  { font-size:13px; color:var(--muted); margin-bottom:26px; }

        .err-box {
            background:rgba(255,80,80,.1); border:1px solid rgba(255,80,80,.25);
            border-radius:10px; padding:10px 14px; color:#FF8080;
            font-size:12.5px; display:flex; align-items:center; gap:8px;
            margin-bottom:16px; animation:shake .35s ease;
        }
        @keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-5px)} 75%{transform:translateX(5px)} }

        .field { margin-bottom:16px; }
        .field-lbl { display:block; font-size:11px; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; margin-bottom:6px; }
        .field-wrap { position:relative; }
        .field-ico { position:absolute; left:13px; top:50%; transform:translateY(-50%); color:rgba(0,229,255,.35); font-size:13px; pointer-events:none; transition:color .2s; }
        .field-input {
            width:100%; background:rgba(0,229,255,.04);
            border:1px solid rgba(0,229,255,.12); border-radius:10px;
            padding:13px 13px 13px 38px; font-size:13.5px;
            font-family:'Inter',sans-serif; color:var(--text);
            outline:none; caret-color:var(--cyan); transition:all .2s;
        }
        .field-input::placeholder { color:rgba(224,247,250,.2); }
        .field-input:focus { background:rgba(0,229,255,.07); border-color:rgba(0,229,255,.5); box-shadow:0 0 0 3px rgba(0,229,255,.1); }
        .field-wrap:focus-within .field-ico { color:var(--cyan); }

        .eye-btn { position:absolute; right:11px; top:50%; transform:translateY(-50%); background:none; border:none; color:var(--muted); cursor:pointer; font-size:13px; transition:color .2s; }
        .eye-btn:hover { color:var(--cyan); }

        .opts-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; }
        .rem { display:flex; align-items:center; gap:7px; cursor:pointer; }
        .rem input { accent-color:var(--cyan); width:13px; height:13px; }
        .rem span  { font-size:12px; color:var(--muted); user-select:none; }
        .forgot { font-size:12px; color:var(--cyan); text-decoration:none; font-weight:500; transition:opacity .2s; }
        .forgot:hover { opacity:.7; }

        .btn-sign {
            width:100%; padding:14px; border:none; border-radius:10px;
            background:linear-gradient(135deg,rgba(0,229,255,.9),rgba(0,180,220,.9));
            color:#020B12; font-size:14px; font-weight:700;
            font-family:'Inter',sans-serif; cursor:pointer; letter-spacing:.03em;
            position:relative; overflow:hidden;
            transition:transform .15s, box-shadow .25s;
            box-shadow:0 4px 24px rgba(0,229,255,.3);
        }
        .btn-sign::before { content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent); transition:left .45s; }
        .btn-sign:hover { transform:translateY(-2px); box-shadow:0 8px 32px rgba(0,229,255,.45); }
        .btn-sign:hover::before { left:100%; }
        .btn-sign:active { transform:translateY(0); }
        .btn-inner { position:relative; z-index:1; display:flex; align-items:center; justify-content:center; gap:8px; }

        .card-ft { margin-top:20px; text-align:center; font-size:11px; color:rgba(224,247,250,.2); }

        /* ── Submitting overlay (full screen) ── */
        #sub-overlay {
            position:fixed; inset:0; z-index:200;
            background:rgba(2,11,18,.97);
            display:none; flex-direction:column;
            align-items:center; justify-content:center; gap:24px;
        }
        #sub-overlay.show { display:flex; }

        .ov-robot { width:160px; filter:drop-shadow(0 0 30px rgba(0,229,255,.8)); animation:bobType .4s ease-in-out infinite; }
        .ov-ring {
            position:absolute; width:240px; height:240px; border-radius:50%;
            border:2px solid rgba(0,229,255,.3);
            box-shadow:0 0 60px rgba(0,229,255,.15);
            animation:ringPulse 1.5s ease-in-out infinite;
        }
        .ov-status { font-size:15px; font-weight:600; color:var(--cyan); letter-spacing:.05em; }
        .ov-dots::after { content:''; animation:dots 1.2s steps(4,end) infinite; }
        @keyframes dots { 0%{content:''} 25%{content:'.'} 50%{content:'..'} 75%{content:'...'} }
        .prog-track { width:260px; height:3px; background:rgba(0,229,255,.1); border-radius:100px; overflow:hidden; }
        .prog-fill  { height:100%; width:0; background:linear-gradient(90deg,var(--cyan),rgba(0,229,255,.4)); border-radius:100px; transition:width .3s ease; box-shadow:0 0 10px var(--cyan-glow); }

        @media(max-width:820px){ .left-col{display:none} .page{justify-content:center;padding:20px} }
    </style>
</head>
<body>

<div class="bg-scene"></div>
<div class="bg-overlay"></div>
<div class="scanlines"></div>
<canvas id="cv"></canvas>

<!-- ── Submitting overlay ── -->
<div id="sub-overlay">
    <div style="position:relative;display:flex;align-items:center;justify-content:center;width:260px;height:260px;">
        <div class="ov-ring"></div>
        <svg class="ov-robot" viewBox="0 0 100 140" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs><filter id="eyeGlowOv"><feGaussianBlur stdDeviation="2" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>
            <rect x="25" y="8" width="50" height="40" rx="8" fill="#0A2535" stroke="#00E5FF" stroke-width="1.5"/>
            <rect x="30" y="14" width="40" height="22" rx="5" fill="rgba(0,229,255,0.1)" stroke="rgba(0,229,255,0.4)" stroke-width="1"/>
            <ellipse class="eye" cx="40" cy="25" rx="5" ry="5" fill="#00FFFF" filter="url(#eyeGlowOv)"/>
            <ellipse class="eye" cx="60" cy="25" rx="5" ry="5" fill="#00FFFF" filter="url(#eyeGlowOv)"/>
            <line x1="50" y1="8" x2="50" y2="1" stroke="#00E5FF" stroke-width="1.5"/><circle cx="50" cy="0" r="2.5" fill="#00E5FF"/>
            <rect x="42" y="48" width="16" height="8" rx="3" fill="#0A2535" stroke="#00E5FF" stroke-width="1"/>
            <rect x="18" y="56" width="64" height="52" rx="10" fill="#071C2C" stroke="#00E5FF" stroke-width="1.5"/>
            <rect x="30" y="64" width="40" height="26" rx="5" fill="rgba(0,229,255,0.06)" stroke="rgba(0,229,255,0.2)" stroke-width="1"/>
            <circle cx="38" cy="75" r="3" fill="#00E5FF" opacity="0.9"/><circle cx="50" cy="75" r="3" fill="#00E5FF" opacity="0.5"/><circle cx="62" cy="75" r="3" fill="#00E5FF" opacity="0.7"/>
            <g class="arm-l"><rect x="2" y="58" width="14" height="36" rx="6" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/><rect x="2" y="92" width="14" height="10" rx="4" fill="#071C2C" stroke="#00E5FF" stroke-width="1"/></g>
            <g class="arm-r"><rect x="84" y="58" width="14" height="36" rx="6" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/><rect x="84" y="92" width="14" height="10" rx="4" fill="#071C2C" stroke="#00E5FF" stroke-width="1"/></g>
            <rect x="28" y="108" width="18" height="28" rx="7" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
            <rect x="54" y="108" width="18" height="28" rx="7" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
        </svg>
    </div>
    <div class="ov-status">Authenticating<span class="ov-dots"></span></div>
    <div class="prog-track"><div class="prog-fill" id="prog-fill"></div></div>
</div>

<!-- ── Main page ── -->
<div class="page">

    <!-- LEFT -->
    <div class="left-col">
        <div class="badge"><span class="dot"></span>System Online</div>

        <h1 class="hero-title">IT ASSET<span class="accent">CONTROL</span></h1>

        <!-- ROBOT (always visible, animates on typing) -->
        <div class="robot-wrap">
            <div class="robot-ring"></div>

            <svg id="robot-svg" viewBox="0 0 100 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <filter id="eyeGlow"><feGaussianBlur stdDeviation="2.5" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter>
                </defs>
                <!-- Head -->
                <rect x="25" y="8" width="50" height="40" rx="8" fill="#0A2535" stroke="#00E5FF" stroke-width="1.5"/>
                <rect x="30" y="14" width="40" height="22" rx="5" fill="rgba(0,229,255,0.08)" stroke="rgba(0,229,255,0.35)" stroke-width="1"/>
                <!-- Eyes -->
                <ellipse class="eye" id="eye-l" cx="40" cy="25" rx="5" ry="5" fill="#00E5FF"/>
                <ellipse class="eye" id="eye-r" cx="60" cy="25" rx="5" ry="5" fill="#00E5FF"/>
                <!-- Antenna -->
                <line x1="50" y1="8" x2="50" y2="1" stroke="#00E5FF" stroke-width="1.5"/>
                <circle cx="50" cy="0" r="2.5" fill="#00E5FF" id="ant-dot"/>
                <!-- Neck -->
                <rect x="42" y="48" width="16" height="8" rx="3" fill="#0A2535" stroke="#00E5FF" stroke-width="1"/>
                <!-- Body -->
                <rect x="18" y="56" width="64" height="52" rx="10" fill="#071C2C" stroke="#00E5FF" stroke-width="1.5"/>
                <rect x="30" y="64" width="40" height="26" rx="5" fill="rgba(0,229,255,0.05)" stroke="rgba(0,229,255,0.2)" stroke-width="1"/>
                <circle id="led1" cx="38" cy="75" r="3" fill="#00E5FF" opacity="0.6"/>
                <circle id="led2" cx="50" cy="75" r="3" fill="#00E5FF" opacity="0.3"/>
                <circle id="led3" cx="62" cy="75" r="3" fill="#00E5FF" opacity="0.5"/>
                <!-- Arms -->
                <g class="arm-l">
                    <rect x="2" y="58" width="14" height="36" rx="6" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
                    <rect x="2" y="92" width="14" height="10" rx="4" fill="#071C2C" stroke="#00E5FF" stroke-width="1"/>
                </g>
                <g class="arm-r">
                    <rect x="84" y="58" width="14" height="36" rx="6" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
                    <rect x="84" y="92" width="14" height="10" rx="4" fill="#071C2C" stroke="#00E5FF" stroke-width="1"/>
                </g>
                <!-- Legs -->
                <rect x="28" y="108" width="18" height="28" rx="7" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
                <rect x="54" y="108" width="18" height="28" rx="7" fill="#0A2535" stroke="#00E5FF" stroke-width="1.2"/>
            </svg>

            <!-- Keyboard -->
            <div class="robot-kb" id="kb"></div>
        </div>

        <div class="stat-row">
            <div><div class="stat-num">100%</div><div class="stat-lbl">Asset Visibility</div></div>
            <div><div class="stat-num">Real-time</div><div class="stat-lbl">Stock Updates</div></div>
            <div><div class="stat-num">24/7</div><div class="stat-lbl">Uptime</div></div>
        </div>
    </div>

    <!-- RIGHT: Login card -->
    <div class="login-card">
        <div class="card-logo">
            <div class="logo-icon"><i class="fas fa-cube"></i></div>
            <div><div class="logo-name">N1 Solution</div><div class="logo-sub">Inventory System</div></div>
        </div>

        <h2 class="card-h2">Welcome back</h2>
        <p class="card-p">Sign in to access your dashboard</p>

        <?php if ($error): ?>
            <div class="err-box"><i class="fas fa-triangle-exclamation"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="login-form" autocomplete="on">
            <div class="field">
                <label class="field-lbl" for="username">Username</label>
                <div class="field-wrap">
                    <input type="text" name="username" id="username" class="field-input" placeholder="Enter username" required autofocus autocomplete="username">
                    <i class="fas fa-user field-ico"></i>
                </div>
            </div>
            <div class="field">
                <label class="field-lbl" for="password">Password</label>
                <div class="field-wrap">
                    <input type="password" name="password" id="password" class="field-input" placeholder="Enter password" required autocomplete="current-password">
                    <i class="fas fa-lock field-ico"></i>
                    <button type="button" class="eye-btn" id="eye-btn"><i class="fas fa-eye" id="eye-ico"></i></button>
                </div>
            </div>
            <div class="opts-row">
                <label class="rem"><input type="checkbox" name="remember"><span>Remember me</span></label>
                <a href="#" class="forgot">Forgot password?</a>
            </div>
            <button type="submit" class="btn-sign" id="login-btn">
                <span class="btn-inner" id="btn-inner">
                    <i class="fas fa-arrow-right-to-bracket"></i> Sign In
                </span>
            </button>
        </form>
        <p class="card-ft">&copy; <?php echo date('Y'); ?> N1 Solution. All rights reserved.</p>
    </div>
</div>

<script>
/* ── Particles ── */
const cv=document.getElementById('cv'), cx=cv.getContext('2d');
function rsz(){ cv.width=innerWidth; cv.height=innerHeight; } rsz();
window.addEventListener('resize',rsz);
const pts=Array.from({length:55},()=>({x:Math.random()*innerWidth,y:Math.random()*innerHeight,r:Math.random()*1.4+.3,dx:(Math.random()-.5)*.3,dy:(Math.random()-.5)*.3,a:Math.random()*.35+.1}));
(function ap(){ cx.clearRect(0,0,cv.width,cv.height); pts.forEach(p=>{ cx.beginPath(); cx.arc(p.x,p.y,p.r,0,Math.PI*2); cx.fillStyle=`rgba(0,229,255,${p.a})`; cx.fill(); p.x+=p.dx; p.y+=p.dy; if(p.x<0||p.x>cv.width)p.dx*=-1; if(p.y<0||p.y>cv.height)p.dy*=-1; }); requestAnimationFrame(ap); })();

/* ── Build keyboard ── */
const kb=document.getElementById('kb');
for(let i=0;i<30;i++){ const k=document.createElement('div'); k.className='key'; k.id='k'+i; kb.appendChild(k); }

/* ── Typing animation state ── */
const robotSvg = document.getElementById('robot-svg');
let typingTimer=null, keyInterval=null, isTyping=false;

function startTypingAnim(){
    if(isTyping) return;
    isTyping=true;
    robotSvg.classList.add('typing');
    // Light up eyes brighter
    document.getElementById('eye-l').setAttribute('fill','#00FFFF');
    document.getElementById('eye-r').setAttribute('fill','#00FFFF');
    document.getElementById('led1').setAttribute('opacity','1');
    document.getElementById('led2').setAttribute('opacity','0.8');
    document.getElementById('led3').setAttribute('opacity','1');
    // Flicker keys
    keyInterval=setInterval(()=>{
        document.querySelectorAll('.key').forEach(k=>k.classList.remove('lit'));
        const count=Math.floor(Math.random()*3)+1;
        for(let i=0;i<count;i++){
            const idx=Math.floor(Math.random()*30);
            const k=document.getElementById('k'+idx);
            if(k) k.classList.add('lit');
        }
    },100);
}

function stopTypingAnim(){
    isTyping=false;
    robotSvg.classList.remove('typing');
    clearInterval(keyInterval); keyInterval=null;
    document.querySelectorAll('.key').forEach(k=>k.classList.remove('lit'));
    document.getElementById('eye-l').setAttribute('fill','#00E5FF');
    document.getElementById('eye-r').setAttribute('fill','#00E5FF');
    document.getElementById('led1').setAttribute('opacity','0.6');
    document.getElementById('led2').setAttribute('opacity','0.3');
    document.getElementById('led3').setAttribute('opacity','0.5');
}

/* Listen on BOTH fields for keydown */
['username','password'].forEach(id=>{
    const el=document.getElementById(id);
    el.addEventListener('keydown',()=>{
        startTypingAnim();
        clearTimeout(typingTimer);
        typingTimer=setTimeout(stopTypingAnim, 800); // stop 0.8s after last keypress
    });
    el.addEventListener('focus',()=>{
        // subtle glow on focus but don't start full typing
        robotSvg.style.filter='drop-shadow(0 0 22px rgba(0,229,255,0.7))';
    });
    el.addEventListener('blur',()=>{
        if(!isTyping) robotSvg.style.filter='drop-shadow(0 0 18px rgba(0,229,255,0.5))';
    });
});

/* ── Submit → show big overlay ── */
document.getElementById('login-form').addEventListener('submit',()=>{
    stopTypingAnim();
    const ov=document.getElementById('sub-overlay');
    ov.classList.add('show');
    // re-start typing on overlay robot
    const ovArms=ov.querySelectorAll('.arm-l,.arm-r');
    ovArms.forEach(a=>{ a.style.animation='armTypeL .18s ease-in-out infinite alternate'; });
    // Progress bar
    const fill=document.getElementById('prog-fill');
    let p=0;
    const pi=setInterval(()=>{ p+=Math.random()*7+2; if(p>=95){p=95; clearInterval(pi);} fill.style.width=p+'%'; },200);
});

/* ── Password toggle ── */
document.getElementById('eye-btn').addEventListener('click',()=>{
    const pw=document.getElementById('password'), ico=document.getElementById('eye-ico');
    pw.type=pw.type==='password'?'text':'password';
    ico.classList.toggle('fa-eye'); ico.classList.toggle('fa-eye-slash');
});
</script>
</body>
</html>
