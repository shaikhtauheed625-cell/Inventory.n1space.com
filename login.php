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
    <title>Sign In — N1 Solution IT Asset Management</title>
    <meta name="description" content="Sign in to N1 Solution IT Asset Management System — centralized inventory control for modern enterprises.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>

/* ═══════════════════════════════════════
   RESET & ROOT
═══════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:          #020617;
    --card-bg:     rgba(15, 23, 42, 0.75);
    --cyan:        #06B6D4;
    --cyan-hover:  #22D3EE;
    --cyan-glow:   rgba(6, 182, 212, 0.35);
    --cyan-dim:    rgba(6, 182, 212, 0.12);
    --white:       #FFFFFF;
    --secondary:   #94A3B8;
    --border:      rgba(255, 255, 255, 0.08);
    --border-focus:rgba(6, 182, 212, 0.55);
    --input-bg:    rgba(2, 6, 23, 0.6);
    --error-bg:    rgba(239, 68, 68, 0.1);
    --error-border:rgba(239, 68, 68, 0.3);
    --error-text:  #FCA5A5;
}

html, body {
    width: 100%; height: 100%;
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
}

/* ═══════════════════════════════════════
   BACKGROUND LAYERS
═══════════════════════════════════════ */
.scene {
    position: fixed; inset: 0; z-index: 0;
    background: var(--bg);
}

/* Animated gradient orbs */
.orb {
    position: absolute; border-radius: 50%;
    filter: blur(80px); opacity: 0.18;
    animation: orbFloat 12s ease-in-out infinite;
}
.orb-1 {
    width: 500px; height: 500px;
    background: radial-gradient(circle, #0891B2 0%, transparent 70%);
    top: -100px; left: -100px;
    animation-delay: 0s;
}
.orb-2 {
    width: 400px; height: 400px;
    background: radial-gradient(circle, #0E7490 0%, transparent 70%);
    bottom: -80px; left: 30%;
    animation-delay: -4s;
}
.orb-3 {
    width: 300px; height: 300px;
    background: radial-gradient(circle, #06B6D4 0%, transparent 70%);
    top: 20%; right: 5%;
    animation-delay: -8s; opacity: 0.1;
}

@keyframes orbFloat {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33%       { transform: translate(30px, -40px) scale(1.05); }
    66%       { transform: translate(-20px, 20px) scale(0.95); }
}

/* Grid lines */
.grid-lines {
    position: fixed; inset: 0; z-index: 1; pointer-events: none;
    background-image:
        linear-gradient(rgba(6,182,212,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(6,182,212,0.04) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: linear-gradient(to bottom, transparent 0%, black 20%, black 80%, transparent 100%);
}

/* Particle canvas */
#particles-canvas {
    position: fixed; inset: 0; z-index: 2; pointer-events: none;
}

/* ═══════════════════════════════════════
   PAGE LAYOUT
═══════════════════════════════════════ */
.page-wrapper {
    position: relative; z-index: 10;
    width: 100vw; height: 100vh;
    display: flex;
    animation: pageIn 0.8s cubic-bezier(0.16,1,0.3,1) both;
}

@keyframes pageIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ═══════════════════════════════════════
   LEFT — HERO SECTION (55%)
═══════════════════════════════════════ */
.hero {
    flex: 0 0 58%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 60px 64px;
    position: relative;
    overflow: hidden;
}

/* Decorative glowing ring */
.hero-ring {
    position: absolute;
    width: 520px; height: 520px;
    border-radius: 50%;
    border: 1px solid rgba(6,182,212,0.12);
    top: 50%; left: 50%;
    transform: translate(-40%, -50%);
    animation: ringPulse 6s ease-in-out infinite;
    pointer-events: none;
}
.hero-ring::before {
    content: '';
    position: absolute; inset: 40px;
    border-radius: 50%;
    border: 1px solid rgba(6,182,212,0.08);
}
.hero-ring::after {
    content: '';
    position: absolute; inset: 100px;
    border-radius: 50%;
    border: 1px solid rgba(6,182,212,0.05);
}
@keyframes ringPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(6,182,212,0); opacity: 0.6; }
    50%       { box-shadow: 0 0 60px 10px rgba(6,182,212,0.06); opacity: 1; }
}

/* Floating geometry shapes */
.geo {
    position: absolute; pointer-events: none;
}
.geo-1 {
    width: 80px; height: 80px;
    top: 18%; right: 22%;
    border: 1px solid rgba(6,182,212,0.15);
    border-radius: 12px;
    transform: rotate(20deg);
    animation: geoFloat1 8s ease-in-out infinite;
}
.geo-2 {
    width: 40px; height: 40px;
    bottom: 28%; right: 18%;
    border: 1px solid rgba(6,182,212,0.1);
    border-radius: 50%;
    animation: geoFloat2 10s ease-in-out infinite;
}
.geo-3 {
    width: 120px; height: 2px;
    top: 35%; right: 12%;
    background: linear-gradient(90deg, transparent, rgba(6,182,212,0.3), transparent);
    animation: geoFloat3 7s ease-in-out infinite;
}
.geo-4 {
    width: 6px; height: 6px;
    top: 28%; right: 30%;
    border-radius: 50%;
    background: var(--cyan);
    box-shadow: 0 0 12px var(--cyan-glow);
    animation: blink 3s ease-in-out infinite;
}
.geo-5 {
    width: 6px; height: 6px;
    bottom: 35%; right: 25%;
    border-radius: 50%;
    background: var(--cyan);
    box-shadow: 0 0 12px var(--cyan-glow);
    animation: blink 3s ease-in-out infinite 1.5s;
}

@keyframes geoFloat1 { 0%,100%{transform:rotate(20deg) translateY(0)} 50%{transform:rotate(25deg) translateY(-15px)} }
@keyframes geoFloat2 { 0%,100%{transform:translateY(0) scale(1)} 50%{transform:translateY(-12px) scale(1.1)} }
@keyframes geoFloat3 { 0%,100%{opacity:0.3; transform:scaleX(1)} 50%{opacity:0.8; transform:scaleX(1.3)} }
@keyframes blink     { 0%,100%{opacity:0.5} 50%{opacity:1; box-shadow:0 0 20px var(--cyan-glow)} }

/* Hero content */
.hero-content { position: relative; z-index: 2; max-width: 520px; }

.status-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(6,182,212,0.08);
    border: 1px solid rgba(6,182,212,0.2);
    border-radius: 100px;
    padding: 6px 16px;
    font-size: 11px; font-weight: 600;
    color: var(--cyan); letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 32px;
}
.status-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #22C55E;
    animation: statusPulse 2s infinite;
}
@keyframes statusPulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0.5); }
    50%       { box-shadow: 0 0 0 5px rgba(34,197,94,0); }
}

