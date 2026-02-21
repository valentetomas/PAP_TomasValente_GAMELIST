<?php
include 'includes/db.php';

$msg = "";
$msgClass = "";
$validToken = false;
$token = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verifica se o token é válido e não expirou
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $validToken = true;
        $user = $result->fetch_assoc();
    } else {
        $msg = "Este token é inválido ou já expirou.";
        $msgClass = "error";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token'])) {
    $token = $_POST['token'];
    $newPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    if (strlen($newPassword) < 6) {
        $msg = "A password deve ter pelo menos 6 caracteres!";
        $msgClass = "error";
        $validToken = true;
    } elseif ($newPassword !== $confirmPassword) {
        $msg = "As passwords não coincidem!";
        $msgClass = "error";
        $validToken = true;
    } else {
        // Verifica o token novamente
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Atualiza a password e remove o token
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $updateStmt->bind_param("si", $passwordHash, $user['id']);
            
            if ($updateStmt->execute()) {
                $msg = "Password alterada com sucesso! Já podes fazer login.";
                $msgClass = "success";
                $validToken = false;
            } else {
                $msg = "Erro ao alterar password. Tenta novamente.";
                $msgClass = "error";
                $validToken = true;
            }
        } else {
            $msg = "Token inválido ou expirado.";
            $msgClass = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Password - GameList</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/logo_favicon.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #00b4ff;
            --secondary: #8a2be2;
            --bg-dark: #0b0c0f;
            --border: rgba(255, 255, 255, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }

        body {
            min-height: 100vh;
            background-color: var(--bg-dark);
            color: #fff;
        }

        .auth-layout {
            width: 100%;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            overflow: hidden;
        }

        .games-panel {
            position: relative;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            background: #08090c;
            overflow: hidden;
        }

        .banner-bg-container {
            position: absolute;
            inset: 0;
            z-index: 1;
            -webkit-mask-image: linear-gradient(to right, black 15%, transparent 98%);
            mask-image: linear-gradient(to right, black 15%, transparent 98%);
        }

        .banner-game-covers {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(125px, 1fr));
            gap: 14px;
            width: 112%;
            margin-left: -6%;
            padding: 20px;
            opacity: 0.48;
            transform: rotate(-2deg) scale(1.05);
        }

        .banner-cover {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 5px 16px rgba(0, 0, 0, 0.5);
            aspect-ratio: 2/3;
            object-fit: cover;
            animation: scrollUp 60s linear infinite;
            will-change: transform;
        }

        .banner-cover:nth-child(2n) { animation-duration: 75s; margin-top: -40px; }
        .banner-cover:nth-child(3n) { animation-duration: 55s; margin-top: 20px; }
        .banner-cover:nth-child(5n) { animation-duration: 85s; }

        @keyframes scrollUp {
            0% { transform: translateY(0); }
            100% { transform: translateY(-400px); }
        }

        .games-overlay {
            position: absolute;
            inset: 0;
            z-index: 2;
            background:
                linear-gradient(90deg, rgba(11,12,15,0.15) 0%, rgba(11,12,15,0.65) 72%, rgba(11,12,15,0.95) 100%),
                radial-gradient(circle at 20% 20%, rgba(0,180,255,0.18), transparent 45%);
        }

        .games-content {
            position: absolute;
            inset: 0;
            z-index: 3;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 34px;
        }

        .games-content h2 {
            font-size: clamp(1.9rem, 3vw, 3rem);
            line-height: 1.05;
            margin-bottom: 10px;
            text-shadow: 0 6px 24px rgba(0, 0, 0, 0.75);
        }

        .games-content p {
            color: #c9d0db;
            max-width: 420px;
            font-size: 0.98rem;
            line-height: 1.5;
        }

        .auth-panel {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: radial-gradient(circle at top right, rgba(138, 43, 226, 0.14), transparent 50%), #0b0c10;
        }

        .reset-card {
            position: relative;
            z-index: 2;
            background: rgba(20, 20, 25, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 450px;
        }

        .reset-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        }

        .logo-area { text-align: center; margin-bottom: 25px; }

        .logo-link {
            display: inline-block;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
        }

        .logo-link:hover {
            transform: scale(1.1);
            filter: drop-shadow(0 0 15px rgba(0, 180, 255, 0.6));
        }

        .brand-logo {
            display: inline-block;
            margin-bottom: 8px;
            font-family: 'Inter', sans-serif;
            font-weight: 900;
            font-size: 2rem;
            color: #fff;
            letter-spacing: -1px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        h1 {
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 25px;
        }

        .input-group { position: relative; margin-bottom: 20px; }

        label {
            display: block;
            color: #d1d5db;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 14px 50px 14px 45px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-group i {
            position: absolute;
            left: 16px;
            top: 42px;
            color: #6b7280;
            transition: 0.3s;
            pointer-events: none;
        }

        input::placeholder { color: #4b5563; }
        input:focus {
            border-color: var(--primary);
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 4px rgba(0, 180, 255, 0.15);
        }
        input:focus + i { color: var(--primary); }

        button, .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            background-size: 200% auto;
            transition: 0.4s;
            box-shadow: 0 4px 20px rgba(0, 180, 255, 0.25);
            display: block;
            text-align: center;
            text-decoration: none;
            margin-top: 10px;
        }

        button:hover, .btn:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(138, 43, 226, 0.4);
        }

        .msg-box {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .msg-box.error {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
            animation: shake 0.4s ease-in-out;
        }

        .msg-box.success {
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .links { margin-top: 25px; text-align: center; }
        .links a {
            color: #9ca3af;
            font-size: 14px;
            text-decoration: none;
            transition: 0.3s;
        }
        .links a:hover { color: #fff; }

        @media (max-width: 980px) {
            .auth-layout {
                grid-template-columns: 1fr;
            }

            .games-panel {
                min-height: 270px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }

            .banner-bg-container {
                -webkit-mask-image: linear-gradient(to bottom, black 20%, transparent 98%);
                mask-image: linear-gradient(to bottom, black 20%, transparent 98%);
            }
        }

        @media (max-width: 480px) {
            .auth-panel { padding: 14px; }
            .reset-card { padding: 34px 22px; }
            .games-content { padding: 20px; }
            .games-content h2 { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
    <div class="auth-layout">
        <aside class="games-panel" aria-hidden="true">
            <div class="banner-bg-container">
                <div class="banner-game-covers" id="auth-banner-covers"></div>
            </div>
            <div class="games-overlay"></div>
            <div class="games-content">
                <h2>Define uma nova password.</h2>
                <p>Protege a tua conta e volta a aceder ao teu perfil em segundos.</p>
            </div>
        </aside>

        <main class="auth-panel">
            <div class="reset-card">
                <div class="logo-area">
                    <a href="index.php" class="logo-link" title="Voltar ao início">
                        <span class="brand-logo">GameList</span>
                    </a>
                </div>

                <h1>Nova Password</h1>

                <?php if ($msg): ?>
                    <div class="msg-box <?php echo $msgClass; ?>">
                        <?php if($msgClass == 'success'): ?>
                            <i class="fa-solid fa-check-circle"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-circle-exclamation"></i>
                        <?php endif; ?>
                        <span><?php echo $msg; ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($validToken): ?>
                    <form method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="input-group">
                            <label for="password">Nova Password</label>
                            <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
                            <i class="fa-solid fa-lock"></i>
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">Confirmar Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Repete a password" required>
                            <i class="fa-solid fa-lock"></i>
                        </div>

                        <button type="submit">Alterar Password</button>
                    </form>
                <?php elseif ($msgClass === "success"): ?>
                    <a href="login.php" class="btn">Ir para iniciar sessão</a>
                <?php else: ?>
                    <a href="forgot_password.php" class="btn">Pedir Novo Link</a>
                <?php endif; ?>

                <div class="links">
                    <a href="index.php">← Voltar à Página Inicial</a>
                </div>
            </div>
        </main>
    </div>

    <script>
        const apiKey = '5fd330b526034329a8f0d9b6676241c5';

        async function loadAuthBannerCovers() {
            try {
                const res = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&page_size=40&ordering=-added`);
                const data = await res.json();
                const container = document.getElementById('auth-banner-covers');
                if (!container || !Array.isArray(data.results)) return;

                container.innerHTML = '';
                data.results.forEach(game => {
                    if (game.background_image) {
                        const img = document.createElement('img');
                        img.src = game.background_image.replace('/media/games/', '/media/crop/600/400/games/');
                        img.className = 'banner-cover';
                        img.loading = 'lazy';
                        img.alt = '';
                        container.appendChild(img);
                    }
                });
            } catch (e) {
                console.error('Erro a carregar capas de jogos:', e);
            }
        }

        loadAuthBannerCovers();
    </script>
</body>
</html>