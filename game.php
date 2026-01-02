 <?php
include 'includes/header.php';

if (!isset($_GET['id'])) {
    die("Jogo não especificado.");
}

$game_id = intval($_GET['id']);

$api_key = "5fd330b526034329a8f0d9b6676241c5";
$screenshots = [];

$ch = curl_init("https://api.rawg.io/api/games/$game_id/screenshots?key=$api_key");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['results'])) {
        $screenshots = $data['results'];
    }
}

// Buscar reviews do jogo
$reviews = $conn->query("SELECT r.*, u.username 
                         FROM reviews r 
                         INNER JOIN users u ON r.user_id = u.id 
                         WHERE r.game_id = $game_id AND r.approved = 1
                         ORDER BY r.created_at DESC");

// Calcular média de rating
$avg_rating = $conn->query("SELECT AVG(rating) as media FROM reviews WHERE game_id = $game_id AND approved = 1")->fetch_assoc()['media'];

// Pega dados do jogo (vindo da RAWG via GET ou podes passar nome e imagem via URL)

$game_name = null;
$game_image = null;
if (isset($_GET['name']) && !empty($_GET['name']) && $_GET['name'] !== 'null') {
    $game_name = urldecode($_GET['name']);
}
if (isset($_GET['image']) && !empty($_GET['image']) && $_GET['image'] !== 'null') {
    $game_image = urldecode($_GET['image']);
}
// Se não vierem por GET, buscar da API RAWG
if (!$game_name || !$game_image) {
    $ch = curl_init("https://api.rawg.io/api/games/$game_id?key=$api_key");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
        $data = json_decode($response, true);
        if (!$game_name && isset($data['name'])) {
            $game_name = $data['name'];
        }
        if (!$game_image && isset($data['background_image'])) {
            $game_image = $data['background_image'];
        }
    }
}
if (!$game_name) $game_name = "Desconhecido";
if (!$game_image) $game_image = "https://via.placeholder.com/250x140?text=Sem+Imagem";

?>
<title><?php echo $game_name; ?> - GameList</title>
</head>
<body>
<style>
        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(180deg, #0b0b0b, #151515);
            color: #eee;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* HERO */
        .game-hero {
            position: relative;
            height: 350px;
            background-image: url('<?php echo $game_image; ?>');
            background-size: cover;
            background-position: center;
        }

        .game-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.7);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            align-items: flex-end;
            padding: 30px;
        }

        .hero-content h1 {
            font-size: 2.5em;
            margin: 0;
            color: #00bfff;
        }

        .hero-content p {
            margin-top: 10px;
            font-size: 1.1em;
        }

        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 30px;
        }

        /* BOTÕES */
        .btn {
            background: linear-gradient(135deg, #00bfff, #0080ff);
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,191,255,0.4);
        }

        /* LISTAS */
        .game-lists form {
            display: inline-block;
            margin: 5px 5px 5px 0;
        }

        /* REVIEW FORM */
        .add-review {
            background: #1c1c1c;
            padding: 20px;
            border-radius: 12px;
            margin: 30px 0;
        }

        .add-review h3 {
            margin-top: 0;
        }

        .add-review input,
        .add-review textarea {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            border-radius: 6px;
            border: none;
            background: #2a2a2a;
            color: #fff;
        }

        /* REVIEWS */
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .review-card {
            background: #1f1f1f;
            padding: 15px;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .review-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        .review-card strong {
            color: #00bfff;
        }

        .review-rating {
            color: #ffcc00;
            font-size: 1.1em;
            margin: 5px 0;
        }

        .review-actions a,
        .review-actions button {
            font-size: 0.85em;
            margin-right: 5px;
        }

        .review-actions button {
            background: #ff4444;
        }

        /* RESPONSIVO */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 1.8em;
            }
        }

        .screenshots-wrapper {
        position: relative;
        margin: 20px 0 40px;
    }

    .screenshots-grid {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        scroll-behavior: smooth;
        padding: 10px 40px;
    }

    /* esconder scrollbar */
    .screenshots-grid::-webkit-scrollbar { display: none; }
    .screenshots-grid {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .screenshots-grid img {
        flex: 0 0 auto;
        width: 300px;
        height: 170px;
        object-fit: cover;
        border-radius: 12px;
        cursor: grab;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .screenshots-grid img:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 25px rgba(0,0,0,0.6);
    }

    /* BOTÕES */
    .scroll-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.7);
        color: #fff;
        border: none;
        width: 40px;
        height: 60px;
        font-size: 24px;
        cursor: pointer;
        border-radius: 10px;
        z-index: 2;
        transition: background 0.2s, transform 0.2s;
    }

    .scroll-btn:hover {
        background: rgba(0,191,255,0.9);
        transform: translateY(-50%) scale(1.1);
    }

    .scroll-btn.left { left: 0; }
    .scroll-btn.right { right: 0; }

    /* MOBILE */
    @media (max-width: 768px) {
        .scroll-btn {
            display: none;
        }
    }
    </style>
