<?php
// Carregar header (que já inclui db.php e session_start)
include 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

$lists = $conn->query("SELECT * FROM lists WHERE user_id = $user_id ORDER BY created_at DESC");
$reviews = $conn->query("SELECT * FROM reviews WHERE user_id = $user_id ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perfil - GameList</title>
<link rel="icon" type="image/png" href="img/logo.png">
<link rel="stylesheet" href="css/style.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body { 
        background: linear-gradient(180deg, #0b0b0b 0%, #151515 100%);
        color: #eee;
        font-family: 'Segoe UI', Tahoma, sans-serif;
        overflow-x: hidden;
        padding-top: 80px;
    }
    
    .container { 
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 20px 60px 20px;
    }

    /* --- Banner e Avatar --- */
    .banner-section {
        position: relative;
        margin-bottom: 80px;
    }

    .banner-container {
        position: relative;
        width: 100%;
        height: 280px;
        border-radius: 16px;
        overflow: hidden;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        box-shadow: 0 8px 32px rgba(0, 191, 255, 0.15);
    }

    .banner-container img.banner {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .edit-banner-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(0, 191, 255, 0.9);
        color: #fff;
        border: none;
        padding: 10px 12px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(0, 191, 255, 0.3);
    }

    .edit-banner-btn:hover { 
        background: #00bfff;
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(0, 191, 255, 0.5);
    }

    .avatar-container {
        position: absolute;
        bottom: -65px;
        left: 50%;
        transform: translateX(-50%);
        width: 160px;
        height: 160px;
        border-radius: 50%;
        border: 6px solid #0b0b0b;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        background: #222;
    }

    .avatar-container img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .edit-avatar-btn {
        position: absolute;
        bottom: 0;
        right: 0;
        background: linear-gradient(135deg, #00bfff, #0080ff);
        color: #fff;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        padding: 8px 9px;
        transition: all 0.3s;
        box-shadow: 0 4px 12px rgba(0, 191, 255, 0.3);
    }

    .edit-avatar-btn:hover { 
        transform: scale(1.15);
        box-shadow: 0 6px 16px rgba(0, 191, 255, 0.5);
    }

    /* --- Perfil Info Card --- */
    .profile-info {
        text-align: center;
        background: rgba(30, 30, 35, 0.6);
        backdrop-filter: blur(10px);
        padding: 40px 30px;
        border-radius: 16px;
        border: 1px solid rgba(0, 191, 255, 0.1);
        margin-top: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    .profile-info h1 {
        color: #00bfff;
        font-size: 2.2rem;
        margin-bottom: 10px;
        text-shadow: 0 2px 8px rgba(0, 191, 255, 0.3);
    }

    .profile-email {
        color: #aaa;
        font-size: 1rem;
        margin-bottom: 8px;
    }

    .profile-joined {
        color: #888;
        font-size: 0.9rem;
        margin-bottom: 20px;
    }

    /* --- Seção de Biografia --- */
    .bio-section {
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid rgba(0, 191, 255, 0.2);
    }

    .bio-section h3 {
        color: #00bfff;
        font-size: 1.1rem;
        margin-bottom: 12px;
    }

    .bio-text {
        color: #ccc;
        font-style: italic;
        line-height: 1.6;
        background: rgba(0, 191, 255, 0.05);
        padding: 12px 16px;
        border-left: 3px solid #00bfff;
        border-radius: 6px;
    }

    /* --- Redes Sociais --- */
    .socials-section {
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid rgba(0, 191, 255, 0.2);
    }

    .socials-section h3 {
        color: #00bfff;
        font-size: 1.1rem;
        margin-bottom: 15px;
    }

    .socials-grid {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .social-item {
        text-align: center;
        background: rgba(0, 191, 255, 0.08);
        padding: 16px 20px;
        border-radius: 12px;
        border: 1px solid rgba(0, 191, 255, 0.2);
        transition: all 0.3s;
        min-width: 120px;
    }

    .social-item:hover {
        background: rgba(0, 191, 255, 0.15);
        border-color: rgba(0, 191, 255, 0.4);
        transform: translateY(-4px);
    }

    .social-icon {
        font-size: 1.8rem;
        margin-bottom: 8px;
    }

    .social-link {
        font-size: 0.9rem;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s;
        display: block;
    }

    .social-link:hover {
        text-decoration: underline;
        text-shadow: 0 0 8px currentColor;
    }

    /* --- Botão de Logout --- */
    .logout-btn {
        display: inline-block;
        margin-top: 25px;
        padding: 12px 28px;
        background: linear-gradient(90deg, #ff4444 0%, #cc0000 100%);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(255, 68, 68, 0.3);
    }

    .logout-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(255, 68, 68, 0.5);
    }

    /* --- Modal --- */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(3px);
        z-index: 999;
        justify-content: center;
        align-items: center;
    }

    .modal {
        background: rgba(30, 30, 35, 0.95);
        padding: 30px;
        border-radius: 16px;
        width: 90%;
        max-width: 450px;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
        position: relative;
        animation: slideIn 0.3s ease;
        border: 1px solid rgba(0, 191, 255, 0.2);
    }

    @keyframes slideIn {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .modal h3 { 
        margin-top: 0;
        color: #00bfff;
        font-size: 1.3rem;
    }

    .modal input[type="file"] {
        display: block;
        margin: 10px auto;
        color: #ccc;
    }

    .modal button {
        background: linear-gradient(90deg, #00bfff 0%, #0080ff 100%);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        margin-top: 10px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .modal button:hover { 
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 191, 255, 0.3);
    }

    .close-btn {
        background: none;
        border: none;
        color: #aaa;
        font-size: 28px;
        position: absolute;
        top: 10px;
        right: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .close-btn:hover { 
        color: #00bfff;
        transform: scale(1.2);
    }

    .preview-box {
        position: relative;
        background: #111;
        border-radius: 10px;
        overflow: visible;
        margin-bottom: 70px;
        height: 230px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        flex-direction: column;
    }

    .preview-banner {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-radius: 10px 10px 0 0;
    }

    .preview-avatar {
        position: absolute;
        bottom: -40px;
        left: 50%;
        transform: translateX(-50%);
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 4px solid #111;
        object-fit: cover;
        background: #111;
    }

    /* --- Listas e Reviews --- */
    .section-title {
        color: #00bfff;
        font-size: 1.6rem;
        margin: 40px 0 25px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid rgba(0, 191, 255, 0.3);
    }

    .lists, .reviews { 
        margin-top: 30px; 
    }

    .list { 
        margin-bottom: 40px; 
    }

    .list h3 {
        color: #00bfff;
        font-size: 1.2rem;
        margin-bottom: 20px;
    }

    .game-grid { 
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
    }

    .game-card { 
        background: rgba(30, 30, 35, 0.6);
        border-radius: 12px;
        overflow: hidden;
        text-align: center;
        transition: all 0.3s;
        border: 1px solid rgba(0, 191, 255, 0.1);
    }

    .game-card:hover { 
        transform: translateY(-8px);
        box-shadow: 0 12px 32px rgba(0, 191, 255, 0.2);
        border-color: rgba(0, 191, 255, 0.3);
    }

    .game-card img { 
        width: 100%;
        height: 180px;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .game-card:hover img {
        transform: scale(1.05);
    }

    .game-card p {
        padding: 10px;
        color: #ccc;
        font-size: 0.9rem;
    }

    .game-card a {
        color: #00bfff;
        text-decoration: none;
    }

    .review-card { 
        background: rgba(30, 30, 35, 0.6);
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 16px;
        border: 1px solid rgba(0, 191, 255, 0.1);
        transition: all 0.3s;
    }

    .review-card:hover {
        border-color: rgba(0, 191, 255, 0.3);
        background: rgba(30, 30, 35, 0.8);
    }

    .review-card p { 
        margin: 8px 0;
        color: #ccc;
    }

    .review-card strong {
        color: #00bfff;
        font-size: 1.1rem;
    }

    .review-rating { 
        color: #ffcc00;
        font-weight: bold;
        font-size: 1.1rem;
    }

    .review-card small { 
        color: #888;
    }

    .remove-btn { 
        background: linear-gradient(90deg, #ff4444, #cc0000);
        border: none;
        padding: 8px 14px;
        border-radius: 6px;
        color: #fff;
        cursor: pointer;
        margin-top: 10px;
        margin-right: 8px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .remove-btn:hover { 
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 68, 68, 0.3);
    }

    .edit-btn {
        background: linear-gradient(90deg, #00bfff, #0080ff);
        color: #fff;
        padding: 8px 14px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
        margin-right: 8px;
    }

    .edit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 191, 255, 0.3);
    }

    .empty-message {
        color: #888;
        font-style: italic;
        padding: 20px;
        text-align: center;
    }

    @media (max-width: 768px) {
        body { padding-top: 70px; }
        .profile-info h1 { font-size: 1.8rem; }
        .avatar-container { width: 140px; height: 140px; }
        .avatar-container img { width: 140px; height: 140px; }
        .game-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); }
        .socials-grid { gap: 12px; }
        .social-item { min-width: 100px; padding: 12px 16px; }
    }
</style>
</head>
<body>

<div class="container">

    <!-- Banner Section -->
    <div class="banner-section">
        <div class="banner-container">
            <img src="<?php echo $user['banner'] ?: 'https://via.placeholder.com/1000x280?text=Sem+Banner'; ?>" class="banner" alt="Banner">
            <button class="edit-banner-btn" onclick="openModal()">✏️</button>
        </div>

        <!-- Avatar -->
        <div class="avatar-container">
            <img src="<?php echo $user['avatar'] ?: 'https://via.placeholder.com/160x160?text=Avatar'; ?>" alt="Avatar">
            <button class="edit-avatar-btn" onclick="openModal()">✏️</button>
        </div>
    </div>

    <!-- Profile Info -->
    <div class="profile-info">
        <h1>👤 <?php echo htmlspecialchars($user['username']); ?></h1>
        <div class="profile-email">📧 <?php echo htmlspecialchars($user['email']); ?></div>
        <div class="profile-joined">Conta criada em <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
        
        <!-- Biografia -->
        <?php if (!empty($user['biography'])): ?>
            <div class="bio-section">
                <h3>📝 Sobre mim</h3>
                <div class="bio-text"><?php echo nl2br(htmlspecialchars($user['biography'])); ?></div>
            </div>
        <?php endif; ?>

        <!-- Redes Sociais -->
        <?php 
        $socials = json_decode($user['social_links'] ?? '{}', true) ?? [];
        $has_socials = !empty($socials['twitter']) || !empty($socials['instagram']) || !empty($socials['discord']);
        if ($has_socials): 
        ?>
            <div class="socials-section">
                <h3>🌐 Redes Sociais</h3>
                <div class="socials-grid">
                    <?php if (!empty($socials['twitter'])): ?>
                        <div class="social-item">
                            <div class="social-icon">𝕏</div>
                            <a href="https://twitter.com/<?php echo htmlspecialchars($socials['twitter']); ?>" target="_blank" class="social-link" style="color: #1DA1F2;">
                                <?php echo htmlspecialchars($socials['twitter']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($socials['instagram'])): ?>
                        <div class="social-item">
                            <div class="social-icon">📷</div>
                            <a href="https://instagram.com/<?php echo htmlspecialchars($socials['instagram']); ?>" target="_blank" class="social-link" style="color: #E4405F;">
                                <?php echo htmlspecialchars($socials['instagram']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($socials['discord'])): ?>
                        <div class="social-item">
                            <div class="social-icon">💬</div>
                            <span class="social-link" style="color: #5865F2;">
                                <?php echo htmlspecialchars($socials['discord']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <a href="logout.php" class="logout-btn">🚪 Terminar sessão</a>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <button class="close-btn" onclick="closeModal()">×</button>
            <h3>Alterar imagens do perfil</h3>

            <!-- Preview -->
            <div class="preview-box" id="previewBox">
                <img src="<?php echo $user['banner'] ?: 'https://via.placeholder.com/1000x250?text=Sem+Banner'; ?>" id="previewBanner" class="preview-banner">
                <img src="<?php echo $user['avatar'] ?: 'https://via.placeholder.com/130x130?text=Avatar'; ?>" id="previewAvatar" class="preview-avatar">
            </div>

            <form action="update_profile_images.php" method="POST" enctype="multipart/form-data">
                <label>🖼️ Novo banner:</label>
                <input type="file" name="banner" id="bannerInput" accept="image/*">
                <label>👤 Novo avatar:</label>
                <input type="file" name="avatar" id="avatarInput" accept="image/*">
                <button type="submit">Guardar Alterações</button>
            </form>
        </div>
    </div>

    <!-- Listas -->
    <div class="lists">
        <h2 class="section-title">🎮 As minhas listas</h2>
        <?php if ($lists->num_rows > 0): ?>
            <?php while($list = $lists->fetch_assoc()): ?>
                <div class="list">
                    <h3><?php echo htmlspecialchars($list['name']); ?></h3>
                    <?php
                        $list_id = $list['id'];
                        $items = $conn->query("SELECT * FROM list_items WHERE list_id = $list_id ORDER BY added_at DESC");
                        if ($items->num_rows > 0):
                    ?>
                        <div class="game-grid">
                            <?php while($item = $items->fetch_assoc()): ?>
                                <div class="game-card">
                                    <a href="game.php?id=<?php echo $item['game_id']; ?>&name=<?php echo urlencode($item['game_name']); ?>&image=<?php echo urlencode($item['game_image']); ?>">
                                        <img src="<?php echo htmlspecialchars($item['game_image']); ?>" alt="<?php echo htmlspecialchars($item['game_name']); ?>">
                                        <p><?php echo htmlspecialchars($item['game_name']); ?></p>
                                    </a>
                                    <form method="POST" action="remove_from_list.php" style="padding: 0 10px 10px 10px;">
                                        <input type="hidden" name="list_id" value="<?php echo $list_id; ?>">
                                        <input type="hidden" name="game_id" value="<?php echo $item['game_id']; ?>">
                                        <button type="submit" class="remove-btn">❌ Remover</button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-message">Lista vazia.</p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-message">Ainda não tens listas criadas.</p>
        <?php endif; ?>
    </div>

    <!-- Reviews -->
    <div class="reviews">
        <h2 class="section-title">📝 As minhas reviews</h2>
        <?php if ($reviews->num_rows > 0): ?>
            <?php while($review = $reviews->fetch_assoc()): ?>
                <div class="review-card">
                    <strong><?php echo htmlspecialchars($review['game_name']); ?></strong>
                    <p class="review-rating">⭐ <?php echo $review['rating']; ?>/10</p>
                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                    <small>Escrita em <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></small>
                    
                    <div style="margin-top: 12px;">
                        <a href="edit_review.php?id=<?php echo $review['id']; ?>" class="edit-btn">✏️ Editar</a>
                        <form method="POST" action="remove_review.php" style="display: inline;">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" class="remove-btn">❌ Apagar</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="empty-message">Ainda não escreveste nenhuma review.</p>
        <?php endif; ?>
    </div>

</div>

<script>
    function openModal() {
        document.getElementById('modalOverlay').style.display = 'flex';
    }
    function closeModal() {
        document.getElementById('modalOverlay').style.display = 'none';
    }

    // Preview das imagens escolhidas
    const bannerInput = document.getElementById('bannerInput');
    const avatarInput = document.getElementById('avatarInput');
    const previewBanner = document.getElementById('previewBanner');
    const previewAvatar = document.getElementById('previewAvatar');

    bannerInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) previewBanner.src = URL.createObjectURL(file);
    });

    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) previewAvatar.src = URL.createObjectURL(file);
    });
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
