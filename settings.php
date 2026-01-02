<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db.php';
require 'includes/email_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

if (!$user) {
    header("Location: login.php");
    exit;
}

$feedback = '';
$error = '';

// Mudar password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!password_verify($current_password, $user['password'])) {
        $error = "Password atual incorreta.";
    } elseif ($new_password !== $confirm_password) {
        $error = "As passwords novas não coincidem.";
    } elseif (strlen($new_password) < 6) {
        $error = "A nova password deve ter pelo menos 6 caracteres.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $conn->query("UPDATE users SET password = '$hashed' WHERE id = $user_id");
        $feedback = "Password alterada com sucesso!";
    }
}

// Mudar email (com verificação)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_email'])) {
    $new_email = trim($_POST['new_email']);
    
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email inválido.";
    } elseif ($conn->query("SELECT id FROM users WHERE email = '$new_email' AND id != $user_id")->num_rows > 0) {
        $error = "Este email já está registado.";
    } else {
        // Gera token e guarda o email pendente
        $emailChangeToken = bin2hex(random_bytes(32));
        
        $stmt = $conn->prepare("UPDATE users SET pending_email = ?, email_change_token = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_email, $emailChangeToken, $user_id);
        
        if ($stmt->execute()) {
            // Envia email de verificação para o novo email
            if (sendEmailChangeVerification($new_email, $user['username'], $emailChangeToken)) {
                $feedback = "Email de verificação enviado para $new_email. Verifica a tua caixa de entrada.";
            } else {
                $error = "Erro ao enviar email de verificação. Tenta novamente.";
            }
        } else {
            $error = "Erro ao processar pedido.";
        }
    }
}

// Adicionar biografia
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_bio'])) {
    $bio = $conn->real_escape_string($_POST['biography']);
    if (strlen($bio) > 500) {
        $error = "A biografia não pode ter mais de 500 caracteres.";
    } else {
        $conn->query("UPDATE users SET biography = '$bio' WHERE id = $user_id");
        $feedback = "Biografia atualizada com sucesso!";
        $user['biography'] = $bio;
    }
}

// Adicionar redes sociais
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_socials'])) {
    $twitter = $conn->real_escape_string($_POST['twitter'] ?? '');
    $instagram = $conn->real_escape_string($_POST['instagram'] ?? '');
    $discord = $conn->real_escape_string($_POST['discord'] ?? '');
    
    $socials = json_encode([
        'twitter' => $twitter,
        'instagram' => $instagram,
        'discord' => $discord
    ]);
    
    $conn->query("UPDATE users SET social_links = '$socials' WHERE id = $user_id");
    $feedback = "Redes sociais atualizadas com sucesso!";
    $user['social_links'] = $socials;
}

// Atualizar privacidade
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_privacy'])) {
    $profile_public = isset($_POST['profile_public']) ? 1 : 0;
    $show_reviews = isset($_POST['show_reviews']) ? 1 : 0;
    
    $conn->query("UPDATE users SET profile_public = $profile_public, show_reviews = $show_reviews WHERE id = $user_id");
    $feedback = "Privacidade atualizada com sucesso!";
    $user['profile_public'] = $profile_public;
    $user['show_reviews'] = $show_reviews;
}

// Mudar tema
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_theme'])) {
    $theme = $_POST['theme'];
    if (in_array($theme, ['light', 'dark'])) {
        $conn->query("UPDATE users SET theme = '$theme' WHERE id = $user_id");
        $feedback = "Tema alterado com sucesso!";
        $user['theme'] = $theme;
    }
}

// Deletar conta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'];
    
    if (!password_verify($password, $user['password'])) {
        $error = "Password incorreta. Não foi possível deletar a conta.";
    } else {
        $conn->query("DELETE FROM reviews WHERE user_id = $user_id");
        $conn->query("DELETE FROM users WHERE id = $user_id");
        session_destroy();
        header("Location: index.php?deleted=1");
        exit;
    }
}

include 'includes/header.php';

