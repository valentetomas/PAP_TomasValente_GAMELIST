<?php
require_once 'includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

$page_title = htmlspecialchars($profile_user['username']) . ' - GameList';
include 'includes/header.php';
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

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        background: linear-gradient(180deg, var(--bg-dark) 0%, #101218 100%);
        color: var(--text-main);
        font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
        padding-top: 80px;
        overflow-x: hidden;
    }

    .profile-shell {
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 20px 60px;
    }

    .hero-card {
        position: relative;
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--border);
        background: var(--surface);
    }

    .banner-media {
        height: 300px;
        position: relative;
        background: linear-gradient(135deg, #1f2330 0%, #151821 100%);
    }

    .banner-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(11, 12, 15, 0.98) 6%, rgba(11, 12, 15, 0.32) 55%, rgba(11, 12, 15, 0.1) 100%);
    }

    .hero-content {
        position: absolute;
        left: 24px;
        right: 24px;
        bottom: 24px;
        z-index: 4;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 22px;
        flex-wrap: wrap;
    }

    .hero-left {
        display: flex;
        align-items: flex-end;
        gap: 16px;
    }

    .avatar-wrap {
        width: 136px;
        height: 136px;
        border-radius: 12px;
        border: 2px solid rgba(255,255,255,0.12);
        background: #12141a;
        overflow: hidden;
        flex-shrink: 0;
        box-shadow: 0 14px 32px rgba(0,0,0,.45);
    }

    .avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .hero-meta h1 {
        font-size: clamp(1.75rem, 2.7vw, 2.5rem);
        line-height: 1.1;
        margin-bottom: 8px;
    }

    .social-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .social-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 999px;
        border: 1px solid var(--border);
        color: #d4d8df;
        background: rgba(255, 255, 255, 0.06);
        text-decoration: none;
        transition: .2s ease;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }

    .social-chip:hover {
        border-color: rgba(255, 51, 102, 0.45);
        color: #fff;
        background: rgba(255, 51, 102, 0.14);
    }

    .social-chip .x-icon svg {
        width: 14px;
        height: 14px;
        fill: currentColor;
    }

    .hero-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .follow-btn {
        border: none;
        border-radius: 10px;
        padding: 10px 18px;
        color: #fff;
        font-weight: 700;
        background: linear-gradient(135deg, var(--accent), #cc2952);
        cursor: pointer;
    }

    .follow-btn:hover {
        background: linear-gradient(135deg, var(--accent-hover), #e62e5c);
    }

    .follow-btn.following {
        background: linear-gradient(135deg, #2a2d36 0%, #1e2129 100%);
    }

    .follow-btn.following:hover {
        background: linear-gradient(135deg, #353947 0%, #262b36 100%);
    }

    .profile-layout {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 18px;
        margin-top: 18px;
    }

    .left-column,
    .right-column {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 16px;
    }

    .panel-title {
        font-size: 0.82rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 12px;
    }

    .bio-text {
        color: #d2d6de;
        line-height: 1.6;
        font-size: 0.94rem;
    }

    .ratings-bars {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        height: 140px;
    }

    .rating-row {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        height: 100%;
    }

    .rating-count { font-size: 11px; color: #fff; min-height: 15px; }

    .rating-bar-wrap {
        width: 100%;
        flex: 1;
        border-radius: 6px;
        background: #0f1116;
        border: 1px solid var(--border);
        display: flex;
        align-items: flex-end;
        overflow: hidden;
    }

    .rating-bar {
        width: 100%;
        background: linear-gradient(180deg, #ff5e88 0%, #cc2952 100%);
        border-radius: 6px;
    }

    .rating-label { font-size: 10px; color: var(--text-muted); }

    .achievement-progress {
        font-size: 0.82rem;
        color: var(--text-muted);
        margin-bottom: 10px;
    }

    .achievement-progress-bar {
        width: 100%;
        height: 6px;
        border-radius: 99px;
        background: #0f1116;
        border: 1px solid var(--border);
        overflow: hidden;
        margin-top: 6px;
    }

    .achievement-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--accent), #ff6b93);
    }

    .achievements-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .achievement-item {
        position: relative;
        text-align: center;
        padding: 10px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: #111319;
    }

    .achievement-item.unlocked {
        border-color: rgba(255, 51, 102, 0.4);
        background: rgba(255, 51, 102, 0.08);
    }

    .achievement-item.locked {
        opacity: .45;
        filter: grayscale(100%);
    }

    .achievement-icon { width: 44px; height: 44px; object-fit: contain; margin-bottom: 8px; }
    .achievement-name { font-size: 10px; font-weight: 600; color: #fff; margin-bottom: 2px; }
    .achievement-points { font-size: 10px; color: #f7c948; }

    .achievement-tooltip {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%);
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 11px;
        white-space: nowrap;
        border: 1px solid var(--border);
        background: #0f1116;
        color: #fff;
        z-index: 10;
    }

    .achievement-item:hover .achievement-tooltip { display: block; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .stat-card {
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 15px;
        text-align: center;
    }

    .stat-value {
        display: block;
        font-size: 1.9rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }

    .stat-label {
        margin-top: 8px;
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--text-muted);
    }

    .tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .tab-btn {
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.02);
        color: #c9ced6;
        border-radius: 999px;
        padding: 8px 14px;
        cursor: pointer;
        font-size: .85rem;
        font-weight: 600;
    }

    .tab-btn.active {
        color: #fff;
        border-color: rgba(255, 51, 102, 0.55);
        background: rgba(255, 51, 102, 0.16);
    }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .list-items {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(154px, 1fr));
        gap: 12px;
        min-height: 250px;
    }

    .list-items .empty-state:only-child {
        grid-column: 1 / -1;
        justify-self: center;
        align-self: center;
        width: min(420px, 100%);
    }

    .game-card {
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 10px;
        overflow: hidden;
        background: #222;
        aspect-ratio: 2 / 3;
        position: relative;
        transition: transform .3s cubic-bezier(.25,.8,.25,1), box-shadow .3s ease, border-color .3s ease;
    }

    .game-card a { text-decoration: none; color: inherit; display: block; height: 100%; }

    .list-item-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: filter .3s ease, transform .3s ease;
    }

    .game-card p {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 88%;
        font-size: 1rem;
        font-weight: 700;
        text-align: center;
        color: #fff;
        text-shadow: 0 2px 10px rgba(0,0,0,.9);
        opacity: 0;
        z-index: 3;
        pointer-events: none;
        transition: opacity .3s ease;
    }

    .game-card:hover {
        transform: scale(1.03);
        border-color: rgba(255,255,255,0.3);
        box-shadow: 0 10px 24px rgba(0,0,0,.45);
        z-index: 5;
    }

    .game-card:hover .list-item-img {
        filter: brightness(0.26);
        transform: scale(1.02);
    }

    .game-card:hover p { opacity: 1; }

    .review-card {
        border: 1px solid var(--border);
        border-radius: 14px;
        background: var(--surface-alt);
        padding: 16px;
        margin-bottom: 14px;
        transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease;
    }

    .review-card:hover {
        border-color: rgba(255, 51, 102, 0.35);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,.28);
    }

    .review-layout {
        display: grid;
        grid-template-columns: 84px 1fr;
        gap: 14px;
        align-items: start;
    }

    .review-cover {
        width: 84px;
        height: 112px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.12);
        background: #10131a;
        flex-shrink: 0;
    }

    .review-cover img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .review-body { min-width: 0; }

    .review-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .review-title { font-size: 1rem; font-weight: 700; color: #fff; margin: 0; }

    .review-score {
        border: 1px solid rgba(255, 209, 102, 0.35);
        background: rgba(255, 209, 102, 0.12);
        color: #ffd166;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: .78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .review-comment {
        color: #d2d6de;
        line-height: 1.6;
        margin-bottom: 10px;
        font-size: .92rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 10px;
        padding: 10px 12px;
    }

    .review-footer {
        margin-top: 8px;
        padding-top: 10px;
        border-top: 1px solid rgba(255,255,255,0.06);
    }

    .review-date {
        color: var(--text-muted);
        font-size: .78rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .empty-state {
        width: 100%;
        text-align: center;
        color: var(--text-muted);
        border: 1px dashed var(--border);
        border-radius: 10px;
        padding: 28px 16px;
    }

    @media (max-width: 992px) {
        .profile-layout { grid-template-columns: 1fr; }
        .hero-content { left: 16px; right: 16px; bottom: 16px; }
    }

    @media (max-width: 680px) {
        body { padding-top: 70px; }
        .banner-media { height: 270px; }
        .hero-left { align-items: center; }
        .avatar-wrap { width: 104px; height: 104px; }
        .hero-meta h1 { font-size: 1.6rem; }
        .stats-grid { grid-template-columns: 1fr; }
        .achievements-grid { grid-template-columns: repeat(2, 1fr); }
        .review-layout { grid-template-columns: 1fr; }
        .review-cover { width: 100%; height: 170px; }
    }
</style>
<?php $socials = json_decode($profile_user['social_links'] ?? '{}', true) ?? []; ?>

<div class="profile-shell">
    <section class="hero-card">
        <div class="banner-media">
            <?php if (!empty($profile_user['banner'])): ?>
                <img src="<?php echo htmlspecialchars($profile_user['banner']); ?>" alt="Banner do perfil">
            <?php endif; ?>
            <div class="hero-overlay"></div>
        </div>

        <div class="hero-content">
            <div class="hero-left">
                <div class="avatar-wrap">
                    <img src="<?php echo htmlspecialchars($profile_user['avatar'] ?: 'https://via.placeholder.com/180?text=User'); ?>" alt="Avatar">
                </div>

                <div class="hero-meta">
                    <h1><?php echo htmlspecialchars($profile_user['username']); ?></h1>

                    <?php if (!empty($socials['twitter']) || !empty($socials['instagram']) || !empty($socials['discord'])): ?>
                        <div class="social-row">
                            <?php if (!empty($socials['twitter'])): ?>
                                <a class="social-chip" href="https://twitter.com/<?php echo htmlspecialchars($socials['twitter']); ?>" target="_blank" aria-label="X" title="@<?php echo htmlspecialchars($socials['twitter']); ?>"><span class="x-icon" aria-hidden="true"><svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M18.901 1.153h3.68l-8.042 9.19 9.461 12.504h-7.406l-5.802-7.584-6.638 7.584H.47l8.602-9.83L0 1.154h7.594l5.244 6.932L18.901 1.153zm-1.292 19.489h2.039L6.486 3.24H4.298L17.609 20.642z"/></svg></span></a>
                            <?php endif; ?>
                            <?php if (!empty($socials['instagram'])): ?>
                                <a class="social-chip" href="https://instagram.com/<?php echo htmlspecialchars($socials['instagram']); ?>" target="_blank" aria-label="Instagram" title="@<?php echo htmlspecialchars($socials['instagram']); ?>"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($socials['discord'])): ?>
                                <button type="button" class="social-chip social-discord-btn" data-discord="<?php echo htmlspecialchars($socials['discord']); ?>" aria-label="Copiar Discord" title="Copiar: <?php echo htmlspecialchars($socials['discord']); ?>"><i class="fa-brands fa-discord" aria-hidden="true"></i></button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="hero-actions">
                    <button id="followBtn" class="follow-btn <?php echo $is_following ? 'following' : ''; ?>" data-user-id="<?php echo $profile_user_id; ?>" data-following="<?php echo $is_following ? 'true' : 'false'; ?>">
                        <?php echo $is_following ? 'A seguir ✓' : 'Seguir'; ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="profile-layout">
        <aside class="left-column">
            <?php if (!empty($profile_user['biography'])): ?>
                <div class="panel">
                    <h3 class="panel-title">Biografia</h3>
                    <div class="bio-text"><?php echo nl2br(htmlspecialchars($profile_user['biography'])); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($total_reviews > 0): ?>
                <div class="panel">
                    <h3 class="panel-title">Distribuição de notas</h3>
                    <div class="ratings-bars">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <div class="rating-row">
                                <div class="rating-count"><?php echo $ratings_distribution[$i] > 0 ? $ratings_distribution[$i] : ''; ?></div>
                                <div class="rating-bar-wrap">
                                    <div class="rating-bar" style="height: <?php echo $max_count > 0 ? ($ratings_distribution[$i] / $max_count * 100) : 0; ?>%;"></div>
                                </div>
                                <div class="rating-label"><?php echo $i; ?>★</div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel">
                <h3 class="panel-title">Conquistas</h3>
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
                            <div class="achievement-tooltip"><?php echo htmlspecialchars($achievement['description']); ?></div>
                            <img src="<?php echo htmlspecialchars($achievement['icon']); ?>" alt="<?php echo htmlspecialchars($achievement['name']); ?>" class="achievement-icon">
                            <div class="achievement-name"><?php echo htmlspecialchars($achievement['name']); ?></div>
                            <div class="achievement-points"><?php echo $achievement['points']; ?> pts</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <main class="right-column">
            <section class="panel">
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
            </section>

            <section class="panel">
                <div class="tabs">
                    <?php
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
                    <button class="tab-btn <?php echo $lists->num_rows === 0 ? 'active' : ''; ?>" data-tab="reviews">Reviews</button>
                </div>

                <?php
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
                                        <img src="<?php echo htmlspecialchars($item['game_image'] ?: 'https://via.placeholder.com/180x240?text=Game'); ?>" alt="<?php echo htmlspecialchars($item['game_name']); ?>" class="list-item-img">
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

                if ($lists->num_rows === 0):
                ?>
                    <div id="list-emptyTab" class="tab-content active">
                        <div class="empty-state">
                            <p>Este utilizador ainda não criou nenhuma lista.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="reviewsTab" class="tab-content <?php echo $lists->num_rows === 0 ? 'active' : ''; ?>">
                    <?php
                    $reviews->data_seek(0);
                    if ($reviews->num_rows > 0):
                    ?>
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <article class="review-card">
                                <div class="review-layout">
                                    <a class="review-cover" href="game.php?id=<?php echo $review['game_id']; ?>" title="Ver jogo">
                                        <img src="<?php echo htmlspecialchars($review['game_image'] ?: 'https://via.placeholder.com/180x240?text=Game'); ?>" alt="Capa de <?php echo htmlspecialchars($review['game_name'] ?? 'Jogo'); ?>">
                                    </a>

                                    <div class="review-body">
                                        <div class="review-head">
                                            <h3 class="review-title"><?php echo htmlspecialchars($review['game_name'] ?? 'Jogo #' . $review['game_id']); ?></h3>
                                            <span class="review-score">★ <?php echo (int)$review['rating']; ?>/10</span>
                                        </div>
                                        <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        <div class="review-footer">
                                            <span class="review-date"><i class="fa-regular fa-clock" aria-hidden="true"></i><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Este utilizador ainda não escreveu nenhuma review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
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

const queryTab = new URLSearchParams(window.location.search).get('tab');
if (queryTab) {
    const tabBtn = document.querySelector(`.tab-btn[data-tab="${queryTab}"]`);
    if (tabBtn) tabBtn.click();
}

document.querySelectorAll('.social-discord-btn').forEach(discordButton => {
    discordButton.addEventListener('click', async () => {
        const discordUsername = discordButton.dataset.discord || '';
        if (!discordUsername) return;

        const previousTitle = discordButton.title;

        try {
            await navigator.clipboard.writeText(discordUsername);
            discordButton.title = 'Copiado!';
        } catch (_) {
            const tempInput = document.createElement('input');
            tempInput.value = discordUsername;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            discordButton.title = 'Copiado!';
        }

        setTimeout(() => {
            discordButton.title = previousTitle;
        }, 1200);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
