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
<link rel="icon" type="image/png" sizes="32x32" href="img/logo.png">
<link rel="icon" type="image/png" sizes="16x16" href="img/logo.png">
<link rel="shortcut icon" href="img/logo.png">
<title>Recuperar Password - GameList</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

    body {
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #121212;
        padding: 20px;
    }

    .form-container {
        background: #1e1e1e;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.7);
        width: 100%;
        max-width: 450px;
    }

    .logo {
        text-align: center;
        font-size: 3rem;
        margin-bottom: 10px;
    }

    h1 {
        text-align: center;
        color: #ffffff;
        margin-bottom: 10px;
        font-size: 26px;
    }

    p {
        text-align: center;
        color: #aaaaaa;
        margin-bottom: 30px;
        font-size: 14px;
    }

    .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
    }

    .message.success {
        background: #1b5e20;
        color: #a5d6a7;
        border: 1px solid #2e7d32;
    }

    .message.error {
        background: #b71c1c;
        color: #ff8a80;
        border: 1px solid #7f0000;
    }

    .input-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        color: #e0e0e0;
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 14px;
    }

    input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #333;
        border-radius: 10px;
        background-color: #2a2a2a;
        color: #e0e0e0;
        font-size: 15px;
        transition: 0.3s;
    }

    input::placeholder {
        color: #aaaaaa;
    }

    input:focus {
        outline: none;
        border-color: #00b4ff;
        box-shadow: 0 0 8px rgba(0,180,255,0.5);
    }

    button {
        width: 100%;
        padding: 14px;
        background: linear-gradient(90deg, #00b4ff, #8a2be2);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        background: linear-gradient(90deg, #8a2be2, #00b4ff);
    }

    .links {
        margin-top: 20px;
        text-align: center;
        font-size: 14px;
    }

    .links a {
        color: #00b4ff;
        text-decoration: none;
        font-weight: 600;
    }

    .links a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>
    <div class="form-container">
        <div class="logo">🎮</div>
        <h1>Recuperar Password</h1>
        <p>Introduz o teu email e enviaremos instruções de recuperação</p>

        <?php if ($msg): ?>
            <div class="message <?php echo $msgClass; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="teu-email@exemplo.com" required>
            </div>

            <button type="submit">Enviar Email de Recuperação</button>
        </form>

        <div class="links">
            <a href="login.php">← Voltar ao Login</a>
        </div>
    </div>
</body>
</html>
