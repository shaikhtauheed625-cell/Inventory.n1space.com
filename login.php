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
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password. Please try again.";
        }
    } else {
        $error = "Please enter your username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — N1 Solution | IT Asset Management</title>
    <meta name="description" content="Sign in to N1 Solution IT Asset Management System.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>

/* ═══════════════ RESET ═══════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
    width: 100%; height: 100%;
    font-family: 'Inter', sans-serif;
    background: #050d18;
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-font-smoothing: antialiased;
}

/* ═══════════════ AUTOFILL FIX ═══════════════ */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
    -webkit-text-fill-color: #fff !important;
    -webkit-box-shadow: 0 0 0 1000px #0b1a2e inset !important;
    transition: background-color 9999s;
    caret-color: #00d4ff;
}

/* ═══════════════ BACKGROUND ═══════════════ */
.bg {
    position: fixed; inset: 0; z-index: 0;
    background:
        radial-gradient(ellipse 60% 60% at 20% 50%, rgba(3, 20, 26, 0.4) 0%, transparent 70%),
        url('assets/network_bg.png') center center / cover no-repeat #03141a;
}

#ptcl { position: fixed; inset: 0; z-index: 1; pointer-events: none; }

/* ═══════════════════════════════════════════════════
   MAIN LAYOUT  — 3 zones:  [text | robot | form]
   grid: left-panel takes 1fr, right-panel is 420px
═══════════════════════════════════════════════════ */
/* ═══════════════ MAIN LAYOUT ═══════════════ */
.page {
    position: relative; z-index: 10;
    width: 100vw; min-height: 100vh;
    display: flex; flex-direction: column;
    padding: 18px 36px 24px 36px;
}

/* ═══════════════ TOP HEADER ═══════════════ */
.header {
    display: flex; align-items: center; justify-content: space-between;
    width: 100%; height: 50px;
}
.brand {
    display: flex; align-items: center; gap: 10px; text-decoration: none;
}
.brand-logo-img {
    width: 28px; height: 28px;
}
.brand-name {
    font-family: 'Outfit', sans-serif;
    font-size: 18px; font-weight: 700;
    color: #fff; letter-spacing: -0.01em;
}
.header-signin-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 8px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.15);
    color: #fff; font-size: 13px; font-weight: 500;
    text-decoration: none; transition: background .2s, border-color .2s;
}
.header-signin-btn:hover {
    background: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.3);
}

/* ═══════════════ CONTENT HERO & FORM ═══════════════ */
.main-container {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    position: relative; z-index: 5;
    padding: 0;
}

