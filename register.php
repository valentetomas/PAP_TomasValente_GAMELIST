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
            --bg-dark: #0f0f12;
            --glass: rgba(255, 255, 255, 0.05);
            --border: rgba(255, 255, 255, 0.1);
            --success: #2ecc71;
            --error: #e74c3c;
            --warning: #f1c40f;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--bg-dark);
            overflow: hidden;
            position: relative;
        }

        /* --- Fundo Animado (Aurora) --- */
        .background-anim {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            overflow: hidden;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.6;
            animation: float 20s infinite ease-in-out alternate;
        }

        .orb-1 { top: -10%; left: -10%; width: 50vw; height: 50vw; background: radial-gradient(circle, var(--secondary), transparent 70%); animation-delay: 0s; }
        .orb-2 { bottom: -10%; right: -10%; width: 60vw; height: 60vw; background: radial-gradient(circle, var(--primary), transparent 70%); animation-delay: -5s; }
        .orb-3 { top: 40%; left: 40%; width: 30vw; height: 30vw; background: radial-gradient(circle, #ff007a, transparent 70%); animation-duration: 25s; opacity: 0.4; }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0, 0) scale(1); }
        }

        .grid-overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 2;
            pointer-events: none;
        }

        /* --- Cartão de Registo --- */
        .register-card {
            position: relative;
            z-index: 10;
            background: rgba(20, 20, 25, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            padding: 40px 40px;
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

        .logo-area img { width: 70px; margin-bottom: 5px; }

        h1 { font-size: 26px; font-weight: 700; color: #fff; margin-bottom: 8px; }
        .subtitle { color: #9ca3af; font-size: 14px; margin-bottom: 30px; }

        /* --- Inputs e Ícones --- */
        .input-group { position: relative; margin-bottom: 15px; text-align: left; }

        input {
            width: 100%;
            padding: 14px 16px 14px 45px; /* Espaço para o ícone à esquerda */
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
        }

        /* Input específico para password (mais espaço à direita para o olho) */
        input[type="password"], input[name="password"] {
            padding-right: 50px; 
        }

        .input-group > i.fa-input-icon {
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
            color: #6b7280;
            transition: 0.3s;
        }
        .toggle-password:hover { color: #fff; }

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

        @media (max-width: 480px) {
            .register-card { padding: 40px 25px; }
            .orb { opacity: 0.4; }
        }
    </style>
</head>
<body>

    <div class="background-anim">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="grid-overlay"></div>

    <div class="register-card">
        <div class="logo-area">
            <a href="index.php" class="logo-link" title="Voltar ao início">
                <img src="img/logo.png" alt="Logo" onerror="this.style.display='none'; document.getElementById('default-icon').style.display='block';">
                <i id="default-icon" class="fa-solid fa-gamepad" style="font-size: 50px; color: var(--primary); display: none;"></i>
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

    <script>
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
    </script>
</body>
</html>