.hero-heading {
    font-size: clamp(38px, 4.5vw, 64px);
    font-weight: 800;
    color: var(--white);
    line-height: 1.08;
    letter-spacing: -0.035em;
    margin-bottom: 24px;
}
.hero-heading .gradient-text {
    background: linear-gradient(135deg, var(--cyan) 0%, var(--cyan-hover) 50%, #A5F3FC 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: block;
}

.hero-subtitle {
    font-size: 16px;
    color: var(--secondary);
    line-height: 1.75;
    max-width: 420px;
    margin-bottom: 48px;
    font-weight: 400;
}

/* Feature chips */
.feature-chips { display: flex; flex-direction: column; gap: 12px; }
.chip {
    display: flex; align-items: center; gap: 14px;
    padding: 12px 16px;
    background: rgba(15,23,42,0.5);
    border: 1px solid var(--border);
    border-radius: 12px;
    width: fit-content;
    min-width: 280px;
    transition: border-color 0.3s, background 0.3s;
}
.chip:hover { border-color: rgba(6,182,212,0.2); background: rgba(6,182,212,0.05); }
.chip-icon {
    width: 34px; height: 34px; border-radius: 8px; flex-shrink: 0;
    background: var(--cyan-dim);
    display: flex; align-items: center; justify-content: center;
    color: var(--cyan); font-size: 13px;
}
.chip-label { font-size: 13px; font-weight: 500; color: var(--white); }
.chip-sub   { font-size: 11px; color: var(--secondary); margin-top: 1px; }

/* ═══════════════════════════════════════
   RIGHT — LOGIN PANEL (42%)
═══════════════════════════════════════ */
.login-panel {
    flex: 0 0 42%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 48px 40px 40px;
    position: relative;
}

/* Subtle right-side gradient */
.login-panel::before {
    content: '';
    position: absolute; inset: 0; pointer-events: none;
    background: linear-gradient(to left, rgba(6,182,212,0.03) 0%, transparent 100%);
}

/* ── Card ── */
.login-card {
    width: 100%; max-width: 420px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 44px 40px;
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    box-shadow:
        0 0 0 1px rgba(255,255,255,0.04),
        0 4px 24px rgba(0,0,0,0.4),
        0 20px 60px rgba(0,0,0,0.3);
    position: relative; overflow: hidden;
    animation: cardIn 0.9s cubic-bezier(0.16,1,0.3,1) 0.1s both;
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Top accent line */
.login-card::before {
    content: '';
    position: absolute; top: 0; left: 10%; right: 10%; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(6,182,212,0.5), transparent);
}

/* Card logo */
.card-logo {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 32px;
}
.logo-mark {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, rgba(6,182,212,0.2), rgba(6,182,212,0.05));
    border: 1px solid rgba(6,182,212,0.25);
    display: flex; align-items: center; justify-content: center;
    color: var(--cyan); font-size: 17px;
    box-shadow: 0 0 20px rgba(6,182,212,0.15);
}
.logo-name { font-size: 16px; font-weight: 700; color: var(--white); letter-spacing: -0.01em; }
.logo-tagline { font-size: 10.5px; color: var(--secondary); letter-spacing: 0.06em; text-transform: uppercase; margin-top: 1px; }

