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

  <main>
    <h1>Explora Jogos</h1>

    <div class="search-bar">
      <input type="text" id="search" placeholder="Procura um jogo...">
      <button onclick="searchGame()">Procurar</button>
    </div>

    <div id="results">
      <!-- Resultados da pesquisa aparecem aqui -->
    </div>
  </main>

  <script>
    async function searchGame() {
      const query = document.getElementById('search').value.trim();
      const resultsDiv = document.getElementById('results');
      resultsDiv.innerHTML = '';

      if (!query) return;

      try {
        const apiKey = '5fd330b526034329a8f0d9b6676241c5';
        const response = await fetch(`https://api.rawg.io/api/games?key=${apiKey}&search=${encodeURIComponent(query)}&page_size=12`);
        const data = await response.json();

        if (data.results.length === 0) {
          resultsDiv.innerHTML = '<p style="text-align:center;color:#aaa;">Nenhum jogo encontrado.</p>';
          return;
        }

        data.results.forEach(game => {
          const card = document.createElement('div');
          card.classList.add('game-card');
          card.innerHTML = `
            <a href="game.php?id=${game.id}&name=${encodeURIComponent(game.name)}&image=${encodeURIComponent(game.background_image)}">
              <img src="${game.background_image || 'https://via.placeholder.com/300x220?text=Sem+Imagem'}" alt="${game.name}">
              <p>${game.name}</p>
            </a>
          `;
          resultsDiv.appendChild(card);
        });
      } catch (error) {
        console.error(error);
        resultsDiv.innerHTML = '<p style="text-align:center;color:#ff4444;">Erro ao buscar jogos.</p>';
      }
    }
  </script>
</body>
</html>
