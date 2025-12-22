<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GameList - Explora Jogos</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" type="image/png" href="img/logo.png">
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

    .search-bar {
      display: flex;
      justify-content: center;
      margin-bottom: 30px;
      gap: 10px;
    }

    .search-bar input {
      width: 300px;
      padding: 12px 15px;
      border-radius: 8px;
      border: none;
      font-size: 16px;
      background: #222;
      color: #eee;
    }

    .search-bar input::placeholder {
      color: #aaa;
    }

    .search-bar button {
      padding: 12px 20px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-weight: bold;
      color: #fff;
      background: linear-gradient(135deg, #00bfff, #0080ff);
      transition: all 0.2s ease;
    }

    .search-bar button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0,191,255,0.4);
    }

    #results {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 20px;
    }

    .game-card {
      background: #1f1f1f;
      border-radius: 12px;
      overflow: hidden;
      text-align: center;
      transition: transform 0.2s, box-shadow 0.2s;
      cursor: pointer;
    }

    .game-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,191,255,0.4);
    }

    .game-card img {
      width: 100%;
      height: 220px;
      object-fit: cover;
    }

    .game-card p {
      margin: 10px 0;
      font-weight: bold;
      color: #00bfff;
    }

    #carousel {
      max-width: 1200px;
      margin: 40px auto 0 auto;
      position: relative;
    }

    #carousel-inner {
      overflow: hidden;
      border-radius: 16px;
    }

    .carousel-slide {
      display: none;
      position: relative;
    }

    .carousel-slide img {
      width: 100%;
      height: 400px;
      object-fit: cover;
    }

    .carousel-slide div {
      position: absolute;
      bottom: 30px;
      left: 30px;
      background: rgba(0, 0, 0, 0.6);
      color: #fff;
      padding: 16px 32px;
      border-radius: 12px;
      font-size: 2rem;
      font-weight: bold;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    }

    #carousel-prev, #carousel-next {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: linear-gradient(135deg, #00bfff, #8a2be2);
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 48px;
      height: 48px;
      font-size: 2rem;
      cursor: pointer;
      z-index: 2;
      box-shadow: 0 4px 16px rgba(0,0,0,0.25);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s, transform 0.2s;
      opacity: 0.85;
    }
    #carousel-prev:hover, #carousel-next:hover {
      background: linear-gradient(135deg, #8a2be2, #00bfff);
      transform: translateY(-50%) scale(1.12);
      opacity: 1;
    }
    #carousel-prev {
      left: 18px;
    }
    #carousel-next {
      right: 18px;
    }
    #carousel-prev svg, #carousel-next svg {
      width: 28px;
      height: 28px;
      display: block;
    }

    @media (max-width: 768px) {
      .search-bar {
        flex-direction: column;
        align-items: center;
      }
      
      .search-bar input {
        width: 100%;
      }
    }
  </style>
</head>

<body>
  <?php include 'includes/header.php'; ?>

  <div id="carousel" style="max-width:1200px;margin:40px auto 0 auto;position:relative;">
    <div id="carousel-inner" style="overflow:hidden;border-radius:16px;">
      <!-- Slides serão inseridos aqui -->
    </div>
    <button id="carousel-prev">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="12" fill="none"/>
        <path d="M15 6L9 12L15 18" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
    <button id="carousel-next">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="12" cy="12" r="12" fill="none"/>
        <path d="M9 6L15 12L9 18" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
  </div>

  <main>
    <h1>Explora Jogos</h1>

    <section style="margin-bottom:24px;">
      <form id="filters-form" style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;align-items:center;margin-bottom:18px;">
        <select id="genre-filter" style="padding:10px 16px;border-radius:8px;background:#222;color:#fff;border:none;font-size:1rem;">
          <option value="">Todos os Géneros</option>
        </select>
        <select id="platform-filter" style="padding:10px 16px;border-radius:8px;background:#222;color:#fff;border:none;font-size:1rem;">
          <option value="">Todas as Plataformas</option>
        </select>
        <select id="year-filter" style="padding:10px 16px;border-radius:8px;background:#222;color:#fff;border:none;font-size:1rem;">
          <option value="">Todos os Anos</option>
        </select>
        <select id="order-filter" style="padding:10px 16px;border-radius:8px;background:#222;color:#fff;border:none;font-size:1rem;">
          <option value="-rating">Melhores Avaliados</option>
          <option value="released">Mais Recentes</option>
          <option value="-added">Mais Populares</option>
          <option value="-metacritic">Melhor Metacritic</option>
        </select>
        <button type="button" class="btn" onclick="applyFilters()">Filtrar</button>
      </form>
    </section>

    <section id="featured-games" style="margin-bottom:40px;">
      <h2 style="color:#00bfff;text-align:center;margin-bottom:18px;">Jogos em Destaque</h2>
      <div id="featured-list" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;"></div>
    </section>

    <section id="upcoming-games" style="margin-bottom:40px;">
      <h2 style="color:#00bfff;text-align:center;margin-bottom:18px;">Próximos Lançamentos</h2>
      <div id="upcoming-list" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;"></div>
    </section>

    <div class="search-bar">
      <input type="text" id="search" placeholder="Procura um jogo...">
      <button onclick="searchGame()">Procurar</button>
    </div>

    <div id="results">
      <!-- Resultados da pesquisa aparecem aqui -->
    </div>
  </main>

  <script>
