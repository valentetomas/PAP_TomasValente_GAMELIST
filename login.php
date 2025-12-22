<?php
include 'includes/db.php';


$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            if ($user['banned']) {
                $msg = "❌ Conta banida. Contacta o administrador.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: index.php");
                exit();
            }
        } else {
            $msg = "❌ Palavra-passe incorreta.";
        }
    } else {
        $msg = "⚠️ Utilizador não encontrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <title>Login - GameList</title>
  <link rel="icon" type="image/png" href="img/logo.png">
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

    .password-wrapper {
      position: relative;
    }

    .password-wrapper input {
      padding-right: 40px;
    }

    .toggle-password {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #bbbbbb;
      font-size: 16px;
      user-select: none;
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
    <h1>Iniciar Sessão</h1>
    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <div class="password-wrapper">
        <input type="password" id="password" name="password" placeholder="Palavra-passe" required>
        <span class="toggle-password" onclick="togglePassword()">👁️</span>
      </div>
      <button type="submit">Entrar</button>
    </form>
    <?php if($msg): ?>
      <p class="msg"><?php echo $msg; ?></p>
    <?php endif; ?>
    <br>
    <p>Não tens conta? <a href="register.php">Regista-te</a></p>
  </div>

  <script>
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
    }
  </script>
</body>
</html>
