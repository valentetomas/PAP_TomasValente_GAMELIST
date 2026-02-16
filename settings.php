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

// Eliminar conta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'];
    
    if (!password_verify($password, $user['password'])) {
        $error = "Password incorreta. Não foi possível eliminar a conta.";
    } else {
        $conn->query("DELETE FROM reviews WHERE user_id = $user_id");
        $conn->query("DELETE FROM users WHERE id = $user_id");
        session_destroy();
        header("Location: index.php?deleted=1");
        exit;
    }
}

// Re-carregar $user antes de usar no HTML (para garantir que tem todos os dados)
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

$page_title = 'Definições - GameList';
include 'includes/header.php';

// O header usa a variável $user internamente (com dados reduzidos),
// por isso voltamos a carregar o utilizador completo para esta página.
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>
<style>
    :root {
        --bg-dark: #0b0c0f;
        --surface: #16171c;
        --surface-alt: #1d2028;
        --border: rgba(255, 255, 255, 0.08);
        --text-main: #ffffff;
        --text-muted: #9ca3af;
        --accent: #ff3366;
        --accent-hover: #ff4d7a;
        --radius: 14px;
    }

    body {
        background: linear-gradient(180deg, var(--bg-dark) 0%, #101218 100%);
        color: var(--text-main);
        font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
        padding-top: 84px;
    }

    .settings-container {
        max-width: 980px;
        margin: 0 auto 60px;
        padding: 20px;
    }

    .settings-header {
        margin-bottom: 18px;
        background: linear-gradient(135deg, #171922 0%, #11131a 100%);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
    }

    .settings-header h1 {
        color: #fff;
        font-size: clamp(1.5rem, 2.2vw, 2.2rem);
        margin: 0 0 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .settings-header p {
        color: var(--text-muted);
        margin: 0;
    }

    .feedback {
        padding: 12px 14px;
        border-radius: 10px;
        margin-bottom: 14px;
        font-weight: 600;
        border: 1px solid transparent;
    }

    .feedback.success {
        background: rgba(34, 197, 94, 0.12);
        color: #86efac;
        border-color: rgba(34, 197, 94, 0.38);
    }

    .feedback.error {
        background: rgba(239, 68, 68, 0.12);
        color: #fca5a5;
        border-color: rgba(239, 68, 68, 0.38);
    }

    .settings-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .tab-btn {
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.02);
        color: #c9ced6;
        border-radius: 999px;
        padding: 9px 14px;
        cursor: pointer;
        font-size: .86rem;
        font-weight: 700;
        transition: .2s ease;
    }

    .tab-btn:hover {
        border-color: rgba(255, 51, 102, 0.45);
        color: #fff;
        background: rgba(255, 51, 102, 0.1);
    }

    .tab-btn.active {
        color: #fff;
        border-color: rgba(255, 51, 102, 0.6);
        background: rgba(255, 51, 102, 0.18);
    }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .settings-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 14px;
    }

    .settings-section h2 {
        color: #fff;
        margin-top: 0;
        margin-bottom: 18px;
        font-size: 1.16rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .settings-section h2 i {
        color: var(--accent);
    }

    .form-group { margin-bottom: 16px; }

    .form-group label {
        display: block;
        color: #d2d6de;
        margin-bottom: 7px;
        font-weight: 600;
        font-size: .93rem;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"],
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 11px 12px;
        background: #10131a;
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 10px;
        color: #eef1f6;
        font-size: 0.95rem;
        transition: .2s ease;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: rgba(255, 51, 102, 0.6);
        box-shadow: 0 0 0 3px rgba(255, 51, 102, 0.15);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 112px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .social-inputs {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .checkbox-group input[type="checkbox"] {
        width: 17px;
        height: 17px;
        accent-color: var(--accent);
        cursor: pointer;
    }

    .checkbox-group label {
        margin: 0;
        color: #e3e7ef;
        cursor: pointer;
        font-weight: 600;
    }

    .helper-text {
        color: var(--text-muted);
        font-size: .84rem;
        margin-top: 7px;
        line-height: 1.45;
    }

    .btn {
        padding: 10px 16px;
        background: linear-gradient(135deg, var(--accent) 0%, #cc2952 100%);
        color: #fff;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 700;
        font-size: .92rem;
        transition: .2s ease;
    }

    .btn:hover {
        background: linear-gradient(135deg, var(--accent-hover) 0%, #e62e5c 100%);
        transform: translateY(-1px);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #f87171 0%, #dc2626 100%);
    }

    .current-value {
        color: #f3b5c7;
        font-weight: 600;
        margin-top: 6px;
        font-size: 0.84rem;
    }

    .danger-zone {
        border-color: rgba(239, 68, 68, 0.35);
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.03) 100%);
    }

    @media (max-width: 860px) {
        .form-row,
        .social-inputs {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .settings-container { padding: 14px; }
        .settings-tabs { flex-direction: column; }
        .tab-btn { width: 100%; text-align: left; border-radius: 10px; }
    }
</style>

<div class="settings-container">
    <div class="settings-header">
        <h1><i class="fa-solid fa-gear" aria-hidden="true"></i>Definições</h1>
        <p>Gere a tua conta, privacidade e preferências</p>
    </div>

        <?php if ($feedback): ?>
            <div class="feedback success"><?php echo $feedback; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="feedback error"><?php echo $error; ?></div>
        <?php endif; ?>

    <div class="settings-tabs">
        <button class="tab-btn active" type="button" data-tab="account">Conta & Segurança</button>
        <button class="tab-btn" type="button" data-tab="profile">Perfil</button>
        <button class="tab-btn" type="button" data-tab="privacy">Privacidade</button>
        <button class="tab-btn" type="button" data-tab="preferences">Preferências</button>
        <button class="tab-btn" type="button" data-tab="danger">Zona de Perigo</button>
    </div>

    <!-- TAB: CONTA & SEGURANÇA -->
    <div id="account" class="tab-content active">
        <div class="settings-section">
            <h2><i class="fa-solid fa-lock"></i>Alterar Password</h2>
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

        <div class="settings-section">
            <h2><i class="fa-solid fa-envelope"></i>Alterar Email</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Email Atual</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
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
        <div class="settings-section">
            <h2><i class="fa-regular fa-note-sticky"></i>Biografia</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Sobre ti (até 500 caracteres)</label>
                        <textarea name="biography" placeholder="Conta um pouco sobre ti, os teus interesses em jogos, etc..."><?php echo htmlspecialchars($user['biography'] ?? ''); ?></textarea>
                        <div class="current-value" id="bioCount">0/500</div>
                    </div>
                <button type="submit" name="update_bio" class="btn">Guardar Biografia</button>
                </form>
        </div>

        <div class="settings-section">
            <h2><i class="fa-solid fa-share-nodes"></i>Redes Sociais</h2>
                <form method="POST">
                    <?php 
                    $socials = json_decode($user['social_links'] ?? '{}', true) ?? [];
                    ?>
                    <div class="social-inputs">
                        <div class="form-group">
                            <label>Twitter / X</label>
                            <input type="text" name="twitter" placeholder="@teu_utilizador" value="<?php echo htmlspecialchars($socials['twitter'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Instagram</label>
                            <input type="text" name="instagram" placeholder="@teu_utilizador" value="<?php echo htmlspecialchars($socials['instagram'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Discord</label>
                            <input type="text" name="discord" placeholder="utilizador#0000" value="<?php echo htmlspecialchars($socials['discord'] ?? ''); ?>">
                        </div>
                    </div>
                <button type="submit" name="update_socials" class="btn" style="margin-top: 8px;">Guardar Redes Sociais</button>
                </form>
        </div>
    </div>

        <!-- TAB: PRIVACIDADE -->
    <div id="privacy" class="tab-content">
        <div class="settings-section">
            <h2><i class="fa-solid fa-user-shield"></i>Definições de Privacidade</h2>
                <form method="POST">
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="profile_public" id="profile_public" <?php echo ($user['profile_public'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="profile_public">Perfil Público</label>
                        </div>
                    <p class="helper-text">Permite que outros utilizadores vejam o teu perfil</p>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="show_reviews" id="show_reviews" <?php echo ($user['show_reviews'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="show_reviews">Mostrar Minhas Reviews</label>
                        </div>
                    <p class="helper-text">Permite que outros vejam as tuas avaliações de jogos</p>
                    </div>

                <button type="submit" name="update_privacy" class="btn">Guardar Privacidade</button>
                </form>
        </div>
    </div>

        <!-- TAB: PREFERÊNCIAS -->
    <div id="preferences" class="tab-content">
        <div class="settings-section">
            <h2><i class="fa-solid fa-palette"></i>Tema</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Escolhe o teu tema preferido</label>
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
        <div class="settings-section danger-zone">
            <h2><i class="fa-solid fa-triangle-exclamation"></i>Eliminar Conta</h2>
            <p class="helper-text" style="color:#fecaca;">
                <strong>Aviso:</strong> Eliminar a tua conta é uma ação permanente. Todos os teus dados, reviews e informações de perfil serão apagados e não poderão ser recuperados.
            </p>
                <form method="POST" onsubmit="return confirm('Tens a certeza que pretendes eliminar a tua conta permanentemente? Esta ação não pode ser desfeita!');">
                    <div class="form-group">
                        <label>Confirma a tua password para eliminar a conta</label>
                        <input type="password" name="delete_password" required placeholder="Escreve a tua password">
                    </div>
                <button type="submit" name="delete_account" class="btn btn-danger">Eliminar Permanentemente</button>
                </form>
        </div>
    </div>
</div>

<script>
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.dataset.tab;
            tabContents.forEach(content => content.classList.remove('active'));
            tabButtons.forEach(button => button.classList.remove('active'));
            const target = document.getElementById(tabName);
            if (target) target.classList.add('active');
            btn.classList.add('active');
        });
    });

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
