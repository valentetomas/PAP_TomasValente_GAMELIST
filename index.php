<?php
include 'includes/header.php';
?>
<title>GameList - Descobre o teu próximo jogo</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<style>
    :root {
        --bg-dark: #0b0c0f;
        --surface: #16171c;
        --accent: #ff3366; 
        --text-main: #ffffff;
        --text-muted: #9ca3af;
        --card-radius: 8px;
    }

    body {
        margin: 0;
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-dark);
        color: var(--text-main);
        overflow-x: hidden;
    }

    /* --- 1. BANNER ESTILO BACKLOGGD --- */
    .top-banner-backlog {
        position: relative;
        width: 100%;
        height: 500px;
        overflow: hidden;
        background-color: var(--bg-dark);
        margin-bottom: 0;
    }

    .banner-bg-container {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: 1;
        -webkit-mask-image: linear-gradient(to bottom, black 10%, transparent 95%);
        mask-image: linear-gradient(to bottom, black 10%, transparent 95%);
    }

    .banner-game-covers {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 15px;
        width: 110%; 
        margin-left: -5%;
        opacity: 0.4;
        transform: rotate(-2deg) scale(1.05);
    }

    .banner-cover {
        width: 100%;
        border-radius: 6px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        aspect-ratio: 2/3; 
        object-fit: cover;
        animation: scrollUp 60s linear infinite;
        will-change: transform;
    }

    .banner-cover:nth-child(2n) { animation-duration: 75s; margin-top: -40px; }
    .banner-cover:nth-child(3n) { animation-duration: 55s; margin-top: 20px; }
    .banner-cover:nth-child(5n) { animation-duration: 85s; }

    @keyframes scrollUp {
        0% { transform: translateY(0); }
        100% { transform: translateY(-400px); }
    }

    .banner-content-wrapper {
        position: relative;
        z-index: 10;
        height: 100%;
        max-width: 1100px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 60px 20px;
        background: radial-gradient(circle at bottom, rgba(11,12,15, 0.8) 0%, rgba(11,12,15,0) 60%);
    }

    .banner-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 30px;
        padding-bottom: 20px;
    }

    .banner-logo {
        font-size: 3.8rem;
        font-weight: 900;
        letter-spacing: -2px;
        line-height: 1;
        margin: 0;
        text-shadow: 0 5px 30px rgba(0,0,0,0.9);
    }
    
    .banner-logo span { color: var(--accent); }

    .banner-tagline {
        font-size: 1.1rem;
        color: #ccc;
        margin: 10px 0 0 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.8);
        font-weight: 500;
    }

    .banner-cta-box { text-align: right; }
    
    .btn-create-account {
        padding: 14px 35px;
        font-size: 1rem;
        font-weight: 700;
        border-radius: 50px;
        background: #fff;
        color: #000;
        text-decoration: none;
        box-shadow: 0 0 20px rgba(255,255,255,0.15);
        transition: transform 0.2s, background 0.2s;
        display: inline-block;
    }
    .btn-create-account:hover {
        transform: scale(1.05);
        background: var(--accent);
        color: #fff;
    }

    /* --- 2. SECÇÃO TENDÊNCIAS COM HOVER --- */
    .trending-section {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .trending-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .trending-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #d1d5db;
        margin: 0;
    }

    .trending-more {
        font-size: 0.9rem;
        color: var(--text-muted);
        text-decoration: none;
        transition: color 0.2s;
    }
    .trending-more:hover { color: #fff; }

    .trending-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
    }

    /* Estilo do Card */
    .trending-card {
        display: block;
        aspect-ratio: 2 / 3;
        background-color: #222;
        border-radius: var(--card-radius);
        overflow: hidden;
        position: relative; /* Necessário para posicionar o texto */
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        text-decoration: none;
    }

    /* Imagem base */
    .trending-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: filter 0.3s ease; /* Animação do escurecimento */
    }

    /* Título (Invisível por defeito) */
    .trending-card-title {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%); /* Centraliza perfeitamente */
        width: 90%;
        text-align: center;
        color: #fff;
        font-weight: 700;
        font-size: 1.1rem;
        text-shadow: 0 2px 10px rgba(0,0,0,0.9);
        opacity: 0; /* Invisível */
        transition: opacity 0.3s ease;
        z-index: 10;
        pointer-events: none; /* Deixa o clique passar para o link */
    }

    /* --- EFEITO HOVER --- */
    .trending-card:hover {
        transform: scale(1.05);
        z-index: 5;
        box-shadow: 0 10px 20px rgba(0,0,0,0.5);
        border: 1px solid rgba(255,255,255,0.3); /* Borda subtil */
    }

    /* Escurecer a imagem */
    .trending-card:hover img {
        filter: brightness(0.25); /* Fica bem escuro para o texto ler-se bem */
    }

    /* Mostrar o texto */
    .trending-card:hover .trending-card-title {
        opacity: 1;
    }

    /* Responsividade */
    @media (max-width: 900px) {
        .banner-header { flex-direction: column; text-align: center; }
        .trending-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 600px) {
        .trending-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
        /* Em telemóvel pode ser melhor mostrar sempre o texto ou manter assim */
    }
</style>

<div class="top-banner-backlog">
    <div class="banner-bg-container">
        <div class="banner-game-covers" id="banner-covers"></div>
    </div>
    <div class="banner-content-wrapper">
        <div class="banner-header">
            <div class="banner-left">
                <h1 class="banner-logo">GameList<span>.</span></h1>
                <p class="banner-tagline">Descobre, joga, acompanha a tua coleção.</p>
            </div>
            <div class="banner-cta-box" id="banner-cta">
                <a href="register.php" class="btn-create-account">Sign up</a>
                <div style="margin-top: 15px; font-size: 0.9rem; color: #aaa;">
                    Já tens conta? <a href="login.php" style="color:white; text-decoration: underline;">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($user) && $user): ?>
