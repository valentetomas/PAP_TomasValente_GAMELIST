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

        /* --- Cartão de Verificação --- */
        .verify-card {
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
            max-width: 500px;
            text-align: center;
        }

        .verify-card::before {
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

        h1 { font-size: 26px; font-weight: 700; color: #fff; margin-bottom: 15px; }

        /* Mensagens */
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

        /* Estilo Sucesso */
        .msg-box.success {
            background: rgba(46, 204, 113, 0.1);
            border: 1px solid rgba(46, 204, 113, 0.3);
            color: #2ecc71;
        }
        
        /* Estilo Erro */
        .msg-box.error {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.3);
            color: #ff6b6b;
        }

        /* Botão */
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

        @media (max-width: 480px) {
            .verify-card { padding: 40px 25px; }
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

    <div class="verify-card">
        <div class="logo-area">
            <a href="index.php" class="logo-link" title="Voltar ao início">
                <img src="img/logo.png" alt="Logo" onerror="this.style.display='none'; document.getElementById('default-icon').style.display='block';">
                <i id="default-icon" class="fa-solid fa-gamepad" style="font-size: 50px; color: var(--primary); display: none;"></i>
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

</body>
</html>