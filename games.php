<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Jogos - GameList';
include 'includes/header.php';
?>

<title>Jogos - GameList</title>

<style>
    :root {
        --bg-dark: #0b0c0f;
        --surface: #16171c;
        --accent: #ff3366;
        --text-main: #ffffff;
        --text-muted: #9ca3af;
        --border-soft: rgba(255, 255, 255, 0.08);
    }

    body {
        margin: 0;
        font-family: 'Inter', sans-serif;
        background: var(--bg-dark);
        color: var(--text-main);
        min-height: 100vh;
    }

    .games-page {
        max-width: 1320px;
        margin: 0 auto;
        padding: 90px 20px 60px;
    }

    .top-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        margin-bottom: 16px;
    }

    .games-count {
        color: var(--text-muted);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .controls-right {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .apply-filters-btn {
        border: none;
        background: transparent;
        color: var(--accent);
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        padding: 0;
    }

    .sort-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .sort-select {
        background: transparent;
        color: var(--accent);
        border: none;
        font-weight: 700;
        cursor: pointer;
        outline: none;
    }

    .sort-select option {
        color: #fff;
        background: #121318;
    }

    .layout {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 18px;
        align-items: start;
    }

    .layout.filters-hidden {
        grid-template-columns: 1fr;
    }

    .filters-panel {
        position: sticky;
        top: 85px;
        background: var(--surface);
        border: 1px solid var(--border-soft);
        border-radius: 12px;
        padding: 16px;
    }

    .filters-panel.hidden {
        display: none;
    }

    .filters-title {
        margin: 0 0 12px;
        font-size: 0.95rem;
        color: var(--text-main);
        font-weight: 700;
    }

    .filter-group {
        margin-bottom: 12px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 6px;
        color: var(--text-muted);
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filter-input,
    .filter-select {
        width: 100%;
        background: #111319;
        border: 1px solid var(--border-soft);
        color: #fff;
        border-radius: 8px;
        padding: 9px 10px;
        font-size: 0.88rem;
        outline: none;
    }

    .filter-input:focus,
    .filter-select:focus {
        border-color: var(--accent);
    }

    .filters-actions {
        display: flex;
        gap: 8px;
        margin-top: 14px;
    }

    .btn-small {
        flex: 1;
        border: 1px solid var(--border-soft);
        background: #101218;
        color: #fff;
        border-radius: 8px;
        padding: 8px 10px;
        font-size: 0.84rem;
        font-weight: 700;
        cursor: pointer;
    }

    .btn-small.primary {
        background: var(--accent);
        border-color: transparent;
    }

    .games-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px;
    }

    .game-card {
        display: block;
        position: relative;
        aspect-ratio: 2 / 3;
        border-radius: 8px;
        overflow: hidden;
        background: #222;
        text-decoration: none;
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }

    .game-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(100deg, #1a1a1a 20%, #2a2a2a 45%, #1a1a1a 70%);
        background-size: 200% 100%;
        animation: cardShimmer 1.2s linear infinite;
        z-index: 1;
        transition: opacity 0.25s ease;
    }

    .game-card.image-ready::before {
        opacity: 0;
        pointer-events: none;
    }

    @keyframes cardShimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .game-card:hover {
        transform: scale(1.05);
        z-index: 5;
        box-shadow: 0 10px 20px rgba(0,0,0,0.5);
        border: 1px solid rgba(255,255,255,0.3);
    }

    .game-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: filter 0.3s ease;
        opacity: 0;
        transition: opacity 0.25s ease, filter 0.3s ease;
        position: relative;
        z-index: 2;
    }

    .game-card img.is-loaded {
        opacity: 1;
    }

    .game-card:hover img {
        filter: brightness(0.25);
    }

    .game-card-title {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        text-align: center;
        color: #fff;
        font-weight: 700;
        font-size: 1.05rem;
        text-shadow: 0 2px 10px rgba(0,0,0,0.9);
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 10;
        pointer-events: none;
    }

    .game-card:hover .game-card-title {
        opacity: 1;
    }

    .status {
        margin: 14px 0 0;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .pagination-wrap {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }

    .pagination {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: center;
    }

    .page-btn {
        border: 1px solid var(--border-soft);
        background: #12151c;
        color: #fff;
        border-radius: 8px;
        padding: 8px 12px;
        font-weight: 700;
        cursor: pointer;
        min-width: 40px;
        font-size: 0.85rem;
    }

    .page-btn.active {
        background: var(--accent);
        border-color: transparent;
    }

    .page-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .page-dots {
        color: var(--text-muted);
        font-size: 0.85rem;
        padding: 0 4px;
    }

    @media (max-width: 980px) {
        .layout {
            grid-template-columns: 1fr;
        }

        .filters-panel {
            position: static;
        }

        .games-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 600px) {
        .games-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<main class="games-page">
    <div class="top-controls">
        <div class="games-count" id="games-count">A carregar jogos...</div>
        <div class="controls-right">
            <button class="apply-filters-btn" id="toggle-filters">Mostrar filtros</button>
            <div class="sort-wrap">
                <span>Sort by</span>
                <select id="sort-order" class="sort-select">
                    <option value="trending">Trending</option>
                    <option value="popular">Popular</option>
                    <option value="rating">Top Rated</option>
                    <option value="newest">Newest</option>
                    <option value="name">Name</option>
                </select>
            </div>
        </div>
    </div>

    <div class="layout filters-hidden" id="games-layout">
        <aside class="filters-panel hidden" id="filters-panel">
            <h2 class="filters-title">Filtros</h2>

            <div class="filter-group">
                <label for="search">Pesquisar</label>
                <input type="text" id="search" class="filter-input" placeholder="Nome do jogo">
            </div>

            <div class="filter-group">
                <label for="genre">Género</label>
                <select id="genre" class="filter-select">
                    <option value="">Todos</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="platform">Plataforma</label>
                <select id="platform" class="filter-select">
                    <option value="">Todas</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="year">Ano</label>
                <select id="year" class="filter-select">
                    <option value="">Todos</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="min-score">Metacritic mínimo</label>
                <select id="min-score" class="filter-select">
                    <option value="">Sem filtro</option>
                    <option value="50">50+</option>
                    <option value="60">60+</option>
                    <option value="70">70+</option>
                    <option value="80">80+</option>
                    <option value="90">90+</option>
                </select>
            </div>

            <div class="filters-actions">
                <button class="btn-small primary" id="apply-filters">Aplicar</button>
                <button class="btn-small" id="reset-filters">Limpar</button>
            </div>
        </aside>

        <section>
            <div class="games-grid" id="games-grid"></div>
            <p class="status" id="status"></p>
            <div class="pagination-wrap" id="pagination-wrap">
                <div class="pagination" id="pagination"></div>
            </div>
        </section>
    </div>
</main>

<script>
    const RAWG_KEY = '5fd330b526034329a8f0d9b6676241c5';
    const PAGE_SIZE = 30;
    const GAMES_CACHE_TTL = 5 * 60 * 1000;
    const FILTERS_CACHE_TTL = 24 * 60 * 60 * 1000;

    const gamesGrid = document.getElementById('games-grid');
    const statusEl = document.getElementById('status');
    const gamesCountEl = document.getElementById('games-count');
    const paginationEl = document.getElementById('pagination');
    const paginationWrapEl = document.getElementById('pagination-wrap');
    const sortSelect = document.getElementById('sort-order');
    const gamesLayout = document.getElementById('games-layout');
    const filtersPanel = document.getElementById('filters-panel');
    const toggleFiltersBtn = document.getElementById('toggle-filters');

    const searchInput = document.getElementById('search');
    const genreSelect = document.getElementById('genre');
    const platformSelect = document.getElementById('platform');
    const yearSelect = document.getElementById('year');
    const minScoreSelect = document.getElementById('min-score');

    const applyBtn = document.getElementById('apply-filters');
    const resetBtn = document.getElementById('reset-filters');

    let currentPage = 1;
    let totalGames = 0;
    let totalPages = 0;
    let loading = false;
    let filtersLoaded = false;
    let activeRequestController = null;
    const gamesCache = new Map();

    function getCachedData(key, ttl) {
        const item = gamesCache.get(key);
        if (!item) {
            return null;
        }
        if ((Date.now() - item.ts) > ttl) {
            gamesCache.delete(key);
            return null;
        }
        return item.data;
    }

    function setCachedData(key, data) {
        gamesCache.set(key, {
            ts: Date.now(),
            data
        });
    }

    function getLocalCache(key, ttl) {
        const raw = localStorage.getItem(key);
        if (!raw) {
            return null;
        }

        try {
            const parsed = JSON.parse(raw);
            if (!parsed || !parsed.ts || !Array.isArray(parsed.data)) {
                return null;
            }
            if ((Date.now() - parsed.ts) > ttl) {
                localStorage.removeItem(key);
                return null;
            }
            return parsed.data;
        } catch (_) {
            return null;
        }
    }

    function setLocalCache(key, data) {
        localStorage.setItem(key, JSON.stringify({
            ts: Date.now(),
            data
        }));
    }

    function fillYears() {
        const now = new Date().getFullYear();
        for (let year = now; year >= 1980; year--) {
            const option = document.createElement('option');
            option.value = String(year);
            option.textContent = String(year);
            yearSelect.appendChild(option);
        }
    }

    async function loadFilterOptions() {
        if (filtersLoaded) {
            return;
        }

        try {
            const cachedGenres = getLocalCache('rawg_genres_cache', FILTERS_CACHE_TTL);
            const cachedPlatforms = getLocalCache('rawg_platforms_cache', FILTERS_CACHE_TTL);

            let genresData = { results: cachedGenres || [] };
            let platformsData = { results: cachedPlatforms || [] };

            if (!cachedGenres || !cachedPlatforms) {
                const [genresRes, platformsRes] = await Promise.all([
                    fetch(`https://api.rawg.io/api/genres?key=${RAWG_KEY}&page_size=40`, { cache: 'force-cache' }),
                    fetch(`https://api.rawg.io/api/platforms/lists/parents?key=${RAWG_KEY}`, { cache: 'force-cache' })
                ]);

                genresData = await genresRes.json();
                platformsData = await platformsRes.json();

                if (Array.isArray(genresData.results)) {
                    setLocalCache('rawg_genres_cache', genresData.results);
                }
                if (Array.isArray(platformsData.results)) {
                    setLocalCache('rawg_platforms_cache', platformsData.results);
                }
            }

            if (genresData.results) {
                genresData.results.forEach(genre => {
                    const option = document.createElement('option');
                    option.value = genre.id;
                    option.textContent = genre.name;
                    genreSelect.appendChild(option);
                });
            }

            if (platformsData.results) {
                platformsData.results.forEach(platform => {
                    const option = document.createElement('option');
                    option.value = platform.id;
                    option.textContent = platform.name;
                    platformSelect.appendChild(option);
                });
            }

            filtersLoaded = true;
        } catch (error) {
            console.error('Erro a carregar filtros:', error);
        }
    }

    function getOrdering() {
        const sortMap = {
            trending: '-added',
            popular: '-suggestions_count',
            rating: '-rating',
            newest: '-released',
            name: 'name'
        };
        return sortMap[sortSelect.value] || '-added';
    }

    function buildApiUrl(page) {
        const params = new URLSearchParams({
            key: RAWG_KEY,
            page_size: String(PAGE_SIZE),
            page: String(page),
            ordering: getOrdering()
        });

        const searchText = searchInput.value.trim();
        if (searchText) {
            params.append('search', searchText);
        }

        if (genreSelect.value) {
            params.append('genres', genreSelect.value);
        }

        if (platformSelect.value) {
            params.append('parent_platforms', platformSelect.value);
        }

        if (yearSelect.value) {
            params.append('dates', `${yearSelect.value}-01-01,${yearSelect.value}-12-31`);
        }

        if (minScoreSelect.value) {
            params.append('metacritic', `${minScoreSelect.value},100`);
        }

        return `https://api.rawg.io/api/games?${params.toString()}`;
    }

    function renderCards(games) {
        const fragment = document.createDocumentFragment();
        const placeholderImage = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22450%22%3E%3Crect fill=%22%23222%22 width=%22300%22 height=%22450%22/%3E%3C/svg%3E';

        games.forEach((game, index) => {
            const card = document.createElement('a');
            card.className = 'game-card';
            card.href = `game.php?id=${game.id}`;
            const coverUrl = game.background_image || placeholderImage;
            const shouldPrioritize = currentPage === 1 && index < 12;

            card.innerHTML = `
                <img src="${coverUrl}" alt="${game.name}" loading="${shouldPrioritize ? 'eager' : 'lazy'}" fetchpriority="${shouldPrioritize ? 'high' : 'low'}" decoding="async">
                <div class="game-card-title">${game.name}</div>
            `;

            const img = card.querySelector('img');
            img.addEventListener('load', () => {
                img.classList.add('is-loaded');
                card.classList.add('image-ready');
            });

            img.addEventListener('error', () => {
                if (img.src !== placeholderImage) {
                    img.src = placeholderImage;
                    return;
                }
                img.classList.add('is-loaded');
                card.classList.add('image-ready');
            });

            fragment.appendChild(card);
        });

        gamesGrid.appendChild(fragment);
    }

    function toggleFilters() {
        const willShow = filtersPanel.classList.contains('hidden');
        filtersPanel.classList.toggle('hidden', !willShow);
        gamesLayout.classList.toggle('filters-hidden', !willShow);
        toggleFiltersBtn.textContent = willShow ? 'Esconder filtros' : 'Mostrar filtros';

        if (willShow) {
            loadFilterOptions();
        }
    }

    function createPageButton(label, page, { disabled = false, active = false } = {}) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `page-btn${active ? ' active' : ''}`;
        btn.textContent = label;
        btn.disabled = disabled;

        if (!disabled && !active) {
            btn.addEventListener('click', () => fetchGames(page));
        }

        return btn;
    }

    function createDots() {
        const dots = document.createElement('span');
        dots.className = 'page-dots';
        dots.textContent = '...';
        return dots;
    }

    function renderPagination() {
        paginationEl.innerHTML = '';

        if (totalPages <= 1) {
            paginationWrapEl.style.display = 'none';
            return;
        }

        paginationWrapEl.style.display = 'flex';
        paginationEl.appendChild(createPageButton('‹', currentPage - 1, { disabled: currentPage === 1 }));

        let start = Math.max(1, currentPage - 2);
        let end = Math.min(totalPages, start + 4);
        start = Math.max(1, end - 4);

        if (start > 1) {
            paginationEl.appendChild(createPageButton('1', 1, { active: currentPage === 1 }));
            if (start > 2) {
                paginationEl.appendChild(createDots());
            }
        }

        for (let page = start; page <= end; page++) {
            paginationEl.appendChild(createPageButton(String(page), page, { active: currentPage === page }));
        }

        if (end < totalPages) {
            if (end < totalPages - 1) {
                paginationEl.appendChild(createDots());
            }
            paginationEl.appendChild(createPageButton(String(totalPages), totalPages, { active: currentPage === totalPages }));
        }

        paginationEl.appendChild(createPageButton('›', currentPage + 1, { disabled: currentPage === totalPages }));
    }

    async function fetchGames(page = 1) {
        if (loading) {
            return;
        }

        loading = true;
        statusEl.textContent = 'A carregar jogos...';
        currentPage = page;
        gamesGrid.innerHTML = '';

        const requestUrl = buildApiUrl(currentPage);

        const cached = getCachedData(requestUrl, GAMES_CACHE_TTL);
        if (cached) {
            totalGames = Number(cached.count || 0);
            totalPages = Math.max(1, Math.ceil(totalGames / PAGE_SIZE));
            gamesCountEl.textContent = `${totalGames.toLocaleString('pt-PT')} Games`;

            const cachedItems = Array.isArray(cached.results) ? cached.results : [];
            if (cachedItems.length === 0) {
                statusEl.textContent = 'Nenhum jogo encontrado com os filtros atuais.';
                paginationWrapEl.style.display = 'none';
            } else {
                renderCards(cachedItems);
                statusEl.textContent = '';
                renderPagination();
            }

            loading = false;
            return;
        }

        if (activeRequestController) {
            activeRequestController.abort();
        }

        activeRequestController = new AbortController();

        try {
            const response = await fetch(requestUrl, {
                signal: activeRequestController.signal,
                cache: 'force-cache'
            });
            const data = await response.json();

            setCachedData(requestUrl, data);

            totalGames = Number(data.count || 0);
            totalPages = Math.max(1, Math.ceil(totalGames / PAGE_SIZE));
            gamesCountEl.textContent = `${totalGames.toLocaleString('pt-PT')} Games`;

            const items = Array.isArray(data.results) ? data.results : [];
            if (items.length === 0) {
                statusEl.textContent = 'Nenhum jogo encontrado com os filtros atuais.';
                paginationWrapEl.style.display = 'none';
            } else {
                renderCards(items);
                statusEl.textContent = '';
                renderPagination();
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            console.error('Erro ao carregar jogos:', error);
            statusEl.textContent = 'Erro ao carregar jogos. Tenta novamente.';
        } finally {
            loading = false;
        }
    }

    function applyFilters() {
        fetchGames(1);
    }

    function resetFilters() {
        searchInput.value = '';
        genreSelect.value = '';
        platformSelect.value = '';
        yearSelect.value = '';
        minScoreSelect.value = '';
        sortSelect.value = 'trending';
        fetchGames(1);
    }

    toggleFiltersBtn.addEventListener('click', toggleFilters);
    applyBtn.addEventListener('click', applyFilters);
    resetBtn.addEventListener('click', resetFilters);
    sortSelect.addEventListener('change', applyFilters);

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyFilters();
        }
    });

    fillYears();
    fetchGames(1);
</script>

<?php include 'includes/footer.php'; ?>