<!-- SECÇÃO PARA UTILIZADORES LOGADOS - DASHBOARD -->
<?php
// Buscar estatísticas do utilizador
$user_id = $_SESSION['user_id'];

// Total de jogos adicionados às listas
$stmt = $conn->prepare("SELECT COUNT(DISTINCT li.game_id) as count 
                        FROM list_items li 
                        JOIN lists l ON li.list_id = l.id 
                        WHERE l.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_games = $stmt->get_result()->fetch_assoc()['count'];

// Total de reviews
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews_count = $stmt->get_result()->fetch_assoc()['count'];

// Seguidores
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$followers_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt->close();
?>

<section class="dashboard-section-top">
    <div class="dashboard-container-top">
        <div class="dashboard-header-top">
            <h2>Bem-vindo de volta, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <a href="profile.php" class="btn-profile-small">
                Ver perfil <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <div class="stats-grid-top">
            <div class="stat-card-top">
                <div class="stat-number-top"><?php echo $total_games; ?></div>
                <div class="stat-label-top">Jogos</div>
            </div>

            <div class="stat-card-top">
                <div class="stat-number-top"><?php echo $reviews_count; ?></div>
                <div class="stat-label-top">Reviews</div>
            </div>

            <div class="stat-card-top">
                <div class="stat-number-top"><?php echo $followers_count; ?></div>
                <div class="stat-label-top">Seguidores</div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="trending-section">
    <div class="trending-header">
        <h2 class="trending-title">Tendências recentes</h2>
        <a href="games.php" class="trending-more">Veja mais</a>
    </div>
    <div class="trending-grid" id="trending-grid"></div>
</section>

<?php if (!isset($user) || !$user): ?>
<!-- SECÇÃO PARA UTILIZADORES NÃO LOGADOS -->
<section class="features-section">
    <!-- Feature 1 - Coleção -->
    <div class="feature-block feature-left">
        <div class="feature-content">
            <h2>Acompanha a tua coleção pessoal de jogos.</h2>
            <p>Regista todos os jogos que já jogaste, estás a jogar e desejas jogar. Mantém estatísticas detalhadas e acompanha o teu progresso ao longo do tempo.</p>
        </div>
        <div class="feature-visual">
            <div class="visual-mockup stats-mockup">
                <div class="mockup-header">
                    <div class="mockup-avatar"></div>
                    <div class="mockup-user-info">
                        <div class="mockup-username"></div>
                        <div class="mockup-subtext"></div>
                    </div>
                </div>
                <div class="mockup-stats">
                    <div class="mock-stat">
                        <div class="mock-stat-num">24</div>
                        <div class="mock-stat-label">Jogados</div>
                    </div>
                    <div class="mock-stat">
                        <div class="mock-stat-num">3</div>
                        <div class="mock-stat-label">A Jogar</div>
                    </div>
                    <div class="mock-stat">
                        <div class="mock-stat-num">47</div>
                        <div class="mock-stat-label">Backlog</div>
                    </div>
                </div>
                <div class="mockup-games-grid">
                    <div class="mock-game-card"></div>
                    <div class="mock-game-card"></div>
                    <div class="mock-game-card"></div>
                    <div class="mock-game-card"></div>
                    <div class="mock-game-card"></div>
                    <div class="mock-game-card"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature 2 - Reviews e Comunidade -->
    <div class="feature-block feature-right">
        <div class="feature-visual">
            <div class="visual-mockup reviews-mockup">
                <div class="mockup-review-card">
                    <div class="review-game-banner"></div>
                    <div class="review-content">
                        <div class="review-rating">
                            <span class="rating-stars">★★★★★</span>
                            <span class="rating-num">9.5/10</span>
                        </div>
                        <div class="review-text-line"></div>
                        <div class="review-text-line short"></div>
                    </div>
                </div>
                <div class="mockup-review-card">
                    <div class="review-game-banner"></div>
                    <div class="review-content">
                        <div class="review-rating">
                            <span class="rating-stars">★★★★☆</span>
                            <span class="rating-num">8.0/10</span>
                        </div>
                        <div class="review-text-line"></div>
                        <div class="review-text-line short"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="feature-content">
            <h2>Avalia jogos e conecta com a comunidade.</h2>
            <p>Escreve reviews detalhadas, classifica os teus jogos favoritos e acompanha as opiniões dos teus amigos. Descobre novos títulos através da comunidade e partilha a tua jornada nos jogos.</p>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- AVALIAÇÕES POPULARES (Para todos) -->
<section class="trending-section">
    <div class="trending-header">
        <h2 class="trending-title">Avaliações populares</h2>
    </div>
    <div class="reviews-grid" id="popular-reviews-grid">
        <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px 20px;">A carregar avaliações...</p>
    </div>
</section>

<!-- PRÓXIMOS LANÇAMENTOS (Para todos) -->
<section class="trending-section">
    <div class="trending-header">
        <h2 class="trending-title">Próximos lançamentos</h2>
        <a href="upcoming.php" class="trending-more">Veja mais</a>
    </div>
    <div class="release-calendar-box">
        <button class="release-calendar-arrow prev" id="index-release-prev" type="button" aria-label="Anterior">‹</button>
        <button class="release-calendar-arrow next" id="index-release-next" type="button" aria-label="Seguinte">›</button>
        <div class="release-calendar-track" id="upcoming-grid"></div>
    </div>
</section>

<style>
.release-calendar-box {
    position: relative;
    background: var(--surface);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 14px 42px;
}

.release-calendar-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(18, 21, 28, 0.88);
    color: #fff;
    border-radius: 999px;
    padding: 0;
    font-size: 0.95rem;
    line-height: 1;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 4;
    transition: background 0.2s ease, border-color 0.2s ease, opacity 0.2s ease;
    opacity: 0;
    pointer-events: none;
}