const apiKey = '5fd330b526034329a8f0d9b6676241c5';

// Carregar carrossel de banners dos jogos mais populares
async function loadCarousel() {
  const carouselInner = document.getElementById('carousel-inner');
  carouselInner.innerHTML = '<div style="text-align:center;color:#aaa;padding:40px;">A carregar...</div>';
  try {
    // Jogos mais populares de 2025
    const startDate = '2025-01-01';
    const endDate = '2025-12-31';
    const res = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&ordering=-rating&page_size=5&dates=${startDate},${endDate}`);
    const data = await res.json();
    carouselInner.innerHTML = '';
    data.results.forEach((game, idx) => {
      const slide = document.createElement('div');
      slide.className = 'carousel-slide';
      slide.style.display = idx === 0 ? 'block' : 'none';
      slide.style.position = 'relative';
      slide.innerHTML = `
        <a href="game.php?id=${game.id}" style="display:block;">
          <img src="${game.background_image || 'https://via.placeholder.com/1200x400?text=Sem+Imagem'}" alt="${game.name}" style="width:100%;height:400px;object-fit:cover;">
          <div style="position:absolute;bottom:30px;left:30px;background:rgba(0,0,0,0.6);color:#fff;padding:16px 32px;border-radius:12px;font-size:2rem;font-weight:bold;box-shadow:0 4px 16px rgba(0,0,0,0.3);">
            ${game.name}
          </div>
        </a>
      `;
      carouselInner.appendChild(slide);
    });
  } catch (err) {
    carouselInner.innerHTML = '<div style="text-align:center;color:#ff4444;padding:40px;">Erro ao carregar carrossel.</div>';
  }
}

// Carrossel navegação + autoplay
let currentSlide = 0;
function showSlide(idx) {
  const slides = document.querySelectorAll('.carousel-slide');
  if (!slides.length) return;
  slides.forEach((slide, i) => {
    slide.style.display = i === idx ? 'block' : 'none';
  });
}
document.getElementById('carousel-prev').onclick = function() {
  const slides = document.querySelectorAll('.carousel-slide');
  currentSlide = (currentSlide - 1 + slides.length) % slides.length;
  showSlide(currentSlide);
};
document.getElementById('carousel-next').onclick = function() {
  const slides = document.querySelectorAll('.carousel-slide');
  currentSlide = (currentSlide + 1) % slides.length;
  showSlide(currentSlide);
};
// Autoplay
setInterval(function() {
  const slides = document.querySelectorAll('.carousel-slide');
  if (slides.length) {
    currentSlide = (currentSlide + 1) % slides.length;
    showSlide(currentSlide);
  }
}, 4000);

// Carregar jogos em destaque (populares)
async function loadFeaturedGames() {
  const featuredDiv = document.getElementById('featured-list');
  featuredDiv.innerHTML = '<p style="text-align:center;color:#aaa;">A carregar...</p>';
  try {
    const popularRes = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&ordering=-rating&page_size=6`);
    const popularData = await popularRes.json();
    featuredDiv.innerHTML = '';
    popularData.results.forEach(game => {
      const card = document.createElement('div');
      card.classList.add('game-card');
      card.innerHTML = `
        <a href="game.php?id=${game.id}">
          <img src="${game.background_image || 'https://via.placeholder.com/300x220?text=Sem+Imagem'}" alt="${game.name}">
          <p>${game.name}</p>
        </a>
      `;
      featuredDiv.appendChild(card);
    });
  } catch (err) {
    featuredDiv.innerHTML = '<p style="text-align:center;color:#ff4444;">Erro ao carregar jogos em destaque.</p>';
  }
}

