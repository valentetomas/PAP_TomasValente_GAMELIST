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
$defaultAvatarPath = 'img/default.png';
$defaultBannerPath = 'img/banner.png';

function fetchUserById(mysqli $conn, int $user_id): ?array {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $user;
}

function normalizeSocialHandle(string $value, int $max = 64): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = ltrim($value, '@');
    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
}

function ensureProfileUploadDir(): string {
    $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function processProfileImageUpload(string $fieldName, int $user_id, int $maxBytes, string $prefix, string $currentPath): array {
    if (!isset($_FILES[$fieldName]) || ($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    $file = $_FILES[$fieldName];
    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($uploadError !== UPLOAD_ERR_OK) {
        return [null, "Falha no upload de {$prefix}. Tenta novamente."];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        $limitMB = (int)($maxBytes / 1024 / 1024);
        return [null, "O {$prefix} excede o limite de {$limitMB}MB."];
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpPath)) {
        return [null, "Upload inválido para {$prefix}."];
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $mimeType = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $tmpPath) ?: '';
            finfo_close($finfo);
        }
    }

    if ($mimeType === '') {
        $imageInfo = @getimagesize($tmpPath);
        $mimeType = $imageInfo['mime'] ?? '';
    }

    if (!isset($allowedMimes[$mimeType])) {
        return [null, "Formato inválido para {$prefix}. Usa JPG, PNG ou WEBP."];
    }

    $extension = $allowedMimes[$mimeType];
    $uploadDir = ensureProfileUploadDir();
    $fileName = $prefix . '_' . $user_id . '_' . bin2hex(random_bytes(10)) . '.' . $extension;
    $destinationAbsPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpPath, $destinationAbsPath)) {
        return [null, "Não foi possível guardar o {$prefix}."];
    }

    if (!empty($currentPath) && substr($currentPath, 0, strlen('uploads/profile/')) === 'uploads/profile/') {
        $oldAbsPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $currentPath);
        if (is_file($oldAbsPath)) {
            @unlink($oldAbsPath);
        }
    }

    $newRelativePath = 'uploads/profile/' . $fileName;
    return [$newRelativePath, null];
}

