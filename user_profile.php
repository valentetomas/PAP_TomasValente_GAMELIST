<?php
include 'includes/header.php';
require_once 'includes/achievements.php';

// Obter ID do utilizador da URL
$profile_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($profile_user_id <= 0) {
    header("Location: index.php");
    exit;
}

// Buscar dados do utilizador com prepared statement
$stmt = $conn->prepare("SELECT id, username, avatar, banner, created_at, banned, biography, social_links FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Utilizador não encontrado.";
    exit;
}

$profile_user = $result->fetch_assoc();

// Verificar se está banido
if ($profile_user['banned']) {
    echo "Este utilizador foi banido.";
    exit;
}

// Verificar se é o próprio perfil
$is_own_profile = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_user_id;

if ($is_own_profile) {
    header("Location: profile.php");
    exit;
}

// Estatísticas
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM lists WHERE user_id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$lists_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ? AND approved = 1");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$reviews_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$followers_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$following_count = $stmt->get_result()->fetch_assoc()['count'];

// Verificar se já segue este utilizador
$is_following = false;
if (isset($_SESSION['user_id'])) {
    $current_user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->bind_param("ii", $current_user_id, $profile_user_id);
    $stmt->execute();
    $is_following = $stmt->get_result()->num_rows > 0;
}

// Buscar listas públicas
$stmt = $conn->prepare("SELECT * FROM lists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$lists = $stmt->get_result();

// Buscar reviews aprovadas
$stmt = $conn->prepare("SELECT * FROM reviews WHERE user_id = ? AND approved = 1 ORDER BY created_at DESC");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$reviews = $stmt->get_result();

// Buscar distribuição de ratings
$stmt = $conn->prepare("SELECT rating, COUNT(*) as count FROM reviews WHERE user_id = ? AND approved = 1 GROUP BY rating");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$ratings_result = $stmt->get_result();

$ratings_distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0];
$total_reviews = 0;
while ($row = $ratings_result->fetch_assoc()) {
    $ratings_distribution[$row['rating']] = (int)$row['count'];
    $total_reviews += (int)$row['count'];
}

// Calcular percentagens
$max_count = max($ratings_distribution);

// Buscar conquistas do utilizador
$all_achievements_result = $conn->query("SELECT * FROM achievements ORDER BY points ASC");
$all_achievements = [];
while ($ach = $all_achievements_result->fetch_assoc()) {
    $all_achievements[] = $ach;
}

$unlocked_ids = [];
$stmt = $conn->prepare("SELECT achievement_id FROM user_achievements WHERE user_id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $unlocked_ids[] = $row['achievement_id'];
}