// Carregar próximos lançamentos
async function loadUpcomingGames() {
  const upcomingDiv = document.getElementById('upcoming-list');
  upcomingDiv.innerHTML = '<p style="text-align:center;color:#aaa;">A carregar...</p>';
  try {
    // Próximos 6 meses
    const today = new Date();
    const next6Months = new Date(today.getFullYear(), today.getMonth()+6, today.getDate());
    const startDate = today.toISOString().split('T')[0];
    const endDate = next6Months.toISOString().split('T')[0];
    const upcomingRes = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&dates=${startDate},${endDate}&ordering=released&page_size=6`);
    const upcomingData = await upcomingRes.json();
    upcomingDiv.innerHTML = '';
    upcomingData.results.forEach(game => {
      const card = document.createElement('div');
      card.classList.add('game-card');
      card.innerHTML = `
        <a href="game.php?id=${game.id}">
          <img src="${game.background_image || 'https://via.placeholder.com/300x220?text=Sem+Imagem'}" alt="${game.name}">
          <p>${game.name}</p>
        </a>
      `;
      upcomingDiv.appendChild(card);
    });
  } catch (err) {
    upcomingDiv.innerHTML = '<p style="text-align:center;color:#ff4444;">Erro ao carregar lançamentos.</p>';
  }
}

// Carregar géneros, plataformas e anos para filtros
async function loadFilters() {
  // Géneros
  const genreSel = document.getElementById('genre-filter');
  const genreRes = await fetch(`https://api.rawg.io/api/genres?key=${apiKey}`);
  const genreData = await genreRes.json();
  genreData.results.forEach(g => {
    const opt = document.createElement('option');
    opt.value = g.slug;
    opt.textContent = g.name;
    genreSel.appendChild(opt);
  });
  // Plataformas
  const platSel = document.getElementById('platform-filter');
  const platRes = await fetch(`https://api.rawg.io/api/platforms?key=${apiKey}`);
  const platData = await platRes.json();
  platData.results.forEach(p => {
    const opt = document.createElement('option');
    opt.value = p.id;
    opt.textContent = p.name;
    platSel.appendChild(opt);
  });
  // Anos
  const yearSel = document.getElementById('year-filter');
  const currentYear = new Date().getFullYear();
  for(let y=currentYear; y>=2000; y--) {
    const opt = document.createElement('option');
    opt.value = y;
    opt.textContent = y;
    yearSel.appendChild(opt);
  }
}

// Aplicar filtros aos jogos em destaque e lançamentos
async function applyFilters(forceLoad=false) {
  const genre = document.getElementById('genre-filter').value;
  const platform = document.getElementById('platform-filter').value;
  const year = document.getElementById('year-filter').value;
  const order = document.getElementById('order-filter').value;
  // Jogos em destaque
  const featuredDiv = document.getElementById('featured-list');
  featuredDiv.innerHTML = '<p style="text-align:center;color:#aaa;">A carregar...</p>';
  let url = `https://api.rawg.io/api/games?key=${apiKey}&ordering=${order}&page_size=6`;
  if (genre) url += `&genres=${genre}`;
  if (platform) url += `&platforms=${platform}`;
  if (year) url += `&dates=${year}-01-01,${year}-12-31`;
  try {
    const res = await fetch(url);
    const data = await res.json();
    featuredDiv.innerHTML = '';
    if (!data.results || !data.results.length) {
      featuredDiv.innerHTML = '<p style="text-align:center;color:#aaa;">Nenhum jogo encontrado.</p>';
      return;
    }
    data.results.forEach(game => {
      const card = document.createElement('div');
      card.classList.add('game-card');
      card.innerHTML = `
        <a href="game.php?id=${game.id}">
          <img src="${game.background_image || 'https://via.placeholder.com/300x220?text=Sem+Imagem'}" alt="${game.name}">
          <p>${game.name}</p>
        </a>
      `;
      featuredDiv.appendChild(card);
    });
  } catch (err) {
    featuredDiv.innerHTML = '<p style="text-align:center;color:#ff4444;">Erro ao carregar jogos em destaque.</p>';
  }
  // Próximos lançamentos NÃO são afetados pelos filtros
  if (forceLoad) {
    loadUpcomingGames();
  }
}

window.addEventListener('DOMContentLoaded', () => {
  loadCarousel();
  loadFilters();
  applyFilters(true);
});
  </script>
</body>
</html>
