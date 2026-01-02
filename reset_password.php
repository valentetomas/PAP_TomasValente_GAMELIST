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
        $msg = "⚠️ Token inválido ou expirado.";
        $msgClass = "error";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token'])) {
    $token = $_POST['token'];
    $newPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    
    if (strlen($newPassword) < 6) {
        $msg = "⚠️ A password deve ter pelo menos 6 caracteres!";
        $msgClass = "error";
        $validToken = true;
    } elseif ($newPassword !== $confirmPassword) {
        $msg = "⚠️ As passwords não coincidem!";
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
                $msg = "✅ Password alterada com sucesso! Já podes fazer login.";
                $msgClass = "success";
                $validToken = false;
            } else {
                $msg = "❌ Erro ao alterar password. Tenta novamente.";
                $msgClass = "error";
                $validToken = true;
            }
        } else {
            $msg = "⚠️ Token inválido ou expirado.";
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
<link rel="icon" type="image/png" sizes="32x32" href="img/logo.png">
<link rel="icon" type="image/png" sizes="16x16" href="img/logo.png">
<link rel="shortcut icon" href="img/logo.png">
<title>Nova Password - GameList</title>
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
        margin-bottom: 30px;
        font-size: 26px;
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

    button, .btn {
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
        display: inline-block;
        text-align: center;
        text-decoration: none;
    }

    button:hover, .btn:hover {
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
        <h1>Nova Password</h1>

        <?php if ($msg): ?>
            <div class="message <?php echo $msgClass; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="input-group">
                    <label for="password">Nova Password</label>
                    <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="input-group">
                    <label for="confirm_password">Confirmar Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repete a password" required>
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
