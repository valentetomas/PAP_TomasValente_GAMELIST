<?php
include 'includes/header.php';

// Função para traduzir texto para português (divide em chunks se for muito longo)
function translateToPortuguese($text) {
    if (empty($text) || strlen($text) < 10) return $text;
    
    $maxChars = 450; // Deixar margem antes do limite de 500
    
    // Se o texto é pequeno, traduz direto
    if (strlen($text) <= $maxChars) {
        $translationURL = "https://api.mymemory.translated.net/get?q=" . urlencode($text) . "&langpair=en|pt";
        
        $ch = curl_init($translationURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
        
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if (isset($response['responseData']['translatedText'])) {
            return $response['responseData']['translatedText'];
        }
        return $text;
    }
    
    // Se é muito longo, divide em partes
    $sentences = preg_split('/(?<=[.!?])\s+/', $text);
    $chunks = [];
    $currentChunk = '';
    
    foreach ($sentences as $sentence) {
        if (strlen($currentChunk) + strlen($sentence) + 1 > $maxChars) {
            if (!empty($currentChunk)) {
                $chunks[] = $currentChunk;
            }
            $currentChunk = $sentence;
        } else {
            $currentChunk .= (empty($currentChunk) ? '' : ' ') . $sentence;
        }
    }
    
    if (!empty($currentChunk)) {
        $chunks[] = $currentChunk;
    }
    
    // Traduz cada chunk
    $translatedText = '';
    foreach ($chunks as $chunk) {
        $translationURL = "https://api.mymemory.translated.net/get?q=" . urlencode($chunk) . "&langpair=en|pt";
        
        $ch = curl_init($translationURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: Mozilla/5.0']);
        
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if (isset($response['responseData']['translatedText'])) {
            $translatedText .= $response['responseData']['translatedText'] . ' ';
        } else {
            $translatedText .= $chunk . ' ';
        }
    }
    
    return trim($translatedText);
}

// --- LÓGICA PHP (MANTIDA IGUAL) ---
if (!isset($_GET['id'])) { die("Jogo não especificado."); }
$game_id = intval($_GET['id']);
$api_key = "5fd330b526034329a8f0d9b6676241c5";

// Dados da API
$ch = curl_init("https://api.rawg.io/api/games/$game_id?key=$api_key");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$game_data = json_decode(curl_exec($ch), true);
curl_close($ch);

// Variáveis
$game_name = $game_data['name'] ?? "Desconhecido";
$game_image = $game_data['background_image'] ?? "https://via.placeholder.com/500x700";
$description_en = $game_data['description_raw'] ?? "Sem descrição disponível.";
$description = translateToPortuguese($description_en);
$released = isset($game_data['released']) ? date("Y", strtotime($game_data['released'])) : "TBA";
$full_release_date = isset($game_data['released']) ? date("d M, Y", strtotime($game_data['released'])) : "TBA";
$developers = implode(", ", array_column($game_data['developers'] ?? [], 'name'));
$platforms = $game_data['platforms'] ?? [];
$metacritic = $game_data['metacritic'] ?? 0;
$playtime = $game_data['playtime'] ?? 0;

// Estado do jogo nas listas do utilizador
$listMap = [];
$inLists = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $listStmt = $conn->prepare("SELECT id, name FROM lists WHERE user_id = ?");
    $listStmt->bind_param("i", $user_id);
    $listStmt->execute();
    $listRes = $listStmt->get_result();
    while ($row = $listRes->fetch_assoc()) {
        $listMap[$row['name']] = intval($row['id']);
    }
    $listStmt->close();

    $inStmt = $conn->prepare("SELECT l.name FROM list_items li JOIN lists l ON li.list_id = l.id WHERE l.user_id = ? AND li.game_id = ?");
    $inStmt->bind_param("ii", $user_id, $game_id);
    $inStmt->execute();
    $inRes = $inStmt->get_result();
    while ($row = $inRes->fetch_assoc()) {
        $inLists[$row['name']] = true;
    }
    $inStmt->close();
}

// Screenshots
$screenshots = [];
$ch_scr = curl_init("https://api.rawg.io/api/games/$game_id/screenshots?key=$api_key");
curl_setopt($ch_scr, CURLOPT_RETURNTRANSFER, true);
$scr_data = json_decode(curl_exec($ch_scr), true);
curl_close($ch_scr);
if (isset($scr_data['results'])) $screenshots = $scr_data['results'];

// Reviews BD
$reviews = $conn->query("SELECT r.*, u.username, u.avatar FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.game_id = $game_id AND r.approved = 1 ORDER BY r.created_at DESC");

// Stats das Reviews (Likes/Comments)
function getReviewStats($review_id) {
    global $conn;
    $likes = $conn->query("SELECT COUNT(*) as total FROM review_likes WHERE review_id = $review_id")->fetch_assoc()['total'];
    $comments = $conn->query("SELECT COUNT(*) as total FROM review_comments WHERE review_id = $review_id")->fetch_assoc()['total'];
    return ['likes' => $likes, 'comments' => $comments];
}

function userLikedReview($review_id, $user_id) {
    global $conn;
    if (!$user_id) return false;
    $result = $conn->query("SELECT id FROM review_likes WHERE review_id = $review_id AND user_id = $user_id");
    return $result->num_rows > 0;
}

function getReviewComments($review_id, $limit = 2) {
    global $conn;
    $stmt = $conn->prepare("SELECT rc.*, u.username, u.avatar FROM review_comments rc JOIN users u ON rc.user_id = u.id WHERE rc.review_id = ? ORDER BY rc.created_at DESC LIMIT ?");
    $stmt->bind_param("ii", $review_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// Média
$avg_data = $conn->query("SELECT AVG(rating) as media, COUNT(*) as total FROM reviews WHERE game_id = $game_id AND approved = 1")->fetch_assoc();
$avg_rating = $avg_data['media'] ? number_format($avg_data['media'], 1) : "N/A";

// Distribuição
$rating_counts = array_fill(0, 11, 0);
$max_count = 0;
$dist_res = $conn->query("SELECT rating, COUNT(*) as qtd FROM reviews WHERE game_id = $game_id AND approved = 1 GROUP BY rating");
while($row = $dist_res->fetch_assoc()) {
    $nota = intval($row['rating']);
    if($nota >= 0 && $nota <= 10) {
        $rating_counts[$nota] = intval($row['qtd']);
        if($rating_counts[$nota] > $max_count) $max_count = $rating_counts[$nota];
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
<style>
    :root {
        --primary-color: #00b4ff;
        --secondary-color: #8a2be2;
        --bg-dark: #050507;
        --surface-glass: rgba(20, 20, 23, 0.85);
        --border-light: rgba(255, 255, 255, 0.08);
        --text-muted: #a0a0a0;
    }

    /* --- CSS RESET & CORREÇÕES VISUAIS --- */
    * { box-sizing: border-box; }
    
    body {
        background-color: var(--bg-dark);
        color: #fff;
        font-family: 'Inter', sans-serif;
        margin: 0; padding: 0;
        overflow-x: hidden;
    }
    
    /* SCROLLBAR PERSONALIZADA */
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: var(--bg-dark); }
    ::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #555; }

    img, video, iframe { max-width: 100%; height: auto; display: block; border: none; }
    a { text-decoration: none; color: inherit; }

    header, nav, .navbar { background: transparent !important; box-shadow: none !important; border: none !important; position: relative; z-index: 1000; }

    /* HERO / BANNER */
    .hero-wrapper { 
        position: absolute; top: 0; left: 0; width: 100%; height: 600px; 
        z-index: 0; overflow: hidden;
    }
    .hero-wrapper::after { 
        content: ""; position: absolute; bottom: 0; left: 0; width: 100%; height: 100%; 
        background: linear-gradient(to bottom, rgba(5,5,7,0.1) 0%, rgba(5,5,7,0.6) 50%, var(--bg-dark) 99%);
        z-index: 1; pointer-events: none;
    }
    .hero-img { 
        width: 100%; height: 100%; object-fit: cover; display: block; 
        border: none; transform: scale(1.05);
    }

    /* LAYOUT */
    .main-container {
        position: relative; z-index: 10;
        width: 92%; max-width: 1150px;
        margin: 0 auto; margin-top: 220px;
        display: grid; grid-template-columns: 250px 1fr;
        gap: 40px; padding-bottom: 50px;
    }

    /* SIDEBAR */
    .sidebar { width: 100%; min-width: 0; }
    .game-cover {
        width: 100%; border-radius: 12px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.6);
        aspect-ratio: 3/4; object-fit: cover;
        border: 1px solid rgba(255,255,255,0.1);
    }
    
    .actions-box { margin-top: 20px; }
    .action-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .action-grid form { margin: 0; }
    
    .act-btn {
        width: 100%; aspect-ratio: 1/1; border: none; border-radius: 14px;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        cursor: pointer; color: #fff; padding: 5px; position: relative; overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid rgba(255,255,255,0.1);
        background: #2a2f39; color: #b9c0cc;
    }
    .act-btn:hover { transform: translateY(-4px); box-shadow: 0 8px 15px rgba(0,0,0,0.4); }
    .act-btn i { font-size: 1.4rem; margin-bottom: 6px; }
    .act-btn span { font-size: 0.65rem; text-transform: uppercase; font-weight: 700; }

    /* Cores Botões (ativo) */
    .act-btn.is-in.btn-later { background: linear-gradient(135deg, #00b4ff 0%, #0099cc 100%); color: #fff; }
    .act-btn.is-in.btn-later:hover { background: linear-gradient(135deg, #00d4ff 0%, #00a8dd 100%); }
    .act-btn.is-in.btn-played { background: linear-gradient(135deg, #1db954 0%, #0f7a36 100%); color: #fff; }
    .act-btn.is-in.btn-played:hover { background: linear-gradient(135deg, #1ed760 0%, #1aa34a 100%); }
    .act-btn.is-in.btn-fav { background: linear-gradient(135deg, #8a2be2 0%, #5d1ba9 100%); color: #fff; }
    .act-btn.is-in.btn-fav:hover { background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); }

    /* Chart */
    .rating-box { background: var(--surface-glass); border-radius: 12px; padding: 20px; margin-top: 20px; text-align: center; border: 1px solid var(--border-light); }
    .rating-big-number { font-size: 3rem; font-weight: 800; color: #fff; margin-bottom: 5px; }
    .chart-container { display: flex; align-items: flex-end; justify-content: space-between; height: 60px; gap: 4px; padding-top: 10px; border-bottom: 1px solid #333; }
    .chart-bar-wrapper { flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; }
    .chart-bar { width: 100%; background: #333; border-radius: 2px 2px 0 0; min-height: 2px; }
    .chart-bar.filled { background: linear-gradient(to top, var(--secondary-color), #a855f7); }

    /* REVIEWS INTERATIVIDADE */
    .review-card {
        background: var(--surface-glass);
        padding: 18px; border-radius: 10px; margin-bottom: 16px;
        border: 1px solid var(--border-light);
        transition: background 0.2s, border-color 0.2s;
    }
    .review-card:hover { background: rgba(20, 20, 23, 0.95); border-color: rgba(255,255,255,0.1); }
    .review-header { display: flex; gap: 12px; align-items: flex-start; margin-bottom: 12px; }
    .review-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .review-info { flex: 1; }
    .review-meta { display: flex; gap: 12px; align-items: center; margin-bottom: 4px; flex-wrap: wrap; }
    .review-username { font-weight: 700; color: #fff; font-size: 0.95rem; }
    .review-rating { color: #ff3366; font-weight: 600; }
    .review-date { font-size: 0.8rem; color: #888; }
    .review-text { margin: 12px 0; color: #bbb; line-height: 1.5; }
    .review-actions { display: flex; gap: 20px; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.05); }
    .review-action-btn {
        background: none; border: none; color: #888; cursor: pointer;
        display: flex; align-items: center; gap: 6px; font-size: 0.85rem;
        transition: color 0.2s; padding: 4px 8px; border-radius: 4px;
    }
    .review-action-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }
    .review-action-btn.liked { color: var(--secondary-color); }
    .review-comments-section { margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-light); }
    .comment-item { display: flex; gap: 10px; margin-bottom: 12px; padding: 10px; background: rgba(255,255,255,0.02); border-radius: 6px; }
    .comment-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    .comment-content { flex: 1; }
    .comment-username { font-weight: 600; color: #fff; font-size: 0.85rem; }
    .comment-text { color: #aaa; font-size: 0.85rem; margin-top: 4px; line-height: 1.4; }
    .comment-date { font-size: 0.75rem; color: #666; margin-top: 4px; }
    .comment-form { display: flex; gap: 10px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-light); }
    .comment-form textarea {
        flex: 1; background: rgba(0, 180, 255, 0.05); border: 1px solid var(--border-light);
        color: #fff; border-radius: 6px; padding: 8px 12px; font-size: 0.85rem;
        resize: none; font-family: inherit; min-height: 40px;
    }
    .comment-form textarea:focus { outline: none; border-color: var(--primary-color); background: rgba(0, 180, 255, 0.1); }
    .comment-form button {
        background: var(--primary-color); color: #fff; border: none; border-radius: 6px;
        padding: 8px 16px; font-weight: 600; cursor: pointer; font-size: 0.85rem; white-space: nowrap; transition: background 0.2s;
    }
    .comment-form button:hover { background: #00d4ff; }
    .expand-comments-btn { color: #888; background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; transition: color 0.2s; }
    .expand-comments-btn:hover { color: #fff; background: rgba(255,255,255,0.05); }

    /* DIREITA */
    .content-area { min-width: 0; }
    .game-title { font-size: 3rem; font-weight: 800; margin: 0 0 10px; line-height: 1.1; text-shadow: 0 4px 10px rgba(0,0,0,0.8); }
    .meta-tags { display: flex; flex-wrap: wrap; gap: 15px; color: #ccc; font-size: 0.9rem; margin-bottom: 30px; align-items: center; }
    .platform-badge { background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; border: 1px solid var(--border-light); }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; margin-bottom: 30px; }
    .stat-card { background: var(--surface-glass); padding: 15px 10px; border-radius: 8px; text-align: center; border: 1px solid var(--border-light); }
    .stat-value { font-size: 1.3rem; font-weight: bold; color: #fff; display: block; }
    .stat-label { font-size: 0.65rem; text-transform: uppercase; color: #888; font-weight: 700; margin-top: 5px; }
    .gallery-wrap { display: flex; gap: 15px; overflow-x: auto; padding-bottom: 15px; }
    .gallery-img { height: 130px; border-radius: 6px; cursor: pointer; transition: transform 0.2s; }
    .gallery-img:hover { transform: scale(1.05); }
    .btn-open-review {
        background: var(--surface-glass); border: 1px solid var(--border-light); color: #fff; width: 100%; padding: 20px;
        border-radius: 10px; cursor: pointer; transition: all 0.2s; text-align: center;
        display: flex; align-items: center; justify-content: center; gap: 10px; font-weight: 600;
    }
    .btn-open-review:hover { background: rgba(20, 20, 23, 0.95); color: var(--primary-color); border-color: var(--primary-color); }

    /* RESPONSIVIDADE */
    @media (max-width: 992px) {
        .main-container { grid-template-columns: 1fr; margin-top: 300px; gap: 30px; }
        .sidebar { display: grid; grid-template-columns: 180px 1fr; gap: 20px; align-items: end; }
        .game-cover { width: 100%; max-width: 180px; }
        .rating-box { display: none; }
        .hero-wrapper { height: 400px; }
        .game-title { font-size: 2.2rem; }
    }
    @media (max-width: 600px) {
        .sidebar { grid-template-columns: 1fr; justify-items: center; }
        .game-cover { max-width: 200px; }
        .main-container { margin-top: 250px; }
    }

    /* MODAL */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(5px);
        z-index: 9999; display: none; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.3s ease;
    }
    .modal-overlay.active { display: flex; opacity: 1; }
    .modal-box {
        background: var(--surface-glass); width: 95%; max-width: 800px; height: 450px;
        border-radius: 12px; box-shadow: 0 30px 60px rgba(0,0,0,0.8);
        border: 1px solid var(--border-light); display: flex; overflow: hidden;
        transform: translateY(20px); transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    .modal-overlay.active .modal-box { transform: translateY(0); }
    .modal-poster-area { width: 280px; flex-shrink: 0; position: relative; background: #000; }
    .modal-poster-img { width: 100%; height: 100%; object-fit: cover; opacity: 0.9; }
    .modal-form-area { flex: 1; padding: 25px; display: flex; flex-direction: column; }
    .modal-top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-heading h3 { margin: 0; font-size: 1.4rem; font-weight: 700; color: #fff; }
    .stars-container { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
    .stars-wrapper i { font-size: 1.5rem; color: #343740; cursor: pointer; margin-right: 2px; transition: 0.2s; }
    .stars-wrapper i.active, .stars-wrapper i.hovered { color: var(--primary-color); }
    .rating-result { font-weight: bold; margin-left: 10px; font-size: 1.2rem; }
    .review-text {
        flex: 1; background: rgba(0, 180, 255, 0.05); border: 1px solid var(--border-light); border-radius: 8px;
        padding: 15px; color: #ddd; resize: none; outline: none; margin-bottom: 20px; font-family: inherit;
    }
    .review-text:focus { border-color: var(--primary-color); background: rgba(0, 180, 255, 0.1); }
    .modal-actions { display: flex; justify-content: flex-end; gap: 15px; }
    .btn-cancel { background: none; border: none; color: #888; cursor: pointer; }
    .btn-publish { background: var(--primary-color); color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.2s; }
    .btn-publish:hover { background: #00d4ff; }
    @media (max-width: 768px) {
        .modal-box { flex-direction: column; height: auto; max-height: 90vh; overflow-y: auto; }
        .modal-poster-area { width: 100%; height: 150px; }
        .modal-poster-img { object-position: center 20%; }
    }

    /* --- TOAST NOTIFICATION PREMIUM --- */
    .toast-notification {
        position: fixed;
        top: 30px;
        right: 30px;
        background: rgba(18, 18, 24, 0.85); /* Fundo muito escuro e ligeiramente transparente */
        backdrop-filter: blur(20px) saturate(180%); /* Blur forte estilo Apple/Glassmorphism */
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border: 1px solid rgba(255, 255, 255, 0.08); /* Borda subtil */
        border-radius: 16px; /* Borda bem arredondada (Moderno) */
        padding: 18px 22px;
        display: flex;
        align-items: center;
        gap: 18px;
        min-width: 340px;
        max-width: 420px;
        box-shadow: 
            0 20px 40px rgba(0,0,0,0.6), 
            0 0 0 1px rgba(255,255,255,0.05); /* Sombra profunda + contorno */
        z-index: 10000;
        transform: translateX(150%) scale(0.95); /* Sai da tela */
        opacity: 0;
        transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); /* Efeito elástico "Spring" */
        overflow: hidden;
        cursor: pointer;
    }

    .toast-notification.active {
        transform: translateX(0) scale(1);
        opacity: 1;
    }

    /* Ícone Brilhante (Glow) */
    .toast-icon-box {
        width: 42px;
        height: 42px;
        border-radius: 12px; /* Squircle */
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    /* Cores e Gradientes Neon */
    .toast-success .toast-icon-box { 
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);
    }
    .toast-success .toast-progress { background: linear-gradient(90deg, #2ecc71, #27ae60); }

    .toast-warning .toast-icon-box { 
        background: linear-gradient(135deg, #f1c40f, #f39c12);
        box-shadow: 0 4px 15px rgba(241, 196, 15, 0.4);
    }
    .toast-warning .toast-progress { background: linear-gradient(90deg, #f1c40f, #f39c12); }

    .toast-error .toast-icon-box { 
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
    }
    .toast-error .toast-progress { background: linear-gradient(90deg, #e74c3c, #c0392b); }

    /* Conteúdo */
    .toast-content { flex: 1; display: flex; flex-direction: column; justify-content: center; }
    .toast-title { font-size: 1rem; font-weight: 700; color: #fff; margin-bottom: 3px; letter-spacing: 0.3px; }
    .toast-message { font-size: 0.85rem; color: #b0b0b0; font-weight: 400; line-height: 1.3; }

    /* Barra de Progresso */
    .toast-progress-bar {
        position: absolute;
        bottom: 0; left: 0;
        height: 3px;
        width: 100%;
        background: rgba(255,255,255,0.05);
    }
    .toast-progress {
        height: 100%;
        width: 100%;
        transform-origin: left;
        transform: scaleX(1);
    }
    
    .toast-notification.active .toast-progress {
        animation: progress-animation 3s linear forwards;
    }

    @keyframes progress-animation {
        from { transform: scaleX(1); }
        to { transform: scaleX(0); }
    }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div id="toastNotification" class="toast-notification" onclick="hideToast()">
    <div class="toast-icon-box" id="toastIcon">
        <i class="fa-solid fa-check"></i>
    </div>
    <div class="toast-content">
        <span class="toast-title" id="toastTitle">Sucesso</span>
        <span class="toast-message" id="toastMessage">Operação realizada.</span>
    </div>
    <div class="toast-progress-bar">
        <div class="toast-progress"></div>
    </div>
</div>

<?php if(isset($_SESSION['msg'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if($_SESSION['msg'] == 'added'): ?>
                showToast('Adicionado', 'O jogo foi adicionado à tua lista!', 'success');
            <?php else: ?>
                showToast('Atenção', 'Este jogo já está na lista.', 'warning');
            <?php endif; ?>
        });
    </script>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>

<div class="hero-wrapper">
    <img src="<?php echo $game_image; ?>" class="hero-img">
</div>

<div class="main-container">
    <aside class="sidebar">
        <img src="<?php echo $game_image; ?>" class="game-cover" alt="Capa">
        
        <div class="actions-box">
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="action-grid">
                    <?php
                        $actions = [
                            'Jogar mais tarde' => ['label' => 'Backlog', 'icon' => 'fa-clock', 'class' => 'btn-later'],
                            'Jogos jogados' => ['label' => 'Jogado', 'icon' => 'fa-check', 'class' => 'btn-played'],
                            'Favoritos' => ['label' => 'Like', 'icon' => 'fa-heart', 'class' => 'btn-fav'],
                        ];
                        foreach ($actions as $listName => $cfg):
                            $isInList = !empty($inLists[$listName]);
                            $listId = $listMap[$listName] ?? null;
                            $formAction = $isInList ? 'remove_from_list.php' : 'add_to_list.php';
                            $btnStateClass = $isInList ? 'is-in' : '';
                    ?>
                        <form action="<?php echo $formAction; ?>" method="POST" data-list-name="<?php echo htmlspecialchars($listName); ?>" data-list-id="<?php echo $listId; ?>" data-state="<?php echo $isInList ? 'in' : 'out'; ?>">
                            <input type="hidden" name="list_name" value="<?php echo htmlspecialchars($listName); ?>">
                            <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
                            <input type="hidden" name="game_name" value="<?php echo $game_name; ?>">
                            <input type="hidden" name="game_image" value="<?php echo $game_image; ?>">
                            <?php if ($listId): ?>
                                <input type="hidden" name="list_id" value="<?php echo $listId; ?>">
                            <?php endif; ?>
                            <button class="act-btn <?php echo $cfg['class']; ?> <?php echo $btnStateClass; ?>">
                                <i class="fa-solid <?php echo $cfg['icon']; ?>"></i> <span><?php echo $cfg['label']; ?></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <a href="login.php" style="display:block; text-align:center; background:#222; padding:15px; border-radius:8px; color:#fff; text-decoration:none;">Login para salvar</a>
            <?php endif; ?>
        </div>

        <div class="rating-box">
            <div class="rating-label">Avaliação Média</div>
            <div class="rating-big-number"><?php echo $avg_rating; ?></div>
            <div class="chart-container">
                <?php for($i=0; $i<=10; $i++): 
                    $count = $rating_counts[$i];
                    $pct = ($max_count > 0) ? ($count / $max_count) * 100 : 0;
                ?>
                    <div class="chart-bar-wrapper" title="<?php echo $count; ?>">
                        <div class="chart-bar <?php echo ($count > 0) ? 'filled' : ''; ?>" style="height: <?php echo $pct; ?>%;"></div>
                    </div>
                <?php endfor; ?>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:#666; margin-top:5px;"><span>0</span><span>10</span></div>
        </div>
    </aside>

    <main class="content-area">
        <h1 class="game-title"><?php echo $game_name; ?></h1>
        
        <div class="meta-tags">
            <span><?php echo $full_release_date; ?></span> • <span><?php echo $developers; ?></span>
            <div style="margin-left:auto; display:flex; gap:5px;">
                <?php foreach(array_slice($platforms, 0, 3) as $p): ?>
                    <span class="platform-badge"><?php echo $p['platform']['name']; ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-value" style="color: <?php echo ($avg_rating >= 7) ? '#00e054' : '#ffcc00'; ?>"><?php echo $avg_rating; ?></span>
                <span class="stat-label">Rating</span>
            </div>
            <div class="stat-card"><span class="stat-value"><?php echo $avg_data['total']; ?></span><span class="stat-label">Reviews</span></div>
            <div class="stat-card"><span class="stat-value"><?php echo $playtime; ?>h</span><span class="stat-label">Tempo</span></div>
            <div class="stat-card"><span class="stat-value"><?php echo $metacritic; ?></span><span class="stat-label">Meta</span></div>
        </div>

        <div style="line-height:1.6; color:#ccc; margin-bottom:40px;"><?php echo nl2br($description); ?></div>

        <?php if(!empty($screenshots)): ?>
            <h3 style="font-size:1.1rem; margin-bottom:15px; color:#fff;">Galeria</h3>
            <div class="gallery-wrap">
                <?php foreach($screenshots as $shot): ?>
                    <img src="<?php echo $shot['image']; ?>" class="gallery-img">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="margin-top:50px;">
            <h3 style="font-size:1.1rem; margin-bottom:20px; color:#fff;">Opiniões</h3>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="btn-open-review" onclick="openReviewModal()"><i class="fa-solid fa-pen-nib"></i> Escrever Review</div>
            <?php endif; ?>

            <div style="margin-top:30px;">
                <?php if($reviews->num_rows > 0): ?>
                    <?php while($rev = $reviews->fetch_assoc()): 
                        $stats = getReviewStats($rev['id']);
                        $userLiked = userLikedReview($rev['id'], $_SESSION['user_id'] ?? null);
                        $comments = getReviewComments($rev['id']);
                        $totalComments = $stats['comments'];
                    ?>
                        <div class="review-card">
                            <div class="review-header">
                                <img src="<?php echo $rev['avatar'] ?: 'https://via.placeholder.com/45'; ?>" class="review-avatar" alt="Avatar">
                                <div class="review-info">
                                    <div class="review-meta">
                                        <span class="review-username"><?php echo htmlspecialchars($rev['username']); ?></span>
                                        <span class="review-rating">★ <?php echo $rev['rating']; ?>/10</span>
                                        <span class="review-date"><?php echo date('d M, Y', strtotime($rev['created_at'])); ?></span>
                                    </div>
                                    <div class="review-text"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></div>
                                </div>
                            </div>

                            <div class="review-actions">
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <button class="review-action-btn like-btn <?php echo $userLiked ? 'liked' : ''; ?>" data-review-id="<?php echo $rev['id']; ?>" data-liked="<?php echo $userLiked ? '1' : '0'; ?>">
                                        <i class="fa-solid fa-heart"></i>
                                        <span class="like-count"><?php echo $stats['likes']; ?></span>
                                    </button>
                                <?php else: ?>
                                    <div class="review-action-btn">
                                        <i class="fa-solid fa-heart"></i>
                                        <span><?php echo $stats['likes']; ?></span>
                                    </div>
                                <?php endif; ?>

                                <button class="review-action-btn expand-comments-btn" data-review-id="<?php echo $rev['id']; ?>">
                                    <i class="fa-solid fa-comments"></i>
                                    <span><?php echo $totalComments; ?> comentário<?php echo $totalComments !== 1 ? 's' : ''; ?></span>
                                </button>
                            </div>

                            <?php if($totalComments > 0): ?>
                                <div class="review-comments-section" id="comments-<?php echo $rev['id']; ?>" style="display:none;">
                                    <?php if($comments->num_rows > 0): ?>
                                        <?php while($comment = $comments->fetch_assoc()): ?>
                                            <div class="comment-item">
                                                <img src="<?php echo $comment['avatar'] ?: 'https://via.placeholder.com/32'; ?>" class="comment-avatar" alt="Avatar">
                                                <div class="comment-content">
                                                    <div class="comment-username"><?php echo htmlspecialchars($comment['username']); ?></div>
                                                    <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                                                    <div class="comment-date"><?php echo date('d M, Y H:i', strtotime($comment['created_at'])); ?></div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php endif; ?>

                                    <?php if(isset($_SESSION['user_id'])): ?>
                                        <form class="comment-form" data-review-id="<?php echo $rev['id']; ?>">
                                            <textarea placeholder="Escreve um comentário..." maxlength="1000" required></textarea>
                                            <button type="submit">Comentar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php elseif(isset($_SESSION['user_id'])): ?>
                                <div class="review-comments-section" id="comments-<?php echo $rev['id']; ?>" style="display:none;">
                                    <form class="comment-form" data-review-id="<?php echo $rev['id']; ?>">
                                        <textarea placeholder="Escreve um comentário..." maxlength="1000" required></textarea>
                                        <button type="submit">Comentar</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:#666;">Sem opiniões ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php if(isset($_SESSION['user_id'])): ?>
<div class="modal-overlay" id="reviewModal">
    <div class="modal-box">
        <div class="modal-poster-area">
            <img src="<?php echo $game_image; ?>" class="modal-poster-img">
        </div>
        <div class="modal-form-area">
            <div class="modal-top-bar">
                <div class="modal-heading"><span>Reviewing</span><h3><?php echo $game_name; ?></h3></div>
                <button class="btn-cancel" onclick="closeReviewModal()"><i class="fa-solid fa-xmark" style="font-size:1.5rem;"></i></button>
            </div>
            <form action="add_review.php" method="POST" id="reviewForm" style="display:flex; flex-direction:column; height:100%;">
                <input type="hidden" name="game_id" value="<?php echo $game_id; ?>">
                <input type="hidden" name="game_name" value="<?php echo htmlspecialchars($game_name); ?>">
                <input type="hidden" name="game_image" value="<?php echo htmlspecialchars($game_image); ?>">
                <input type="hidden" name="rating" id="ratingInput" value="0">

                <div class="stars-container">
                    <span style="color:#888; font-weight:bold;">Avaliação:</span>
                    <div class="stars-wrapper" id="starContainer">
                        <?php for($k=1; $k<=10; $k++): ?><i class="fa-solid fa-star" data-value="<?php echo $k; ?>"></i><?php endfor; ?>
                    </div>
                    <span class="rating-result" id="ratingDisplay">0</span>
                </div>
                <textarea name="comment" class="review-text" placeholder="Escreve a tua opinião..."></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeReviewModal()">Cancelar</button>
                    <button type="submit" class="btn-publish">Publicar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    /* --- SCRIPT TOAST NOTIFICATION PREMIUM --- */
    let toastTimer;
    const toastEl = document.getElementById('toastNotification');
    const toastIcon = document.getElementById('toastIcon');
    const toastTitle = document.getElementById('toastTitle');
    const toastMessage = document.getElementById('toastMessage');

    function showToast(title, message, type = 'success') {
        if (!toastEl) return;
        
        // Reset e Definir Classes
        toastEl.className = 'toast-notification';
        toastEl.classList.add('toast-' + type);
        
        // Ícones Dinâmicos
        let iconClass = 'fa-check';
        if (type === 'warning') iconClass = 'fa-exclamation';
        if (type === 'error') iconClass = 'fa-xmark';
        
        toastIcon.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
        toastTitle.textContent = title;
        toastMessage.textContent = message;

        // Resetar Animação
        void toastEl.offsetWidth; 
        toastEl.classList.add('active');

        // Timer de Fecho
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            hideToast();
        }, 3000); 
    }

    function hideToast() {
        if(toastEl) toastEl.classList.remove('active');
    }

    /* Lógica Modal */
    const modal = document.getElementById('reviewModal');
    function openReviewModal() { modal.classList.add('active'); document.body.style.overflow='hidden'; }
    function closeReviewModal() { modal.classList.remove('active'); document.body.style.overflow='auto'; }
    modal.addEventListener('click', (e) => { if(e.target === modal) closeReviewModal(); });

    /* Lógica Estrelas */
    const stars = document.querySelectorAll('.stars-wrapper i');
    const input = document.getElementById('ratingInput');
    const display = document.getElementById('ratingDisplay');
    let current = 0;
    stars.forEach(star => {
        star.addEventListener('mouseover', () => { const v=parseInt(star.dataset.value); highlight(v); display.innerText=v; });
        star.addEventListener('mouseout', () => { highlight(current); display.innerText=current; });
        star.addEventListener('click', () => { current=parseInt(star.dataset.value); input.value=current; });
    });
    function highlight(v) { stars.forEach(s => { const val=parseInt(s.dataset.value); s.classList.toggle('active', val<=v); }); }

    /* Lógica AJAX Botões */
    document.querySelectorAll('.actions-box form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data && data.message) {
                    
                    if (data.status === 'added') {
                        showToast('Sucesso', 'Jogo adicionado à lista!', 'success');
                        form.dataset.state = 'in';
                        form.action = 'remove_from_list.php';
                        const btn = form.querySelector('.act-btn');
                        if (btn) btn.classList.add('is-in');
                    } else if (data.status === 'exists') {
                        showToast('Atenção', 'O jogo já está na lista.', 'warning');
                    } else if (data.status === 'removed') {
                        showToast('Removido', 'Jogo removido da lista.', 'warning');
                        form.dataset.state = 'out';
                        form.action = 'add_to_list.php';
                        const btn = form.querySelector('.act-btn');
                        if (btn) btn.classList.remove('is-in');
                    } else {
                        showToast('Info', data.message, 'error');
                    }
                }
            } catch (err) {
                showToast('Erro', 'Ocorreu um erro ao processar.', 'error');
            }
        });
    });

    /* LIKES & COMENTÁRIOS */
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const reviewId = this.dataset.reviewId;
            try {
                const res = await fetch('like_review.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `review_id=${reviewId}`
                });
                const data = await res.json();
                if (data.status === 'success') {
                    this.querySelector('.like-count').textContent = data.data.likes;
                    this.classList.toggle('liked', data.data.action === 'added');
                } else if (data.status === 'auth') {
                    window.location.href = 'login.php';
                }
            } catch (err) {
                showToast('Erro', 'Erro ao dar like.', 'error');
            }
        });
    });

    document.querySelectorAll('.expand-comments-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const reviewId = this.dataset.reviewId;
            const section = document.getElementById(`comments-${reviewId}`);
            if (section) section.style.display = section.style.display === 'none' ? 'block' : 'none';
        });
    });

    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const reviewId = this.dataset.reviewId;
            const textarea = this.querySelector('textarea');
            const comment = textarea.value.trim();

            if (!comment) { showToast('Aviso', 'Comentário não pode estar vazio.', 'warning'); return; }

            const formData = new FormData();
            formData.append('review_id', reviewId);
            formData.append('comment', comment);

            try {
                const res = await fetch('add_comment_review.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast('Sucesso', 'Comentário adicionado!', 'success');
                    textarea.value = '';
                    const section = document.getElementById(`comments-${reviewId}`);
                    const newComment = document.createElement('div');
                    newComment.className = 'comment-item';
                    newComment.innerHTML = `
                        <img src="${data.data.avatar || 'https://via.placeholder.com/32'}" class="comment-avatar" alt="Avatar">
                        <div class="comment-content">
                            <div class="comment-username">${escapeHtml(data.data.username)}</div>
                            <div class="comment-text">${escapeHtml(data.data.comment)}</div>
                            <div class="comment-date">${new Date().toLocaleDateString('pt-PT')} ${new Date().toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })}</div>
                        </div>
                    `;
                    const form = section.querySelector('.comment-form');
                    form.parentNode.insertBefore(newComment, form);
                } else if (data.status === 'auth') {
                    window.location.href = 'login.php';
                } else {
                    showToast('Erro', data.message || 'Erro ao adicionar comentário.', 'error');
                }
            } catch (err) {
                showToast('Erro', 'Erro ao adicionar comentário.', 'error');
            }
        });
    });

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>
<?php endif; ?>

</body>
</html>
<?php include 'includes/footer.php'; ?>