/* Card headings */
.card-title   { font-size: 24px; font-weight: 700; color: var(--white); letter-spacing: -0.025em; margin-bottom: 6px; }
.card-subtitle { font-size: 14px; color: var(--secondary); margin-bottom: 28px; line-height: 1.5; }

/* Error box */
.error-box {
    background: var(--error-bg);
    border: 1px solid var(--error-border);
    border-radius: 10px;
    padding: 11px 14px;
    color: var(--error-text);
    font-size: 13px;
    display: flex; align-items: flex-start; gap: 9px;
    margin-bottom: 20px;
    animation: errorIn 0.3s ease;
}
.error-box i { margin-top: 1px; flex-shrink: 0; }
@keyframes errorIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Input fields ── */
.field-group { margin-bottom: 18px; }

.field-label {
    display: block;
    font-size: 12px; font-weight: 600;
    color: var(--secondary);
    text-transform: uppercase; letter-spacing: 0.07em;
    margin-bottom: 7px;
}

.field-wrap { position: relative; }

.field-icon {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: #475569; font-size: 13.5px;
    pointer-events: none;
    transition: color 0.2s ease;
    z-index: 1;
}

.field-input {
    width: 100%;
    background: var(--input-bg);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 13px 14px 13px 42px;
    font-size: 14px; font-family: 'Inter', sans-serif;
    color: var(--white);
    outline: none;
    caret-color: var(--cyan);
    transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    min-height: 48px;
}
.field-input::placeholder { color: #334155; }
.field-input:hover { border-color: rgba(255,255,255,0.12); }
.field-input:focus {
    background: rgba(6,182,212,0.04);
    border-color: var(--border-focus);
    box-shadow: 0 0 0 3px rgba(6,182,212,0.1);
}
.field-wrap:focus-within .field-icon { color: var(--cyan); }

/* Password toggle */
.pwd-toggle {
    position: absolute; right: 13px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none;
    color: #475569; cursor: pointer; font-size: 13.5px;
    padding: 6px; border-radius: 6px;
    transition: color 0.2s, background 0.2s;
    min-width: 32px; min-height: 32px;
    display: flex; align-items: center; justify-content: center;
}
.pwd-toggle:hover { color: var(--cyan); background: rgba(6,182,212,0.08); }
.pwd-toggle:focus-visible { outline: 2px solid var(--cyan); outline-offset: 2px; }
#password { padding-right: 44px; }

/* ── Options row ── */
.options-row {
    display: flex; align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}

.remember-wrap {
    display: flex; align-items: center; gap: 9px;
    cursor: pointer; user-select: none;
}
.remember-wrap input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: var(--cyan); cursor: pointer;
    border-radius: 4px;
}
.remember-label { font-size: 13.5px; color: var(--secondary); }