.release-calendar-box:hover .release-calendar-arrow,
.release-calendar-box:focus-within .release-calendar-arrow {
    opacity: 1;
    pointer-events: auto;
}

.release-calendar-arrow:hover {
    background: rgba(30, 34, 45, 0.95);
    border-color: rgba(255, 255, 255, 0.2);
}

.release-calendar-arrow.prev {
    left: 8px;
}

.release-calendar-arrow.next {
    right: 8px;
}

.release-calendar-track {
    display: grid;
    grid-template-columns: repeat(9, minmax(0, 1fr));
    gap: 10px;
    align-items: end;
    min-height: 210px;
}

.release-calendar-item {
    border: 0;
    background: transparent;
    color: #fff;
    cursor: pointer;
    padding: 0;
    transition: transform 0.18s ease;
}

.release-calendar-item:hover {
    transform: translateY(-2px);
}

.release-calendar-cover {
    width: 100%;
    aspect-ratio: 2 / 3;
    position: relative;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    overflow: hidden;
    background: #1a1d24;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35);
}

.release-calendar-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: filter 0.25s ease;
}

.release-calendar-name {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    text-align: center;
    color: #fff;
    font-weight: 700;
    font-size: 0.9rem;
    line-height: 1.2;
    text-shadow: 0 2px 10px rgba(0,0,0,0.9);
    opacity: 0;
    transition: opacity 0.25s ease;
    pointer-events: none;
}

.release-calendar-item:hover .release-calendar-cover img,
.release-calendar-item:focus-visible .release-calendar-cover img {
    filter: brightness(0.28);
}

.release-calendar-item:hover .release-calendar-name,
.release-calendar-item:focus-visible .release-calendar-name {
    opacity: 1;
}

.release-calendar-date {
    text-align: center;
    margin-top: 6px;
    font-size: 0.72rem;
    color: #9ca3af;
    font-weight: 700;
    letter-spacing: 0.6px;
}

.release-calendar-item.active {
    transform: scale(1.06);
    z-index: 3;
}

.release-calendar-item.active .release-calendar-cover {
    border-color: var(--accent);
    box-shadow: 0 6px 16px rgba(255, 51, 102, 0.18);
}

.release-calendar-item.active .release-calendar-date {
    font-size: 0.95rem;
    color: #d1d5db;
}

@media (max-width: 900px) {
    .release-calendar-box {
        padding: 12px 34px;
    }

    .release-calendar-arrow {
        width: 26px;
        height: 26px;
        font-size: 0.85rem;
    }

    .release-calendar-arrow.prev {
        left: 6px;
    }

    .release-calendar-arrow.next {
        right: 6px;
    }

    .release-calendar-track {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }
}

/* --- FEATURES SECTION (Utilizadores não logados - Estilo BackloggD) --- */
.features-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px 80px;
}

.feature-block {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 80px;
    align-items: center;
    margin-bottom: 80px;
}

.feature-block:last-child {
    margin-bottom: 0;
}

.feature-left .feature-content {
    order: 1;
}

.feature-left .feature-visual {
    order: 2;
}

.feature-right .feature-visual {
    order: 1;
}

.feature-right .feature-content {
    order: 2;
}

.feature-content h2 {
    font-size: 2.2rem;
    font-weight: 700;
    color: #e8e8e8;
    margin-bottom: 20px;
    line-height: 1.2;
    letter-spacing: -1px;
}

.feature-content p {
    font-size: 1.05rem;
    line-height: 1.75;
    color: #9ca3af;
    font-weight: 400;
}

/* Visual Mockups */
.feature-visual {
    position: relative;
}

.visual-mockup {
    background: #14151a;
    border: 1px solid #1e1f26;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

/* Stats Mockup */
.mockup-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 30px;
    padding-bottom: 25px;
    border-bottom: 1px solid #1e1f26;
}

.mockup-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #ff5580);
}

.mockup-user-info {
    flex: 1;
}

.mockup-username {
    height: 18px;
    width: 120px;
    background: #2a2b35;
    border-radius: 4px;
    margin-bottom: 8px;
}

.mockup-subtext {
    height: 12px;
    width: 80px;
    background: #1e1f26;
    border-radius: 4px;
}

.mockup-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.mock-stat {
    background: var(--bg-dark);
    border: 1px solid #1e1f26;
    border-radius: 6px;
    padding: 20px;
    text-align: center;
}

.mock-stat-num {
    font-size: 2rem;
    font-weight: 700;
    color: #e8e8e8;
    margin-bottom: 5px;
}