function deleteLocalProfileImage(string $path): void {
    if (!empty($path) && substr($path, 0, strlen('uploads/profile/')) === 'uploads/profile/') {
        $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$allowedTabs = ['account', 'profile', 'privacy', 'preferences', 'danger'];
$active_tab = 'account';
if (isset($_GET['tab']) && in_array($_GET['tab'], $allowedTabs, true)) {
    $active_tab = $_GET['tab'];
}

$user = fetchUserById($conn, (int)$user_id);
if (!$user) {
    header("Location: login.php");
    exit;
}

$feedback = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Pedido inválido. Atualiza a página e tenta novamente.";
    } else {
        $submittedTab = $_POST['active_tab'] ?? 'account';
        if (in_array($submittedTab, $allowedTabs, true)) {
            $active_tab = $submittedTab;
        }

        if (isset($_POST['update_account_info'])) {
            $new_username = trim($_POST['username'] ?? '');

            if ($new_username === '') {
                $error = "O nome de utilizador é obrigatório.";
            } elseif (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $new_username)) {
                $error = "O nome de utilizador deve ter 3-30 caracteres (letras, números, _ e .).";
            } else {
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
                $checkStmt->bind_param("si", $new_username, $user_id);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();

                if ($exists) {
                    $error = "Esse nome de utilizador já está a ser usado.";
                } else {
                    $updateStmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $new_username, $user_id);

                    if ($updateStmt->execute()) {
                        $feedback = "Dados da conta atualizados com sucesso!";
                    } else {
                        $error = "Não foi possível atualizar os dados da conta.";
                    }

                    $updateStmt->close();
                }
            }
        }

        if (!$error && isset($_POST['change_password'])) {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            if (!password_verify($current_password, $user['password'])) {
                $error = "Password atual incorreta.";
            } elseif ($new_password !== $confirm_password) {
                $error = "As passwords novas não coincidem.";
            } elseif (strlen($new_password) < 6) {
                $error = "A nova password deve ter pelo menos 6 caracteres.";
            } else {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed, $user_id);

                if ($stmt->execute()) {
                    $feedback = "Password alterada com sucesso!";
                } else {
                    $error = "Não foi possível alterar a password.";
                }

                $stmt->close();
            }
        }

        if (!$error && isset($_POST['change_email'])) {
            $new_email = trim($_POST['new_email'] ?? '');

            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $error = "Email inválido.";
            } else {
                $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                $checkStmt->bind_param("si", $new_email, $user_id);
                $checkStmt->execute();
                $alreadyExists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();

                if ($alreadyExists) {
                    $error = "Este email já está registado.";
                } else {
                    $emailChangeToken = bin2hex(random_bytes(32));
                    $stmt = $conn->prepare("UPDATE users SET pending_email = ?, email_change_token = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $new_email, $emailChangeToken, $user_id);

                    if ($stmt->execute()) {
                        if (sendEmailChangeVerification($new_email, $user['username'], $emailChangeToken)) {
                            $feedback = "Email de verificação enviado para {$new_email}. Verifica a tua caixa de entrada.";
                        } else {
                            $error = "Erro ao enviar email de verificação. Tenta novamente.";
                        }
                    } else {
                        $error = "Erro ao processar pedido.";
                    }

                    $stmt->close();
                }
            }
        }

        if (!$error && isset($_POST['update_bio'])) {
            $bio = trim($_POST['biography'] ?? '');
            $bioLength = function_exists('mb_strlen') ? mb_strlen($bio) : strlen($bio);

            if ($bioLength > 500) {
                $error = "A biografia não pode ter mais de 500 caracteres.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET biography = ? WHERE id = ?");
                $stmt->bind_param("si", $bio, $user_id);

                if ($stmt->execute()) {
                    $feedback = "Biografia atualizada com sucesso!";
                } else {
                    $error = "Não foi possível atualizar a biografia.";
                }

                $stmt->close();
            }
        }

        if (!$error && isset($_POST['update_profile_images'])) {
            $removeAvatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';
            $removeBanner = isset($_POST['remove_banner']) && $_POST['remove_banner'] === '1';

            [$newAvatarPath, $avatarError] = processProfileImageUpload('avatar', (int)$user_id, 3 * 1024 * 1024, 'avatar', $user['avatar'] ?? '');
            if ($avatarError) {
                $error = $avatarError;
            }

            [$newBannerPath, $bannerError] = processProfileImageUpload('banner', (int)$user_id, 6 * 1024 * 1024, 'banner', $user['banner'] ?? '');
            if (!$error && $bannerError) {
                $error = $bannerError;
            }

            if (!$error) {
                $fields = [];
                $types = '';
                $params = [];

                if ($removeAvatar && $newAvatarPath === null) {
                    deleteLocalProfileImage($user['avatar'] ?? '');
                    $fields[] = 'avatar = ?';
                    $types .= 's';
                    $params[] = $defaultAvatarPath;
                }

                if ($removeBanner && $newBannerPath === null) {
                    deleteLocalProfileImage($user['banner'] ?? '');
                    $fields[] = 'banner = ?';
                    $types .= 's';
                    $params[] = $defaultBannerPath;
                }

                if ($newAvatarPath !== null) {
                    $fields[] = 'avatar = ?';
                    $types .= 's';
                    $params[] = $newAvatarPath;
                }

                if ($newBannerPath !== null) {
                    $fields[] = 'banner = ?';
                    $types .= 's';
                    $params[] = $newBannerPath;
                }

                if (empty($fields)) {
                    $error = 'Seleciona pelo menos uma imagem (avatar ou banner).';
                } else {
                    $query = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
                    $types .= 'i';
                    $params[] = $user_id;

                    $stmt = $conn->prepare($query);
                    if ($stmt) {
                        $stmt->bind_param($types, ...$params);
                        if ($stmt->execute()) {
                            $feedback = 'Imagens de perfil atualizadas com sucesso!';
                        } else {
                            $error = 'Não foi possível atualizar as imagens de perfil.';
                        }
                        $stmt->close();
                    } else {
                        $error = 'Não foi possível preparar a atualização das imagens.';
                    }
                }
            }
        }

        if (!$error && isset($_POST['update_socials'])) {
            $twitter = normalizeSocialHandle($_POST['twitter'] ?? '');
            $instagram = normalizeSocialHandle($_POST['instagram'] ?? '');
            $discordRaw = $_POST['discord'] ?? '';
            $discord = trim(function_exists('mb_substr') ? mb_substr($discordRaw, 0, 64) : substr($discordRaw, 0, 64));

            $socials = json_encode([
                'twitter' => $twitter,
                'instagram' => $instagram,
                'discord' => $discord
            ]);

            $stmt = $conn->prepare("UPDATE users SET social_links = ? WHERE id = ?");
            $stmt->bind_param("si", $socials, $user_id);

            if ($stmt->execute()) {
                $feedback = "Redes sociais atualizadas com sucesso!";
            } else {
                $error = "Não foi possível atualizar as redes sociais.";
            }

            $stmt->close();
        }

        if (!$error && isset($_POST['update_privacy'])) {
            $profile_public = isset($_POST['profile_public']) ? 1 : 0;
            $show_reviews = isset($_POST['show_reviews']) ? 1 : 0;

            $stmt = $conn->prepare("UPDATE users SET profile_public = ?, show_reviews = ? WHERE id = ?");
            $stmt->bind_param("iii", $profile_public, $show_reviews, $user_id);

            if ($stmt->execute()) {
                $feedback = "Privacidade atualizada com sucesso!";
            } else {
                $error = "Não foi possível atualizar a privacidade.";
            }

            $stmt->close();
        }

        if (!$error && isset($_POST['change_theme'])) {
            $theme = $_POST['theme'] ?? 'dark';

            if (!in_array($theme, ['light', 'dark'], true)) {
                $error = "Tema inválido.";
            } else {
                $stmt = $conn->prepare("UPDATE users SET theme = ? WHERE id = ?");
                $stmt->bind_param("si", $theme, $user_id);

                if ($stmt->execute()) {
                    $feedback = "Tema alterado com sucesso!";
                } else {
                    $error = "Não foi possível alterar o tema.";
                }

                $stmt->close();
            }
        }

        if (!$error && isset($_POST['delete_account'])) {
            $password = $_POST['delete_password'] ?? '';

            if (!password_verify($password, $user['password'])) {
                $error = "Password incorreta. Não foi possível eliminar a conta.";
            } else {
                $conn->begin_transaction();

                try {
                    $stmt = $conn->prepare("DELETE rc FROM review_comments rc INNER JOIN reviews r ON rc.review_id = r.id WHERE r.user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE FROM review_comments WHERE user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE rl FROM review_likes rl INNER JOIN reviews r ON rl.review_id = r.id WHERE r.user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE FROM review_likes WHERE user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE FROM follows WHERE follower_id = ? OR following_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ii", $user_id, $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE FROM user_achievements WHERE user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE li FROM list_items li INNER JOIN lists l ON li.list_id = l.id WHERE l.user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE FROM lists WHERE user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE FROM reviews WHERE user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                    if (!$stmt) {
                        throw new Exception('Falha ao preparar eliminação da conta.');
                    }

                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $deletedRows = $stmt->affected_rows;
                    $stmt->close();

                    if ($deletedRows !== 1) {
                        throw new Exception('Conta não encontrada para eliminar.');
                    }

                    $conn->commit();
                    session_destroy();
                    header("Location: index.php?deleted=1");
                    exit;
                } catch (Throwable $e) {
                    $conn->rollback();
                    $error = "Não foi possível eliminar a conta neste momento.";
                }
            }
        }
    }
}