// Re-carregar $user antes de usar no HTML (para garantir que tem todos os dados)
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>
<title>Definições - GameList</title>
</head>
<body>
<style>
    body {
            background: linear-gradient(180deg, #0b0b0b, #151515);
            color: #eee;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding-top: 100px;
        }

        .settings-container {
            max-width: 900px;
            margin: 0 auto 60px auto;
            padding: 0 20px;
            padding-top: 30px;
        }

        .settings-header {
            margin-bottom: 40px;
        }

        .settings-header h1 {
            color: #00bfff;
            font-size: 2.5rem;
            margin: 0 0 10px 0;
        }

        .settings-header p {
            color: #aaa;
            margin: 0;
        }

        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
        }

        .tab-btn {
            background: transparent;
            border: none;
            color: #aaa;
            padding: 10px 16px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
        }

        .tab-btn:hover {
            color: #00bfff;
        }

        .tab-btn.active {
            color: #00bfff;
            border-bottom-color: #00bfff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .settings-section {
            background: rgba(30, 30, 35, 0.5);
            border: 1px solid #333;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
        }

        .settings-section h2 {
            color: #00bfff;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #ccc;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: #222;
            border: 1px solid #444;
            border-radius: 6px;
            color: #eee;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00bfff;
            box-shadow: 0 0 10px rgba(0, 191, 255, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            color: #ccc;
        }

        .btn {
            padding: 12px 24px;
            background: linear-gradient(90deg, #00bfff 0%, #0080ff 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-danger {
            background: linear-gradient(90deg, #ff4444 0%, #cc0000 100%);
        }

        .btn-secondary {
            background: #444;
            color: #eee;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .feedback {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .feedback.success {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid #4caf50;
        }

        .feedback.error {
            background: rgba(244, 67, 54, 0.2);
            color: #ff6b6b;
            border: 1px solid #f44336;
        }

        .current-value {
            color: #00bfff;
            font-weight: bold;
            margin-top: 5px;
            font-size: 0.9rem;
        }

        .social-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        @media (max-width: 768px) {
            .settings-container {
                padding: 0 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .settings-tabs {
                flex-direction: column;
            }

            .tab-btn {
                width: 100%;
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <div class="settings-header">
            <h1>⚙️ Definições</h1>
            <p>Gerencie sua conta, privacidade e preferências</p>
        </div>

        <?php if ($feedback): ?>
            <div class="feedback success"><?php echo $feedback; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="feedback error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="settings-tabs">
            <button class="tab-btn active" onclick="switchTab('account')">Conta & Segurança</button>
            <button class="tab-btn" onclick="switchTab('profile')">Perfil</button>
            <button class="tab-btn" onclick="switchTab('privacy')">Privacidade</button>
            <button class="tab-btn" onclick="switchTab('preferences')">Preferências</button>
            <button class="tab-btn" onclick="switchTab('danger')">Zona de Perigo</button>
        </div>

        <!-- TAB: CONTA & SEGURANÇA -->
        <div id="account" class="tab-content active">
            <!-- Mudar Password -->
            <div class="settings-section">
                <h2>🔐 Alterar Password</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Password Atual</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nova Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirmar Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    <button type="submit" name="change_password" class="btn">Alterar Password</button>
                </form>
            </div>

            <!-- Mudar Email -->
            <div class="settings-section">
                <h2>📧 Alterar Email</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Email Atual</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <div class="current-value">✓ Verificado</div>
                    </div>
                    <div class="form-group">
                        <label>Novo Email</label>
                        <input type="email" name="new_email" required>
                    </div>
                    <button type="submit" name="change_email" class="btn">Alterar Email</button>
                </form>
            </div>
        </div>

        <!-- TAB: PERFIL -->
        <div id="profile" class="tab-content">
            <!-- Biografia -->
            <div class="settings-section">
                <h2>📝 Biografia</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Sobre ti (até 500 caracteres)</label>
                        <textarea name="biography" placeholder="Conte um pouco sobre você, seus interesses em jogos, etc..."><?php echo htmlspecialchars($user['biography'] ?? ''); ?></textarea>
                        <div class="current-value" id="bioCount">0/500</div>
                    </div>
                    <button type="submit" name="update_bio" class="btn">Guardar Biografia</button>
                </form>
            </div>

            <!-- Redes Sociais -->
            <div class="settings-section">
                <h2>🌐 Redes Sociais</h2>
                <form method="POST">
                    <?php 
                    $socials = json_decode($user['social_links'] ?? '{}', true) ?? [];
                    ?>
                    <div class="social-inputs">
                        <div class="form-group">
                            <label>Twitter / X</label>
                            <input type="text" name="twitter" placeholder="@seu_usuario" value="<?php echo htmlspecialchars($socials['twitter'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Instagram</label>
                            <input type="text" name="instagram" placeholder="@seu_usuario" value="<?php echo htmlspecialchars($socials['instagram'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Discord</label>
                            <input type="text" name="discord" placeholder="usuario#0000" value="<?php echo htmlspecialchars($socials['discord'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" name="update_socials" class="btn" style="margin-top: 15px;">Guardar Redes Sociais</button>
                </form>
            </div>
        </div>

        <!-- TAB: PRIVACIDADE -->
        <div id="privacy" class="tab-content">
            <div class="settings-section">
                <h2>🔒 Configurações de Privacidade</h2>
                <form method="POST">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="profile_public" id="profile_public" <?php echo ($user['profile_public'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="profile_public">Perfil Público</label>
                        </div>
                        <p style="color: #888; font-size: 0.9rem; margin-top: 8px;">Permite que outros utilizadores vejam o seu perfil</p>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="show_reviews" id="show_reviews" <?php echo ($user['show_reviews'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="show_reviews">Mostrar Minhas Reviews</label>
                        </div>
                        <p style="color: #888; font-size: 0.9rem; margin-top: 8px;">Permite que outros vejam as suas avaliações de jogos</p>
                    </div>

                    <button type="submit" name="update_privacy" class="btn">Guardar Privacidade</button>
                </form>
            </div>
        </div>

        <!-- TAB: PREFERÊNCIAS -->
        <div id="preferences" class="tab-content">
            <div class="settings-section">
                <h2>🎨 Tema</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Escolha o seu tema preferido</label>
                        <select name="theme">
                            <option value="dark" <?php echo ($user['theme'] ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Escuro (padrão)</option>
                            <option value="light" <?php echo ($user['theme'] ?? 'dark') === 'light' ? 'selected' : ''; ?>>Claro</option>
                        </select>
                    </div>
                    <button type="submit" name="change_theme" class="btn">Guardar Tema</button>
                </form>
            </div>
        </div>

        <!-- TAB: ZONA DE PERIGO -->
        <div id="danger" class="tab-content">
            <div class="settings-section" style="border-color: #ff6b6b;">
                <h2 style="color: #ff6b6b;">⚠️ Deletar Conta</h2>
                <p style="color: #ccc; line-height: 1.6;">
                    ⚠️ <strong>Aviso:</strong> Deletar sua conta é uma ação permanente. Todos os seus dados, reviews e informações de perfil serão apagados e não poderão ser recuperados.
                </p>
                <form method="POST" onsubmit="return confirm('Tem a certeza que deseja deletar sua conta permanentemente? Esta ação não pode ser desfeita!');">
                    <div class="form-group">
                        <label>Confirme sua password para deletar a conta</label>
                        <input type="password" name="delete_password" required placeholder="Digite sua password">
                    </div>
                    <button type="submit" name="delete_account" class="btn btn-danger">Deletar Permanentemente</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Esconder todos os conteúdos
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));

            // Desativar todos os botões
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Mostrar conteúdo selecionado
            document.getElementById(tabName).classList.add('active');

            // Ativar botão selecionado
            event.target.classList.add('active');
        }

        // Contador de caracteres para biografia
        const bioTextarea = document.querySelector('textarea[name="biography"]');
        if (bioTextarea) {
            function updateBioCount() {
                const count = bioTextarea.value.length;
                document.getElementById('bioCount').textContent = count + '/500';
            }
            bioTextarea.addEventListener('input', updateBioCount);
            updateBioCount();
        }
    </script>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
