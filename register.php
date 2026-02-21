<?php
include 'includes/db.php';
require 'includes/email_helper.php';
require_once 'includes/achievements.php';

$msg = "";
$msgClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password']);

    if (!$email) {
        $msg = "⚠️ Email inválido!";
        $msgClass = "error";
    } elseif (strlen($password) < 6) {
        $msg = "⚠️ A password deve ter pelo menos 6 caracteres!";
        $msgClass = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $msg = "⚠️ Este email já está registado!";
            $msgClass = "error";
        } else {
            // Gera token de verificação
            $verificationToken = bin2hex(random_bytes(32));
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, verification_token, email_verified) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("ssss", $username, $email, $passwordHash, $verificationToken);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;

                // Cria listas padrão
                $defaultLists = ["Favoritos", "Jogar mais tarde", "Jogos jogados"];
                foreach ($defaultLists as $listName) {
                    $createList = $conn->prepare("INSERT INTO lists (user_id, name) VALUES (?, ?)");
                    $createList->bind_param("is", $user_id, $listName);
                    $createList->execute();
                }

                // Verificar conquistas (Early Adopter - criar conta)
                checkAndUnlockAchievements($user_id);

                // Envia email de verificação
                if (sendVerificationEmail($email, $username, $verificationToken)) {
                    $msg = "✅ Conta criada! Verifica o teu email para ativar a conta.";
                    $msgClass = "success";
                } else {
                    $msg = "⚠️ Conta criada, mas houve erro ao enviar email de verificação. <a href='login.php'>Tentar iniciar sessão</a>";
                    $msgClass = "warning";
                }
            } else {
                $msg = "❌ Erro ao criar conta. Tenta novamente mais tarde.";
                $msgClass = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar - GameList</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/logo_favicon.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #00b4ff;
            --secondary: #8a2be2;
            --bg-dark: #0b0c0f;
            --glass: rgba(255, 255, 255, 0.05);
            --border: rgba(255, 255, 255, 0.1);
            --success: #2ecc71;
            --error: #e74c3c;
            --warning: #f1c40f;
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

        /* --- Cartão de Registo --- */
        .register-card {
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
            max-width: 420px;
            text-align: center;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        }

        .logo-area { margin-bottom: 30px; }

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

        h1 { font-size: 28px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .subtitle { color: #9ca3af; font-size: 14px; margin-bottom: 35px; }

        /* --- Inputs e Ícones --- */
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }

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

        .input-group > i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            transition: 0.3s;
            pointer-events: none;
            z-index: 5;
        }

        input::placeholder { color: #4b5563; }
        input:focus {
            border-color: var(--primary);
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 4px rgba(0, 180, 255, 0.15);
        }
        input:focus + i { color: var(--primary); }

        /* Botão do Olho */
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password i {
            color: #6b7280;
            font-size: 16px;
            transition: color 0.3s;
        }

        .toggle-password:hover i { color: #fff; }

        /* Botão Registar */
        button {
            width: 100%;
            padding: 14px;
            margin-top: 10px;
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
        }

        button:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(138, 43, 226, 0.4);
        }

        /* Estilo das Mensagens PHP */
        .msg {
            margin-top: 20px;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            text-align: left;
            animation: shake 0.4s ease-in-out;
            line-height: 1.5;
        }

        .msg.error {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
        }

        .msg.success {
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
            animation: none; /* Sucesso não precisa tremer */
        }

        .msg.warning {
            background: rgba(241, 196, 15, 0.15);
            border: 1px solid rgba(241, 196, 15, 0.3);
            color: #f1c40f;
        }

        .msg a { color: inherit; font-weight: bold; text-decoration: underline; }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .links {
            margin-top: 25px;
            font-size: 14px;
            color: #9ca3af;
        }
        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }
        .links a:hover {
            color: var(--secondary);
            text-shadow: 0 0 10px rgba(138, 43, 226, 0.5);
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
            .register-card { padding: 34px 22px; }
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
                <h2>Cria a tua biblioteca de jogos.</h2>
                <p>Regista-te para guardar favoritos, escrever reviews e seguir a comunidade.</p>
            </div>
        </aside>

        <main class="auth-panel">
            <div class="register-card">
        <div class="logo-area">
            <a href="index.php" class="logo-link" title="Voltar ao início">
                <span class="brand-logo">GameList</span>
            </a>
            <h1>Cria a tua conta</h1>
            <p class="subtitle">Junta-te à comunidade GameList</p>
        </div>

        <form method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Nome de utilizador" required autocomplete="username">
                <i class="fa-regular fa-user fa-input-icon"></i>
            </div>

            <div class="input-group">
                <input type="email" name="email" placeholder="O teu email" required autocomplete="email">
                <i class="fa-regular fa-envelope fa-input-icon"></i>
            </div>

            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Palavra-passe (min. 6 caracteres)" required>
                <i class="fa-solid fa-lock fa-input-icon"></i>
                
                <span class="toggle-password" onclick="togglePassword()">
                    <i class="fa-regular fa-eye" id="eye-icon"></i>
                </span>
            </div>

            <button type="submit">Registar Conta</button>
        </form>

        <?php if($msg): ?>
            <p class="msg <?php echo $msgClass; ?>">
                <?php echo $msg; ?>
            </p>
        <?php endif; ?>

        <div class="links">
            <span>Já tens conta? <a href="login.php">Inicia sessão aqui</a></span>
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

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }

        loadAuthBannerCovers();
    </script>
</body>
</html>