.mock-stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.mockup-games-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.mock-game-card {
    aspect-ratio: 2/3;
    background: linear-gradient(135deg, #2a2b35, #1e1f26);
    border-radius: 4px;
}

/* Reviews Mockup */
.mockup-review-card {
    background: var(--bg-dark);
    border: 1px solid #1e1f26;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 15px;
}

.mockup-review-card:last-child {
    margin-bottom: 0;
}

.review-game-banner {
    height: 120px;
    background: linear-gradient(135deg, #2a2b35, #1e1f26);
}

.review-content {
    padding: 20px;
}

.review-rating {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.rating-stars {
    color: var(--accent);
    font-size: 1.2rem;
    letter-spacing: 2px;
}

.rating-num {
    font-size: 1.1rem;
    font-weight: 700;
    color: #e8e8e8;
}

.review-text-line {
    height: 12px;
    background: #1e1f26;
    border-radius: 4px;
    margin-bottom: 8px;
}

.review-text-line.short {
    width: 70%;
}

/* Activity Mockup */
.activity-mockup {
    padding: 25px;
}

.activity-item {
    display: flex;
    gap: 15px;
    padding: 20px 0;
    border-bottom: 1px solid #1e1f26;
}

.activity-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.activity-item:first-child {
    padding-top: 0;
}

.activity-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    flex-shrink: 0;
}

.activity-details {
    flex: 1;
}

.activity-line {
    height: 14px;
    background: #1e1f26;
    border-radius: 4px;
    margin-bottom: 12px;
    width: 80%;
}

.activity-game-thumb {
    width: 100px;
    height: 60px;
    background: linear-gradient(135deg, #2a2b35, #1e1f26);
    border-radius: 4px;
}

/* Lists Mockup */
.mock-list-card {
    background: var(--bg-dark);
    border: 1px solid #1e1f26;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 15px;
}

.mock-list-card:last-child {
    margin-bottom: 0;
}

.list-header-line {
    height: 16px;
    width: 60%;
    background: #2a2b35;
    border-radius: 4px;
    margin-bottom: 15px;
}

.list-games-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
}

.list-game-mini {
    aspect-ratio: 2/3;
    background: linear-gradient(135deg, #2a2b35, #1e1f26);
    border-radius: 4px;
}

/* Responsividade */
@media (max-width: 900px) {
    .feature-block {
        grid-template-columns: 1fr;
        gap: 40px;
        margin-bottom: 60px;
    }
    
    .feature-left .feature-content,
    .feature-left .feature-visual,
    .feature-right .feature-content,
    .feature-right .feature-visual {
        order: unset;
    }
    
    .feature-content h2 {
        font-size: 1.8rem;
    }
}

@media (max-width: 600px) {
    .features-section {
        padding: 40px 20px 60px;
    }
    
    .feature-block {
        margin-bottom: 50px;
    }
    
    .feature-content h2 {
        font-size: 1.6rem;
    }
    
    .feature-content p {
        font-size: 1rem;
    }
    
    .mockup-stats {
        grid-template-columns: 1fr;
    }
    
    .mockup-games-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* --- REVIEWS GRID --- */
.reviews-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin: 0 auto;
    max-width: 1200px;
}

.review-card {
    background: var(--surface);
    border-radius: var(--card-radius);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}

.review-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
}

.review-card-header {
    display: flex;
    gap: 15px;
    padding: 20px 20px 15px;
    align-items: center;
}

.review-game-thumb {
    width: 60px;
    height: 80px;
    border-radius: 4px;
    object-fit: cover;
    flex-shrink: 0;
}

.review-header-info {
    flex: 1;
    min-width: 0;
}

.review-user {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.review-user-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.review-user-avatar-img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.review-username {
    font-size: 0.9rem;
    color: var(--text-muted);
    text-decoration: none;
}

.review-username:hover {
    color: var(--text-main);
}

.review-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.review-stars {
    color: #fbbf24;
    font-size: 1rem;
}

.review-score {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.review-game-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-decoration: none;
    display: block;
}

.review-game-name:hover {
    color: var(--accent);
}

.review-card-body {
    padding: 0 20px 15px;
}

.review-text {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 12px;
}

.review-card-footer {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 20px;
    border-top: 1px solid rgba(255,255,255,0.05);
    font-size: 0.85rem;
    color: var(--text-muted);
}

.review-stat {
    display: flex;
    align-items: center;
    gap: 5px;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 0.85rem;
    padding: 0;
    transition: color 0.2s;
}

.review-stat:hover {
    color: var(--accent);
}

.review-stat.liked {
    color: var(--accent);
}

.review-stat i {
    font-size: 0.85rem;
}

.review-link {
    margin-left: auto;
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
}

.review-link:hover {
    color: #ff5580;
}

.review-date {
    font-size: 0.8rem;
}

/* --- REVIEW COMMENTS SECTION (Inline) --- */
.review-comments-section {
    padding: 16px 20px 0 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    margin-top: 12px;
}

.review-comments-section.hidden {
    display: none;
}

.comments-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 12px;
    max-height: 300px;
    overflow-y: auto;
}

.inline-comment {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.inline-comment-header {
    display: flex;
    align-items: center;
    gap: 8px;
}

.inline-comment-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    flex-shrink: 0;
}

.inline-comment-avatar-img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.inline-comment-user {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
}

.inline-comment-username {
    font-weight: 500;
    color: var(--text-main);
    text-decoration: none;
}

.inline-comment-username:hover {
    color: var(--accent);
}

.inline-comment-date {
    color: var(--text-muted);
    font-size: 0.75rem;
}

.inline-comment-text {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.4;
    margin: 0;
}

.add-comment-inline {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 12px 0 16px 0;
}

.comment-textarea {
    background: var(--bg-dark);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-main);
    border-radius: 6px;
    padding: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    resize: vertical;
    min-height: 40px;
}

.comment-textarea:focus {
    outline: none;
    border-color: var(--accent);
}

.comment-submit-btn {
    background: var(--accent);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.9rem;
    transition: background 0.2s;
    align-self: flex-start;
}

.comment-submit-btn:hover {
    background: #ff5580;
}

.comment-submit-btn:disabled {
    background: var(--text-muted);
    cursor: not-allowed;
}

/* --- MODAL --- */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal.hidden {
    display: none;
}

.modal-content {
    background: var(--surface);
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 75vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.modal-close {
    background: none;
    border: none;
    color: var(--text-muted);
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: var(--text-main);
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

#comments-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 100px;
}

.comment-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.comment-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.comment-header-compact {
    display: flex;
    align-items: center;
    gap: 8px;
}

.comment-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    flex-shrink: 0;
}

.comment-avatar-img {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-info {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
}

.comment-username {
    font-weight: 500;
    color: var(--text-main);
    text-decoration: none;
}

.comment-username:hover {
    color: var(--accent);
}

.comment-date {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.comment-text {
    color: var(--text-muted);
    line-height: 1.5;
    word-break: break-word;
    margin: 0;
    font-size: 0.95rem;
}

.add-comment-form {
    flex-shrink: 0;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    padding-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

#comment-text {
    background: var(--bg-dark);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: var(--text-main);
    border-radius: 6px;
    padding: 8px;
    font-family: 'Inter', sans-serif;
    resize: vertical;
    font-size: 0.9rem;
}

#comment-text:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 2px rgba(255, 51, 102, 0.1);
}

.btn-primary {
    background: var(--accent);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    font-size: 0.9rem;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #ff5580;
}

.btn-primary:disabled {
    background: var(--text-muted);
    cursor: not-allowed;
}

@media (max-width: 900px) {
    .reviews-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {
    .review-card-header {
        padding: 15px;
    }
    
    .review-game-thumb {
        width: 50px;
        height: 66px;
    }
    
    .review-card-body {
        padding: 0 15px 12px;
    }
    
    .review-card-footer {
        padding: 10px 15px;
        flex-wrap: wrap;
    }
}

/* --- ESTILOS PARA UTILIZADORES NÃO LOGADOS (Estilo Backloggd) --- */
.info-section {
    max-width: 1100px;
    margin: 70px auto;
    padding: 0 20px;
}

.info-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.info-card {
    background: #14151a;
    border: 1px solid #1e1f26;
    border-radius: 4px;
    padding: 40px 35px;
    transition: border-color 0.2s ease;
}

.info-card:hover {
    border-color: #2a2b35;
}

.info-icon {
    width: 48px;
    height: 48px;
    margin-bottom: 20px;
    background: rgba(255, 51, 102, 0.1);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: var(--accent);
}

.info-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 12px;
    color: #e8e8e8;
    letter-spacing: -0.3px;
}

.info-card p {
    font-size: 0.95rem;
    line-height: 1.65;
    color: #9ca3af;
    font-weight: 400;
}

/* --- ESTILOS PARA UTILIZADORES LOGADOS (Estilo Backloggd) --- */
.dashboard-section {
    max-width: 1100px;
    margin: 70px auto 80px;
    padding: 0 20px;
}

.dashboard-container {
    background: transparent;
}

.dashboard-header {
    margin-bottom: 45px;
    padding-bottom: 25px;
    border-bottom: 1px solid #1e1f26;
}

.dashboard-header h2 {
    font-size: 1.75rem;
    font-weight: 600;
    color: #e8e8e8;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}

.dashboard-header h2 span {
    display: inline-block;
    margin-left: 5px;
}

.dashboard-header p {
    font-size: 0.95rem;
    color: #6b7280;
    font-weight: 400;
}

/* Grid de Estatísticas - Estilo Backloggd */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin-bottom: 50px;
}

.stat-card {
    background: #14151a;
    border: 1px solid #1e1f26;
    border-radius: 4px;
    padding: 24px 20px;
    text-align: center;
    transition: border-color 0.15s ease, transform 0.15s ease;
}

.stat-card:hover {
    border-color: var(--accent);
    transform: translateY(-2px);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #e8e8e8;
    line-height: 1;
    margin-bottom: 8px;
    letter-spacing: -1px;
}

.stat-label {
    font-size: 0.8rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

/* --- DASHBOARD NO TOPO (UTILIZADOR LOGADO) --- */
.dashboard-section-top {
    max-width: 1100px;
    margin: 50px auto 40px;
    padding: 0 20px;
}

.dashboard-container-top {
    background: #14151a;
    border: 1px solid #1e1f26;
    border-radius: 4px;
    padding: 30px 35px;
}

.dashboard-header-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #1e1f26;
}

.dashboard-header-top h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #e8e8e8;
    margin: 0;
    letter-spacing: -0.5px;
}

.btn-profile-small {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: transparent;
    color: #9ca3af;
    text-decoration: none;
    border: 1px solid #2a2b35;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.85rem;
    transition: all 0.15s ease;
}

.btn-profile-small:hover {
    border-color: var(--accent);
    color: var(--accent);
}

.btn-profile-small i {
    font-size: 0.75rem;
    transition: transform 0.2s ease;
}

.btn-profile-small:hover i {
    transform: translateX(3px);
}

.stats-grid-top {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.stat-card-top {
    background: var(--bg-dark);
    border: 1px solid #1e1f26;
    border-radius: 4px;
    padding: 25px 20px;
    text-align: center;
    transition: border-color 0.15s ease, transform 0.15s ease;
}

.stat-card-top:hover {
    border-color: var(--accent);
    transform: translateY(-2px);
}

.stat-number-top {
    font-size: 2.5rem;
    font-weight: 700;
    color: #e8e8e8;
    line-height: 1;
    margin-bottom: 10px;
    letter-spacing: -1px;
}

.stat-label-top {
    font-size: 0.85rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

/* Atividade Recente */
.recent-activity {
    margin-bottom: 45px;
}

.recent-activity h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #e8e8e8;
    margin-bottom: 18px;
    letter-spacing: -0.3px;
    text-transform: none;
}

.activity-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.activity-card {
    background: #14151a;
    border: 1px solid #1e1f26;
    border-radius: 4px;
    overflow: hidden;
    text-decoration: none;
    transition: border-color 0.15s ease, transform 0.15s ease;
    display: block;
}

.activity-card:hover {
    border-color: #2a2b35;
    transform: translateY(-2px);
}

.activity-card img {
    width: 100%;
    aspect-ratio: 16/9;
    object-fit: cover;
    display: block;
}

.activity-info {
    padding: 16px;
    background: #14151a;
}

.activity-info h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #e8e8e8;
    margin-bottom: 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: -0.2px;
}

.activity-rating {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.rating-badge {
    background: rgba(255, 51, 102, 0.15);
    color: var(--accent);
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 0.8rem;
    font-weight: 700;
    border: 1px solid rgba(255, 51, 102, 0.2);
}

.activity-date {
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}

/* Botão CTA */
.dashboard-cta {
    text-align: center;
    margin-top: 40px;
    padding-top: 40px;
    border-top: 1px solid #1e1f26;
}

.btn-profile {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 28px;
    background: transparent;
    color: #e8e8e8;
    text-decoration: none;
    border: 1px solid #2a2b35;
    border-radius: 4px;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.15s ease;
}

.btn-profile:hover {
    background: #14151a;
    border-color: var(--accent);
    color: var(--accent);
    transform: translateY(-1px);
}

.btn-profile i {
    font-size: 0.8rem;
    transition: transform 0.2s ease;
}

.btn-profile:hover i {
    transform: translateX(3px);
}

/* Responsividade */
@media (max-width: 900px) {
    .info-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .activity-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .dashboard-header h2 {
        font-size: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .stat-card {
        padding: 20px 15px;
    }
    
    .stat-number {
        font-size: 1.6rem;
    }
    
    .activity-grid {
        grid-template-columns: 1fr;
    }
    
    .info-card {
        padding: 30px 25px;
    }
    
    .stats-grid-top {
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    
    .dashboard-header-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}

@media (max-width: 600px) {
    .dashboard-container-top {
        padding: 25px 20px;
    }
    
    .dashboard-header-top h2 {
        font-size: 1.3rem;
    }
    
    .stats-grid-top {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .stat-card-top {
        padding: 20px 15px;
    }
    
    .stat-number-top {
        font-size: 2rem;
    }
}
</style>

<script>
const apiKey = '5fd330b526034329a8f0d9b6676241c5';

// --- A. CARREGAR CAPAS DO BANNER ---
async function loadBannerCovers() {
    try {
        const res = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&page_size=40&ordering=-added`);
        const data = await res.json();
        const container = document.getElementById('banner-covers');
        container.innerHTML = '';
        data.results.forEach(game => {
            if (game.background_image) {
                const img = document.createElement('img');
                img.src = game.background_image.replace('/media/games/', '/media/crop/600/400/games/');
                img.className = 'banner-cover';
                container.appendChild(img);
            }
        });
    } catch (e) { console.error('Erro banner:', e); }
}

// --- B. CARREGAR TENDÊNCIAS (COM HOVER EFFECT) ---
async function loadTrending() {
    const container = document.getElementById('trending-grid');
    const date = new Date();
    const currentYear = date.getFullYear();
    const nextYear = currentYear + 1;
    
    try {
        const res = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&dates=2024-01-01,${nextYear}-12-31&ordering=-added&page_size=6`);
        const data = await res.json();
        
        container.innerHTML = '';

        data.results.forEach(game => {
            if (game.background_image) {
                const link = document.createElement('a');
                link.href = `game.php?id=${game.id}`;
                link.className = 'trending-card';

                const img = document.createElement('img');
                img.src = game.background_image.replace('/media/games/', '/media/crop/600/400/games/');
                img.alt = game.name;
                img.loading = "lazy";

                const titleDiv = document.createElement('div');
                titleDiv.className = 'trending-card-title';
                titleDiv.innerText = game.name;

                link.appendChild(img);
                link.appendChild(titleDiv);
                container.appendChild(link);
            }
        });
    } catch (e) {
        console.error('Erro trending:', e);
    }
}

// --- C. VERIFICAR LOGIN ---
function checkUserStatus() {
    const isUserLoggedIn = <?php echo isset($user) && $user ? 'true' : 'false'; ?>;
    if (isUserLoggedIn) {
        const ctaContainer = document.getElementById('banner-cta');
        if (ctaContainer) ctaContainer.style.display = 'none';
    }
}

// --- D. CARREGAR AVALIAÇÕES POPULARES ---
async function loadPopularReviews() {
    try {
        const res = await fetch('get_popular_reviews.php');
        const reviews = await res.json();
        console.log('Reviews recebidas:', reviews);
        const container = document.getElementById('popular-reviews-grid');
        container.innerHTML = '';
        
        if (!reviews || reviews.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px 20px;">Ainda não há avaliações. Seja o primeiro a avaliar um jogo!</p>';
            return;
        }
        
        reviews.forEach((review, index) => {
            console.log(`Review ${index}:`, review);
            
            const card = document.createElement('div');
            card.className = 'review-card';
            
            // Validar dados
            if (!review.username || !review.game_name) {
                console.warn('Review incompleta:', review);
                return;
            }
            
            // Calcular estrelas
            const rating = parseFloat(review.rating) || 0;
            const fullStars = Math.floor(rating / 2);
            const halfStar = (rating % 2) >= 1;
            let stars = '★'.repeat(Math.min(fullStars, 5));
            if (halfStar) stars += '★';
            stars += '☆'.repeat(Math.max(0, 5 - fullStars - (halfStar ? 1 : 0)));
            
            // Avatar do utilizador
            const initial = (review.username && review.username.length > 0) ? review.username.charAt(0).toUpperCase() : 'U';
            const avatarUrl = review.avatar_url || '';
            
            // Formatar data
            const date = new Date(review.created_at);
            const dateStr = date.toLocaleDateString('pt-PT', { year: 'numeric', month: 'short', day: 'numeric' });
            
            // Preparar imagem (com fallback)
            const imageUrl = review.game_image || 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23333%22 width=%22100%22 height=%22100%22/%3E%3C/svg%3E';
            
            // Criar avatar HTML (com imagem se existir)
            let avatarHtml = '';
            if (avatarUrl) {
                avatarHtml = `<img src="${avatarUrl}" alt="${review.username}" class="review-user-avatar-img" />`;
            } else {
                avatarHtml = `<div class="review-user-avatar">${initial}</div>`;
            }
            
            card.innerHTML = `
                <div class="review-card-header">
                    <img src="${imageUrl}" alt="${review.game_name}" class="review-game-thumb" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23444%22 width=%22100%22 height=%22100%22/%3E%3C/svg%3E'" />
                    <div class="review-header-info">
                        <div class="review-user">
                            ${avatarHtml}
                            <a href="user_profile.php?id=${review.user_id}" class="review-username">${review.username}</a>
                        </div>
                        <div class="review-rating">
                            <span class="review-stars">${stars}</span>
                            <span class="review-score">${rating}/10</span>
                        </div>
                        <a href="game.php?id=${review.game_id}" class="review-game-name">${review.game_name}</a>
                    </div>
                </div>
                <div class="review-card-body">
                    <p class="review-text">${review.review_text || 'Sem comentário...'}</p>
                </div>
                <div class="review-card-footer">
                    <button class="review-stat like-btn" data-review-id="${review.id}" data-user-id="${review.user_id}">
                        <i class="fa-regular fa-heart"></i>
                        <span class="likes-count">${review.likes_count || 0}</span>
                    </button>
                    <button class="review-stat comment-btn" data-review-id="${review.id}" data-game-id="${review.game_id}">
                        <i class="fa-regular fa-comment"></i>
                        <span class="comments-count">${review.comments_count || 0}</span>
                    </button>
                    <span class="review-date">${dateStr}</span>
                    <a href="game.php?id=${review.game_id}#review-${review.id}" class="review-link">Ver review completa</a>
                </div>
                <div class="review-comments-section hidden" data-review-id="${review.id}">
                    <div class="comments-container"></div>
                    <div class="add-comment-inline">
                        <textarea class="comment-textarea" placeholder="Adiciona um comentário..."></textarea>
                        <button class="comment-submit-btn" data-review-id="${review.id}">Comentar</button>
                    </div>
                </div>
            `;
            
            container.appendChild(card);
            
            // Adicionar event listeners para like
            const likeBtn = card.querySelector('.like-btn');
            likeBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const reviewId = likeBtn.dataset.reviewId;
                try {
                    const res = await fetch('like_review.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ review_id: reviewId })
                    });
                    const data = await res.json();
                    if (data.success) {
                        const countSpan = likeBtn.querySelector('.likes-count');
                        countSpan.textContent = data.likes_count;
                        likeBtn.classList.toggle('liked');
                    }
                } catch (e) {
                    console.error('Erro ao dar like:', e);
                }
            });
            
            // Adicionar event listeners para comentar
            const commentBtn = card.querySelector('.comment-btn');
            commentBtn.addEventListener('click', async () => {
                await toggleComments(review.id, review.game_id, card);
            });
        });
    } catch (e) {
        console.error('Erro ao carregar reviews:', e);
        const container = document.getElementById('popular-reviews-grid');
        container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px 20px;">Erro ao carregar avaliações. Tente novamente mais tarde.</p>';
    }
}

// --- EXPANDIR COMENTÁRIOS INLINE ---
async function toggleComments(reviewId, gameId, card) {
    const section = card.querySelector('.review-comments-section');
    
    // Se já está aberto, fechar
    if (!section.classList.contains('hidden')) {
        section.classList.add('hidden');
        return;
    }
    
    // Abrir e carregar comentários
    section.classList.remove('hidden');
    const container = section.querySelector('.comments-container');
    container.innerHTML = '<p style="text-align: center; color: var(--text-muted); font-size: 0.9rem;">A carregar...</p>';
    
    try {
        const res = await fetch(`get_review_comments.php?review_id=${reviewId}`);
        const data = await res.json();
        
        container.innerHTML = '';
        
        if (data.success && data.comments.length > 0) {
            data.comments.forEach(comment => {
                const dateStr = new Date(comment.created_at).toLocaleDateString('pt-PT', { 
                    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit'
                });
                
                const initial = comment.username.charAt(0).toUpperCase();
                const avatarUrl = comment.avatar || '';
                
                let avatarHtml = '';
                if (avatarUrl && avatarUrl !== 'img/default.png') {
                    avatarHtml = `<img src="${avatarUrl}" alt="${comment.username}" class="inline-comment-avatar-img" />`;
                } else {
                    avatarHtml = `<div class="inline-comment-avatar">${initial}</div>`;
                }
                
                const commentHTML = `
                    <div class="inline-comment">
                        <div class="inline-comment-header">
                            ${avatarHtml}
                            <div class="inline-comment-user">
                                <a href="user_profile.php?id=${comment.user_id}" class="inline-comment-username">${comment.username}</a>
                                <span class="inline-comment-date">${dateStr}</span>
                            </div>
                        </div>
                        <p class="inline-comment-text">${comment.comment}</p>
                    </div>
                `;
                container.innerHTML += commentHTML;
            });
        } else {
            container.innerHTML = '<p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; padding: 12px 0;">Sem comentários. Sê o primeiro!</p>';
        }
    } catch (e) {
        console.error('Erro ao carregar comentários:', e);
        container.innerHTML = '<p style="text-align: center; color: var(--text-muted); font-size: 0.9rem;">Erro ao carregar</p>';
    }
    
    // Configurar botão de submit
    const submitBtn = section.querySelector('.comment-submit-btn');
    const textarea = section.querySelector('.comment-textarea');
    
    // Remover listeners antigos
    submitBtn.replaceWith(submitBtn.cloneNode(true));
    const newSubmitBtn = section.querySelector('.comment-submit-btn');
    
    newSubmitBtn.addEventListener('click', async () => {
        if (!textarea.value.trim()) {
            alert('Adiciona um comentário!');
            return;
        }
        
        try {
            const res = await fetch('add_comment_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `review_id=${reviewId}&comment=${encodeURIComponent(textarea.value)}`
            });
            
            if (res.ok) {
                textarea.value = '';
                // Recarregar comentários
                await toggleComments(reviewId, gameId, card);
            } else {
                alert('Erro ao adicionar comentário. Certifica-te que estás logado!');
            }
        } catch (e) {
            console.error('Erro ao adicionar comentário:', e);
            alert('Erro ao adicionar comentário');
        }
    });
}