.forgot-link {
    font-size: 13.5px; font-weight: 500;
    color: var(--cyan); text-decoration: none;
    transition: color 0.2s;
    border-radius: 4px; padding: 2px 4px; margin-right: -4px;
}
.forgot-link:hover { color: var(--cyan-hover); text-decoration: underline; }
.forgot-link:focus-visible { outline: 2px solid var(--cyan); outline-offset: 2px; }

/* ── Submit button ── */
.btn-signin {
    width: 100%; min-height: 48px;
    border: none; border-radius: 10px;
    background: linear-gradient(135deg, #0891B2 0%, #06B6D4 50%, #22D3EE 100%);
    color: #020617;
    font-size: 14.5px; font-weight: 700;
    font-family: 'Inter', sans-serif; letter-spacing: 0.02em;
    cursor: pointer; position: relative; overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.25s ease, filter 0.2s ease;
    box-shadow: 0 4px 20px rgba(6,182,212,0.3), 0 1px 0 rgba(255,255,255,0.15) inset;
}
.btn-signin::before {
    content: '';
    position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
    transition: left 0.5s ease;
}
.btn-signin:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(6,182,212,0.45), 0 1px 0 rgba(255,255,255,0.15) inset;
    filter: brightness(1.05);
}
.btn-signin:hover:not(:disabled)::before { left: 100%; }
.btn-signin:active:not(:disabled)  { transform: translateY(0); }
.btn-signin:focus-visible { outline: 2px solid var(--cyan); outline-offset: 3px; }
.btn-signin:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

.btn-inner {
    position: relative; z-index: 1;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}

