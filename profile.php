<?php
include 'includes/header.php';
require_once 'includes/achievements.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar dados do utilizador com prepared statement
$stmt = $conn->prepare("SELECT id, username, avatar, banner, email, created_at, biography, social_links FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$bio_error = '';
$bio_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bio_profile'])) {
    $bio = trim($_POST['biography'] ?? '');
    $bioLength = function_exists('mb_strlen') ? mb_strlen($bio) : strlen($bio);

    if ($bioLength > 500) {
        $bio_error = 'A biografia não pode ter mais de 500 caracteres.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET biography = ? WHERE id = ?");
        $stmt->bind_param("si", $bio, $user_id);

        if ($stmt->execute()) {
            $user['biography'] = $bio;
            $bio_success = 'Biografia atualizada com sucesso!';
        } else {
            $bio_error = 'Não foi possível atualizar a biografia.';
        }

        $stmt->close();
    }
}

$bio_form_open = !empty($bio_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review_profile'])) {
    $review_id = intval($_POST['review_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($review_id > 0 && $rating >= 0 && $rating <= 10 && $comment !== '') {
        $updateReviewStmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE id = ? AND user_id = ?");
        $updateReviewStmt->bind_param("isii", $rating, $comment, $review_id, $user_id);
        $updateReviewStmt->execute();
        $updateReviewStmt->close();
    }

    header("Location: profile.php?tab=reviews");
    exit;
}

// Estatísticas
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM lists WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$lists_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$followers_count = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$following_count = $stmt->get_result()->fetch_assoc()['count'];

// Buscar listas
$stmt = $conn->prepare("SELECT * FROM lists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$lists = $stmt->get_result();

// Buscar reviews
$stmt = $conn->prepare("SELECT * FROM reviews WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews = $stmt->get_result();

// Buscar distribuição de ratings
$stmt = $conn->prepare("SELECT rating, COUNT(*) as count FROM reviews WHERE user_id = ? GROUP BY rating");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ratings_result = $stmt->get_result();

$ratings_distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0];
$total_reviews = 0;
while ($row = $ratings_result->fetch_assoc()) {
    $ratings_distribution[$row['rating']] = (int)$row['count'];
    $total_reviews += (int)$row['count'];
}

// Calcular percentagens
$max_count = max($ratings_distribution);

// Buscar conquistas
$all_achievements_result = $conn->query("SELECT * FROM achievements ORDER BY points ASC");
$all_achievements = [];
while ($ach = $all_achievements_result->fetch_assoc()) {
    $all_achievements[] = $ach;
}

$unlocked_ids = [];
$stmt = $conn->prepare("SELECT achievement_id FROM user_achievements WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $unlocked_ids[] = $row['achievement_id'];
}

$achievement_progress = getAchievementProgress($user_id);
?>
<title>O Meu Perfil - GameList</title>
</head>
<body>
<style>
    :root {
        --bg-dark: #0b0c0f;
        --surface: #16171c;
        --surface-alt: #1d2028;
        --border: rgba(255, 255, 255, 0.08);
        --text-main: #ffffff;
        --text-muted: #9ca3af;
        --accent: #ff3366;
        --accent-hover: #ff4d7a;
        --radius: 14px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        background: linear-gradient(180deg, var(--bg-dark) 0%, #101218 100%);
        color: var(--text-main);
        font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
        padding-top: 80px;
        overflow-x: hidden;
    }

    .profile-shell {
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 20px 60px;
    }

    .hero-card {
        position: relative;
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--border);
        background: var(--surface);
    }

    .banner-media {
        height: 300px;
        position: relative;
        background: linear-gradient(135deg, #1f2330 0%, #151821 100%);
    }

    .banner-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .hero-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(11, 12, 15, 0.98) 6%, rgba(11, 12, 15, 0.32) 55%, rgba(11, 12, 15, 0.1) 100%);
    }

    .icon-edit {
        position: absolute;
        z-index: 5;
        border: none;
        border-radius: 999px;
        width: 38px;
        height: 38px;
        cursor: pointer;
        font-size: 17px;
        color: #fff;
        background: linear-gradient(135deg, var(--accent), #cc2952);
        box-shadow: 0 8px 20px rgba(255, 51, 102, 0.28);
        transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
        opacity: 0;
        pointer-events: none;
    }

    .icon-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(255, 51, 102, 0.38);
    }

    .banner-edit { top: 14px; right: 14px; }

    .banner-media:hover + .banner-edit,
    .banner-edit:hover,
    .banner-edit:focus-visible {
        opacity: 1;
        pointer-events: auto;
    }

    .hero-content {
        position: absolute;
        left: 24px;
        right: 24px;
        bottom: 24px;
        z-index: 4;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 22px;
        flex-wrap: wrap;
    }

    .hero-left {
        display: flex;
        align-items: flex-end;
        gap: 16px;
    }

    .avatar-wrap {
        width: 136px;
        height: 136px;
        border-radius: 12px;
        border: 2px solid rgba(255,255,255,0.12);
        background: #12141a;
        overflow: hidden;
        position: relative;
        flex-shrink: 0;
        box-shadow: 0 14px 32px rgba(0,0,0,.45);
    }

    .avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .avatar-edit {
        right: 7px;
        bottom: 7px;
        top: auto;
        width: 32px;
        height: 32px;
        font-size: 14px;
    }

    .avatar-wrap:hover .avatar-edit,
    .avatar-wrap:focus-within .avatar-edit,
    .avatar-edit:hover,
    .avatar-edit:focus-visible {
        opacity: 1;
        pointer-events: auto;
    }

    @media (hover: none) {
        .icon-edit {
            opacity: 1;
            pointer-events: auto;
        }
    }

    .hero-meta h1 {
        font-size: clamp(1.75rem, 2.7vw, 2.5rem);
        line-height: 1.1;
        margin-bottom: 8px;
    }

    .hero-meta .email {
        color: var(--text-muted);
        font-size: 0.92rem;
        margin-bottom: 12px;
    }

    .social-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .social-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        appearance: none;
        -webkit-appearance: none;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid var(--border);
        color: #d4d8df;
        border-radius: 999px;
        width: 38px;
        height: 38px;
        padding: 0;
        font-size: 0.8rem;
        text-decoration: none;
        transition: .2s ease;
    }

    .social-chip i,
    .social-chip .social-icon {
        font-size: 1rem;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .social-chip .x-icon svg {
        width: 14px;
        height: 14px;
        fill: currentColor;
    }

    .social-chip:hover {
        border-color: rgba(255, 51, 102, 0.45);
        color: #fff;
        background: rgba(255, 51, 102, 0.14);
    }

    .hero-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .hero-action-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--border);
        color: #d4d8df;
        text-decoration: none;
        background: rgba(255, 255, 255, 0.05);
        transition: .2s ease;
        font-size: 1rem;
    }

    .hero-action-icon:hover {
        color: #fff;
        border-color: rgba(255, 51, 102, 0.45);
        background: rgba(255, 51, 102, 0.16);
        transform: translateY(-1px);
    }

    .btn {
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 10px 16px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        cursor: pointer;
        transition: .2s ease;
    }

    .btn-primary {
        color: #fff;
        border-color: transparent;
        background: linear-gradient(135deg, var(--accent), #cc2952);
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--accent-hover), #e62e5c);
    }

    .btn-muted {
        background: rgba(255, 255, 255, 0.04);
        color: #c8cdd6;
    }

    .btn-muted:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }

    .profile-layout {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 18px;
        margin-top: 18px;
    }

    .left-column,
    .right-column {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .panel {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 16px;
    }

    .panel-title {
        font-size: 0.82rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 12px;
    }

    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .panel-header .panel-title {
        margin-bottom: 0;
    }

    .panel-edit-icon {
        width: 32px;
        height: 32px;
        border-radius: 9px;
        border: 1px solid var(--border);
        background: rgba(255, 255, 255, 0.04);
        color: #d4d8df;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: .2s ease;
        opacity: 0;
        pointer-events: none;
    }

    .bio-panel:hover .panel-edit-icon,
    .bio-panel:focus-within .panel-edit-icon,
    .panel-edit-icon[aria-expanded="true"] {
        opacity: 1;
        pointer-events: auto;
    }

    .panel-edit-icon:hover,
    .panel-edit-icon[aria-expanded="true"] {
        color: #fff;
        border-color: rgba(255, 51, 102, 0.55);
        background: rgba(255, 51, 102, 0.16);
    }

    @media (hover: none) {
        .panel-edit-icon {
            opacity: 1;
            pointer-events: auto;
        }
    }

    .bio-text {
        color: #d2d6de;
        line-height: 1.6;
        font-size: 0.94rem;
        margin-bottom: 12px;
    }

    .bio-edit-form {
        display: none;
        margin-top: 8px;
    }

    .bio-edit-form.open {
        display: block;
    }

    .bio-edit-form textarea {
        width: 100%;
        min-height: 120px;
        resize: vertical;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: #0f1116;
        color: #fff;
        padding: 10px 12px;
        font-family: inherit;
        font-size: .9rem;
    }

    .bio-edit-form textarea:focus {
        outline: none;
        border-color: rgba(255, 51, 102, 0.6);
        box-shadow: 0 0 0 2px rgba(255, 51, 102, 0.18);
    }

    .bio-form-footer {
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .bio-count {
        font-size: .8rem;
        color: var(--text-muted);
    }

    .bio-save-btn {
        border: none;
        border-radius: 8px;
        padding: 8px 12px;
        color: #fff;
        font-size: .82rem;
        font-weight: 600;
        background: linear-gradient(135deg, var(--accent), #cc2952);
        cursor: pointer;
    }

    .bio-save-btn:hover {
        background: linear-gradient(135deg, var(--accent-hover), #e62e5c);
    }

    .bio-feedback {
        font-size: .82rem;
        margin-bottom: 8px;
        transition: opacity .3s ease;
    }

    .bio-feedback.success {
        color: #4ade80;
    }

    .bio-feedback.error {
        color: #fb7185;
    }

    .bio-feedback.fade-out {
        opacity: 0;
    }

    .ratings-bars {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        height: 140px;
    }

    .rating-row {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        height: 100%;
    }

    .rating-count { font-size: 11px; color: #fff; min-height: 15px; }

    .rating-bar-wrap {
        width: 100%;
        flex: 1;
        border-radius: 6px;
        background: #0f1116;
        border: 1px solid var(--border);
        display: flex;
        align-items: flex-end;
        overflow: hidden;
    }

    .rating-bar {
        width: 100%;
        background: linear-gradient(180deg, #ff5e88 0%, #cc2952 100%);
        border-radius: 6px;
    }

    .rating-label { font-size: 10px; color: var(--text-muted); }

    .achievement-progress {
        font-size: 0.82rem;
        color: var(--text-muted);
        margin-bottom: 10px;
    }

    .achievement-progress-bar {
        width: 100%;
        height: 6px;
        border-radius: 99px;
        background: #0f1116;
        border: 1px solid var(--border);
        overflow: hidden;
        margin-top: 6px;
    }

    .achievement-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--accent), #ff6b93);
    }

    .achievements-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .achievement-item {
        position: relative;
        text-align: center;
        padding: 10px;
        border-radius: 10px;
        border: 1px solid var(--border);
        background: #111319;
    }

    .achievement-item.unlocked {
        border-color: rgba(255, 51, 102, 0.4);
        background: rgba(255, 51, 102, 0.08);
    }

    .achievement-item.locked {
        opacity: .45;
        filter: grayscale(100%);
    }

    .achievement-icon {
        width: 44px;
        height: 44px;
        object-fit: contain;
        margin-bottom: 8px;
    }

    .achievement-name {
        font-size: 10px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 2px;
    }

    .achievement-points {
        font-size: 10px;
        color: #f7c948;
    }

    .achievement-tooltip {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%);
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 11px;
        white-space: nowrap;
        border: 1px solid var(--border);
        background: #0f1116;
        color: #fff;
        z-index: 10;
    }

    .achievement-item:hover .achievement-tooltip { display: block; }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }

    .stat-card {
        background: var(--surface-alt);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 15px;
        text-align: center;
    }

    .stat-value {
        display: block;
        font-size: 1.9rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }

    .stat-label {
        margin-top: 8px;
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--text-muted);
    }

    .tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }

    .tab-btn {
        border: 1px solid var(--border);
        background: rgba(255,255,255,0.02);
        color: #c9ced6;
        border-radius: 999px;
        padding: 8px 14px;
        cursor: pointer;
        font-size: .85rem;
        font-weight: 600;
    }

    .tab-btn.active {
        color: #fff;
        border-color: rgba(255, 51, 102, 0.55);
        background: rgba(255, 51, 102, 0.16);
    }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

    .list-items {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(154px, 1fr));
        gap: 12px;
        min-height: 250px;
    }

    .list-items .empty-state:only-child {
        grid-column: 1 / -1;
        justify-self: center;
        align-self: center;
        width: min(420px, 100%);
    }

    .game-card {
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 10px;
        overflow: hidden;
        background: #222;
        aspect-ratio: 2 / 3;
        position: relative;
        transition: transform .3s cubic-bezier(.25,.8,.25,1), box-shadow .3s ease, border-color .3s ease;
    }

    .game-card a {
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
    }

    .list-item-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: filter .3s ease, transform .3s ease;
    }

    .game-card p {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 88%;
        font-size: 1rem;
        font-weight: 700;
        text-align: center;
        color: #fff;
        text-shadow: 0 2px 10px rgba(0,0,0,.9);
        opacity: 0;
        z-index: 3;
        pointer-events: none;
        transition: opacity .3s ease;
    }

    .card-actions {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 4;
        opacity: 0;
        transform: translateY(-6px);
        transition: opacity .25s ease, transform .25s ease;
    }

    .game-card:hover {
        transform: scale(1.03);
        border-color: rgba(255,255,255,0.3);
        box-shadow: 0 10px 24px rgba(0,0,0,.45);
        z-index: 5;
    }

    .game-card:hover .list-item-img {
        filter: brightness(0.26);
        transform: scale(1.02);
    }

    .game-card:hover p {
        opacity: 1;
    }

    .game-card:hover .card-actions {
        opacity: 1;
        transform: translateY(0);
    }

    .card-actions .btn-danger {
        width: 34px;
        height: 34px;
        padding: 0;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.28);
        background: rgba(9, 11, 15, 0.78);
        backdrop-filter: blur(3px);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        line-height: 1;
    }

    .card-actions .btn-danger:hover {
        border-color: rgba(255, 51, 102, 0.65);
        background: rgba(255, 51, 102, 0.2);
    }

    @media (hover: none) {
        .card-actions {
            opacity: 1;
            transform: none;
        }
    }

    .btn-danger {
        width: 100%;
        border: 1px solid rgba(255,255,255,0.2);
        background: rgba(10, 12, 16, 0.72);
        backdrop-filter: blur(3px);
        color: #eef2f8;
        border-radius: 8px;
        padding: 8px;
        font-size: .8rem;
        font-weight: 600;
        cursor: pointer;
        transition: .2s ease;
    }

    .btn-danger:hover {
        color: #fff;
        border-color: rgba(255, 51, 102, 0.5);
        background: rgba(255, 51, 102, 0.15);
    }

    .review-card {
        border: 1px solid var(--border);
        border-radius: 14px;
        background: var(--surface-alt);
        padding: 16px;
        margin-bottom: 14px;
        transition: border-color .2s ease, transform .2s ease, box-shadow .2s ease;
    }

    .review-layout {
        display: grid;
        grid-template-columns: 84px 1fr;
        gap: 14px;
        align-items: start;
    }

    .review-cover {
        width: 84px;
        height: 112px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.12);
        background: #10131a;
        flex-shrink: 0;
    }

    .review-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .review-body {
        min-width: 0;
    }

    .review-card:hover {
        border-color: rgba(255, 51, 102, 0.35);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,.28);
    }

    .review-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
    }

    .review-title {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
    }

    .review-score {
        border: 1px solid rgba(255, 209, 102, 0.35);
        background: rgba(255, 209, 102, 0.12);
        color: #ffd166;
        border-radius: 999px;
        padding: 5px 10px;
        font-size: .78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .review-comment {
        color: #d2d6de;
        line-height: 1.6;
        margin-bottom: 10px;
        font-size: .92rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 10px;
        padding: 10px 12px;
    }

    .review-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 8px;
        padding-top: 10px;
        border-top: 1px solid rgba(255,255,255,0.06);
    }

    .review-date {
        color: var(--text-muted);
        font-size: .78rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .review-actions {
        display: flex;
        gap: 8px;
    }

    .review-action-btn {
        text-decoration: none;
        color: #dce1ea;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 8px;
        padding: 7px 10px;
        font-size: .8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: .2s ease;
    }

    .review-action-btn:hover {
        color: #fff;
        border-color: rgba(255, 51, 102, 0.45);
        background: rgba(255, 51, 102, 0.14);
    }

    .review-action-btn.danger:hover {
        border-color: rgba(255, 107, 122, 0.55);
        background: rgba(255, 107, 122, 0.16);
    }

    @media (max-width: 620px) {
        .review-layout {
            grid-template-columns: 1fr;
        }

        .review-cover {
            width: 100%;
            height: 170px;
        }
    }

    .empty-state {
        width: 100%;
        text-align: center;
        color: var(--text-muted);
        border: 1px dashed var(--border);
        border-radius: 10px;
        padding: 28px 16px;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(3,4,6,.78);
        backdrop-filter: blur(4px);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        padding: 16px;
    }

    .profile-modal {
        width: min(560px, 100%);
        background: #12141a;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 18px;
        position: relative;
    }

    .close-btn {
        position: absolute;
        top: 8px;
        right: 10px;
        border: none;
        background: transparent;
        color: #9aa1ad;
        font-size: 28px;
        cursor: pointer;
    }

    .profile-modal h3 {
        margin-bottom: 14px;
        font-size: 1.15rem;
    }

    .profile-modal label {
        display: block;
        margin-bottom: 6px;
        margin-top: 10px;
        color: #d8dde6;
        font-size: .85rem;
        font-weight: 600;
    }

    .profile-modal input[type="file"] {
        width: 100%;
        background: #0d0f14;
        border: 1px solid var(--border);
        border-radius: 10px;
        color: #cdd3dd;
        padding: 10px;
    }

    .profile-modal input[type="file"]::file-selector-button {
        border: none;
        border-radius: 8px;
        padding: 7px 10px;
        margin-right: 10px;
        color: #fff;
        font-weight: 600;
        background: linear-gradient(135deg, var(--accent), #cc2952);
        cursor: pointer;
    }

    .profile-modal button[type="submit"] {
        width: 100%;
        margin-top: 14px;
        border: none;
        border-radius: 10px;
        padding: 11px;
        color: #fff;
        font-weight: 700;
        cursor: pointer;
        background: linear-gradient(135deg, var(--accent), #cc2952);
    }

    .review-edit-modal {
        background: #12141a;
        width: min(860px, 96%);
        height: 460px;
        border-radius: 14px;
        border: 1px solid var(--border);
        box-shadow: 0 30px 60px rgba(0,0,0,.7);
        display: flex;
        overflow: hidden;
        position: relative;
    }

    .review-edit-poster {
        width: 280px;
        flex-shrink: 0;
        background: #000;
    }

    .review-edit-poster img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: .9;
    }

    .review-edit-form-area {
        flex: 1;
        padding: 24px;
        display: flex;
        flex-direction: column;
    }

    .review-edit-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
        gap: 12px;
    }

    .review-edit-heading span {
        color: #999;
        font-size: .9rem;
        font-weight: 700;
        display: block;
        margin-bottom: 4px;
    }

    .review-edit-heading h3 {
        margin: 0;
        font-size: 2rem;
        line-height: 1.1;
        font-weight: 800;
        color: #fff;
    }

    .review-edit-stars-label {
        color: #8f949c;
        font-size: 1rem;
        font-weight: 700;
        margin-right: 6px;
    }

    .review-stars-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 16px;
    }

    .review-stars {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .review-stars i {
        font-size: 1.45rem;
        color: #343740;
        cursor: pointer;
        transition: color .15s ease, transform .15s ease;
    }

    .review-stars i.active,
    .review-stars i.hovered {
        color: var(--accent);
    }

    .review-stars i:hover {
        transform: translateY(-1px);
    }

    .review-stars-value {
        margin-left: 8px;
        min-width: 30px;
        font-size: 1.65rem;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }

    .review-edit-text {
        flex: 1;
        width: 100%;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,.08);
        background: rgba(255, 51, 102, 0.05);
        color: #fff;
        padding: 14px;
        font-family: inherit;
        font-size: 1rem;
        resize: none;
        margin-bottom: 18px;
    }

    .review-edit-text:focus {
        outline: none;
        border-color: rgba(255, 51, 102, 0.6);
        box-shadow: 0 0 0 2px rgba(255, 51, 102, 0.18);
        background: rgba(255, 51, 102, 0.1);
    }

    .review-edit-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        align-items: center;
    }

    .review-edit-cancel {
        border: none;
        background: none;
        color: #8f949c;
        font-size: 1rem;
        cursor: pointer;
        padding: 8px 4px;
    }

    .review-edit-save {
        border: none;
        border-radius: 10px;
        padding: 11px 26px;
        color: #fff;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        background: linear-gradient(135deg, var(--accent), #cc2952);
    }

    .review-edit-save:hover {
        background: linear-gradient(135deg, var(--accent-hover), #e62e5c);
    }

    @media (max-width: 860px) {
        .review-edit-modal {
            flex-direction: column;
            height: auto;
            max-height: 92vh;
            overflow-y: auto;
        }

        .review-edit-poster {
            width: 100%;
            height: 170px;
        }

        .review-edit-heading h3 {
            font-size: 1.45rem;
        }
    }

    .preview-box {
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #0d0f14;
        overflow: hidden;
        position: relative;
        margin-bottom: 12px;
    }

    .preview-banner {
        width: 100%;
        height: 130px;
        object-fit: cover;
        display: block;
    }

    .preview-avatar {
        width: 72px;
        height: 72px;
        border-radius: 10px;
        object-fit: cover;
        position: absolute;
        left: 14px;
        bottom: 10px;
        border: 2px solid #0d0f14;
    }

    .preview-label {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0,0,0,.55);
        color: #fff;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 10px;
        letter-spacing: .08em;
    }

    @media (max-width: 992px) {
        .profile-layout { grid-template-columns: 1fr; }
        .hero-content { left: 16px; right: 16px; bottom: 16px; }
    }

    @media (max-width: 680px) {
        body { padding-top: 70px; }
        .banner-media { height: 270px; }
        .hero-left { align-items: center; }
        .avatar-wrap { width: 104px; height: 104px; }
        .hero-meta h1 { font-size: 1.6rem; }
        .stats-grid { grid-template-columns: 1fr; }
        .achievements-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<?php $socials = json_decode($user['social_links'] ?? '{}', true) ?? []; ?>

<div class="profile-shell">
    <section class="hero-card">
        <div class="banner-media">
            <?php if (!empty($user['banner'])): ?>
                <img src="<?php echo htmlspecialchars($user['banner']); ?>" alt="Banner do perfil">
            <?php endif; ?>
            <div class="hero-overlay"></div>
        </div>

        <button class="icon-edit banner-edit" onclick="openModal()" aria-label="Editar imagens">✎</button>

        <div class="hero-content">
            <div class="hero-left">
                <div class="avatar-wrap">
                    <img src="<?php echo htmlspecialchars($user['avatar'] ?: 'https://via.placeholder.com/180?text=User'); ?>" alt="Avatar">
                    <button class="icon-edit avatar-edit" onclick="openModal()" aria-label="Editar avatar">✎</button>
                </div>

                <div class="hero-meta">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p class="email"><?php echo htmlspecialchars($user['email']); ?></p>

                    <?php if (!empty($socials['twitter']) || !empty($socials['instagram']) || !empty($socials['discord'])): ?>
                        <div class="social-row">
                            <?php if (!empty($socials['twitter'])): ?>
                                <a class="social-chip" href="https://twitter.com/<?php echo htmlspecialchars($socials['twitter']); ?>" target="_blank" aria-label="X" title="@<?php echo htmlspecialchars($socials['twitter']); ?>"><span class="social-icon x-icon" aria-hidden="true"><svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M18.901 1.153h3.68l-8.042 9.19 9.461 12.504h-7.406l-5.802-7.584-6.638 7.584H.47l8.602-9.83L0 1.154h7.594l5.244 6.932L18.901 1.153zm-1.292 19.489h2.039L6.486 3.24H4.298L17.609 20.642z"/></svg></span></a>
                            <?php endif; ?>
                            <?php if (!empty($socials['instagram'])): ?>
                                <a class="social-chip" href="https://instagram.com/<?php echo htmlspecialchars($socials['instagram']); ?>" target="_blank" aria-label="Instagram" title="@<?php echo htmlspecialchars($socials['instagram']); ?>"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a>
                            <?php endif; ?>
                            <?php if (!empty($socials['discord'])): ?>
                                <button type="button" class="social-chip social-discord-btn" data-discord="<?php echo htmlspecialchars($socials['discord']); ?>" aria-label="Copiar Discord" title="Copiar: <?php echo htmlspecialchars($socials['discord']); ?>"><i class="fa-brands fa-discord" aria-hidden="true"></i></button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hero-actions">
                <a href="settings.php" class="hero-action-icon" aria-label="Definições" title="Definições">
                    <i class="fa-solid fa-gear" aria-hidden="true"></i>
                </a>
                <a href="logout.php" class="hero-action-icon" aria-label="Terminar sessão" title="Terminar sessão">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

    <div class="profile-layout">
        <aside class="left-column">
            <div class="panel bio-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Biografia</h3>
                    <button type="button" class="panel-edit-icon" id="toggleBioEdit" aria-label="Editar biografia" aria-expanded="<?php echo $bio_form_open ? 'true' : 'false'; ?>">
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    </button>
                </div>

                <?php if (!empty($bio_success)): ?>
                    <div class="bio-feedback success" id="bioSuccessMessage"><?php echo htmlspecialchars($bio_success); ?></div>
                <?php endif; ?>
                <?php if (!empty($bio_error)): ?>
                    <div class="bio-feedback error"><?php echo htmlspecialchars($bio_error); ?></div>
                <?php endif; ?>

                <div class="bio-text"><?php echo !empty($user['biography']) ? nl2br(htmlspecialchars($user['biography'])) : 'Ainda não tens biografia. Escreve algo sobre ti.'; ?></div>

                <form method="POST" class="bio-edit-form <?php echo $bio_form_open ? 'open' : ''; ?>" id="bioEditForm">
                    <input type="hidden" name="update_bio_profile" value="1">
                    <textarea id="bioTextareaProfile" name="biography" maxlength="500" placeholder="Conta um pouco sobre ti, os teus interesses em jogos, etc..."><?php echo htmlspecialchars($user['biography'] ?? ''); ?></textarea>
                    <div class="bio-form-footer">
                        <span class="bio-count" id="bioCountProfile">0/500</span>
                        <button type="submit" class="bio-save-btn">Guardar biografia</button>
                    </div>
                </form>
            </div>

            <?php if ($total_reviews > 0): ?>
                <div class="panel">
                    <h3 class="panel-title">Distribuição de notas</h3>
                    <div class="ratings-bars">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <div class="rating-row">
                                <div class="rating-count"><?php echo $ratings_distribution[$i] > 0 ? $ratings_distribution[$i] : ''; ?></div>
                                <div class="rating-bar-wrap">
                                    <div class="rating-bar" style="height: <?php echo $max_count > 0 ? ($ratings_distribution[$i] / $max_count * 100) : 0; ?>%;"></div>
                                </div>
                                <div class="rating-label"><?php echo $i; ?>★</div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel">
                <h3 class="panel-title">Conquistas</h3>
                <div class="achievement-progress">
                    <?php echo $achievement_progress['unlocked']; ?> / <?php echo $achievement_progress['total']; ?> desbloqueadas (<?php echo $achievement_progress['percentage']; ?>%)
                    <div class="achievement-progress-bar">
                        <div class="achievement-progress-fill" style="width: <?php echo $achievement_progress['percentage']; ?>%;"></div>
                    </div>
                </div>

                <div class="achievements-grid">
                    <?php foreach ($all_achievements as $achievement): ?>
                        <?php $is_unlocked = in_array($achievement['id'], $unlocked_ids); ?>
                        <div class="achievement-item <?php echo $is_unlocked ? 'unlocked' : 'locked'; ?>">
                            <div class="achievement-tooltip"><?php echo htmlspecialchars($achievement['description']); ?></div>
                            <img src="<?php echo htmlspecialchars($achievement['icon']); ?>" alt="<?php echo htmlspecialchars($achievement['name']); ?>" class="achievement-icon">
                            <div class="achievement-name"><?php echo htmlspecialchars($achievement['name']); ?></div>
                            <div class="achievement-points"><?php echo $achievement['points']; ?> pts</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <main class="right-column">
            <section class="panel">
                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-value"><?php echo $followers_count; ?></span>
                        <span class="stat-label">Seguidores</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value"><?php echo $following_count; ?></span>
                        <span class="stat-label">A seguir</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-value"><?php echo $reviews_count; ?></span>
                        <span class="stat-label">Reviews</span>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="tabs">
                    <?php
                    $lists->data_seek(0);
                    $list_index = 0;
                    while ($list = $lists->fetch_assoc()):
                    ?>
                        <button class="tab-btn <?php echo $list_index === 0 ? 'active' : ''; ?>" data-tab="list-<?php echo $list['id']; ?>">
                            <?php echo htmlspecialchars($list['name']); ?>
                        </button>
                    <?php
                        $list_index++;
                    endwhile;
                    ?>
                    <button class="tab-btn <?php echo $lists->num_rows === 0 ? 'active' : ''; ?>" data-tab="reviews">Reviews</button>
                </div>

                <?php
                $lists->data_seek(0);
                $list_index = 0;
                while ($list = $lists->fetch_assoc()):
                ?>
                    <div id="list-<?php echo $list['id']; ?>Tab" class="tab-content <?php echo $list_index === 0 ? 'active' : ''; ?>">
                        <div class="list-items">
                            <?php
                            $list_id = $list['id'];
                            $stmt = $conn->prepare("SELECT * FROM list_items WHERE list_id = ? ORDER BY added_at DESC");
                            $stmt->bind_param("i", $list_id);
                            $stmt->execute();
                            $items = $stmt->get_result();

                            if ($items->num_rows > 0):
                                while ($item = $items->fetch_assoc()):
                            ?>
                                <div class="game-card">
                                    <a href="game.php?id=<?php echo $item['game_id']; ?>&name=<?php echo urlencode($item['game_name']); ?>&image=<?php echo urlencode($item['game_image']); ?>">
                                        <img src="<?php echo htmlspecialchars($item['game_image'] ?: 'https://via.placeholder.com/180x240?text=Game'); ?>" alt="<?php echo htmlspecialchars($item['game_name']); ?>" class="list-item-img">
                                        <p><?php echo htmlspecialchars($item['game_name']); ?></p>
                                    </a>
                                    <div class="card-actions">
                                        <form method="POST" action="remove_from_list.php">
                                            <input type="hidden" name="list_id" value="<?php echo $list_id; ?>">
                                            <input type="hidden" name="game_id" value="<?php echo $item['game_id']; ?>">
                                            <button type="submit" class="btn-danger" aria-label="Remover da lista" title="Remover da lista">
                                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <div class="empty-state">
                                    <p>Esta lista está vazia.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                    $list_index++;
                endwhile;

                if ($lists->num_rows === 0):
                ?>
                    <div id="list-emptyTab" class="tab-content active">
                        <div class="empty-state">
                            <p>Ainda não criaste nenhuma lista.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="reviewsTab" class="tab-content <?php echo $lists->num_rows === 0 ? 'active' : ''; ?>">
                    <?php
                    $reviews->data_seek(0);
                    if ($reviews->num_rows > 0):
                    ?>
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <article class="review-card">
                                <div class="review-layout">
                                    <a class="review-cover" href="game.php?id=<?php echo $review['game_id']; ?>" title="Ver jogo">
                                        <img src="<?php echo htmlspecialchars($review['game_image'] ?: 'https://via.placeholder.com/180x240?text=Game'); ?>" alt="Capa de <?php echo htmlspecialchars($review['game_name'] ?? 'Jogo'); ?>">
                                    </a>

                                    <div class="review-body">
                                        <div class="review-head">
                                            <h3 class="review-title"><?php echo htmlspecialchars($review['game_name'] ?? 'Jogo #' . $review['game_id']); ?></h3>
                                            <span class="review-score">★ <?php echo (int)$review['rating']; ?>/10</span>
                                        </div>
                                        <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                        <div class="review-footer">
                                            <span class="review-date"><i class="fa-regular fa-clock" aria-hidden="true"></i><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                                            <div class="review-actions">
                                                <button type="button" class="review-action-btn open-review-edit" data-review-id="<?php echo (int)$review['id']; ?>" data-rating="<?php echo (int)$review['rating']; ?>" data-comment="<?php echo htmlspecialchars($review['comment'], ENT_QUOTES); ?>" data-game-name="<?php echo htmlspecialchars($review['game_name'] ?? ('Jogo #' . $review['game_id']), ENT_QUOTES); ?>" data-game-image="<?php echo htmlspecialchars($review['game_image'] ?: 'https://via.placeholder.com/280x420?text=Game', ENT_QUOTES); ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i><span>Editar</span></button>
                                                <form method="POST" action="remove_review.php">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <button type="submit" class="review-action-btn danger"><i class="fa-solid fa-trash" aria-hidden="true"></i><span>Apagar</span></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Ainda não escreveste nenhuma review.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<div class="modal-overlay" id="reviewEditOverlay">
    <div class="review-edit-modal" role="dialog" aria-modal="true" aria-label="Editar review">
        <div class="review-edit-poster">
            <img id="reviewEditPoster" src="https://via.placeholder.com/280x420?text=Game" alt="Capa do jogo">
        </div>

        <div class="review-edit-form-area">
            <div class="review-edit-top-bar">
                <div class="review-edit-heading">
                    <span>Reviewing</span>
                    <h3 id="reviewEditTitle">Editar review</h3>
                </div>
                <button class="close-btn" type="button" onclick="closeReviewEditModal()">×</button>
            </div>

            <form method="POST" id="reviewEditForm" style="display:flex; flex-direction:column; height:100%;">
                <input type="hidden" name="update_review_profile" value="1">
                <input type="hidden" name="review_id" id="reviewEditId" value="">

                <div class="review-stars-row">
                    <span class="review-edit-stars-label">Avaliação:</span>
                    <input type="hidden" name="rating" id="reviewEditRating" value="0">
                    <div class="review-stars" id="reviewEditStars">
                        <?php for ($k = 1; $k <= 10; $k++): ?>
                            <i class="fa-solid fa-star" data-value="<?php echo $k; ?>" aria-hidden="true"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="review-stars-value" id="reviewEditRatingValue">0</span>
                </div>

                <textarea id="reviewEditComment" class="review-edit-text" name="comment" placeholder="Escreve a tua opinião..." required></textarea>

                <div class="review-edit-actions">
                    <button type="button" class="review-edit-cancel" onclick="closeReviewEditModal()">Cancelar</button>
                    <button type="submit" class="review-edit-save">Publicar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay">
    <div class="profile-modal" role="dialog" aria-modal="true" aria-label="Personalizar perfil">
        <button class="close-btn" onclick="closeModal()">×</button>
        <h3>Personalizar perfil</h3>

        <div class="preview-box">
            <div class="preview-label">Preview</div>
            <img src="<?php echo $user['banner'] ?: 'https://via.placeholder.com/1000x250?text=Sem+Banner'; ?>" id="previewBanner" class="preview-banner" alt="Preview banner">
            <img src="<?php echo $user['avatar'] ?: 'https://via.placeholder.com/130x130?text=Avatar'; ?>" id="previewAvatar" class="preview-avatar" alt="Preview avatar">
        </div>

        <form action="update_profile_images.php" method="POST" enctype="multipart/form-data">
            <label for="bannerInput">Banner do perfil</label>
            <input type="file" name="banner" id="bannerInput" accept="image/*">

            <label for="avatarInput">Foto de perfil</label>
            <input type="file" name="avatar" id="avatarInput" accept="image/*">

            <button type="submit">Guardar alterações</button>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.tab-btn').forEach(tabButton => {
        tabButton.addEventListener('click', () => {
            const tabName = tabButton.dataset.tab;
            const tabTarget = document.getElementById(tabName + 'Tab');
            if (!tabTarget) return;

            document.querySelectorAll('.tab-btn').forEach(button => button.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            tabButton.classList.add('active');
            tabTarget.classList.add('active');
        });
    });

    const modalOverlay = document.getElementById('modalOverlay');
    const bannerInput = document.getElementById('bannerInput');
    const avatarInput = document.getElementById('avatarInput');
    const previewBanner = document.getElementById('previewBanner');
    const previewAvatar = document.getElementById('previewAvatar');
    const reviewEditOverlay = document.getElementById('reviewEditOverlay');
    const reviewEditForm = document.getElementById('reviewEditForm');
    const reviewEditId = document.getElementById('reviewEditId');
    const reviewEditRating = document.getElementById('reviewEditRating');
    const reviewEditStars = document.querySelectorAll('#reviewEditStars i');
    const reviewEditRatingValue = document.getElementById('reviewEditRatingValue');
    const reviewEditComment = document.getElementById('reviewEditComment');
    const reviewEditTitle = document.getElementById('reviewEditTitle');
    const reviewEditPoster = document.getElementById('reviewEditPoster');
    let currentEditRating = 0;

    function openModal() {
        if (modalOverlay) modalOverlay.style.display = 'flex';
    }

    function closeModal() {
        if (modalOverlay) modalOverlay.style.display = 'none';
    }

    function openReviewEditModal(reviewId, rating, comment, gameName, gameImage) {
        if (!reviewEditOverlay) return;
        if (reviewEditId) reviewEditId.value = reviewId;
        currentEditRating = parseInt(rating, 10) || 0;
        if (reviewEditRating) reviewEditRating.value = currentEditRating;
        if (reviewEditRatingValue) reviewEditRatingValue.textContent = currentEditRating;
        updateReviewStars(currentEditRating);
        if (reviewEditComment) reviewEditComment.value = comment;
        if (reviewEditTitle) reviewEditTitle.textContent = gameName;
        if (reviewEditPoster) {
            reviewEditPoster.src = gameImage || 'https://via.placeholder.com/280x420?text=Game';
            reviewEditPoster.alt = `Capa de ${gameName}`;
        }
        reviewEditOverlay.style.display = 'flex';
    }

    function closeReviewEditModal() {
        if (reviewEditOverlay) reviewEditOverlay.style.display = 'none';
    }

    if (modalOverlay) {
        modalOverlay.addEventListener('click', event => {
            if (event.target === modalOverlay) closeModal();
        });
    }

    if (reviewEditOverlay) {
        reviewEditOverlay.addEventListener('click', event => {
            if (event.target === reviewEditOverlay) closeReviewEditModal();
        });
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeModal();
            closeReviewEditModal();
        }
    });

    document.querySelectorAll('.open-review-edit').forEach(editButton => {
        editButton.addEventListener('click', () => {
            openReviewEditModal(
                editButton.dataset.reviewId,
                editButton.dataset.rating,
                editButton.dataset.comment || '',
                editButton.dataset.gameName || 'Review',
                editButton.dataset.gameImage || ''
            );
        });
    });

    function updateReviewStars(value) {
        reviewEditStars.forEach(star => {
            const starValue = parseInt(star.dataset.value, 10);
            star.classList.toggle('active', starValue <= value);
            star.classList.remove('hovered');
        });
    }

    reviewEditStars.forEach(star => {
        star.addEventListener('mouseover', () => {
            const hoveredValue = parseInt(star.dataset.value, 10);
            reviewEditStars.forEach(starItem => {
                const starValue = parseInt(starItem.dataset.value, 10);
                starItem.classList.toggle('hovered', starValue <= hoveredValue);
            });
            if (reviewEditRatingValue) reviewEditRatingValue.textContent = hoveredValue;
        });

        star.addEventListener('mouseout', () => {
            reviewEditStars.forEach(starItem => starItem.classList.remove('hovered'));
            if (reviewEditRatingValue) reviewEditRatingValue.textContent = currentEditRating;
        });

        star.addEventListener('click', () => {
            currentEditRating = parseInt(star.dataset.value, 10);
            if (reviewEditRating) reviewEditRating.value = currentEditRating;
            if (reviewEditRatingValue) reviewEditRatingValue.textContent = currentEditRating;
            updateReviewStars(currentEditRating);
        });
    });

    if (reviewEditForm) {
        reviewEditForm.addEventListener('submit', event => {
            if (!reviewEditRating || parseInt(reviewEditRating.value, 10) < 1) {
                event.preventDefault();
                if (reviewEditRatingValue) reviewEditRatingValue.textContent = '0';
                alert('Seleciona uma nota entre 1 e 10.');
            }
        });
    }

    const queryTab = new URLSearchParams(window.location.search).get('tab');
    if (queryTab) {
        const tabBtn = document.querySelector(`.tab-btn[data-tab="${queryTab}"]`);
        if (tabBtn) tabBtn.click();
    }

    if (bannerInput && previewBanner) {
        bannerInput.addEventListener('change', event => {
            const file = event.target.files[0];
            if (file) previewBanner.src = URL.createObjectURL(file);
        });
    }

    if (avatarInput && previewAvatar) {
        avatarInput.addEventListener('change', event => {
            const file = event.target.files[0];
            if (file) previewAvatar.src = URL.createObjectURL(file);
        });
    }

    const bioTextareaProfile = document.getElementById('bioTextareaProfile');
    const bioCountProfile = document.getElementById('bioCountProfile');
    const bioEditForm = document.getElementById('bioEditForm');
    const toggleBioEdit = document.getElementById('toggleBioEdit');

    if (toggleBioEdit && bioEditForm) {
        toggleBioEdit.addEventListener('click', () => {
            const isOpen = bioEditForm.classList.toggle('open');
            toggleBioEdit.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (isOpen && bioTextareaProfile) {
                bioTextareaProfile.focus();
                bioTextareaProfile.setSelectionRange(bioTextareaProfile.value.length, bioTextareaProfile.value.length);
            }
        });
    }

    if (bioTextareaProfile && bioCountProfile) {
        const updateBioCount = () => {
            bioCountProfile.textContent = `${bioTextareaProfile.value.length}/500`;
        };

        bioTextareaProfile.addEventListener('input', updateBioCount);
        updateBioCount();
    }

    document.querySelectorAll('.social-discord-btn').forEach(discordButton => {
        discordButton.addEventListener('click', async () => {
            const discordUsername = discordButton.dataset.discord || '';
            if (!discordUsername) return;

            const previousTitle = discordButton.title;

            try {
                await navigator.clipboard.writeText(discordUsername);
                discordButton.title = 'Copiado!';
            } catch (_) {
                const tempInput = document.createElement('input');
                tempInput.value = discordUsername;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                discordButton.title = 'Copiado!';
            }

            setTimeout(() => {
                discordButton.title = previousTitle;
            }, 1200);
        });
    });

    const bioSuccessMessage = document.getElementById('bioSuccessMessage');
    if (bioSuccessMessage) {
        setTimeout(() => {
            bioSuccessMessage.classList.add('fade-out');
            setTimeout(() => bioSuccessMessage.remove(), 320);
        }, 3000);
    }
</script>

<?php include 'includes/footer.php'; ?>