// --- E. CARREGAR PRÓXIMOS LANÇAMENTOS ---
async function loadUpcoming() {
    const container = document.getElementById('upcoming-grid');
    const prevBtn = document.getElementById('index-release-prev');
    const nextBtn = document.getElementById('index-release-next');
    const placeholderImage = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22450%22%3E%3Crect fill=%22%23222%22 width=%22300%22 height=%22450%22/%3E%3C/svg%3E';
    let releaseItems = [];
    let activeIndex = 0;

    function formatDateLabel(dateString) {
        const date = new Date(dateString);
        const day = date.toLocaleDateString('pt-PT', { day: '2-digit' });
        const month = date.toLocaleDateString('en-US', { month: 'short' }).toUpperCase();
        return `${day} | ${month}`;
    }

    function renderReleaseCalendar() {
        if (!releaseItems.length) {
            container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 20px;">Sem lançamentos para mostrar.</div>';
            return;
        }

        const windowSize = 9;
        const half = Math.floor(windowSize / 2);
        let start = Math.max(0, activeIndex - half);
        let end = Math.min(releaseItems.length - 1, start + windowSize - 1);
        start = Math.max(0, end - windowSize + 1);

        container.innerHTML = '';

        for (let index = start; index <= end; index++) {
            const game = releaseItems[index];
            const card = document.createElement('button');
            card.type = 'button';
            card.className = `release-calendar-item${index === activeIndex ? ' active' : ''}`;
            card.innerHTML = `
                <div class="release-calendar-cover">
                    <img src="${game.background_image || placeholderImage}" alt="${game.name}" loading="lazy" decoding="async">
                    <div class="release-calendar-name">${game.name}</div>
                </div>
                <div class="release-calendar-date">${formatDateLabel(game.released)}</div>
            `;

            card.addEventListener('click', () => {
                activeIndex = index;
                renderReleaseCalendar();
            });

            const image = card.querySelector('img');
            image.addEventListener('error', () => {
                image.src = placeholderImage;
            }, { once: true });

            container.appendChild(card);
        }
    }

    if (prevBtn && nextBtn) {
        prevBtn.onclick = () => {
            if (!releaseItems.length) return;
            activeIndex = Math.max(0, activeIndex - 1);
            renderReleaseCalendar();
        };

        nextBtn.onclick = () => {
            if (!releaseItems.length) return;
            activeIndex = Math.min(releaseItems.length - 1, activeIndex + 1);
            renderReleaseCalendar();
        };
    }

    const today = new Date();
    const plusSixMonths = new Date(today.getFullYear(), today.getMonth() + 6, today.getDate());
    const todayStr = today.toISOString().split('T')[0];
    const endStr = plusSixMonths.toISOString().split('T')[0];

    try {
        const res = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&dates=${todayStr},${endStr}&ordering=released&page_size=30`, { cache: 'force-cache' });
        const data = await res.json();
        releaseItems = Array.isArray(data.results)
            ? data.results.filter(game => game.released)
            : [];

        activeIndex = 0;
        renderReleaseCalendar();
    } catch (e) {
        console.error('Erro upcoming:', e);
        container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 20px;">Erro ao carregar calendário.</div>';
    }
}

window.addEventListener('DOMContentLoaded', () => {
    loadBannerCovers();
    loadTrending();
    loadPopularReviews();
    loadUpcoming();
    checkUserStatus();
    
    // Inicializar modal
    const modal = document.getElementById('comments-modal');
    const closeBtn = modal.querySelector('.modal-close');
    
    closeBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
    });
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

<!-- COMMENTS MODAL -->
<div id="comments-modal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Comentários</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="comments-list"></div>
            <div class="add-comment-form">
                <textarea id="comment-text" placeholder="Adiciona um comentário..." rows="2"></textarea>
                <button id="submit-comment" class="btn-primary">Comentar</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>