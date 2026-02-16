<?php
// Configuração de Base de Dados e Sessão
if (file_exists(__DIR__ . '/db.php')) include __DIR__ . '/db.php'; 
if (session_status() === PHP_SESSION_NONE) session_start();

// Recuperar dados do utilizador
$user = null;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $user_id = $_SESSION['user_id'];
    if ($stmt = $conn->prepare("SELECT username, avatar, role FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $_SESSION['role'] = $user['role'];
        $stmt->close();
    }
}

$page_title = $page_title ?? 'GameList';
$body_class = $body_class ?? 'gamelist-body';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="img/logo_favicon.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/header.css">
</head>
<body class="<?php echo htmlspecialchars($body_class); ?>">

<nav class="navbar navbar-expand-lg fixed-top bkd-navbar">
    <div class="container-fluid px-lg-4">
        
        <a class="navbar-brand bkd-brand" href="index.php">
            GameList
        </a>

        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 bkd-nav-links">
                <li class="nav-item"><a class="nav-link" href="games.php">Jogos</a></li>
                <li class="nav-item"><a class="nav-link" href="upcoming.php">Próximos Lançamentos</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3 right-section">
                
                <div class="bkd-search-wrapper">
    <form autocomplete="off" onsubmit="return false;">
        <input id="headerSearch" class="bkd-search-input" type="text" placeholder="Pesquisar">
        
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        
        <div class="bkd-search-results" id="headerResults"></div>
    </form>
</div>

                <?php if ($user): ?>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none profile-trigger" data-bs-toggle="dropdown">
                            <img src="<?php echo htmlspecialchars($user['avatar'] ?: 'https://via.placeholder.com/40'); ?>" class="bkd-avatar">
                            <i class="fa-solid fa-caret-down ms-2 text-secondary" style="font-size: 0.8rem;"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end bkd-dropdown">
                            <li class="px-3 py-2 text-muted small fw-bold text-uppercase"><?php echo htmlspecialchars($user['username']); ?></li>
                            <li><a class="dropdown-item" href="profile.php">Perfil</a></li>
                            <?php if ($user['role'] == 'admin'): ?>
                                <li><a class="dropdown-item text-warning" href="admin.php">Admin</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="settings.php">Definições</a></li>
                            <li><hr class="dropdown-divider bg-secondary opacity-25"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Sair</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a href="login.php" class="bkd-login-link">Log in</a>
                        <a href="register.php" class="bkd-signup-btn">Sign up</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    const RAWG_KEY = '5fd330b526034329a8f0d9b6676241c5';
    const input = document.getElementById('headerSearch');
    const results = document.getElementById('headerResults');
    let timeout;

    const escapeHtml = (value) => {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');
    };

    if(input && results){
        input.addEventListener('input', () => {
            clearTimeout(timeout);
            const q = input.value.trim();
            if(q.length < 2) { results.style.display = 'none'; return; }

            timeout = setTimeout(async () => {
                try {
                    const [gamesRes, usersRes] = await Promise.all([
                        fetch(`https://api.rawg.io/api/games?key=${RAWG_KEY}&search=${encodeURIComponent(q)}&page_size=4`),
                        fetch(`search_users.php?q=${encodeURIComponent(q)}`)
                    ]);

                    const gamesData = await gamesRes.json();
                    const usersData = await usersRes.json();
                    const users = (usersData && usersData.success && Array.isArray(usersData.users)) ? usersData.users : [];
                    
                    results.innerHTML = '';
                    let hasAnyResult = false;

                    if(users.length){
                        hasAnyResult = true;
                        users.forEach(u => {
                            const avatar = u.avatar || 'https://via.placeholder.com/40';
                            const html = `
                                <div class="bkd-result-item" onclick="window.location.href='user_profile.php?id=${u.id}'">
                                    <img src="${escapeHtml(avatar)}" class="bkd-result-img bkd-result-avatar">
                                    <div class="bkd-result-info">
                                        <div class="name">${escapeHtml(u.username)}</div>
                                        <div class="meta">Utilizador · ${u.follower_count || 0} seguidores</div>
                                    </div>
                                </div>`;
                            results.innerHTML += html;
                        });
                    }

                    if(gamesData.results && gamesData.results.length){
                        hasAnyResult = true;
                        gamesData.results.forEach(g => {
                            const year = g.released ? g.released.split('-')[0] : '';
                            const html = `
                                <div class="bkd-result-item" onclick="window.location.href='game.php?id=${g.id}'">
                                    <img src="${escapeHtml(g.background_image || '')}" class="bkd-result-img">
                                    <div class="bkd-result-info">
                                        <div class="name">${escapeHtml(g.name)}</div>
                                        <div class="meta">Jogo${year ? ' · ' + escapeHtml(year) : ''}</div>
                                    </div>
                                </div>`;
                            results.innerHTML += html;
                        });
                    }

                    results.style.display = hasAnyResult ? 'block' : 'none';
                } catch(e) {}
            }, 300);
        });

        // Fechar ao clicar fora
        document.addEventListener('click', e => {
            if(!input.contains(e.target) && !results.contains(e.target)) results.style.display = 'none';
        });
        
        // Efeito visual Backloggd: Input fica branco ao focar
        input.addEventListener('focus', () => { input.classList.add('active'); });
        input.addEventListener('blur', () => { input.classList.remove('active'); });
    }
})();
</script>