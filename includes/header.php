<?php
// Ajuste do include do DB para funcionar no XAMPP
include __DIR__ . '/db.php'; // assumindo que db.php está na mesma pasta que header.php ou ajuste o caminho

$user = null;
if (isset($_SESSION['user_id'])) {
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

<header class="main-header">
    <div class="header-inner">
        <!-- Logo -->
        <a href="index.php" class="logo" aria-label="Home">
            <img src="img/logo.png" alt="GameList Logo" width="40" height="40"/>
            <span>GameList</span>
        </a>

        <!-- Search -->
        <div class="search-wrapper" role="search">
            <form class="search-form" autocomplete="off" onsubmit="return false;">
                <button type="submit" class="search-icon" aria-label="Buscar">🔍</button>
                <input id="headerSearch" type="search" class="search-input" placeholder="Pesquisar jogos, listas ou autores..." aria-label="Pesquisar">
                <div class="search-suggestions" id="headerResults" aria-hidden="true"></div>
            </form>
        </div>

        <!-- Navigation -->
        <nav class="nav" aria-label="Menu principal">
            <ul class="nav-links">
                <li><a href="index.php">Início</a></li>
            </ul>

            <div class="account">
                <?php if ($user): ?>
                    <button class="account-btn" id="accountBtn" aria-haspopup="true" aria-expanded="false">
                        <img class="avatar" src="<?php echo htmlspecialchars($user['avatar'] ?: 'https://via.placeholder.com/48?text=U'); ?>" alt="Avatar">
                        <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                        ▼
                    </button>
                    <div class="account-menu" id="accountMenu" role="menu" aria-hidden="true">
                        <a href="profile.php" role="menuitem">Perfil</a>
                        <?php if ($user && $user['role'] == 'admin'): ?>
                        <a href="admin.php" role="menuitem">Admin</a>
                        <?php endif; ?>
                        <a href="settings.php" role="menuitem">Definições</a>
                        <a href="logout.php" role="menuitem" class="danger">Sair</a>
                    </div>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="login.php" class="btn-outline">Entrar</a>
                        <a href="register.php" class="btn-primary">Registar</a>
                    </div>
                <?php endif; ?>
            </div>

            <button class="hamburger" id="hamburger" aria-label="Abrir menu" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
        </nav>
    </div>
</header>

<style>
:root{
    --bg: rgba(20,20,22,0.9);
    --glass: rgba(255,255,255,0.06);
    --accent1: #00b4ff;
    --accent2: #8a2be2;
    --text: #eaeaf2;
    --muted: #b8b7c6;
}

/* Header */
.main-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 64px;
    z-index: 9999;
    background: linear-gradient(180deg, var(--bg), rgba(10,10,12,0.35));
    border-bottom: 1px solid rgba(255,255,255,0.04);
    display: flex;
    align-items: center;
}

.header-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    width: 100%;
    height: 100%;
}

/* Logo */
.logo {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: var(--text);
    height: 40px;
    flex-shrink: 0;
}

.logo img {
    width: 40px;
    height: 40px;
}

/* Search */
.search-wrapper {
    width: 500px;
    height: 40px;
    display: flex;
    justify-content: center;
    flex-shrink: 0;
}

.search-form {
    width: 100%;
    max-width: 500px;
    height: 32px;
    display: flex;
    align-items: center;
    background: var(--glass);
    border-radius: 999px;
    padding: 6px 10px;
    border: 1px solid rgba(255,255,255,0.04);
    gap: 8px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
}

.search-icon {
    background: transparent;
    border: none;
    color: var(--muted);
    cursor: pointer;
    flex-shrink: 0;
}

.search-input {
    flex: 1;
    background: transparent;
    border: 0;
    color: var(--text);
    outline: none;
    padding: 0 8px;
    font-size: 14px;
    height: 20px;
}

.search-input::placeholder {
    color: var(--muted);
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #0f0f11;
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 10px;
    margin-top: 4px;
    width: 100%;
    max-width: 500px;
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 1000;
}

.search-suggestions div {
    padding: 8px 12px;
    color: var(--muted);
    cursor: pointer;
}

.search-suggestions div:hover {
    background: rgba(255,255,255,0.05);
    color: var(--text);
}

/* Navigation */
.nav {
    display: flex;
    align-items: center;
    gap: 12px;
    height: 40px;
    flex-shrink: 0;
}

.nav-links {
    display: flex;
    gap: 12px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-links a {
    color: var(--muted);
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.18s;
}

.nav-links a:hover {
    color: var(--text);
    background: linear-gradient(90deg, rgba(0,180,255,0.06), rgba(138,43,226,0.06));
    transform: translateY(-1px);
}

/* Account */
.account {
    position: relative;
    display: flex;
    align-items: center;
    height: 40px;
    flex-shrink: 0;
}

.account-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(90deg, var(--accent1), var(--accent2));
    padding: 6px 10px;
    border-radius: 999px;
    border: none;
    color: #fff;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(138,43,226,0.12);
}