.hero-left {
    display: flex; flex-direction: column;
    align-items: center; text-align: center;
    max-width: 650px; margin-bottom: 16px; z-index: 5;
}
.hero-title {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(26px, 3.2vw, 42px);
    font-weight: 800; line-height: 1.1;
    color: #fff; letter-spacing: -.02em;
    margin-bottom: 6px; text-align: center;
}
.hero-title .blue-grad {
    background: linear-gradient(120deg, #38bdf8 0%, #60a5fa 50%, #818cf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hero-subtitle {
    font-family: 'Outfit', sans-serif;
    font-size: 14px; font-weight: 500;
    color: rgba(255,255,255,.85);
    margin-bottom: 6px; text-align: center;
}
.hero-divider {
    width: 50px; height: 2px;
    background: linear-gradient(90deg, #3b82f6, #a855f7);
    border-radius: 2px; margin-bottom: 8px;
}
.hero-desc {
    font-size: 12.5px; line-height: 1.4;
    color: rgba(255,255,255,.45);
    max-width: 450px; text-align: center;
}



/* ── TEXT COLUMN (left ~52% of left panel) ── */
.lp-text {
    flex: 0 0 52%;
    display: flex;
    flex-direction: column;
    padding: 28px 24px 28px 36px;
    z-index: 5;
    position: relative;
}

/* Brand row */
.brand {
    display: flex; align-items: center; gap: 11px;
    margin-bottom: 8px;
}
.brand-ico {
    width: 36px; height: 36px; border-radius: 9px;
    background: rgba(0,212,255,0.1);
    border: 1px solid rgba(0,212,255,0.22);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 0 14px rgba(0,212,255,0.1);
}
.brand-ico svg { width: 17px; height: 17px; }
.brand-name {
    font-family: 'Outfit', sans-serif;
    font-size: 14px; font-weight: 700;
    color: #fff; letter-spacing: .01em;
}
.brand-tag {
    font-size: 9px; font-weight: 600;
    color: #00d4ff; letter-spacing: .13em;
    text-transform: uppercase; display: block;
}

.status-pill {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(0,212,255,0.06);
    border: 1px solid rgba(0,212,255,0.16);
    border-radius: 100px;
    padding: 4px 12px 4px 8px;
    margin-bottom: 20px;
    width: fit-content;
}
.sdot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #00ff88; box-shadow: 0 0 7px #00ff88;
    animation: sdotBlink 2s ease-in-out infinite;
}
@keyframes sdotBlink {
    0%,100% { box-shadow: 0 0 7px #00ff88; }
    50% { box-shadow: 0 0 13px #00ff88; opacity: .7; }
}
.stxt {
    font-size: 10px; font-weight: 600;
    color: rgba(255,255,255,.5); letter-spacing: .1em; text-transform: uppercase;
}

/* Hero */
.eyebrow {
    font-size: 10px; font-weight: 600;
    color: #00d4ff; letter-spacing: .18em;
    text-transform: uppercase; margin-bottom: 10px;
}
.hero-title {
    font-family: 'Outfit', sans-serif;
    font-size: clamp(34px, 4vw, 56px);
    font-weight: 800; line-height: 1.05;
    color: #fff; letter-spacing: -.02em;
    margin-bottom: 16px;
}
.hero-title .grad {
    background: linear-gradient(120deg, #38bdf8 0%, #60a5fa 50%, #818cf8 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hero-subtitle {
    font-family: 'Outfit', sans-serif;
    font-size: 18px; font-weight: 500;
    color: rgba(255,255,255,.9);
    margin-bottom: 10px;
}
.hero-desc {
    font-size: 13.5px; line-height: 1.6;
    color: rgba(255,255,255,.45);
    margin-bottom: 28px;
    max-width: 380px;
}
.cta-btn {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, #2563eb 0%, #4f46e5 50%, #7c3aed 100%);
    color: #fff; font-family: 'Outfit', sans-serif;
    font-weight: 600; font-size: 14px;
    padding: 12px 24px; border-radius: 10px;
    box-shadow: 0 4px 20px rgba(79, 70, 229, 0.4);
    text-decoration: none; border: none; cursor: pointer;
    transition: transform .25s, box-shadow .25s;
    width: fit-content; margin-bottom: 24px;
}
.cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(79, 70, 229, 0.6);
}

/* Feature cards */
.feats { display: flex; flex-direction: column; gap: 8px; }
.feat {
    display: flex; align-items: center; gap: 11px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(0,212,255,0.1);
    border-radius: 10px;
    padding: 9px 13px;
    transition: background .3s, border-color .3s, transform .3s;
    cursor: default;
}
.feat:hover {
    background: rgba(0,212,255,0.07);
    border-color: rgba(0,212,255,0.22);
    transform: translateX(4px);
}
.feat-ico {
    width: 32px; height: 32px; flex-shrink: 0;
    border-radius: 8px;
    background: rgba(0,180,220,0.1);
    border: 1px solid rgba(0,212,255,0.13);
    display: flex; align-items: center; justify-content: center;
}
.feat-ico svg { width: 15px; height: 15px; }
.feat-name {
    font-family: 'Outfit', sans-serif;
    font-size: 12.5px; font-weight: 600;
    color: rgba(255,255,255,.88); display: block;
}
.feat-desc { font-size: 11px; color: rgba(255,255,255,.38); }

/* push feats to fill remaining space */
.spacer { flex: 1; }

/* ── ROBOT COLUMN (right ~48% of left panel) ── */
.lp-visual {
    flex: 1;
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    overflow: hidden;
}

/* subtle radial glow behind robot */
.lp-visual::before {
    content: '';
    position: absolute;
    bottom: -30px; left: 50%; transform: translateX(-50%);
    width: 420px; height: 420px; border-radius: 50%;
    background: radial-gradient(circle, rgba(0,180,220,0.18) 0%, transparent 60%);
    filter: blur(30px);
    pointer-events: none; z-index: 0;
}

/*
  Grid spotlight in robot column — img element approach ensures reliable
  blend mode rendering across all browsers (pseudo-element + PNG + blend
  can silently fail in some compositing contexts).
*/
.grid-layer {
    position: absolute; inset: 0; z-index: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    object-position: center bottom;
    mix-blend-mode: screen;
    opacity: 0.55;
    -webkit-mask-image:
        radial-gradient(ellipse 90% 85% at 50% 68%,
            black 0%,
            rgba(0,0,0,0.75) 38%,
            rgba(0,0,0,0.25) 62%,
            transparent 82%);
    mask-image:
        radial-gradient(ellipse 90% 85% at 50% 68%,
            black 0%,
            rgba(0,0,0,0.75) 38%,
            rgba(0,0,0,0.25) 62%,
            transparent 82%);
    pointer-events: none;
    animation: gridBreath 9s ease-in-out infinite;
}

.robot-img {
    position: relative; z-index: 1;
    width: 100%;
    max-width: 480px;
    height: auto;
    object-fit: contain;
    object-position: center center;
    display: block;
    mix-blend-mode: screen;
    filter:
        drop-shadow(0 0 40px rgba(56,189,248,0.4))
        brightness(1.05);
    animation: robotFloat 6s ease-in-out infinite;
}
@keyframes robotFloat {
    0%,100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* ════════════════════════════════
   CENTER FLOATING GLASS CARD
════════════════════════════════ */
.rp {
    position: relative; z-index: 10;
    display: flex; flex-direction: column;
    justify-content: center;
    width: 100%; max-width: 400px;
    padding: 24px 28px;
    background: rgba(6, 14, 28, 0.82);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 18px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.65), inset 0 1px 0 rgba(255,255,255,0.12);
}

.rp-brand {
    display: flex; flex-direction: column; align-items: center;
    margin-bottom: 16px; text-align: center;
}
.rp-ico {
    width: 40px; height: 40px; border-radius: 12px;
    background: rgba(56, 189, 248, 0.1);
    border: 1px solid rgba(56, 189, 248, 0.2);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 8px;
    box-shadow: 0 0 20px rgba(56, 189, 248, 0.15);
}
.rp-ico svg { width: 20px; height: 20px; }

.rp-title {
    font-family: 'Outfit', sans-serif;
    font-size: 22px; font-weight: 700;
    color: #fff; letter-spacing: -.01em;
    margin-bottom: 4px; text-align: center;
}
.rp-sub {
    font-size: 12px; color: rgba(255,255,255,.4);
    margin-bottom: 16px; text-align: center;
}

/* error */
.err-box {
    display: flex; align-items: flex-start; gap: 9px;
    background: rgba(240,60,90,.09);
    border: 1px solid rgba(240,60,90,.22);
    border-radius: 9px;
    padding: 10px 13px;
    color: #ffa8b8; font-size: 12.5px;
    margin-bottom: 18px;
    animation: errAni .3s ease;
}
@keyframes errAni {
    from { opacity:0; transform: translateY(-6px); }
    to   { opacity:1; transform: translateY(0); }
}

/* form fields */
.fg { margin-bottom: 16px; }
.flabel {
    display: block;
    font-size: 10px; font-weight: 600;
    letter-spacing: .14em; text-transform: uppercase;
    color: rgba(255,255,255,.38);
    margin-bottom: 6px;
}
.fwrap { position: relative; display: flex; align-items: center; }
.ficon {
    position: absolute; left: 13px;
    color: rgba(255,255,255,.22);
    pointer-events: none; display: flex;
    transition: color .25s;
}
.ficon svg { width: 15px; height: 15px; }

.finput {
    width: 100%;
    background: #0b1a2e !important;
    border: 1px solid #1c3050;
    border-radius: 10px;
    padding: 11px 12px 11px 40px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    color: #fff !important;
    outline: none;
    caret-color: #00d4ff;
    transition: border-color .25s, box-shadow .25s;
}
.finput::placeholder { color: rgba(255,255,255,.18); font-size: 13px; }
.fwrap:focus-within .finput {
    border-color: rgba(0,212,255,.48);
    box-shadow: 0 0 0 3px rgba(0,212,255,.09);
}
.fwrap:focus-within .ficon { color: #00d4ff; }

.eye-btn {
    position: absolute; right: 12px;
    background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,.22);
    display: flex; padding: 0;
    transition: color .2s;
}
.eye-btn:hover { color: #00d4ff; }
.eye-btn svg { width: 15px; height: 15px; }

/* meta row */
.meta-row {
    display: flex; align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}
.chk {
    display: flex; align-items: center; gap: 8px;
    cursor: pointer; user-select: none; position: relative;
}
.chk input[type="checkbox"] {
    position: absolute; width: 0; height: 0;
    opacity: 0; pointer-events: none;
}
.chkbox {
    width: 16px; height: 16px; border-radius: 4px;
    border: 1.5px solid rgba(0,212,255,.32);
    background: #0b1a2e;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: background .2s, border-color .2s, box-shadow .2s;
}
.chk:hover .chkbox {
    border-color: #00d4ff;
    box-shadow: 0 0 0 3px rgba(0,212,255,.08);
}
.chk input:checked ~ .chkbox {
    background: #00d4ff; border-color: #00d4ff;
    box-shadow: 0 0 8px rgba(0,212,255,.28);
}
.chk-ico { display: none; }
.chk input:checked ~ .chkbox .chk-ico { display: block; }
.chktxt { font-size: 12.5px; color: rgba(255,255,255,.4); }

.fgt {
    font-size: 12.5px; font-weight: 500;
    color: #00d4ff; text-decoration: none;
    transition: opacity .2s;
}
.fgt:hover { opacity: .7; text-decoration: underline; }

/* button */
.signin-btn {
    width: 100%; min-height: 48px;
    border: none; border-radius: 12px;
    background: linear-gradient(135deg, #0284c7 0%, #2563eb 50%, #7c3aed 100%);
    color: #fff;
    font-family: 'Outfit', sans-serif;
    font-size: 15px; font-weight: 600;
    cursor: pointer;
    position: relative; overflow: hidden;
    transition: transform .18s, box-shadow .25s, filter .2s;
    box-shadow: 0 6px 24px rgba(37, 99, 235, 0.4);
    margin-bottom: 24px;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.signin-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(124, 58, 237, 0.5);
}
.signin-btn:hover:not(:disabled)::before { left: 100%; }
.signin-btn:active:not(:disabled) { transform: translateY(0); }
.signin-btn:disabled { opacity: .5; cursor: not-allowed; }
.btn-arrow { display: flex; transition: transform .25s; }
.signin-btn:hover .btn-arrow { transform: translateX(4px); }

/* divider + badges */
.divrow {
    display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
}
.divline {
    flex: 1; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.07), transparent);
}
.divtxt {
    font-size: 10px; color: rgba(255,255,255,.18);
    letter-spacing: .08em; text-transform: uppercase; white-space: nowrap;
}
.badges {
    display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;
}
.badge {
    display: flex; align-items: center; gap: 5px;
    font-size: 10px; color: rgba(255,255,255,.2);
}
.badge svg { width: 11px; height: 11px; opacity: .5; }

/* ═══ RESPONSIVE ═══ */
@media (max-width: 860px) {
    .page { grid-template-columns: 1fr; }
    .lp { display: none; }
    .rp { padding: 32px 24px; }
    .rp::before { display: none; }
}
</style>
</head>
<body>

<div class="bg"></div>
<canvas id="ptcl"></canvas>

<div class="page">

    <!-- Header Navigation -->
    <header class="header">
        <a href="#" class="brand">
            <svg class="brand-logo-img" viewBox="0 0 24 24" fill="none">
                <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="#00d4ff" stroke-width="2" stroke-linejoin="round"/>
                <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="#00ffcc" stroke-width="2" stroke-linejoin="round"/>
            </svg>
            <span class="brand-name">N1 Inventory</span>
        </a>
        <a href="#" class="header-signin-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            Sign In
        </a>
    </header>

    <!-- Main Section -->
    <main class="main-container">

        <!-- Right Panel Form Card -->
        <div class="rp" role="main">

            <div class="rp-brand">
                <div class="rp-ico">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="#38bdf8" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="#818cf8" stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="rp-title">Welcome back</h2>
                <p class="rp-sub">Sign in to your account to continue</p>
            </div>

        <!-- Error -->
        <?php if ($error): ?>
        <div class="err-box" role="alert">
            <svg width="15" height="15" viewBox="0 0 20 20" fill="none" style="flex-shrink:0;margin-top:1px">
                <circle cx="10" cy="10" r="9" stroke="#f03c5a" stroke-width="1.5"/>
                <path d="M10 6v5M10 13.5v.5" stroke="#f03c5a" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" id="login-form" novalidate autocomplete="on">

            <!-- Username -->
            <div class="fg">
                <label class="flabel" for="username">Username</label>
                <div class="fwrap">
                    <span class="ficon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input type="text" id="username" name="username" class="finput"
                        placeholder="Enter your username"
                        required autofocus autocomplete="username"
                        aria-required="true" spellcheck="false" autocapitalize="none">
                </div>
            </div>

            <!-- Password -->
            <div class="fg">
                <label class="flabel" for="password">Password</label>
                <div class="fwrap">
                    <span class="ficon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password" class="finput"
                        placeholder="Enter your password"
                        required autocomplete="current-password" aria-required="true">
                    <button type="button" class="eye-btn" id="eye-btn" aria-label="Toggle password">
                        <svg id="eye-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Remember + Forgot -->
            <div class="meta-row">
                <label class="chk">
                    <input type="checkbox" name="remember" id="remember">
                    <div class="chkbox">
                        <svg class="chk-ico" width="9" height="9" viewBox="0 0 10 10" fill="none">
                            <path d="M2 5l2.5 2.5L8 3" stroke="#030e1a" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="chktxt">Remember me</span>
                </label>
                <a href="#" class="fgt">Forgot password?</a>
            </div>

            <!-- Sign In -->
            <button type="submit" class="signin-btn" id="sbtn">
                Sign In
                <span class="btn-arrow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </span>
            </button>

            <!-- Divider -->
            <div class="divrow">
                <div class="divline"></div>
                <span class="divtxt">Secured by N1 Solution</span>
                <div class="divline"></div>
            </div>

            <!-- Badges -->
            <div class="badges">
                <div class="badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    SSL Encrypted
                </div>
                <div class="badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    Enterprise Security
                </div>
                <div class="badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    24/7 Monitoring
                </div>
            </div>

        </form>
    </div><!-- /rp -->

</div><!-- /page -->

<script>
/* ── Particles ── */
(function(){
    const c=document.getElementById('ptcl'), x=c.getContext('2d');
    let W,H,p=[];
    function resize(){ W=c.width=innerWidth; H=c.height=innerHeight; }
    function init(){
        const n=Math.min(Math.floor(W*H/16000),60);
        p=Array.from({length:n},()=>({
            x:Math.random()*W, y:Math.random()*H,
            vx:(Math.random()-.5)*.25, vy:(Math.random()-.5)*.25,
            r:Math.random()*1.2+.3, a:Math.random()*.4+.07
        }));
    }
    function draw(){
        x.clearRect(0,0,W,H);
        for(let i=0;i<p.length;i++) for(let j=i+1;j<p.length;j++){
            const dx=p[i].x-p[j].x, dy=p[i].y-p[j].y, d=Math.sqrt(dx*dx+dy*dy);
            if(d<120){ x.beginPath(); x.moveTo(p[i].x,p[i].y); x.lineTo(p[j].x,p[j].y);
                x.strokeStyle=`rgba(0,200,240,${(1-d/120)*.055})`; x.lineWidth=.6; x.stroke(); }
        }
        p.forEach(q=>{
            x.beginPath(); x.arc(q.x,q.y,q.r,0,Math.PI*2);
            x.fillStyle=`rgba(150,210,255,${q.a})`; x.fill();
            q.x+=q.vx; q.y+=q.vy;
            if(q.x<0)q.x=W; if(q.x>W)q.x=0;
            if(q.y<0)q.y=H; if(q.y>H)q.y=0;
        });
        requestAnimationFrame(draw);
    }
    resize(); init(); draw();
    window.addEventListener('resize',()=>{resize();init();});
})();

/* ── Password eye toggle ── */
(function(){
    const btn=document.getElementById('eye-btn'),
          inp=document.getElementById('password'),
          svg=document.getElementById('eye-svg');
    const on=`<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    const off=`<path d="M17.94 17.94A10 10 0 0 1 12 20c-7 0-11-8-11-8a18 18 0 0 1 5.06-5.94M9.9 4.24A9 9 0 0 1 12 4c7 0 11 8 11 8a18 18 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;
    btn.addEventListener('click',()=>{ const s=inp.type==='password'; inp.type=s?'text':'password'; svg.innerHTML=s?off:on; });
})();

/* ── Submit spinner ── */
(function(){
    const form=document.getElementById('login-form'), btn=document.getElementById('sbtn');
    form.addEventListener('submit',()=>{
        btn.disabled=true;
        btn.innerHTML=`<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin .8s linear infinite"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-.37-4.39"/></svg>&nbsp;Signing in…`;
        const s=document.createElement('style'); s.textContent='@keyframes spin{to{transform:rotate(360deg)}}'; document.head.appendChild(s);
    });
})();
</script>
</body>
</html>
