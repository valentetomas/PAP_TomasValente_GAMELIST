<?php
// Verifica se o ficheiro de base de dados existe antes de incluir
if (file_exists(__DIR__ . '/db.php')) {
    include __DIR__ . '/db.php'; 
}

// Inicia sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = null;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $user_id = $_SESSION['user_id'];
    if ($stmt = $conn->prepare("SELECT username, avatar, role FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $_SESSION['role'] = $user['role'];
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameList</title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/logo_favicon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #00b4ff;
            --secondary-color: #8a2be2;
            --bg-dark: #050507;
            --surface-glass: rgba(20, 20, 23, 0.85);
            --border-light: rgba(255, 255, 255, 0.08);
            --text-muted: #a0a0a0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            padding-top: 120px; /* Espaço para o header flutuante */
        }

        /* --- HEADER "ILHA" FLUTUANTE --- */
        .island-navbar {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 95%;
            max-width: 1280px;
            z-index: 1000;
            
            /* Vidro Fosco */
            background: var(--surface-glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
            padding: 10px 20px;
            transition: all 0.3s ease;
        }

        /* Logo */
        .navbar-brand img {
            height: 35px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
            transition: transform 0.3s;
        }
        .navbar-brand:hover img { transform: scale(1.05); }

        /* Links */
        .nav-link {
            color: #ccc !important;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 8px 16px !important;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .nav-link:hover {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.08);
        }

        /* --- BARRA DE PESQUISA --- */
        .search-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-light);
            border-radius: 50px;
            padding: 10px 15px 10px 45px;
            color: #fff;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            background: rgba(0, 0, 0, 0.5);
            border-color: var(--primary-color);
            box-shadow: 0 0 15px rgba(0, 180, 255, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-suggestions {
            position: absolute;
            top: 100%; left: 0; width: 100%;
            background: #121214;
            border: 1px solid var(--border-light);
            border-radius: 16px;
            margin-top: 10px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.8);
            z-index: 1050;
            max-height: 400px;
            overflow-y: auto;
        }

        .search-section-title {
            font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;
            color: var(--secondary-color); padding: 12px 15px;
            background: rgba(255,255,255,0.02); font-weight: 700;
        }

        .search-result-item {
            display: flex; align-items: center; gap: 15px;
            padding: 10px 15px; border-bottom: 1px solid rgba(255,255,255,0.03);
            cursor: pointer; transition: background 0.2s;
        }
        .search-result-item:hover { background: rgba(255,255,255,0.05); }
        .search-result-item img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }
        .result-name { font-size: 0.9rem; color: #fff; font-weight: 600; }
        .result-meta { font-size: 0.75rem; color: #888; }
        .no-results { padding: 20px; text-align: center; color: #777; font-size: 0.9rem; }

        /* --- BOTÃO REGISTAR --- */
        .btn-register {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff; border: none; border-radius: 50px;
            padding: 8px 24px; font-weight: 600; font-size: 0.9rem; transition: 0.3s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 15px rgba(138, 43, 226, 0.4);
            color: #fff;
        }

        /* --- NOVO ESTILO: AVATAR RING --- */
        .avatar-ring {
            width: 42px; height: 42px;
            padding: 2px;
            border: 2px solid rgba(255, 255, 255, 0.15); /* Anel padrão */
            border-radius: 50%;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
        }

        .avatar-ring img {
            width: 100%; height: 100%;
            border-radius: 50%; object-fit: cover;
            display: block;
        }

        /* Interação no Avatar */
        .profile-trigger:hover .avatar-ring,
        .profile-trigger[aria-expanded="true"] .avatar-ring {
            border-color: var(--primary-color);
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(0, 180, 255, 0.4);
        }

        /* Animação do Menu Dropdown */
        .dropdown-menu-custom {
            animation: dropIn 0.2s ease;
            background: #18181b;
            border: 1px solid var(--border-light);
        }

        @keyframes dropIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile */
        @media (max-width: 991px) {
            .island-navbar { width: 95%; top: 15px; padding: 15px; }
            .search-wrapper { margin: 15px 0; max-width: 100%; }
            .navbar-collapse { background: transparent; padding-top: 10px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg island-navbar">
    <div class="container-fluid">
        
        <a class="navbar-brand d-flex align-items-center me-4" href="index.php">
            <img src="img/logo.png" alt="GameList">
        </a>

        <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            
            <div class="mx-auto search-wrapper">
                <form class="w-100" autocomplete="off" onsubmit="return false;">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input id="headerSearch" class="search-input" type="search" placeholder="Pesquisar...">
                    <div class="search-suggestions" id="headerResults" style="display: none;"></div>
                </form>
            </div>

            <ul class="navbar-nav ms-auto align-items-center gap-3">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="upcoming.php">Lançamentos</a>
                </li>

                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link p-0 profile-trigger" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="avatar-ring">
                                <img src="<?php echo htmlspecialchars($user['avatar'] ?: 'https://via.placeholder.com/40?text=U'); ?>" alt="Perfil">
                            </div>
                        </a>
                        
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark dropdown-menu-custom mt-3 shadow-lg rounded-4 p-2">
                            <li><h6 class="dropdown-header text-white fw-bold text-center py-2 border-bottom border-secondary border-opacity-25">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </h6></li>
                            
                            <li><a class="dropdown-item rounded-2 mt-2" href="profile.php"><i class="fa-solid fa-user me-2 text-primary"></i> Meu Perfil</a></li>
                            
                            <?php if ($user['role'] == 'admin'): ?>
                                <li><a class="dropdown-item rounded-2 text-warning" href="admin.php"><i class="fa-solid fa-shield-halved me-2"></i> Admin</a></li>
                            <?php endif; ?>
                            
                            <li><a class="dropdown-item rounded-2" href="settings.php"><i class="fa-solid fa-gear me-2 text-muted"></i> Definições</a></li>
                            <li><hr class="dropdown-divider bg-secondary opacity-25"></li>
                            <li><a class="dropdown-item rounded-2 text-danger" href="logout.php"><i class="fa-solid fa-power-off me-2"></i> Sair</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link" href="login.php">Entrar</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn-register" href="register.php">Registar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    const RAWG_KEY = '5fd330b526034329a8f0d9b6676241c5';
    const searchInput = document.getElementById('headerSearch');
    const searchResults = document.getElementById('headerResults');
    
    if(!searchInput || !searchResults) return;

    let timeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(timeout);
        const query = searchInput.value.trim();
        if(query.length < 2){ searchResults.style.display = 'none'; return; }

        timeout = setTimeout(async () => {
            try{
                const [gamesRes, usersRes] = await Promise.all([
                    fetch(`https://api.rawg.io/api/games?key=${RAWG_KEY}&search=${encodeURIComponent(query)}&page_size=3`),
                    fetch(`search_users.php?q=${encodeURIComponent(query)}`)
                ]);
                const gamesData = await gamesRes.json();
                let usersData = { success: false, users: [] };
                try { if(usersRes.ok) usersData = await usersRes.json(); } catch(e){}

                searchResults.innerHTML = '';
                let hasResults = false;

                if(usersData.success && usersData.users.length > 0){
                    hasResults = true;
                    searchResults.innerHTML += '<div class="search-section-title">Comunidade</div>';
                    usersData.users.forEach(user => {
                        searchResults.innerHTML += `
                            <div class="search-result-item" onclick="window.location.href='user_profile.php?id=${user.id}'">
                                <img src="${user.avatar || 'https://via.placeholder.com/40?text=U'}" alt="${user.username}">
                                <div class="result-info">
                                    <div class="result-name">${user.username}</div>
                                    <div class="result-meta">Ver perfil</div>
                                </div>
                            </div>`;
                    });
                }

                if(gamesData.results && gamesData.results.length > 0){
                    hasResults = true;
                    searchResults.innerHTML += '<div class="search-section-title">Jogos</div>';
                    gamesData.results.forEach(game => {
                        searchResults.innerHTML += `
                            <div class="search-result-item" onclick="window.location.href='game.php?id=${game.id}'">
                                <img src="${game.background_image || 'https://via.placeholder.com/40?text=Game'}" alt="${game.name}">
                                <div class="result-info">
                                    <div class="result-name">${game.name}</div>
                                    <div class="result-meta">${game.released ? game.released.split('-')[0] : 'N/A'}</div>
                                </div>
                            </div>`;
                    });
                }

                if(!hasResults) searchResults.innerHTML = '<div class="no-results">Nada encontrado...</div>';
                searchResults.style.display = 'block';
            } catch(err){ console.error(err); }
        }, 400);
    });

    document.addEventListener('click', e => {
        if(!searchResults.contains(e.target) && e.target !== searchInput) searchResults.style.display = 'none';
    });
})();
</script>

</body>
</html>