$user = fetchUserById($conn, (int)$user_id);
if (!$user) {
    header("Location: login.php");
    exit;
}

$page_title = 'Definições - GameList';
include 'includes/header.php';

// O header usa a variável $user internamente (com dados reduzidos),
// por isso voltamos a carregar o utilizador completo para esta página.
$user = fetchUserById($conn, (int)$user_id);
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
        flex-direction: column;
        gap: 8px;
        align-self: start;
        position: sticky;
        top: 96px;
    }

    .settings-layout {
        display: grid;
        grid-template-columns: 240px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }

    .settings-panels {
        min-width: 0;
    }

    .tab-btn {
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.02);
        color: #c9ced6;
        border-radius: 10px;
        padding: 9px 14px;
        cursor: pointer;
        font-size: .86rem;
        font-weight: 700;
        transition: .2s ease;
        text-align: left;
        width: 100%;
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

    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.76rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.04);
        color: #d9dee7;
    }

    .status-pill.ok {
        border-color: rgba(34, 197, 94, 0.45);
        color: #86efac;
        background: rgba(34, 197, 94, 0.12);
    }

    .account-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 8px;
    }

    .media-stack {
        display: grid;
        gap: 18px;
        margin-bottom: 14px;
    }

    .media-row-block {
        display: grid;
        gap: 8px;
    }

    .media-row-block h3 {
        margin: 0;
        font-size: .95rem;
        color: #eef1f6;
    }

    .media-row {
        display: grid;
        grid-template-columns: 150px minmax(0, 1fr);
        gap: 14px;
        align-items: stretch;
    }

    .hidden-file-input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        overflow: hidden;
        pointer-events: none;
    }

    .drop-upload {
        border: 1px dashed rgba(255,255,255,0.2);
        border-radius: 10px;
        background: #0f1c31;
        color: #b9d0ea;
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 12px;
        font-weight: 500;
        line-height: 1.4;
        cursor: pointer;
        transition: border-color .2s ease, background .2s ease, color .2s ease;
    }

    .drop-upload:hover {
        border-color: rgba(255,255,255,0.34);
        background: #12233d;
        color: #d4e4f7;
    }

    .media-preview {
        width: 100%;
        border: 1px solid rgba(255,255,255,0.1);
        background: #0d1118;
        border-radius: 10px;
        object-fit: cover;
        display: block;
    }

    .preview-wrap {
        position: relative;
        width: fit-content;
    }

    .reset-image-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 8px;
        background: rgba(17, 24, 39, 0.52);
        color: #ffffff;
        font-size: 1.2rem;
        line-height: 1;
        font-weight: 700;
        display: grid;
        place-items: center;
        cursor: pointer;
        transition: background .2s ease, transform .2s ease, opacity .2s ease;
        opacity: 0;
        pointer-events: none;
    }

    .reset-image-btn:hover {
        background: rgba(220, 38, 38, 0.82);
        transform: translateY(-1px);
    }

    .preview-wrap:hover .reset-image-btn,
    .preview-wrap:focus-within .reset-image-btn,
    .reset-image-btn:focus-visible {
        opacity: 1;
        pointer-events: auto;
    }

    .media-preview.avatar {
        width: 230px;
        height: 230px;
        max-width: 100%;
        min-height: 0;
        max-height: none;
    }

    .media-preview.banner {
        aspect-ratio: auto;
        height: 230px;
        min-height: 0;
    }

    .selected-file {
        font-size: .8rem;
        color: var(--text-muted);
    }

    .danger-zone {
        border-color: rgba(239, 68, 68, 0.35);
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.08) 0%, rgba(239, 68, 68, 0.03) 100%);
    }

    @media (max-width: 860px) {
        .settings-layout {
            grid-template-columns: 1fr;
        }

        .form-row,
        .media-row,
        .social-inputs {
            grid-template-columns: 1fr;
        }

        .drop-upload {
            min-height: 120px;
        }

        .media-preview.avatar {
            width: 180px;
            height: 180px;
        }

        .media-preview.banner {
            height: 180px;
        }

        .settings-tabs {
            position: static;
            flex-direction: row;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .tab-btn {
            width: auto;
            border-radius: 999px;
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
            <div class="feedback success"><?php echo htmlspecialchars($feedback); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="feedback error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

    <?php
    $emailVerified = (int)($user['email_verified'] ?? 0) === 1;
    $pendingEmail = trim($user['pending_email'] ?? '');
    ?>

    <div class="settings-layout">
        <div class="settings-tabs">
            <button class="tab-btn <?php echo $active_tab === 'account' ? 'active' : ''; ?>" type="button" data-tab="account">Conta & Segurança</button>
            <button class="tab-btn <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" type="button" data-tab="profile">Perfil</button>
            <button class="tab-btn <?php echo $active_tab === 'privacy' ? 'active' : ''; ?>" type="button" data-tab="privacy">Privacidade</button>
            <button class="tab-btn <?php echo $active_tab === 'preferences' ? 'active' : ''; ?>" type="button" data-tab="preferences">Preferências</button>
            <button class="tab-btn <?php echo $active_tab === 'danger' ? 'active' : ''; ?>" type="button" data-tab="danger">Zona de Perigo</button>
        </div>

        <div class="settings-panels">
    <!-- TAB: CONTA & SEGURANÇA -->
    <div id="account" class="tab-content <?php echo $active_tab === 'account' ? 'active' : ''; ?>">
        <div class="settings-section">
            <h2><i class="fa-solid fa-user"></i>Dados da Conta</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="active_tab" value="account">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome de Utilizador</label>
                        <input type="text" name="username" maxlength="30" required value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                        <div class="helper-text">3-30 caracteres. Permitidos: letras, números, _ e .</div>
                    </div>
                    <div class="form-group">
                        <label>Email da Conta</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                        <div class="account-meta">
                            <span class="status-pill <?php echo $emailVerified ? 'ok' : ''; ?>"><?php echo $emailVerified ? '✓ Verificado' : '⚠ Não verificado'; ?></span>
                            <?php if (!empty($pendingEmail)): ?>
                                <span class="status-pill">Pendente: <?php echo htmlspecialchars($pendingEmail); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <button type="submit" name="update_account_info" class="btn">Guardar Dados da Conta</button>
            </form>
        </div>

        <div class="settings-section">
            <h2><i class="fa-solid fa-lock"></i>Alterar Password</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="active_tab" value="account">
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="active_tab" value="account">
                    <div class="form-group">
                        <label>Email Atual</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                        <div class="current-value"><?php echo $emailVerified ? '✓ Verificado' : '⚠ Não verificado'; ?></div>
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
    <div id="profile" class="tab-content <?php echo $active_tab === 'profile' ? 'active' : ''; ?>">
        <div class="settings-section">
            <h2><i class="fa-regular fa-image"></i>Avatar e Banner</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="active_tab" value="profile">
                <input type="hidden" name="remove_avatar" id="removeAvatarFlag" value="0">
                <input type="hidden" name="remove_banner" id="removeBannerFlag" value="0">

                <div class="media-stack">
                    <div class="media-row-block">
                        <h3>Avatar</h3>
                        <p class="helper-text">Allowed Formats: JPEG, PNG. Max size: 3mb. Optimal dimensions: 230x230</p>
                        <div class="media-row">
                            <input class="hidden-file-input" type="file" name="avatar" id="settingsAvatarInput" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <label class="drop-upload" for="settingsAvatarInput">Drop image here or<br>click to upload</label>
                            <div class="preview-wrap">
                                <img
                                    src="<?php echo htmlspecialchars($user['avatar'] ?: $defaultAvatarPath); ?>"
                                    id="settingsAvatarPreview"
                                    class="media-preview avatar"
                                    alt="Preview do avatar"
                                    data-default-src="<?php echo htmlspecialchars($defaultAvatarPath); ?>"
                                >
                                <button type="button" class="reset-image-btn" id="resetAvatarBtn" title="Repor avatar">×</button>
                            </div>
                        </div>
                        <div class="selected-file" id="settingsAvatarFileName">Nenhum ficheiro selecionado</div>
                    </div>

                    <div class="media-row-block">
                        <h3>Banner</h3>
                        <p class="helper-text">Allowed Formats: JPEG, PNG. Max size: 6mb. Optimal dimensions: 1700x330</p>
                        <div class="media-row">
                            <input class="hidden-file-input" type="file" name="banner" id="settingsBannerInput" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <label class="drop-upload" for="settingsBannerInput">Drop image here or<br>click to upload</label>
                            <div class="preview-wrap">
                                <img
                                    src="<?php echo htmlspecialchars($user['banner'] ?: $defaultBannerPath); ?>"
                                    id="settingsBannerPreview"
                                    class="media-preview banner"
                                    alt="Preview do banner"
                                    data-default-src="<?php echo htmlspecialchars($defaultBannerPath); ?>"
                                >
                                <button type="button" class="reset-image-btn" id="resetBannerBtn" title="Repor banner">×</button>
                            </div>
                        </div>
                        <div class="selected-file" id="settingsBannerFileName">Nenhum ficheiro selecionado</div>
                    </div>
                </div>

                <button type="submit" name="update_profile_images" class="btn">Guardar Avatar e Banner</button>
            </form>
        </div>

        <div class="settings-section">
            <h2><i class="fa-regular fa-note-sticky"></i>Biografia</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="active_tab" value="profile">
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="active_tab" value="profile">
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
    <div id="privacy" class="tab-content <?php echo $active_tab === 'privacy' ? 'active' : ''; ?>">
        <div class="settings-section">
            <h2><i class="fa-solid fa-user-shield"></i>Definições de Privacidade</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="active_tab" value="privacy">
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
    <div id="preferences" class="tab-content <?php echo $active_tab === 'preferences' ? 'active' : ''; ?>">
        <div class="settings-section">
            <h2><i class="fa-solid fa-palette"></i>Tema</h2>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="active_tab" value="preferences">
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
    <div id="danger" class="tab-content <?php echo $active_tab === 'danger' ? 'active' : ''; ?>">
        <div class="settings-section danger-zone">
            <h2><i class="fa-solid fa-triangle-exclamation"></i>Eliminar Conta</h2>
            <p class="helper-text" style="color:#fecaca;">
                <strong>Aviso:</strong> Eliminar a tua conta é uma ação permanente. Todos os teus dados, reviews e informações de perfil serão apagados e não poderão ser recuperados.
            </p>
                <form method="POST" onsubmit="return confirm('Tens a certeza que pretendes eliminar a tua conta permanentemente? Esta ação não pode ser desfeita!');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="active_tab" value="danger">
                    <div class="form-group">
                        <label>Confirma a tua password para eliminar a conta</label>
                        <input type="password" name="delete_password" required placeholder="Escreve a tua password">
                    </div>
                <button type="submit" name="delete_account" class="btn btn-danger">Eliminar Permanentemente</button>
                </form>
        </div>
    </div>
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

    function wireImagePreview(inputId, previewId, fileNameId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const fileNameEl = fileNameId ? document.getElementById(fileNameId) : null;
        if (!input || !preview) return;

        input.addEventListener('change', event => {
            const file = event.target.files?.[0];
            if (!file) return;

            if (fileNameEl) {
                fileNameEl.textContent = file.name;
            }

            const fileReader = new FileReader();
            fileReader.onload = (readerEvent) => {
                preview.src = readerEvent.target?.result;
            };
            fileReader.readAsDataURL(file);
        });
    }

    function wireImageReset(resetBtnId, inputId, previewId, flagId, fileNameId) {
        const resetBtn = document.getElementById(resetBtnId);
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const flag = document.getElementById(flagId);
        const fileNameEl = fileNameId ? document.getElementById(fileNameId) : null;

        if (!resetBtn || !input || !preview || !flag) return;

        resetBtn.addEventListener('click', () => {
            const defaultSrc = preview.dataset.defaultSrc || '';
            if (defaultSrc) {
                preview.src = defaultSrc;
            }

            input.value = '';
            flag.value = '1';

            if (fileNameEl) {
                fileNameEl.textContent = 'Vai voltar para a imagem default ao guardar';
            }
        });

        input.addEventListener('change', () => {
            flag.value = '0';
        });
    }

    wireImagePreview('settingsAvatarInput', 'settingsAvatarPreview', 'settingsAvatarFileName');
    wireImagePreview('settingsBannerInput', 'settingsBannerPreview', 'settingsBannerFileName');
    wireImageReset('resetAvatarBtn', 'settingsAvatarInput', 'settingsAvatarPreview', 'removeAvatarFlag', 'settingsAvatarFileName');
    wireImageReset('resetBannerBtn', 'settingsBannerInput', 'settingsBannerPreview', 'removeBannerFlag', 'settingsBannerFileName');
</script>

<?php include 'includes/footer.php'; ?>
