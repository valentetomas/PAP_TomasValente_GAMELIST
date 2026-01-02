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
            $message = "❌ Este email já está a ser usado por outra conta.";
            $messageClass = "error";
        } else {
            // Atualiza o email
            $updateStmt = $conn->prepare("UPDATE users SET email = ?, pending_email = NULL, email_change_token = NULL WHERE id = ?");
            $updateStmt->bind_param("si", $user['pending_email'], $user['id']);
            
            if ($updateStmt->execute()) {
                $message = "✅ Email alterado com sucesso!";
                $messageClass = "success";
            } else {
                $message = "❌ Erro ao alterar email. Tenta novamente.";
                $messageClass = "error";
            }
        }
    } else {
        $message = "⚠️ Token inválido ou expirado.";
        $messageClass = "error";
    }
} else {
    $message = "⚠️ Token não fornecido.";
    $messageClass = "error";
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
<title>Verificação de Email - GameList</title>
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
    
    .container {
        background: #1e1e1e;
        padding: 50px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.7);
        text-align: center;
        max-width: 500px;
        width: 100%;
    }
    
    .logo {
        font-size: 3rem;
        margin-bottom: 20px;
    }
    
    h1 {
        color: #ffffff;
        margin-bottom: 20px;
        font-size: 28px;
    }
    
    .message {
        padding: 20px;
        border-radius: 10px;
        margin: 30px 0;
        font-size: 16px;
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
    
    .btn {
        display: inline-block;
        padding: 12px 30px;
        background: linear-gradient(90deg, #00b4ff, #8a2be2);
        color: white;
        text-decoration: none;
        border-radius: 10px;
        font-weight: 600;
        transition: 0.3s;
        margin-top: 20px;
    }
    
    .btn:hover {
        background: linear-gradient(90deg, #8a2be2, #00b4ff);
    }
</style>
</head>
<body>
    <div class="container">
        <div class="logo">🎮</div>
        <h1>GameList</h1>
        <div class="message <?php echo $messageClass; ?>">
            <?php echo $message; ?>
        </div>
        <?php if ($messageClass === "success"): ?>
            <a href="settings.php" class="btn">Ir para Definições</a>
        <?php else: ?>
            <a href="index.php" class="btn">Voltar à Página Inicial</a>
        <?php endif; ?>
    </div>
</body>
</html>
