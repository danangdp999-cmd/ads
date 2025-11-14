<?php
// signup.php — OGORooms sign up page

session_start();

// Kalau sudah login, langsung lempar ke home
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// pakai config DB dari ogo-api
require_once __DIR__ . '/ogo-api/config.php';

$error  = '';
$success = false;
$nameValue  = '';
$emailValue = '';

// proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    $nameValue  = $name;
    $emailValue = $email;

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please fill all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please use a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Password confirmation does not match.';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            // cek email sudah dipakai atau belum
            $check = $pdo->prepare('SELECT id FROM ogo_users WHERE email = ? LIMIT 1');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'This email is already registered. Try logging in.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);

                $ins = $pdo->prepare(
                    'INSERT INTO ogo_users (email, password_hash, name, role, status, created_at)
                     VALUES (?, ?, ?, "guest", "active", NOW())'
                );
                $ins->execute([$email, $hash, $name]);

                $userId = (int)$pdo->lastInsertId();

                // auto login setelah signup
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role']  = 'guest';

                $success = true;
                header('Location: index.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Server error while creating account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>OGORooms – Sign up</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f7f7f8;
            --bg-card: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --accent: #b2743b;
            --accent-soft: #f6e5d6;
            --border-subtle: #e5e7eb;
            --pill-bg: #f3f4f6;
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.12);
            --radius-xl: 999px;
            --radius-lg: 24px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
        }

        a { color: inherit; text-decoration: none; }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* NAVBAR sama style login/home */
        .nav {
            position: sticky;
            top: 0;
            z-index: 40;
            backdrop-filter: blur(16px);
            background: rgba(255,255,255,0.95);
            border-bottom: 1px solid rgba(229,231,235,0.8);
        }
        .nav-inner {
            max-width: 1240px;
            margin: 0 auto;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .nav-left { display:flex;align-items:center;gap:10px; }
        .nav-logo-circle {
            width: 34px;height:34px;border-radius:50%;
            background:var(--accent);
            display:flex;align-items:center;justify-content:center;
            color:#fff;font-weight:700;letter-spacing:0.04em;font-size:14px;
        }
        .nav-brand-text { display:flex;flex-direction:column;line-height:1.1; }
        .nav-brand-title { font-size:17px;font-weight:700;letter-spacing:0.08em; }
        .nav-brand-sub {
            font-size:10px;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.18em;
        }
        .nav-center {
            display:flex;gap:18px;align-items:center;font-size:14px;font-weight:500;
        }
        .nav-center button {
            border:none;background:transparent;padding:8px 0;cursor:pointer;position:relative;color:#4b5563;
        }
        .nav-center button.active { color:var(--text-main); }
        .nav-center button.active::after{
            content:"";position:absolute;left:0;right:0;bottom:-6px;margin-inline:auto;
            width:18px;height:3px;border-radius:999px;background:var(--accent);
        }
        .nav-right {display:flex;align-items:center;gap:10px;font-size:14px;}
        .nav-link {
            padding:6px 10px;border-radius:var(--radius-xl);cursor:pointer;color:#374151;
        }
        .nav-link:hover {background:#f3f4f6;}
        .nav-pill{
            border-radius:var(--radius-xl);
            border:1px solid var(--border-subtle);
            padding:5px 10px 5px 14px;
            display:flex;align-items:center;gap:12px;
            background:#ffffff;cursor:pointer;
        }
        .nav-pill-icon{
            width:32px;height:32px;border-radius:999px;
            background:#4b5563;display:flex;align-items:center;justify-content:center;
            color:#fff;font-size:13px;font-weight:600;
        }

        /* MAIN */
        .main {
            flex: 1;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:30px 16px;
        }

        .auth-wrapper {
            max-width: 440px;
            width: 100%;
        }

        .auth-card {
            background: var(--bg-card);
            border-radius: 24px;
            border: 1px solid rgba(229,231,235,0.9);
            box-shadow: 0 20px 45px rgba(148,163,184,0.25);
            padding: 22px 22px 18px;
        }

        .auth-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .auth-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        .field-group {
            margin-bottom: 12px;
        }
        .field-label {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .input {
            width: 100%;
            border-radius: 999px;
            border: 1px solid rgba(209,213,219,1);
            padding: 9px 14px;
            font-size: 14px;
            outline: none;
            background: #f9fafb;
        }
        .input:focus {
            border-color: var(--accent);
            background: #ffffff;
            box-shadow: 0 0 0 1px rgba(178,116,59,0.3);
        }

        .btn-primary {
            width: 100%;
            border-radius: 999px;
            border: none;
            padding: 9px 16px;
            background: var(--accent);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(178,116,59,0.45);
            margin-top: 6px;
        }

        .auth-footer {
            margin-top: 16px;
            font-size: 12px;
            color: var(--text-muted);
            display:flex;
            justify-content:space-between;
            gap:12px;
            flex-wrap:wrap;
        }

        .auth-footer a {
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .error-box {
            margin-bottom: 12px;
            font-size: 13px;
            color: #b91c1c;
            background: #fef2f2;
            border-radius: 14px;
            padding: 8px 12px;
            border: 1px solid #fecaca;
        }

        @media(max-width:640px){
            .auth-card {
                padding:18px 16px 16px;
            }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- NAV -->
    <header class="nav">
        <div class="nav-inner">
            <div class="nav-left">
                <div class="nav-logo-circle">OG</div>
                <div class="nav-brand-text">
                    <span class="nav-brand-title">OGOROOMS</span>
                    <span class="nav-brand-sub">HOMES · EXPERIENCES · SERVICES</span>
                </div>
            </div>

            <div class="nav-center">
                <button type="button" onclick="window.location.href='index.php';">Homes</button>
                <button type="button">Experiences</button>
                <button type="button">Services</button>
            </div>

            <div class="nav-right">
                <a href="host-start.php" class="nav-link">Become a host</a>
                <a href="login.php" class="nav-link">Log in</a>
                <button class="nav-pill" type="button">
                    <span style="font-size:16px;">🌐</span>
                    <span class="nav-pill-icon">H</span>
                </button>
            </div>
        </div>
    </header>

    <!-- MAIN -->
    <main class="main">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-title">Create your OGORooms account</div>
                <div class="auth-sub">One account for traveling and hosting. You can upgrade to host later.</div>

                <?php if ($error !== ''): ?>
                    <div class="error-box">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="signup.php" autocomplete="on">
                    <div class="field-group">
                        <div class="field-label">Full name</div>
                        <input
                            type="text"
                            name="name"
                            class="input"
                            placeholder="Your name"
                            value="<?php echo htmlspecialchars($nameValue, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        />
                    </div>

                    <div class="field-group">
                        <div class="field-label">Email</div>
                        <input
                            type="email"
                            name="email"
                            class="input"
                            placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>"
                            required
                        />
                    </div>

                    <div class="field-group">
                        <div class="field-label">Password</div>
                        <input
                            type="password"
                            name="password"
                            class="input"
                            placeholder="At least 8 characters"
                            required
                        />
                    </div>

                    <div class="field-group">
                        <div class="field-label">Confirm password</div>
                        <input
                            type="password"
                            name="password_confirm"
                            class="input"
                            placeholder="Repeat your password"
                            required
                        />
                    </div>

                    <button type="submit" class="btn-primary">Create account</button>
                </form>

                <div class="auth-footer">
                    <span>Already have an account? <a href="login.php">Log in</a></span>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
