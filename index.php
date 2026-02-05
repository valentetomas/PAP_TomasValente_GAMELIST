<?php
include 'includes/header.php';
?>
<title>GameList - Descobre o teu próximo jogo</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
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
    }

    body {
        margin: 0;
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-dark);
        color: var(--text-main);
        overflow-x: hidden;
    }

    /* --- 1. NOVO BANNER ESTILO BACKLOGGD --- */
    .top-banner-backlog {
        position: relative;
        width: 100%;
        height: 550px;
        overflow: hidden;
        background-color: var(--bg-dark);
        margin-bottom: 0;
    }

    /* Container das capas com máscara de desvanecimento */
    .banner-bg-container {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        z-index: 1;
        -webkit-mask-image: linear-gradient(to bottom, black 10%, transparent 95%);
        mask-image: linear-gradient(to bottom, black 10%, transparent 95%);
    }

    /* A Grid de capas animada */
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

    /* Conteúdo Texto */
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
        margin: 10px 0 0 0; /* Margem ajustada já que não há stats */
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
        box-shadow: 0 0 30px rgba(255, 51, 102, 0.4);
    }

    /* --- 2. HERO SLIDER CINEMÁTICO --- */
    #hero-slider {
        position: relative;
        width: 100%;
        height: 500px;
        background: #000;
        overflow: hidden;
        margin-bottom: 40px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.6);
    }

    .hero-slide {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        opacity: 0; transition: opacity 1s ease-in-out;
        display: flex; align-items: center; justify-content: center;
        visibility: hidden;
    }
    .hero-slide.active { opacity: 1; visibility: visible; z-index: 10; }

    .hero-backdrop {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        object-fit: cover; filter: blur(8px) brightness(0.4);
        transform: scale(1.1); z-index: 1;
    }

    .hero-content {
        position: relative; z-index: 2; width: 100%; max-width: 1100px;
        padding: 0 20px; display: grid; grid-template-columns: 260px 1fr;
        gap: 50px; align-items: center; margin-top: 40px;
    }

    .hero-poster {
        width: 260px; aspect-ratio: 3/4; border-radius: 12px;
        box-shadow: 0 25px 50px rgba(0,0,0,0.8);
        border: 1px solid rgba(255,255,255,0.15);
        object-fit: cover; transform: translateY(30px); opacity: 0;
        transition: transform 0.8s ease-out 0.2s, opacity 0.8s ease-out 0.2s;
    }
    .hero-slide.active .hero-poster { transform: translateY(0); opacity: 1; }

    .hero-info {
        color: #fff; transform: translateY(30px); opacity: 0;
        transition: transform 0.8s ease-out 0.4s, opacity 0.8s ease-out 0.4s;
    }
    .hero-slide.active .hero-info { transform: translateY(0); opacity: 1; }

    .trending-badge {
        background: var(--accent); color: white; padding: 6px 14px;
        border-radius: 20px; font-size: 0.85rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 1px; display: inline-block;
        margin-bottom: 15px; box-shadow: 0 4px 15px rgba(255, 51, 102, 0.4);
    }

    .hero-title {
        font-size: 3.5rem; font-weight: 900; line-height: 1.1;
        margin: 0 0 20px 0; text-shadow: 0 4px 20px rgba(0,0,0,0.8);
    }

    .hero-meta {
        font-size: 1.1rem; color: #ddd; margin-bottom: 30px;
        display: flex; gap: 15px; align-items: center;
    }

    .btn-hero {
        padding: 14px 35px; background: #fff; color: #000; font-weight: 800;
        border-radius: 8px; text-decoration: none;
        display: inline-flex; align-items: center; gap: 10px;
        transition: transform 0.2s, background 0.2s; font-size: 1rem;
    }
    .btn-hero:hover { transform: scale(1.05); background: #f0f0f0; }

    .hero-dots {
        position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%);
        display: flex; gap: 10px; z-index: 20;
    }
    .hero-dot {
        width: 10px; height: 10px; border-radius: 50%; background: rgba(255,255,255,0.3);
        cursor: pointer; transition: 0.3s;
    }
    .hero-dot.active { background: #fff; transform: scale(1.3); }


    /* --- LAYOUT PRINCIPAL --- */
    main {
        max-width: 1100px; margin: 0 auto; padding: 0 20px 80px 20px;
    }

    /* Section Headers */
    .section-header {
        display: flex; justify-content: space-between; align-items: flex-end;
        margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 15px;
    }
    .section-title { font-size: 1.8rem; font-weight: 800; margin: 0; position: relative; }
    .section-title::after {
        content: ''; position: absolute; bottom: -16px; left: 0;
        width: 60px; height: 3px; background: var(--accent);
    }
    .see-more { color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .see-more:hover { color: #fff; }

    /* Grid de Jogos */
    .games-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 20px;
    }

    .game-card {
        position: relative; aspect-ratio: 3/4; border-radius: 12px;
        overflow: hidden; background: #222; cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s;
    }
    .game-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 30px rgba(0,0,0,0.5); z-index: 5;
    }
    .game-card img { width: 100%; height: 100%; object-fit: cover; transition: filter 0.3s; }
    .game-card:hover img { filter: brightness(0.3); }

    .card-info {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        padding: 20px; display: flex; flex-direction: column; justify-content: flex-end;
        opacity: 0; transition: opacity 0.3s;
    }
    .game-card:hover .card-info { opacity: 1; }
    .card-title { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 5px; }
    .card-meta { font-size: 0.8rem; color: #ccc; font-weight: 600; }
    .metacritic-badge {
        position: absolute; top: 10px; right: 10px;
        background: rgba(0,0,0,0.8); color: #6c3;
        padding: 4px 8px; border-radius: 6px; font-weight: 800; font-size: 0.8rem;
        border: 1px solid rgba(102, 204, 51, 0.3);
    }

    @media (max-width: 900px) {
        .banner-header { flex-direction: column; text-align: center; justify-content: center; }
        .banner-left, .banner-cta-box { width: 100%; text-align: center; }
        .hero-content { grid-template-columns: 1fr; text-align: center; }
        .hero-poster { display: none; }
        .games-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
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

<div id="hero-slider">
    <div style="display:flex; justify-content:center; align-items:center; height:100%; color:#555;">
        <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
    </div>
</div>

<main>
    <section style="margin-bottom: 60px;">
        <div class="section-header">
            <h2 class="section-title">Trending Agora</h2>
            <a href="#" class="see-more">Ver Top 100 <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div id="featured-list" class="games-grid"></div>
    </section>

    <section>
        <div class="section-header">
            <h2 class="section-title">Em Breve</h2>
            <a href="upcoming.php" class="see-more">Calendário Completo <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div id="upcoming-list" class="games-grid"></div>
    </section>
</main>

<script>
const apiKey = '5fd330b526034329a8f0d9b6676241c5';

// --- A. CARREGAR CAPAS DO BANNER (BACKLOGGD STYLE) ---
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
                img.onerror = function() { this.src = game.background_image; };
                img.className = 'banner-cover';
                img.alt = game.name;
                container.appendChild(img);
            }
        });
    } catch (e) {
        console.error('Erro ao carregar capas:', e);
    }
}