<body>
    <?php if(isset($_SESSION['msg'])): ?>
    <div style="background: #4CAF50; color: white; padding: 15px; text-align: center; font-weight: bold; position: fixed; top: 64px; left: 0; right: 0; z-index: 1000;">
        <?php if($_SESSION['msg'] == 'added'): ?>
        ✅ Jogo adicionado à lista com sucesso!
        <?php elseif($_SESSION['msg'] == 'exists'): ?>
        ⚠️ Este jogo já está na lista.
        <?php endif; ?>
    </div>
    <script>
        setTimeout(() => {
            document.querySelector('div[style*="background: #4CAF50"]').style.display = 'none';
        }, 3000);
    </script>
    <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>
    <div class="game-hero">
    <div class="hero-content">
        <div>
            <h1><?php echo $game_name; ?></h1>
            <p>⭐ <?php echo $avg_rating ? number_format($avg_rating,1) : "N/A"; ?>/10</p>
        </div>
    </div>
</div>

<?php if(!empty($screenshots)): ?>
    <h2>📸 Screenshots do jogo</h2>

<div class="screenshots-wrapper">
    <button class="scroll-btn left" onclick="scrollScreenshots(-1)">❮</button>

    <div class="screenshots-grid" id="screenshots">
        <?php foreach($screenshots as $shot): ?>
            <img src="<?php echo $shot['image']; ?>" alt="Screenshot do jogo">
        <?php endforeach; ?>
    </div>

    <button class="scroll-btn right" onclick="scrollScreenshots(1)">❯</button>
</div>
<?php endif; ?>

<div class="container">

    <?php if(isset($_SESSION['user_id'])): ?>

        <!-- Botões para adicionar o jogo a diferentes listas -->
        <div class="game-lists">
            <form method="POST" action="add_to_list.php" style="display:inline-block;">
                <input type="hidden" name="list_name" value="Favoritos">
                <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
                <input type="hidden" name="game_name" value="<?php echo $game_name; ?>">
                <input type="hidden" name="game_image" value="<?php echo $game_image; ?>">
                <button type="submit" class="btn">⭐ Favoritos</button>
            </form>

            <form method="POST" action="add_to_list.php" style="display:inline-block;">
                <input type="hidden" name="list_name" value="Jogar mais tarde">
                <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
                <input type="hidden" name="game_name" value="<?php echo $game_name; ?>">
                <input type="hidden" name="game_image" value="<?php echo $game_image; ?>">
                <button type="submit" class="btn">⏱ Jogar mais tarde</button>
            </form>

            <form method="POST" action="add_to_list.php" style="display:inline-block;">
                <input type="hidden" name="list_name" value="Jogos jogados">
                <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
                <input type="hidden" name="game_name" value="<?php echo $game_name; ?>">
                <input type="hidden" name="game_image" value="<?php echo $game_image; ?>">
                <button type="submit" class="btn">✔ Já joguei</button>
            </form>
        </div>

        <!-- Formulário para adicionar review -->
        <div class="add-review">
            <form method="POST" action="add_review.php">
                <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
                <input type="hidden" name="game_name" value="<?php echo $game_name; ?>">
                <input type="hidden" name="game_image" value="<?php echo $game_image; ?>">
                <input type="number" name="rating" placeholder="Nota 0-10" min="0" max="10" required>
                <textarea name="comment" placeholder="Comentário" required></textarea>
                <button type="submit" class="btn">Adicionar Review</button>
            </form>
        </div>

    <?php else: ?>
        <p>⚠️ Tens de <a href="login.php">iniciar sessão</a> para adicionar aos favoritos, jogar mais tarde, marcar como jogado ou adicionar uma review.</p>
    <?php endif; ?>

    <!-- Lista de reviews -->
    <h2>Reviews</h2>
    <?php if($reviews->num_rows > 0): ?>
    <div class="reviews-grid">
        <?php while($review = $reviews->fetch_assoc()): ?>
            <div class="review-card">
                <p><strong><?php echo htmlspecialchars($review['username']); ?></strong></p>
                <p class="review-rating">⭐ <?php echo $review['rating']; ?>/10</p>
                <p><?php echo htmlspecialchars($review['comment']); ?></p>
                <small>Escrita em <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></small>

                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['user_id']): ?>
                    <div style="margin-top:8px;">
                        <a href="edit_review.php?id=<?php echo $review['id']; ?>" style="background:#00bfff;padding:5px 10px;border-radius:6px;color:#fff;text-decoration:none;">✏️ Editar</a>
                        <form method="POST" action="delete_review.php" style="display:inline;">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" style="background:#ff3333;color:#fff;border:none;padding:5px 10px;border-radius:6px;cursor:pointer;">❌ Apagar</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
        <p>Nenhuma review ainda para este jogo.</p>
    <?php endif; ?>
    </div>

    <script>
function scrollScreenshots(direction) {
    const container = document.getElementById('screenshots');
    const scrollAmount = container.clientWidth * 0.8;
    container.scrollBy({
        left: scrollAmount * direction,
        behavior: 'smooth'
    });
}
</script>
</body>
</html>
<?php include 'includes/footer.php'; ?>