.account-btn .avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.12);
}

.account-btn .username {
    font-weight: 700;
    font-size: 14px;
}

.account-menu {
    position: absolute;
    right: 0;
    top: 58px;
    background: #0f0f11;
    border: 1px solid rgba(255,255,255,0.04);
    min-width: 180px;
    border-radius: 12px;
    padding: 8px;
    box-shadow: 0 20px 40px rgba(3,3,6,0.6);
    display: none;
    flex-direction: column;
    gap: 6px;
    z-index: 200;
}

.account-menu a {
    padding: 8px 10px;
    color: var(--muted);
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
}

.account-menu a:hover {
    background: rgba(255,255,255,0.02);
    color: var(--text);
}

.account-menu a.danger {
    color: #ff7b7b;
}

/* Auth links */
.auth-links {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn-outline {
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.06);
    color: var(--text);
    text-decoration: none;
}

.btn-primary {
    padding: 8px 12px;
    border-radius: 10px;
    background: linear-gradient(90deg, var(--accent1), var(--accent2));
    color: #fff;
    text-decoration: none;
    box-shadow: 0 6px 18px rgba(10,10,10,0.25);
}

/* Hamburger */
.hamburger {
    display: none;
    background: transparent;
    border: none;
    flex-direction: column;
    gap: 4px;
    padding: 8px;
    cursor: pointer;
}

.hamburger span {
    width: 20px;
    height: 2px;
    background: var(--text);
    display: block;
    border-radius: 2px;
}

/* Media queries */
@media (max-width: 900px) {
    .search-wrapper {
        display: none;
    }
    .nav-links {
        display: none;
    }
    .hamburger {
        display: flex;
    }
    .account-btn .username {
        display: none;
    }
}

.is-open {
    display: flex !important;
}

body {
    margin: 0;
    padding-top: 64px !important;
}
</style>

<script>
(function(){
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.querySelector('.nav-links');
    const accountBtn = document.getElementById('accountBtn');
    const accountMenu = document.getElementById('accountMenu');
    const searchInput = document.getElementById('headerSearch');
    const searchResults = document.getElementById('headerResults');

    // Hamburger
    if(hamburger && navLinks){
        hamburger.addEventListener('click', () => {
            const expanded = hamburger.getAttribute('aria-expanded') === 'true';
            hamburger.setAttribute('aria-expanded', !expanded);
            navLinks.classList.toggle('is-open');
        });
    }

    // Account menu
    if(accountBtn && accountMenu){
        accountBtn.addEventListener('click', () => {
            const open = accountMenu.style.display === 'flex';
            accountBtn.setAttribute('aria-expanded', !open);
            accountMenu.style.display = open ? 'none' : 'flex';
        });
    }

    // Fechar menus ao clicar fora
    document.addEventListener('click', e => {
        if(accountMenu && accountBtn && !accountBtn.contains(e.target) && !accountMenu.contains(e.target)){
            accountMenu.style.display = 'none';
            accountBtn.setAttribute('aria-expanded', 'false');
        }
    });

    // Escape key
    document.addEventListener('keydown', e => {
        if(e.key === 'Escape'){
            if(accountMenu) { accountMenu.style.display = 'none'; accountBtn.setAttribute('aria-expanded','false'); }
            if(navLinks) { hamburger.setAttribute('aria-expanded','false'); navLinks.classList.remove('is-open'); }
            if(searchResults) { searchResults.style.display = 'none'; }
        }
    });

    // Pesquisa RAWG
    if(searchInput && searchResults){
        const RAWG_KEY = '5fd330b526034329a8f0d9b6676241c5';
        let timeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(timeout);
            const query = searchInput.value.trim();
            if(query.length < 2){ searchResults.style.display = 'none'; return; }

            timeout = setTimeout(async () => {
                try{
                    const res = await fetch(`https://api.rawg.io/api/games?key=${RAWG_KEY}&search=${encodeURIComponent(query)}&page_size=5`);
                    const data = await res.json();

                    searchResults.innerHTML = '';
                    if(!data.results || data.results.length === 0){
                        searchResults.style.display = 'none';
                        return;
                    }

                    data.results.forEach(game => {
                        const div = document.createElement('div');
                        div.textContent = game.name;
                        div.addEventListener('click', () => {
                            const nameParam = game.name ? `&name=${encodeURIComponent(game.name)}` : '';
                            const imageParam = game.background_image ? `&image=${encodeURIComponent(game.background_image)}` : '';
                            window.location.href = `game.php?id=${game.id}${nameParam}${imageParam}`;
                        });
                        searchResults.appendChild(div);
                    });
                    searchResults.style.display = 'flex';
                } catch(err){
                    console.error('Erro na pesquisa RAWG:', err);
                }
            }, 300);
        });

        document.addEventListener('click', e => {
            if(!searchResults.contains(e.target) && e.target !== searchInput){
                searchResults.style.display = 'none';
            }
        });
    }
})();
</script>