$achievement_progress = getAchievementProgress($profile_user_id);
?>
<title><?php echo htmlspecialchars($profile_user['username']); ?> - GameList</title>
</head>
<body>
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

    /* Banner e Avatar */
    .banner-section {
        position: relative;
        margin-bottom: 20px;
        margin-top: 0;
    }

    .banner-container {
        position: relative;
        width: 100%;
        height: 280px;
        border-radius: 0;
        overflow: hidden;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    }

    .banner-container img.banner {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Avatar e Info no Banner */
    .profile-header {
        position: absolute;
        bottom: 20px;
        left: 30px;
        display: flex;
        align-items: flex-end;
        gap: 20px;
        z-index: 10;
    }

    .avatar-container {
        width: 180px;
        height: 180px;
        border-radius: 4px;
        border: 4px solid #0b0b0b;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.8);
        background: #222;
        flex-shrink: 0;
    }

    .avatar-container img {
        width: 100%;
        height: 100%;
        border-radius: 2px;
        object-fit: cover;
    }

    .profile-header-info {
        padding-bottom: 10px;
    }

    .profile-header-info h1 {
        font-size: 2.5rem;
        color: #fff;
        margin: 0 0 8px 0;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
    }

    .profile-social-links {
        display: flex;
        gap: 12px;
        margin-bottom: 12px;
    }

    .profile-social-links a {
        color: #00bfff;
        text-decoration: none;
        font-size: 14px;
        padding: 6px 12px;
        background: rgba(0, 0, 0, 0.7);
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }

    .profile-social-links a:hover {
        background: rgba(0, 191, 255, 0.2);
        transform: translateY(-2px);
    }

    /* User Info */
    .content-wrapper {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 20px;
        margin-top: 20px;
    }

    .sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .main-content {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Bio Card */
    .bio-card {
        background: #1a1a1a;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #2a2a2a;
    }

    .bio-card h3 {
        color: #00bfff;
        font-size: 1rem;
        margin: 0 0 12px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }

    .bio-text {
        color: #ccc;
        line-height: 1.6;
        font-size: 14px;
    }

    /* Personal Ratings Chart */
    .ratings-card {
        background: #1a1a1a;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #2a2a2a;
    }

    .ratings-card h3 {
        color: #00bfff;
        font-size: 1rem;
        margin: 0 0 15px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }

    .ratings-bars {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        height: 150px;
    }

    .rating-row {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        gap: 6px;
        height: 100%;
    }

    .rating-count {
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        min-height: 16px;
    }

    .rating-bar-container {
        width: 100%;
        flex: 1;
        background: #0f0f0f;
        border-radius: 4px;
        overflow: hidden;
        position: relative;
        display: flex;
        align-items: flex-end;
    }

    .rating-bar {
        width: 100%;
        background: linear-gradient(180deg, #ff69b4, #ff1493);
        transition: height 0.5s ease;
        border-radius: 4px;
    }

    .rating-label {
        color: #999;
        font-size: 11px;
        text-align: center;
    }

    /* Achievements Card */
    .achievements-card {
        background: #1a1a1a;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #2a2a2a;
    }

    .achievements-card h3 {
        color: #00bfff;
        font-size: 1rem;
        margin: 0 0 8px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }

    .achievement-progress {
        font-size: 12px;
        color: #999;
        margin-bottom: 15px;
    }

    .achievement-progress-bar {
        height: 6px;
        background: #0f0f0f;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 8px;
    }

    .achievement-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #00b4ff, #8a2be2);
        transition: width 0.5s ease;
    }

    .achievements-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .achievement-item {
        background: #0f0f0f;
        border: 2px solid #2a2a2a;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        position: relative;
    }

    .achievement-item.unlocked {
        border-color: #00bfff;
        background: rgba(0, 191, 255, 0.05);
    }

    .achievement-item.locked {
        opacity: 0.4;
        filter: grayscale(100%);
    }

    .achievement-item:hover {
        transform: translateY(-3px);
        border-color: #00bfff;
    }

    .achievement-icon {
        width: 50px;
        height: 50px;
        object-fit: contain;
        margin-bottom: 8px;
    }

    .achievement-name {
        font-size: 11px;
        color: #fff;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .achievement-points {
        font-size: 10px;
        color: #ffd700;
    }

    .achievement-tooltip {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #000;
        color: #fff;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 11px;
        white-space: nowrap;
        margin-bottom: 8px;
        z-index: 1000;
        border: 1px solid #00bfff;
    }

    .achievement-item:hover .achievement-tooltip {
        display: block;
    }

    /* Stats Grid */
    .stats-grid {
        display: flex;
        gap: 20px;
        justify-content: space-between;
    }

    .stat-card {
        background: #1a1a1a;
        border-radius: 8px;
        padding: 25px 20px;
        text-align: center;
        border: 1px solid #2a2a2a;
        transition: all 0.3s;
        flex: 1;
    }

    .stat-card:hover {
        border-color: #00bfff;
        transform: translateY(-2px);
    }

    .stat-value {
        font-size: 2.2rem;
        font-weight: 700;
        color: #00bfff;
        display: block;
        margin-bottom: 6px;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Follow Button */
    .follow-btn {
        padding: 10px 24px;
        border: none;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        background: #00b4ff;
        color: #fff;
    }

    .follow-btn:hover {
        background: #0095d9;
    }

    .follow-btn.following {
        background: #444;
    }

    .follow-btn.following:hover {
        background: #ff4444;
    }

    /* Tabs */
    .tabs {
        display: flex;
        gap: 5px;
        margin-bottom: 20px;
        border-bottom: 2px solid #2a2a2a;
        padding-bottom: 0;
    }

    .tab-btn {
        padding: 12px 24px;
        border: none;
        background: transparent;
        color: #999;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
    }

    .tab-btn.active {
        color: #fff;
        border-bottom-color: #00bfff;
    }

    .tab-btn:hover {
        color: #fff;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* Lists */
    .list-card {
        background: #1a1a1a;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .list-card h3 {
        color: #00bfff;
        margin-bottom: 10px;
    }

    .list-card p {
        color: #ccc;
        font-size: 14px;
        margin-bottom: 10px;
    }

    /* Lists - Game Grid */
    .list-items {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 20px;
    }

    .game-card {
        background: #1a1a1a;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s;
        border: 1px solid #2a2a2a;
    }

    .game-card:hover {
        transform: translateY(-5px);
        border-color: #00bfff;
        box-shadow: 0 8px 20px rgba(0, 191, 255, 0.3);
    }

    .game-card a {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .list-item-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .game-card p {
        padding: 12px;
        color: #fff;
        font-size: 14px;
        font-weight: 500;
        text-align: center;
    }

    /* Reviews */
    .review-card {
        background: #1a1a1a;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .review-card h3 {
        color: #00bfff;
        margin-bottom: 10px;
    }

    .review-rating {
        color: #ffd700;
        margin-bottom: 10px;
    }

    .review-comment {
        color: #ccc;
        line-height: 1.6;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }

    .empty-state svg {
        width: 80px;
        height: 80px;
        margin-bottom: 20px;
        opacity: 0.3;
    }

    /* Social Links */
    .socials-card {
        background: #1a1a1a;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #2a2a2a;
    }

    .socials-card h3 {
        color: #00bfff;
        font-size: 1rem;
        margin: 0 0 15px 0;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
    }

    .socials-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .social-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px;
        background: rgba(0, 191, 255, 0.05);
        border-radius: 4px;
        transition: all 0.2s;
    }

    .social-item:hover {
        background: rgba(0, 191, 255, 0.1);
    }

    .social-icon {
        font-size: 1.5rem;
    }

    .social-link {
        font-size: 0.9rem;
        text-decoration: none;
        font-weight: 500;
        color: #00bfff;
        word-break: break-word;
    }

    .social-link:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .content-wrapper {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            flex-direction: column;
        }

        .stat-value {
            font-size: 2rem;
        }

        .profile-header {
            flex-direction: column;
            align-items: flex-start;
            left: 20px;
            bottom: 10px;
        }

        .avatar-container {
            width: 120px;
            height: 120px;
        }

        .profile-header-info h1 {
            font-size: 1.8rem;
        }
    }
</style>

<div class="container">
    <div class="banner-section">
        <div class="banner-container">
            <?php if ($profile_user['banner']): ?>
                <img src="<?php echo htmlspecialchars($profile_user['banner']); ?>" alt="Banner" class="banner">
            <?php endif; ?>
            
            <div class="profile-header">
                <div class="avatar-container">
                    <img src="<?php echo htmlspecialchars($profile_user['avatar'] ?: 'https://via.placeholder.com/180?text=User'); ?>" alt="Avatar">
                </div>
                <div class="profile-header-info">
                    <h1><?php echo htmlspecialchars($profile_user['username']); ?></h1>
                    
                    <?php 
                    $socials = json_decode($profile_user['social_links'] ?? '{}', true) ?? [];
                    if (!empty($socials['twitter']) || !empty($socials['instagram']) || !empty($socials['discord'])): 
                    ?>
                        <div class="profile-social-links">
                            <?php if (!empty($socials['twitter'])): ?>
                                <a href="https://twitter.com/<?php echo htmlspecialchars($socials['twitter']); ?>" target="_blank">
                                    𝕏 <?php echo htmlspecialchars($socials['twitter']); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($socials['instagram'])): ?>
                                <a href="https://instagram.com/<?php echo htmlspecialchars($socials['instagram']); ?>" target="_blank">
                                    📷 <?php echo htmlspecialchars($socials['instagram']); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($socials['discord'])): ?>
                                <span style="color: #999; font-size: 14px; padding: 6px 12px; background: rgba(0, 0, 0, 0.7); border-radius: 4px;">
                                    💬 <?php echo htmlspecialchars($socials['discord']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button id="followBtn" class="follow-btn <?php echo $is_following ? 'following' : ''; ?>" 
                                data-user-id="<?php echo $profile_user_id; ?>"
                                data-following="<?php echo $is_following ? 'true' : 'false'; ?>">
                            <?php echo $is_following ? 'A seguir ✓' : 'Seguir'; ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <?php if (!empty($profile_user['biography'])): ?>
                <div class="bio-card">
                    <h3>Bio</h3>
                    <div class="bio-text"><?php echo nl2br(htmlspecialchars($profile_user['biography'])); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($total_reviews > 0): ?>
                <div class="ratings-card">
                    <h3>Avaliações Pessoais</h3>
                    <div class="ratings-bars">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <div class="rating-row">
                                <div class="rating-count"><?php echo $ratings_distribution[$i] > 0 ? $ratings_distribution[$i] : ''; ?></div>
                                <div class="rating-bar-container">
                                    <div class="rating-bar" style="height: <?php echo $max_count > 0 ? ($ratings_distribution[$i] / $max_count * 100) : 0; ?>%;"></div>
                                </div>
                                <div class="rating-label"><?php echo $i; ?>★</div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Achievements -->
            <div class="achievements-card">
                <h3>Conquistas</h3>
                <div class="achievement-progress">
                    <?php echo $achievement_progress['unlocked']; ?> / <?php echo $achievement_progress['total']; ?> desbloqueadas (<?php echo $achievement_progress['percentage']; ?>%)
                    <div class="achievement-progress-bar">
                        <div class="achievement-progress-fill" style="width: <?php echo $achievement_progress['percentage']; ?>%;"></div>
                    </div>
                </div>
                <div class="achievements-grid">
                    <?php foreach ($all_achievements as $achievement): ?>
                        <?php $is_unlocked = in_array($achievement['id'], $unlocked_ids); ?>
                        <div class="achievement-item <?php echo $is_unlocked ? 'unlocked' : 'locked'; ?>">
                            <div class="achievement-tooltip">
                                <?php echo htmlspecialchars($achievement['description']); ?>
                            </div>
                            <img src="<?php echo htmlspecialchars($achievement['icon']); ?>" 
                                 alt="<?php echo htmlspecialchars($achievement['name']); ?>" 
                                 class="achievement-icon">
                            <div class="achievement-name"><?php echo htmlspecialchars($achievement['name']); ?></div>
                            <div class="achievement-points"><?php echo $achievement['points']; ?> pts</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-value" id="followersCount"><?php echo $followers_count; ?></span>
                    <span class="stat-label">Seguidores</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $following_count; ?></span>
                    <span class="stat-label">A seguir</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?php echo $reviews_count; ?></span>
                    <span class="stat-label">Reviews</span>
                </div>
            </div>

            <div class="tabs">
                <?php 
                // Resetar o ponteiro do resultado para reler as listas
                $lists->data_seek(0);
                $list_index = 0;
                while ($list = $lists->fetch_assoc()): 
                ?>
                    <button class="tab-btn <?php echo $list_index === 0 ? 'active' : ''; ?>" data-tab="list-<?php echo $list['id']; ?>">
                        <?php echo htmlspecialchars($list['name']); ?>
                    </button>
                <?php 
                    $list_index++;
                endwhile; 
                ?>
                <button class="tab-btn" data-tab="reviews">Reviews</button>
            </div>

            <?php 
            // Resetar novamente para criar o conteúdo das tabs
            $lists->data_seek(0);
            $list_index = 0;
            while ($list = $lists->fetch_assoc()): 
            ?>
                <div id="list-<?php echo $list['id']; ?>Tab" class="tab-content <?php echo $list_index === 0 ? 'active' : ''; ?>">
                    <div class="list-items">
                        <?php
                        $list_id = $list['id'];
                        $stmt = $conn->prepare("SELECT * FROM list_items WHERE list_id = ? ORDER BY added_at DESC");
                        $stmt->bind_param("i", $list_id);
                        $stmt->execute();
                        $items = $stmt->get_result();
                        
                        if ($items->num_rows > 0):
                            while ($item = $items->fetch_assoc()):
                        ?>
                            <div class="game-card">
                                <a href="game.php?id=<?php echo $item['game_id']; ?>&name=<?php echo urlencode($item['game_name']); ?>&image=<?php echo urlencode($item['game_image']); ?>">
                                    <img src="<?php echo htmlspecialchars($item['game_image'] ?: 'https://via.placeholder.com/180x240?text=Game'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['game_name']); ?>" 
                                         class="list-item-img">
                                    <p><?php echo htmlspecialchars($item['game_name']); ?></p>
                                </a>
                            </div>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <div class="empty-state">
                                <p>Esta lista está vazia.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php 
                $list_index++;
            endwhile; 
            
            // Se não há listas, mostrar mensagem
            if ($lists->num_rows === 0):
            ?>
                <div id="list-emptyTab" class="tab-content active">
                    <div class="empty-state">
                        <p>Este utilizador ainda não criou nenhuma lista.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div id="reviewsTab" class="tab-content">
        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="review-card">
                    <h3><?php echo htmlspecialchars($review['game_name'] ?? 'Jogo #' . $review['game_id']); ?></h3>
                    <div class="review-rating">
                        <?php echo str_repeat('⭐', $review['rating']); ?>
                    </div>
                    <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    <p style="font-size: 12px; color: #666; margin-top: 10px;">
                        <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                    </p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>Este utilizador ainda não escreveu nenhuma review.</p>
            </div>
        <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;
        
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        btn.classList.add('active');
        document.getElementById(tab + 'Tab').classList.add('active');
    });
});

// Follow/Unfollow
const followBtn = document.getElementById('followBtn');
if (followBtn) {
    followBtn.addEventListener('click', async () => {
        const userId = followBtn.dataset.userId;
        const isFollowing = followBtn.dataset.following === 'true';
        const action = isFollowing ? 'unfollow' : 'follow';
        
        followBtn.disabled = true;
        
        try {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('user_id', userId);
            
            const response = await fetch('follow.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                const newFollowing = action === 'follow';
                followBtn.dataset.following = newFollowing ? 'true' : 'false';
                followBtn.textContent = newFollowing ? 'A seguir ✓' : 'Seguir';
                followBtn.classList.toggle('following', newFollowing);
                
                // Atualizar contador
                const followersCount = document.getElementById('followersCount');
                const currentCount = parseInt(followersCount.textContent);
                followersCount.textContent = newFollowing ? currentCount + 1 : currentCount - 1;
            } else {
                alert(data.message || 'Erro ao processar ação');
            }
        } catch (error) {
            console.error('Erro:', error);
            alert('Erro ao processar ação');
        } finally {
            followBtn.disabled = false;
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
