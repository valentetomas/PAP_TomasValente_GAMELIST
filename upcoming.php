<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'includes/header.php';
?>
<title>Próximos Lançamentos - GameList</title>
</head>
<body>
<style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, sans-serif;
      background: linear-gradient(180deg, #0b0b0b, #151515);
      color: #eee;
    }

    main {
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    h1 {
      color: #00bfff;
      font-size: 2.5rem;
      text-align: center;
      margin-bottom: 30px;
    }

    .filters-section {
      background: #222;
      padding: 24px;
      border-radius: 12px;
      margin-bottom: 32px;
      box-shadow: 0 4px 16px rgba(0,191,255,0.1);
    }

    .filters-row {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
    }

    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .filter-group label {
      color: #00bfff;
      font-weight: bold;
      font-size: 0.9rem;
    }

    .filter-group select {
      padding: 10px 16px;
      border-radius: 8px;
      background: #181818;
      color: #fff;
      border: 1px solid #333;
      font-size: 1rem;
      min-width: 180px;
    }

    .btn {
      padding: 12px 24px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-weight: bold;
      color: #fff;
      background: linear-gradient(135deg, #00bfff, #0080ff);
      transition: all 0.2s ease;
      font-size: 1rem;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,191,255,0.4);
    }

    #games-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 28px;
      margin-top: 32px;
    }

    .game-card {
      background: #1f1f1f;
      border-radius: 12px;
      overflow: hidden;
      text-align: left;
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
      position: relative;
    }

    .game-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,191,255,0.4);
    }

    .game-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .game-info {
      padding: 16px;
    }

    .game-info h3 {
      margin: 0 0 12px 0;
      color: #00bfff;
      font-size: 1.1rem;
    }

    .game-date {
      color: #aaa;
      font-size: 0.9rem;
      margin-bottom: 8px;
    }

    .badge {
      position: absolute;
      top: 12px;
      right: 12px;
      background: linear-gradient(135deg, #ff6b6b, #ff4444);
      color: #fff;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: bold;
      box-shadow: 0 2px 8px rgba(255,68,68,0.4);
    }

    .loading {
      text-align: center;
      color: #aaa;
      padding: 40px;
      font-size: 1.1rem;
    }
  </style>
</head>

<body>
  <main>
    <h1>Próximos Lançamentos</h1>

    <div class="filters-section">
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
            <option value="">Todas</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="genre-filter">Género</label>
          <select id="genre-filter">
            <option value="">Todos</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="sort-filter">Ordenar por</label>
          <select id="sort-filter">
            <option value="-added">Mais Esperados</option>
            <option value="released">Data de Lançamento</option>
            <option value="-rating">Melhor Avaliados</option>
          </select>
        </div>
        <div class="filter-group" style="justify-content: flex-end;">
          <label>&nbsp;</label>
          <button class="btn" onclick="applyFilters()">Aplicar Filtros</button>
        </div>
      </div>
    </div>

    <div id="games-grid">
      <div class="loading">A carregar jogos...</div>
    </div>
  </main>

  <script>
    const apiKey = '5fd330b526034329a8f0d9b6676241c5';

    // Carregar plataformas e géneros
    async function loadFilters() {
      try {
        const platRes = await fetch(`https://api.rawg.io/api/platforms?key=${apiKey}`);
        const platData = await platRes.json();
        const platSelect = document.getElementById('platform-filter');
        platData.results.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.id;
          opt.textContent = p.name;
          platSelect.appendChild(opt);
        });

        const genreRes = await fetch(`https://api.rawg.io/api/genres?key=${apiKey}`);
        const genreData = await genreRes.json();
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

    // Aplicar filtros e carregar jogos
    async function applyFilters() {
      const grid = document.getElementById('games-grid');
      grid.innerHTML = '<div class="loading">A carregar jogos...</div>';

      const period = parseInt(document.getElementById('period-filter').value);
      const platform = document.getElementById('platform-filter').value;
      const genre = document.getElementById('genre-filter').value;
      const sort = document.getElementById('sort-filter').value;

      const today = new Date();
      const futureDate = new Date(today.getFullYear(), today.getMonth() + period, today.getDate());
      const startDate = today.toISOString().split('T')[0];
      const endDate = futureDate.toISOString().split('T')[0];

      let url = `https://api.rawg.io/api/games?key=${apiKey}&dates=${startDate},${endDate}&ordering=${sort}&page_size=18`;
      if (platform) url += `&platforms=${platform}`;
      if (genre) url += `&genres=${genre}`;

      try {
        const res = await fetch(url);
        const data = await res.json();
        grid.innerHTML = '';

        if (!data.results || data.results.length === 0) {
          grid.innerHTML = '<div class="loading">Nenhum jogo encontrado.</div>';
          return;
        }

        data.results.forEach(game => {
          const releaseDate = new Date(game.released);
          const daysUntilRelease = Math.ceil((releaseDate - today) / (1000 * 60 * 60 * 24));
          const isComingSoon = daysUntilRelease <= 30 && daysUntilRelease >= 0;

          const card = document.createElement('div');
          card.classList.add('game-card');
          card.innerHTML = `
            ${isComingSoon ? '<div class="badge">Em Breve</div>' : ''}
            <a href="game.php?id=${game.id}" style="text-decoration:none;color:inherit;">
              <img src="${game.background_image || 'https://via.placeholder.com/300x200?text=Sem+Imagem'}" alt="${game.name}">
              <div class="game-info">
                <h3>${game.name}</h3>
                <div class="game-date">📅 ${game.released || 'Data TBA'}</div>
                ${game.platforms ? `<div style="color:#888;font-size:0.85rem;">🎮 ${game.platforms.slice(0,3).map(p => p.platform.name).join(', ')}</div>` : ''}
              </div>
            </a>
          `;
          grid.appendChild(card);
        });
      } catch (err) {
        console.error('Erro ao carregar jogos:', err);
        grid.innerHTML = '<div class="loading" style="color:#ff4444;">Erro ao carregar jogos.</div>';
      }
    }

    window.addEventListener('DOMContentLoaded', () => {
      loadFilters();
      applyFilters();
    });
  </script>

  <?php include 'includes/footer.php'; ?>
</body>
</html>
