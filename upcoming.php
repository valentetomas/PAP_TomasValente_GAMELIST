<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Certifique-se que o header.php não fecha a tag <head> prematuramente
// ou ajuste conforme necessário.
include 'includes/header.php';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">

<title>Próximos Lançamentos - GameList</title>

<style>
    :root {
        --bg-dark: #0b0c0f;
        --surface: #16171c;
        --accent: #ff3366;
        --text-main: #ffffff;
        --text-muted: #9ca3af;
        --card-radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        margin: 0;
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-dark);
        color: var(--text-main);
        min-height: 100vh;
        overflow-x: hidden;
    }

    main {
        max-width: 1100px;
        margin: 0 auto;
        padding: 36px 20px 80px;
    }

    /* Hero */
    .hero {
        position: relative;
        padding: 32px 28px;
        border-radius: 16px;
        background: var(--surface);
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        margin-bottom: 36px;
    }

    .hero::after {
        content: '';
        position: absolute;
        inset: -40% -10% auto auto;
        width: 280px;
        height: 280px;
        background: radial-gradient(circle, rgba(255, 51, 102, 0.15), transparent 65%);
        filter: blur(10px);
        opacity: 0.6;
        pointer-events: none;
    }

    .hero-title {
        font-size: clamp(2rem, 3vw, 3rem);
        margin: 0 0 10px 0;
        color: var(--text-main);
        font-weight: 800;
        letter-spacing: -1px;
    }

    .hero-subtitle {
        color: var(--text-muted);
        max-width: 720px;
        line-height: 1.6;
        font-size: 1rem;
        margin: 0 0 18px 0;
    }

    .hero-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: var(--text-main);
        font-size: 0.8rem;
    }

    /* Filters Section */
    .filters-section {
        background: var(--surface);
        padding: 20px 25px;
        border-radius: 16px;
        margin-bottom: 50px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .filters-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        gap: 16px;
    }

    .filters-title {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        color: var(--text-main);
    }

    .filters-hint {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin: 0;
    }

    .filters-row {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
        flex: 1;
        min-width: 180px;
    }

    .filter-group label {
        color: var(--text-muted);
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Custom Select Styling */
    .filter-group select {
        padding: 10px 15px;
        border-radius: 8px;
        background-color: var(--bg-dark);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.1);
        font-family: inherit;
        font-size: 0.9rem;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1em;
        transition: var(--transition);
    }

    .filter-group select:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(255, 51, 102, 0.15);
    }

    .btn {
        padding: 10px 25px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 700;
        color: white;
        background: var(--accent);
        font-family: inherit;
        font-size: 0.9rem;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(255, 51, 102, 0.2);
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 51, 102, 0.35);
    }

    /* Grid Layout */
    #games-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 20px;
    }

    /* Game Card (estilo index) */
    .game-card {
        position: relative;
        aspect-ratio: 3/4;
        border-radius: 12px;
        overflow: hidden;
        background: #222;
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    }

    .game-card:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 15px 30px rgba(0,0,0,0.5);
        z-index: 5;
    }

    .game-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: filter 0.3s;
    }

    .game-card:hover img { filter: brightness(0.3); }

    .card-info {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .game-card:hover .card-info { opacity: 1; }

    .card-title {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 5px;
    }

    .card-meta {
        font-size: 0.8rem;
        color: #ccc;
        font-weight: 600;
    }

    .metacritic-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,0.8);
        color: #6c3;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 800;
        font-size: 0.8rem;
        border: 1px solid rgba(102, 204, 51, 0.3);
    }

    .badge {
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(255, 51, 102, 0.9);
        backdrop-filter: blur(4px);
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 800;
        z-index: 2;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    }

    /* Loading Skeleton */
    .skeleton {
        background: linear-gradient(90deg, #161b22 25%, #21262d 50%, #161b22 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: var(--card-radius);
    }
    
    .skeleton-card {
        aspect-ratio: 3/4;
        border-radius: 12px;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .filters-row {
            flex-direction: column;
            align-items: stretch;
        }

        .filters-header {
            flex-direction: column;
            align-items: flex-start;
        }

        #games-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
    }
</style>

<main>
    <section class="hero">
        <h1 class="hero-title">Próximos Lançamentos</h1>
        <p class="hero-subtitle">Descobre os jogos mais aguardados e filtra por plataforma, género e período. Mantém a tua lista sempre atualizada.</p>
        <div class="hero-badges">
            <span class="hero-badge">🎮 Curadoria diária</span>
            <span class="hero-badge">🚀 Atualizações em tempo real</span>
            <span class="hero-badge">📅 Calendário inteligente</span>
        </div>
    </section>

    <div class="filters-section">
        <div class="filters-header">
            <h2 class="filters-title">Filtra e ordena</h2>
            <p class="filters-hint">Combina filtros para encontrares o teu próximo jogo.</p>
        </div>
        <div class="filters-row">
            <div class="filter-group">
                <label for="period-filter">Período</label>
                <select id="period-filter">
                    <option value="1">Próximo mês</option>
                    <option value="3" selected>Próximos 3 meses</option>
                    <option value="6">Próximos 6 meses</option>
                    <option value="12">Próximo ano</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="platform-filter">Plataforma</label>
                <select id="platform-filter">
                    <option value="">Todas as plataformas</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="genre-filter">Género</label>
                <select id="genre-filter">
                    <option value="">Todos os géneros</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="sort-filter">Ordenar</label>
                <select id="sort-filter">
                    <option value="-added">Mais Populares</option>
                    <option value="released">Data de Lançamento</option>
                    <option value="-rating">Melhor Avaliados</option>
                </select>
            </div>
            <div class="filter-group" style="justify-content: flex-end; flex: 0 0 auto;">
                <button class="btn" onclick="applyFilters()">
                    Atualizar Lista
                </button>
            </div>
        </div>
    </div>

    <div id="games-grid">
        </div>
</main>

<script>
    const apiKey = '5fd330b526034329a8f0d9b6676241c5';

    // Função de Loading Skeleton (Efeito visual de carregamento)
    function showLoading() {
        const grid = document.getElementById('games-grid');
        grid.innerHTML = '';
        // Criar 8 cards fantasmas
        for(let i=0; i<8; i++) {
            const skel = document.createElement('div');
            skel.className = 'skeleton skeleton-card';
            grid.appendChild(skel);
        }
    }

    async function loadFilters() {
        try {
            // Paralelizar requisições para ser mais rápido
            const [platRes, genreRes] = await Promise.all([
                fetch(`https://api.rawg.io/api/platforms?key=${apiKey}`),
                fetch(`https://api.rawg.io/api/genres?key=${apiKey}`)
            ]);

            const platData = await platRes.json();
            const genreData = await genreRes.json();

            const platSelect = document.getElementById('platform-filter');
            // Ordenar alfabeticamente ou por popularidade se a API permitir
            platData.results.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name;
                platSelect.appendChild(opt);
            });

            const genreSelect = document.getElementById('genre-filter');
            genreData.results.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g.slug;
                opt.textContent = g.name;
                genreSelect.appendChild(opt);
            });

        } catch (err) {
            console.error('Erro ao carregar filtros:', err);
        }
    }

    async function applyFilters() {
        showLoading(); // Mostra o skeleton

        const period = parseInt(document.getElementById('period-filter').value);
        const platform = document.getElementById('platform-filter').value;
        const genre = document.getElementById('genre-filter').value;
        const sort = document.getElementById('sort-filter').value;

        const today = new Date();
        const futureDate = new Date(today.getFullYear(), today.getMonth() + period, today.getDate());
        const startDate = today.toISOString().split('T')[0];
        const endDate = futureDate.toISOString().split('T')[0];

        let url = `https://api.rawg.io/api/games?key=${apiKey}&dates=${startDate},${endDate}&ordering=${sort}&page_size=20`; // Aumentei para 20
        if (platform) url += `&platforms=${platform}`;
        if (genre) url += `&genres=${genre}`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            const grid = document.getElementById('games-grid');
            grid.innerHTML = '';

            if (!data.results || data.results.length === 0) {
                grid.innerHTML = '<div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 40px;">Nenhum jogo encontrado com estes filtros.</div>';
                return;
            }

            data.results.forEach(game => {
                const releaseDate = game.released ? new Date(game.released) : null;
                const dateStr = releaseDate ? releaseDate.toLocaleDateString('pt-PT', {day:'numeric', month:'short'}) : 'TBA';
                
                const daysUntil = releaseDate ? Math.ceil((releaseDate - today) / (1000 * 60 * 60 * 24)) : 999;
                const isComingSoon = daysUntil <= 30 && daysUntil >= 0;

                const card = document.createElement('div');
                card.classList.add('game-card');
                card.onclick = () => window.location.href = `game.php?id=${game.id}`;

                const meta = game.metacritic ? `<div class="metacritic-badge">${game.metacritic}</div>` : '';
                const genre = game.genres && game.genres[0] ? game.genres[0].name : '';
                const metaText = genre ? `${dateStr} • ${genre}` : dateStr;

                card.innerHTML = `
                    <img src="${game.background_image || 'https://via.placeholder.com/300x400'}" alt="${game.name}" loading="lazy">
                    ${meta}
                    ${isComingSoon ? '<div class="badge">🔥 Em Breve</div>' : ''}
                    <div class="card-info">
                        <div class="card-title">${game.name}</div>
                        <div class="card-meta">${metaText}</div>
                    </div>
                `;
                grid.appendChild(card);
            });
        } catch (err) {
            console.error('Erro ao carregar jogos:', err);
            document.getElementById('games-grid').innerHTML = '<div style="color:#ff4444; grid-column:1/-1; text-align:center;">Erro ao ligar à API.</div>';
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        loadFilters();
        applyFilters();
    });
</script>

<?php include 'includes/footer.php'; ?>