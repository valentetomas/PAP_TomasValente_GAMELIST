async function searchGame() {
  const query = document.getElementById('search').value.trim();
  if (!query) return;

  const apiKey = "5fd330b526034329a8f0d9b6676241c5";
  const url = `https://api.rawg.io/api/games?key=${apiKey}&search=${encodeURIComponent(query)}`;

  const res = await fetch(url);
  const data = await res.json();

  const resultsDiv = document.getElementById('results');
  resultsDiv.innerHTML = "";

  if (!data.results.length) {
    resultsDiv.innerHTML = "<p>Nenhum jogo encontrado.</p>";
    return;
  }

  data.results.forEach(game => {
    const image = game.background_image ? game.background_image : "https://via.placeholder.com/250x140?text=Sem+Imagem";
    const card = `
      <div class="game">
        <img src="${image}" alt="${game.name}">
        <h2>
          <a href="game.php?id=${game.id}&name=${encodeURIComponent(game.name)}&image=${encodeURIComponent(image)}" style="color:#00bfff; text-decoration:none;">
            ${game.name}
          </a>
        </h2>
        <p>⭐ ${game.rating} | 📅 ${game.released || "Desconhecido"}</p>

        <!-- Botões para adicionar o jogo a diferentes listas -->
        <div style="display:flex; gap:5px; flex-wrap:wrap;">
          <form method="POST" action="add_to_list.php">
            <input type="hidden" name="list_name" value="Favoritos">
            <input type="hidden" name="game_id" value="${game.id}">
            <input type="hidden" name="game_name" value="${game.name}">
            <input type="hidden" name="game_image" value="${image}">
            <button type="submit" class="btn">⭐ Favoritos</button>
          </form>

          <form method="POST" action="add_to_list.php">
            <input type="hidden" name="list_name" value="Jogar mais tarde">
            <input type="hidden" name="game_id" value="${game.id}">
            <input type="hidden" name="game_name" value="${game.name}">
            <input type="hidden" name="game_image" value="${image}">
            <button type="submit" class="btn">⏱ Jogar mais tarde</button>
          </form>

          <form method="POST" action="add_to_list.php">
            <input type="hidden" name="list_name" value="Jogos jogados">
            <input type="hidden" name="game_id" value="${game.id}">
            <input type="hidden" name="game_name" value="${game.name}">
            <input type="hidden" name="game_image" value="${image}">
            <button type="submit" class="btn">✔ Já joguei</button>
          </form>
        </div>

      </div>
    `;
    resultsDiv.innerHTML += card;
  });
}
