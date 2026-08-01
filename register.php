<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username && $password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match. Please try again.";
        } elseif (strlen($password) < 4) {
            $error = "Password must be at least 4 characters long.";
        } else {
            // Check if username exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->execute([$username]);
            if ($checkStmt->fetch()) {
                $error = "Username already taken. Please choose another.";
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'staff')");
                    $stmt->execute([$username, $hash]);
                    
                    $success = "Account created successfully! You can now sign in.";
                } catch (Exception $e) {
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — N1 Inventory</title>
    <meta name="description" content="Create a new account for N1 Inventory System.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>

/* ═══════════════ RESET ═══════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body {
    width: 100%; height: 100%;
    font-family: 'Inter', sans-serif;
    background: #03141a;
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

/* ═══════════════ MAIN LAYOUT ═══════════════ */
.page {
    position: relative; z-index: 10;
    width: 100vw; min-height: 100vh;
    display: flex; flex-direction: column;
    padding: 12px 28px;
}

/* ═══════════════ TOP HEADER ═══════════════ */
.header {
    display: flex; align-items: center; justify-content: space-between;
    width: 100%; height: 40px;
}
.brand {
    display: flex; align-items: center; gap: 10px; text-decoration: none;
}
.brand-logo-img { width: 28px; height: 28px; }
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
    padding: 10px 0;
}

/* ════════════════════════════════
   CENTER FLOATING GLASS CARD
════════════════════════════════ */
.rp {
    position: relative; z-index: 10;
    display: flex; flex-direction: column;
    justify-content: center;
    width: 100%; max-width: 420px;
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

/* alerts */
.err-box {
    display: flex; align-items: flex-start; gap: 9px;
    background: rgba(240,60,90,.09);
    border: 1px solid rgba(240,60,90,.22);
    border-radius: 9px; padding: 10px 13px;
    color: #ffa8b8; font-size: 12.5px; margin-bottom: 16px;
}
.success-box {
    display: flex; align-items: flex-start; gap: 9px;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: 9px; padding: 10px 13px;
    color: #6ee7b7; font-size: 12.5px; margin-bottom: 16px;
}

/* form fields */
.fg { margin-bottom: 14px; }
.flabel {
    display: block; font-size: 10px; font-weight: 600;
    letter-spacing: .14em; text-transform: uppercase;
    color: rgba(255,255,255,.38); margin-bottom: 5px;
}
.fwrap { position: relative; display: flex; align-items: center; }
.ficon {
    position: absolute; left: 13px;
    color: rgba(255,255,255,.22); pointer-events: none;
    display: flex; transition: color .25s;
}
.ficon svg { width: 15px; height: 15px; }

.finput {
    width: 100%;
    background: #0b1a2e !important;
    border: 1px solid #1c3050;
    border-radius: 10px;
    padding: 10px 12px 10px 40px;
    font-size: 13.5px; font-family: 'Inter', sans-serif;
    color: #fff !important; outline: none;
    caret-color: #00d4ff;
    transition: border-color .25s, box-shadow .25s;
}
.finput::placeholder { color: rgba(255,255,255,.18); font-size: 13px; }
.fwrap:focus-within .finput {
    border-color: rgba(0,212,255,.48);
    box-shadow: 0 0 0 3px rgba(0,212,255,.09);
}
.fwrap:focus-within .ficon { color: #00d4ff; }

/* button */
.signin-btn {
    width: 100%; min-height: 44px;
    border: none; border-radius: 12px;
    background: linear-gradient(135deg, #0284c7 0%, #2563eb 50%, #7c3aed 100%);
    color: #fff; font-family: 'Outfit', sans-serif;
    font-size: 14.5px; font-weight: 600;
    cursor: pointer; position: relative; overflow: hidden;
    transition: transform .18s, box-shadow .25s, filter .2s;
    box-shadow: 0 6px 24px rgba(37, 99, 235, 0.4);
    margin-top: 6px; margin-bottom: 16px;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.signin-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(124, 58, 237, 0.5);
}

.switch-link {
    text-align: center; font-size: 12.5px; color: rgba(255,255,255,.4);
}
.switch-link a {
    color: #00d4ff; font-weight: 600; text-decoration: none;
}
.switch-link a:hover { text-decoration: underline; }

</style>
</head>
<body>

<div class="bg"></div>
<canvas id="ptcl"></canvas>

<div class="page">

    <!-- Header Navigation -->
    <header class="header">
        <a href="login.php" class="brand">
            <svg class="brand-logo-img" viewBox="0 0 24 24" fill="none">
                <path d="M12 2L2 7l10 5 10-5-10-5z" stroke="#00d4ff" stroke-width="2" stroke-linejoin="round"/>
                <path d="M2 17l10 5 10-5M2 12l10 5 10-5" stroke="#00ffcc" stroke-width="2" stroke-linejoin="round"/>
            </svg>
            <span class="brand-name">N1 Inventory</span>
        </a>
        <a href="login.php" class="header-signin-btn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            Sign In
        </a>
    </header>

    <!-- Main Section -->
    <main class="main-container">

        <!-- Form Card -->
        <div class="rp" role="main">

            <div class="rp-brand">
                <div class="rp-ico">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="#38bdf8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="8.5" cy="7" r="4" stroke="#38bdf8" stroke-width="1.8"/>
                        <line x1="20" y1="8" x2="20" y2="14" stroke="#818cf8" stroke-width="1.8" stroke-linecap="round"/>
                        <line x1="23" y1="11" x2="17" y2="11" stroke="#818cf8" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </div>
                <h2 class="rp-title">Create Account</h2>
                <p class="rp-sub">Join N1 Inventory to manage assets & stock</p>
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

            <!-- Success -->
            <?php if ($success): ?>
            <div class="success-box" role="alert">
                <svg width="15" height="15" viewBox="0 0 20 20" fill="none" style="flex-shrink:0;margin-top:1px">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" fill="#10b981"/>
                </svg>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="on">

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
                            placeholder="Choose a username" required autofocus spellcheck="false" autocapitalize="none">
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
                            placeholder="Create a strong password" required>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="fg">
                    <label class="flabel" for="confirm_password">Confirm Password</label>
                    <div class="fwrap">
                        <span class="ficon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" class="finput"
                            placeholder="Re-enter password" required>
                    </div>
                </div>

                <!-- Register Button -->
                <button type="submit" class="signin-btn">
                    Create Account
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>

                <div class="switch-link">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>

            </form>
        </div><!-- /rp -->

    </main>

</div><!-- /page -->

<script>
/* Particles */
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
</script>
</body>
</html>
