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

        /* --- Cartão de Reset --- */
        .reset-card {
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
        }

        .reset-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        }

        /* Logo Area */
        .logo-area { text-align: center; margin-bottom: 25px; }
        .logo-area img { width: 70px; margin-bottom: 5px; }

        h1 { 
            text-align: center;
            font-size: 26px; 
            font-weight: 700; 
            color: #fff; 
            margin-bottom: 25px; 
        }

        /* Inputs */
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
            top: 42px; /* Ajustado para alinhar com o input considerando o label */
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

        /* Mensagens */
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

        @media (max-width: 480px) {
            .reset-card { padding: 40px 25px; }
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

    <div class="reset-card">
        <div class="logo-area">
            <img src="img/logo.png" alt="Logo" onerror="this.style.display='none'; document.getElementById('default-icon').style.display='inline-block';">
            <i id="default-icon" class="fa-solid fa-gamepad" style="font-size: 50px; color: var(--primary); display: none;"></i>
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
            <a href="login.php" class="btn">Ir para Login</a>
        <?php else: ?>
            <a href="forgot_password.php" class="btn">Pedir Novo Link</a>
        <?php endif; ?>

        <div class="links">
            <a href="index.php">← Voltar à Página Inicial</a>
        </div>
    </div>

</body>
</html>