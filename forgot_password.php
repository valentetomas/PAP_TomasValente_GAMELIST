<?php
include 'includes/db.php';
require 'includes/email_helper.php';

$msg = "";
$msgClass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $msg = "⚠️ Email inválido!";
        $msgClass = "error";
    } else {
        // Verifica se o email existe
        $stmt = $conn->prepare("SELECT id, username, email_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if ($user['email_verified'] == 0) {
                $msg = "⚠️ Email não verificado. Verifica primeiro o teu email.";
                $msgClass = "error";
            } else {
                // Gera token de reset (válido por 1 hora)
                $resetToken = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Guarda o token na BD
                $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
                $updateStmt->bind_param("ssi", $resetToken, $expires, $user['id']);
                
                if ($updateStmt->execute()) {
                    // Envia email
                    if (sendPasswordResetEmail($email, $user['username'], $resetToken)) {
                        $msg = "✅ Email enviado! Verifica a tua caixa de entrada.";
                        $msgClass = "success";
                    } else {
                        $msg = "❌ Erro ao enviar email. Tenta novamente mais tarde.";
                        $msgClass = "error";
                    }
                }
            }
        } else {
            // Por segurança, mostra a mesma mensagem
            $msg = "✅ Se o email existir, receberás instruções de recuperação.";
            $msgClass = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Password - GameList</title>
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

        /* --- Fundo Animado --- */
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

        /* --- Cartão de Recuperação --- */
        .recover-card {
            position: relative;
            z-index: 10;
            background: rgba(20, 20, 25, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .recover-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        }

        /* Logo Area */
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

        h1 { font-size: 26px; font-weight: 700; color: #fff; margin-bottom: 10px; }
        .subtitle { color: #9ca3af; font-size: 14px; margin-bottom: 30px; line-height: 1.5; }

        /* Inputs */
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }

        input {
            width: 100%;
            padding: 14px 16px 14px 45px;
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
            top: 50%;
            transform: translateY(-50%);
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

        /* Botão */
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

        /* Mensagens */
        .msg {
            margin-top: 20px;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .msg.error {
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
            animation: shake 0.4s ease-in-out;
        }

        .msg.success {
            background: rgba(46, 204, 113, 0.15);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .links { margin-top: 25px; font-size: 14px; }
        .links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .links a:hover {
            color: var(--secondary);
            text-shadow: 0 0 10px rgba(138, 43, 226, 0.5);
            transform: translateX(-3px);
        }

        @media (max-width: 480px) {
            .recover-card { padding: 40px 25px; }
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

    <div class="recover-card">
        <div class="logo-area">
            <a href="index.php" class="logo-link" title="Voltar ao início">
                <img src="img/logo.png" alt="Logo" onerror="this.style.display='none'; document.getElementById('default-icon').style.display='block';">
                <i id="default-icon" class="fa-solid fa-gamepad" style="font-size: 50px; color: var(--primary); display: none;"></i>
            </a>
            <h1>Recuperar Conta</h1>
            <p class="subtitle">Introduz o teu email e enviaremos instruções para recuperares a tua password.</p>
        </div>

        <form method="POST">
            <div class="input-group">
                <input type="email" id="email" name="email" placeholder="O teu email registado" required>
                <i class="fa-regular fa-envelope"></i>
            </div>

            <button type="submit">Enviar Email de Recuperação</button>
        </form>

        <?php if ($msg): ?>
            <div class="msg <?php echo $msgClass; ?>">
                <?php if($msgClass == 'success'): ?>
                    <i class="fa-solid fa-check-circle"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-exclamation"></i>
                <?php endif; ?>
                <span><?php echo $msg; ?></span>
            </div>
        <?php endif; ?>

        <div class="links">
            <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao Login</a>
        </div>
    </div>

</body>
</html>