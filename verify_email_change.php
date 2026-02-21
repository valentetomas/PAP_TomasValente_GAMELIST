<?php
include 'includes/db.php';

$message = "";
$messageClass = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Busca o user com este token
    $stmt = $conn->prepare("SELECT id, username, pending_email FROM users WHERE email_change_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verifica se o novo email já não está a ser usado
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $user['pending_email'], $user['id']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $message = "Este email já está a ser usado por outra conta.";
            $messageClass = "error";
        } else {
            // Atualiza o email
            $updateStmt = $conn->prepare("UPDATE users SET email = ?, pending_email = NULL, email_change_token = NULL WHERE id = ?");
            $updateStmt->bind_param("si", $user['pending_email'], $user['id']);
            
            if ($updateStmt->execute()) {
                $message = "Email alterado com sucesso! O teu novo email já está ativo.";
                $messageClass = "success";
            } else {
                $message = "Erro ao alterar email. Tenta novamente.";
                $messageClass = "error";
            }
        }
    } else {
        $message = "Este token é inválido ou expirou.";
        $messageClass = "error";
    }
} else {
    $message = "Token de verificação não fornecido.";
    $messageClass = "error";
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alteração de Email - GameList</title>
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

        .verify-card {
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
            max-width: 500px;
            text-align: center;
        }

        .verify-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        }

        .logo-area { margin-bottom: 25px; }

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

        h1 { font-size: 28px; font-weight: 700; color: #fff; margin-bottom: 15px; }

        .msg-box {
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            font-size: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 15px;
            line-height: 1.5;
        }

        .msg-box i { font-size: 40px; margin-bottom: 5px; }

        .msg-box.success {
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }

        .msg-box.error {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            background-size: 200% auto;
            transition: 0.4s;
            box-shadow: 0 4px 20px rgba(0, 180, 255, 0.25);
        }

        .btn:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(138, 43, 226, 0.4);
            color: #fff;
        }

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
            .verify-card { padding: 34px 22px; }
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
                <h2>Email atualizado.</h2>
                <p>A tua conta continua segura e pronta para acompanhar os teus jogos.</p>
            </div>
        </aside>

        <main class="auth-panel">
            <div class="verify-card">
                <div class="logo-area">
                    <a href="index.php" class="logo-link" title="Voltar ao início">
                        <span class="brand-logo">GameList</span>
                    </a>
                </div>

                <h1>Alterar Email</h1>

                <div class="msg-box <?php echo $messageClass; ?>">
                    <?php if ($messageClass === "success"): ?>
                        <i class="fa-solid fa-circle-check"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-xmark"></i>
                    <?php endif; ?>

                    <span><?php echo $message; ?></span>
                </div>

                <?php if ($messageClass === "success"): ?>
                    <a href="settings.php" class="btn">Ir para Definições</a>
                <?php else: ?>
                    <a href="index.php" class="btn">Voltar ao Início</a>
                <?php endif; ?>
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