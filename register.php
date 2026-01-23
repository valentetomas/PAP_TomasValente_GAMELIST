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
        $msg = "⚠️ A senha deve ter pelo menos 6 caracteres!";
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
                    $msg = "⚠️ Conta criada, mas houve erro ao enviar email de verificação. <a href='login.php'>Tentar login</a>";
                    $msgClass = "warning";
                }
            } else {
                $msg = "❌ Erro ao criar conta. Tente novamente mais tarde.";
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
<link rel="icon" type="image/png" sizes="32x32" href="img/logo.png">
<link rel="icon" type="image/png" sizes="16x16" href="img/logo.png">
<link rel="shortcut icon" href="img/logo.png">
<title>Registar - GameList</title>
</head>
<body>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

    body {
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: #121212;
      color: #e0e0e0;
    }

    .container {
      background: #1e1e1e;
      padding: 40px 30px;
      border-radius: 15px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.7);
      width: 100%;
      max-width: 400px;
      text-align: center;
    }

    h1 {
      margin-bottom: 30px;
      font-size: 28px;
      color: #ffffff;
    }

    input {
      width: 100%;
      padding: 12px 15px;
      margin: 10px 0;
      border: 1px solid #333;
      border-radius: 10px;
      background-color: #2a2a2a;
      color: #e0e0e0;
      font-size: 16px;
      transition: 0.3s;
    }

    input::placeholder {
      color: #aaaaaa;
    }

    input:focus {
      border-color: #00b4ff;
      outline: none;
      box-shadow: 0 0 8px rgba(0,180,255,0.5);
    }

    button {
      width: 100%;
      padding: 12px;
      margin-top: 15px;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      color: #fff;
      background: linear-gradient(90deg, #00b4ff, #8a2be2);
      transition: 0.3s;
    }

    button:hover {
      background: linear-gradient(90deg, #8a2be2, #00b4ff);
    }

    .msg {
      margin-top: 15px;
      padding: 10px;
      border-radius: 10px;
      font-size: 14px;
    }

    .success {
      background: #2e7d32;
      color: #a5d6a7;
      border: 1px solid #1b5e20;
    }

    .error {
      background: #b71c1c;
      color: #ff8a80;
      border: 1px solid #7f0000;
    }

    p a {
      color: #00b4ff;
      text-decoration: none;
      font-weight: 500;
      transition: 0.2s;
    }

    p a:hover {
      color: #8a2be2;
      text-decoration: underline;
    }
  </style>
  <div class="container">
    <h1>Cria a tua conta</h1>
    <form method="POST">
      <input type="text" name="username" placeholder="Nome de utilizador" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Palavra-passe" required>
      <button type="submit">Registar</button>
    </form>
    <?php if($msg): ?>
      <p class="msg <?php echo $msgClass; ?>"><?php echo $msg; ?></p>
    <?php endif; ?>
    <br>
    <p>Já tens conta? <a href="login.php">Inicia sessão</a></p>
  </div>
</body>
</html>