// --- B. VERIFICAR LOGIN ---
function checkUserStatus() {
    const isUserLoggedIn = <?php echo isset($user) && $user ? 'true' : 'false'; ?>;
    if (isUserLoggedIn) {
        const ctaContainer = document.getElementById('banner-cta');
        if (ctaContainer) ctaContainer.style.display = 'none';
    }
}

// --- C. CARREGAR HERO SLIDER (TOP 5) ---
async function loadHero() {
    const container = document.getElementById('hero-slider');
    try {
        const res = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&ordering=-added&page_size=5&dates=2024-01-01,2025-12-31`);
        const data = await res.json();
        
        container.innerHTML = '';
        let dotsHtml = '<div class="hero-dots">';
        
        data.results.forEach((game, index) => {
            const active = index === 0 ? 'active' : '';
            const year = game.released ? game.released.split('-')[0] : 'TBA';
            const genre = game.genres[0] ? game.genres[0].name : '';

            const slide = document.createElement('div');
            slide.className = `hero-slide ${active}`;
            slide.dataset.index = index;
            slide.innerHTML = `
                <img src="${game.background_image}" class="hero-backdrop">
                <div class="hero-content">
                    <img src="${game.background_image}" class="hero-poster">
                    <div class="hero-info">
                        <div class="trending-badge"><i class="fa-solid fa-fire"></i> Trending #${index + 1}</div>
                        <h1 class="hero-title">${game.name}</h1>
                        <div class="hero-meta">
                            <span>${year}</span><span>•</span><span>${genre}</span>
                        </div>
                        <a href="game.php?id=${game.id}" class="btn-hero"><i class="fa-solid fa-play"></i> Ver Detalhes</a>
                    </div>
                </div>
            `;
            container.appendChild(slide);
            dotsHtml += `<div class="hero-dot ${active}" onclick="goToSlide(${index})"></div>`;
        });
        
        dotsHtml += '</div>';
        container.insertAdjacentHTML('beforeend', dotsHtml);
        startSlider();
    } catch (e) { console.error("Erro slider", e); }
}

let slideInterval;
function startSlider() {
    if(slideInterval) clearInterval(slideInterval);
    slideInterval = setInterval(() => nextSlide(), 6000);
}
function nextSlide() {
    const slides = document.querySelectorAll('.hero-slide');
    let current = document.querySelector('.hero-slide.active');
    let curIndex = parseInt(current.dataset.index);
    let nextIndex = (curIndex + 1) % slides.length;
    goToSlide(nextIndex);
}
function goToSlide(index) {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-dot');
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    slides[index].classList.add('active');
    dots[index].classList.add('active');
    startSlider();
}

// --- D. FUNÇÃO AUXILIAR: CARD ---
function createCard(game) {
    const card = document.createElement('div');
    card.className = 'game-card';
    card.onclick = () => window.location.href = `game.php?id=${game.id}`;
    const meta = game.metacritic ? `<div class="metacritic-badge">${game.metacritic}</div>` : '';
    const year = game.released ? game.released.split('-')[0] : 'TBA';
    const genre = game.genres[0] ? game.genres[0].name : '';
    card.innerHTML = `<img src="${game.background_image || 'https://via.placeholder.com/300x400'}" loading="lazy">${meta}<div class="card-info"><div class="card-title">${game.name}</div><div class="card-meta">${year} • ${genre}</div></div>`;
    return card;
}

// --- E. CARREGAR LISTAS PRINCIPAIS ---
async function applyFilters() {
    const container = document.getElementById('featured-list');
    try {
        const res = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&page_size=12&ordering=-added`);
        const data = await res.json();
        container.innerHTML = '';
        data.results.forEach(game => { if(game.background_image) container.appendChild(createCard(game)); });
    } catch (e) { container.innerHTML = 'Erro ao carregar lista.'; }
}

async function loadUpcoming() {
    const container = document.getElementById('upcoming-list');
    const today = new Date().toISOString().split('T')[0];
    const nextYear = new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toISOString().split('T')[0];
    try {
        const res = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&dates=${today},${nextYear}&ordering=released&page_size=6`);
        const data = await res.json();
        container.innerHTML = '';
        data.results.forEach(game => { if(game.background_image) container.appendChild(createCard(game)); });
    } catch (e) { console.error(e); }
}

window.addEventListener('DOMContentLoaded', () => {
    loadBannerCovers();
    checkUserStatus();
    loadHero();
    applyFilters();
    loadUpcoming();
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>