/* Loading state */
.spinner {
    display: none;
    width: 16px; height: 16px;
    border: 2px solid rgba(2,6,23,0.3);
    border-top-color: #020617;
    border-radius: 50%;
    animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.btn-signin.loading .spinner { display: block; }
.btn-signin.loading .btn-text { opacity: 0.85; }

/* ── Security indicators ── */
.security-row {
    display: flex; align-items: center; justify-content: center;
    gap: 20px; margin-top: 22px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}
.sec-item {
    display: flex; align-items: center; gap: 5px;
    font-size: 11px; color: #475569;
    transition: color 0.2s;
}
.sec-item i { color: #334155; font-size: 11px; }
.sec-item:hover { color: var(--secondary); }
.sec-item:hover i { color: var(--cyan); }

/* ── Divider ── */
.card-divider {
    display: flex; align-items: center; gap: 12px;
    margin: 22px 0 0;
}
.card-divider::before, .card-divider::after {
    content: ''; flex: 1; height: 1px; background: var(--border);
}
.card-divider span {
    font-size: 11px; color: #334155;
    text-transform: uppercase; letter-spacing: 0.08em;
    white-space: nowrap;
}

/* ═══════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════ */
@media (max-width: 960px) {
    .hero { flex: 0 0 50%; padding: 40px 36px; }
    .hero-heading { font-size: clamp(30px, 4vw, 48px); }
    .hero-subtitle { font-size: 14px; }
    .feature-chips { display: none; }
    .login-panel { flex: 1; padding: 40px 28px; }
    .login-card { padding: 36px 28px; }
}

@media (max-width: 700px) {
    .hero { display: none; }
    .login-panel { flex: 1; padding: 24px 20px; }
    .login-card { max-width: 100%; padding: 32px 24px; }
    .page-wrapper { align-items: center; justify-content: center; }
}

    </style>
</head>
<body>

<!-- Background scene -->
<div class="scene" aria-hidden="true">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>
<div class="grid-lines" aria-hidden="true"></div>
<canvas id="particles-canvas" aria-hidden="true"></canvas>

<!-- ═══════════════════════════════════════
     PAGE
═══════════════════════════════════════ -->
<div class="page-wrapper" role="main">

    <!-- ── LEFT: Hero ── -->
    <section class="hero" aria-label="Product information">
        <!-- Decorative elements -->
        <div class="hero-ring" aria-hidden="true"></div>
        <div class="geo geo-1" aria-hidden="true"></div>
        <div class="geo geo-2" aria-hidden="true"></div>
        <div class="geo geo-3" aria-hidden="true"></div>
        <div class="geo geo-4" aria-hidden="true"></div>
        <div class="geo geo-5" aria-hidden="true"></div>

        <div class="hero-content">
            <div class="status-badge" role="status" aria-label="System status: online">
                <span class="status-dot" aria-hidden="true"></span>
                System Online
            </div>

            <h1 class="hero-heading">
                IT Asset
                <span class="gradient-text">Control</span>
            </h1>

            <p class="hero-subtitle">
                Centralized inventory management for modern enterprises.
                Securely manage devices, software licenses, and
                organizational assets from one platform.
            </p>

            <div class="feature-chips" aria-label="Platform features">
                <div class="chip">
                    <div class="chip-icon" aria-hidden="true"><i class="fas fa-laptop"></i></div>
                    <div>
                        <div class="chip-label">Asset Tracking</div>
                        <div class="chip-sub">Real-time device & hardware visibility</div>
                    </div>
                </div>
                <div class="chip">
                    <div class="chip-icon" aria-hidden="true"><i class="fas fa-key"></i></div>
                    <div>
                        <div class="chip-label">License Management</div>
                        <div class="chip-sub">Software & warranty monitoring</div>
                    </div>
                </div>
                <div class="chip">
                    <div class="chip-icon" aria-hidden="true"><i class="fas fa-chart-bar"></i></div>
                    <div>
                        <div class="chip-label">Inventory Reports</div>
                        <div class="chip-sub">Insights, alerts & audit trails</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── RIGHT: Login ── -->
    <section class="login-panel" aria-label="Login">
        <div class="login-card" role="region" aria-label="Sign in form">

            <!-- Logo -->
            <div class="card-logo">
                <div class="logo-mark" aria-hidden="true"><i class="fas fa-cube"></i></div>
                <div>
                    <div class="logo-name">N1 Solution</div>
                    <div class="logo-tagline">IT Asset Management</div>
                </div>
            </div>

            <!-- Heading -->
            <h2 class="card-title">Welcome back</h2>
            <p class="card-subtitle">Sign in to your account to continue</p>

            <!-- Error -->
            <?php if ($error): ?>
                <div class="error-box" role="alert" aria-live="assertive">
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" id="signin-form" novalidate autocomplete="on">

                <!-- Username -->
                <div class="field-group">
                    <label class="field-label" for="username">Username</label>
                    <div class="field-wrap">
                        <i class="fas fa-user field-icon" aria-hidden="true"></i>
                        <input
                            type="text"
                            name="username"
                            id="username"
                            class="field-input"
                            placeholder="Enter your username"
                            required
                            autofocus
                            autocomplete="username"
                            aria-required="true"
                            aria-label="Username"
                            spellcheck="false"
                            autocapitalize="none"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="field-group">
                    <label class="field-label" for="password">Password</label>
                    <div class="field-wrap">
                        <i class="fas fa-lock field-icon" aria-hidden="true"></i>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="field-input"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                            aria-required="true"
                            aria-label="Password"
                        >
                        <button
                            type="button"
                            class="pwd-toggle"
                            id="pwd-toggle"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            <i class="fas fa-eye" id="pwd-icon" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <!-- Options -->
                <div class="options-row">
                    <label class="remember-wrap">
                        <input type="checkbox" name="remember" id="remember" aria-label="Remember me">
                        <span class="remember-label">Remember me</span>
                    </label>
                    <a href="#" class="forgot-link" aria-label="Forgot password? Reset it">Forgot password?</a>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-signin" id="signin-btn" aria-label="Sign in to dashboard">
                    <span class="btn-inner">
                        <span class="spinner" aria-hidden="true"></span>
                        <span class="btn-text">Sign In</span>
                        <i class="fas fa-arrow-right btn-arrow" aria-hidden="true"></i>
                    </span>
                </button>

                <!-- Divider -->
                <div class="card-divider" aria-hidden="true">
                    <span>Secured by N1 Solution</span>
                </div>

                <!-- Security indicators -->
                <div class="security-row" role="list" aria-label="Security features">
                    <div class="sec-item" role="listitem">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <span>SSL Encrypted</span>
                    </div>
                    <div class="sec-item" role="listitem">
                        <i class="fas fa-shield-halved" aria-hidden="true"></i>
                        <span>Enterprise Security</span>
                    </div>
                    <div class="sec-item" role="listitem">
                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                        <span>24/7 Monitoring</span>
                    </div>
                </div>

            </form>
        </div>
    </section>

</div><!-- .page-wrapper -->

<script>
/* ═══════════════════════════════════════
   PARTICLES
═══════════════════════════════════════ */
(function(){
    const canvas = document.getElementById('particles-canvas');
    const ctx    = canvas.getContext('2d');
    let W, H, pts, conns;

    function resize(){
        W = canvas.width  = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }

    function createParticles(){
        const count = Math.floor((W * H) / 18000);
        pts = Array.from({ length: count }, () => ({
            x:  Math.random() * W,
            y:  Math.random() * H,
            r:  Math.random() * 1.2 + 0.3,
            dx: (Math.random() - 0.5) * 0.25,
            dy: (Math.random() - 0.5) * 0.25,
            a:  Math.random() * 0.5 + 0.15
        }));
    }

    function draw(){
        ctx.clearRect(0, 0, W, H);

        // Draw connection lines between nearby particles
        for(let i = 0; i < pts.length; i++){
            for(let j = i+1; j < pts.length; j++){
                const dx = pts[i].x - pts[j].x;
                const dy = pts[i].y - pts[j].y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if(dist < 120){
                    ctx.beginPath();
                    ctx.moveTo(pts[i].x, pts[i].y);
                    ctx.lineTo(pts[j].x, pts[j].y);
                    ctx.strokeStyle = `rgba(6,182,212,${0.08 * (1 - dist/120)})`;
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                }
            }
        }

        // Draw particles
        pts.forEach(p => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(6,182,212,${p.a})`;
            ctx.fill();

            p.x += p.dx; p.y += p.dy;
            if(p.x < 0) p.x = W; if(p.x > W) p.x = 0;
            if(p.y < 0) p.y = H; if(p.y > H) p.y = 0;
        });

        requestAnimationFrame(draw);
    }

    resize(); createParticles(); draw();
    window.addEventListener('resize', () => { resize(); createParticles(); });
})();

/* ═══════════════════════════════════════
   PASSWORD TOGGLE
═══════════════════════════════════════ */
(function(){
    const toggle = document.getElementById('pwd-toggle');
    const input  = document.getElementById('password');
    const icon   = document.getElementById('pwd-icon');

    toggle.addEventListener('click', () => {
        const isPass = input.type === 'password';
        input.type   = isPass ? 'text' : 'password';
        icon.className = isPass ? 'fas fa-eye-slash' : 'fas fa-eye';
        toggle.setAttribute('aria-label',  isPass ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', isPass ? 'true' : 'false');
    });
})();

/* ═══════════════════════════════════════
   FORM SUBMIT — LOADING STATE
═══════════════════════════════════════ */
(function(){
    const form    = document.getElementById('signin-form');
    const btn     = document.getElementById('signin-btn');
    const spinner = btn.querySelector('.spinner');
    const text    = btn.querySelector('.btn-text');
    const arrow   = btn.querySelector('.btn-arrow');

    form.addEventListener('submit', function(e){
        // Basic client-side validation
        const user = document.getElementById('username').value.trim();
        const pass = document.getElementById('password').value;
        if(!user || !pass) return; // let PHP handle the error

        // Show loading state
        btn.disabled = true;
        btn.classList.add('loading');
        text.textContent = 'Signing in...';
        arrow.style.display = 'none';

        // Safety reset after 6s if redirect fails
        setTimeout(() => {
            btn.disabled = false;
            btn.classList.remove('loading');
            text.textContent = 'Sign In';
            arrow.style.display = '';
        }, 6000);
    });
})();

/* ═══════════════════════════════════════
   KEYBOARD NAVIGATION ENHANCEMENT
═══════════════════════════════════════ */
document.addEventListener('keydown', function(e){
    if(e.key === 'Tab'){
        document.body.classList.add('keyboard-nav');
    }
});
document.addEventListener('mousedown', function(){
    document.body.classList.remove('keyboard-nav');
});
</script>

<style>
/* Keyboard nav focus styles — only shown during keyboard navigation */
.keyboard-nav .field-input:focus,
.keyboard-nav .btn-signin:focus-visible,
.keyboard-nav .pwd-toggle:focus-visible,
.keyboard-nav .forgot-link:focus-visible {
    outline: 2px solid var(--cyan);
    outline-offset: 3px;
}
</style>

</body>
</html>
