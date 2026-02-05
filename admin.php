<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include 'includes/db.php';

// --- SEGURANÇA ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// --- LÓGICA DE EXPORTAÇÃO CSV (Deve ser antes de qualquer HTML) ---
if (isset($_POST['export_logs'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="admin_logs_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'Admin ID', 'Ação', 'Target ID', 'Detalhes', 'Data'));
    $rows = $conn->query("SELECT * FROM admin_logs ORDER BY created_at DESC");
    while ($row = $rows->fetch_assoc()) fputcsv($output, $row);
    fclose($output);
    exit;
}

$feedback = "";
$feedback_type = "";

// --- LÓGICA DE AÇÕES (POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Ações Individuais (User)
    if (isset($_POST['update_role'])) {
        $uid = intval($_POST['user_id']);
        $role = $_POST['role'];
        $conn->query("UPDATE users SET role = '$role' WHERE id = $uid");
        logAction($uid, "Role changed to $role");
        $feedback = "Role atualizado."; $feedback_type = "success";
    }

    if (isset($_POST['toggle_ban'])) {
        $uid = intval($_POST['user_id']);
        $curr = $conn->query("SELECT banned FROM users WHERE id = $uid")->fetch_assoc()['banned'];
        $new = $curr ? 0 : 1;
        $conn->query("UPDATE users SET banned = $new WHERE id = $uid");
        logAction($uid, "User ban status: " . ($new ? 'Banned' : 'Active'));
        $feedback = "Status de banimento alterado."; $feedback_type = $new ? "warning" : "success";
    }

    // Ações em Massa (Bulk Users)
    if (isset($_POST['bulk_action_type']) && isset($_POST['selected_users'])) {
        $ids = array_map('intval', $_POST['selected_users']);
        $ids_str = implode(',', $ids);
        $type = $_POST['bulk_action_type'];
        
        if ($type == 'ban') {
            $conn->query("UPDATE users SET banned = 1 WHERE id IN ($ids_str)");
            logAction(0, "Bulk Ban on IDs: $ids_str");
            $feedback = count($ids) . " utilizadores banidos."; $feedback_type = "warning";
        } elseif ($type == 'unban') {
            $conn->query("UPDATE users SET banned = 0 WHERE id IN ($ids_str)");
            logAction(0, "Bulk Unban on IDs: $ids_str");
            $feedback = count($ids) . " utilizadores desbanidos."; $feedback_type = "success";
        }
    }

    // Gestão de Reviews (Com Motivo)
    if (isset($_POST['review_action'])) {
        $rid = intval($_POST['review_id']);
        $action = $_POST['review_action'];
        
        if ($action == 'approve') {
            $conn->query("UPDATE reviews SET approved = 1 WHERE id = $rid");
            logAction($rid, "Review Approved");
            $feedback = "Review aprovada."; $feedback_type = "success";
        } elseif ($action == 'reject') {
            $reason = $conn->real_escape_string($_POST['reject_reason'] ?? 'Sem motivo');
            $conn->query("DELETE FROM reviews WHERE id = $rid");
            logAction($rid, "Review Rejected. Reason: $reason");
            $feedback = "Review rejeitada."; $feedback_type = "error";
        }
    }
}

function logAction($target, $details) {
    global $conn;
    $admin = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, target_id, details) VALUES (?, 'admin_action', ?, ?)");
    $stmt->bind_param("iis", $admin, $target, $details);
    $stmt->execute();
}

// --- BUSCAR DADOS (COM FILTROS) ---

// Filtros Users
$filter_role = $_GET['filter_role'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$user_query = "SELECT * FROM users WHERE 1=1";
if($filter_role) $user_query .= " AND role = '$filter_role'";
if($filter_status == 'banned') $user_query .= " AND banned = 1";
if($filter_status == 'active') $user_query .= " AND banned = 0";
$user_query .= " ORDER BY created_at DESC LIMIT 50";
$users_list = $conn->query($user_query);

// Stats Avançados
$total_reviews = $conn->query("SELECT COUNT(*) as c FROM reviews")->fetch_assoc()['c'];
$approved_reviews = $conn->query("SELECT COUNT(*) as c FROM reviews WHERE approved = 1")->fetch_assoc()['c'];
$approval_rate = $total_reviews > 0 ? round(($approved_reviews / $total_reviews) * 100) : 0;

// Gráfico de Atividade (Últimos 7 dias)
$activity_data = [];
for($i=6; $i>=0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $conn->query("SELECT COUNT(*) as c FROM reviews WHERE DATE(created_at) = '$date'")->fetch_assoc()['c'];
    $activity_data[] = ['date' => date('d/m', strtotime($date)), 'count' => $count];
}
$max_activity = max(array_column($activity_data, 'count')) ?: 1; // Evitar divisão por zero

// Listas Normais
$stats = [
    'users' => $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'],
    'pending' => $conn->query("SELECT COUNT(*) as c FROM reviews WHERE approved = 0")->fetch_assoc()['c']
];

$pending_reviews = $conn->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.approved = 0 ORDER BY r.created_at DESC");
$logs = $conn->query("SELECT l.*, u.username as admin_name FROM admin_logs l LEFT JOIN users u ON l.admin_id = u.id ORDER BY l.created_at DESC LIMIT 30");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard Pro - GameList</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --bg-body: #0b0c0f; --sidebar-bg: #111216; --card-bg: #1a1d24;
            --border: rgba(255,255,255,0.08); --accent: #00bfff; --success: #00e054; --warning: #ffab00; --danger: #ff4444;
            --text-main: #fff; --text-muted: #8899a6;
        }
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); color: var(--text-main); font-family: 'Inter', sans-serif; margin: 0; display: flex; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--sidebar-bg); border-right: 1px solid var(--border); position: fixed; height: 100vh; padding: 25px 15px; display: flex; flex-direction: column; z-index: 100; }
        .brand img { max-width: 140px; margin-bottom: 40px; display: block; margin-left: 10px; }
        .nav-item { padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; color: var(--text-muted); cursor: pointer; font-weight: 500; display: flex; align-items: center; gap: 12px; transition: 0.2s; position: relative; }
        .nav-item:hover, .nav-item.active { background: rgba(0, 191, 255, 0.1); color: var(--accent); }
        
        /* Badge de Notificação */
        .nav-badge { position: absolute; right: 15px; background: var(--danger); color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-weight: 700; }

        /* CONTEÚDO */
        .main-content { margin-left: 260px; flex: 1; padding: 40px; max-width: 1600px; }
        .header-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 1.8rem; font-weight: 700; }

        /* STATS & GRAPHS */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .card { background: var(--card-bg); padding: 25px; border-radius: 12px; border: 1px solid var(--border); position: relative; }
        
        .stat-big { font-size: 2.5rem; font-weight: 800; margin: 10px 0; }
        .progress-circle { width: 100%; height: 10px; background: #333; border-radius: 5px; margin-top: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: var(--success); transition: width 1s ease; }

        /* Gráfico de Barras CSS */
        .chart-container { display: flex; align-items: flex-end; gap: 10px; height: 100px; margin-top: 20px; }
        .chart-bar-wrap { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px; height: 100%; justify-content: flex-end; }
        .chart-bar { width: 100%; background: var(--accent); border-radius: 4px 4px 0 0; opacity: 0.7; transition: 0.3s; min-height: 4px; }
        .chart-bar:hover { opacity: 1; transform: scaleY(1.05); }
        .chart-label { font-size: 0.7rem; color: var(--text-muted); }

        /* FILTROS E AÇÕES */
        .toolbar { display: flex; gap: 15px; margin-bottom: 20px; background: var(--card-bg); padding: 15px; border-radius: 8px; border: 1px solid var(--border); align-items: center; flex-wrap: wrap; }
        .filter-select { background: #000; border: 1px solid var(--border); color: #fff; padding: 8px 12px; border-radius: 6px; }
        
        /* Floating Bulk Action Bar */
        .bulk-actions { 
            display: none; align-items: center; gap: 15px; 
            background: rgba(0, 191, 255, 0.15); border: 1px solid var(--accent); 
            padding: 10px 20px; border-radius: 8px; animation: fadeIn 0.3s; 
        }
        .bulk-actions.active { display: flex; }

        /* TABELAS */
        .table-wrapper { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border); overflow: hidden; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 20px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; background: rgba(0,0,0,0.2); }
        td { padding: 15px 20px; border-bottom: 1px solid var(--border); color: #eee; font-size: 0.9rem; vertical-align: middle; }
        tr:hover { background: rgba(255,255,255,0.02); }

        /* BADGES */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .bg-admin { background: rgba(0, 191, 255, 0.2); color: var(--accent); }
        .bg-user { background: rgba(255, 255, 255, 0.1); color: #aaa; }
        .bg-banned { background: rgba(255, 68, 68, 0.2); color: var(--danger); }
        .bg-active { background: rgba(0, 224, 84, 0.2); color: var(--success); }

        /* BOTÕES */
        .btn-sm { padding: 6px 12px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-success { background: rgba(0, 224, 84, 0.1); color: var(--success); }
        .btn-success:hover { background: var(--success); color: #000; }
        .btn-danger { background: rgba(255, 68, 68, 0.1); color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: #fff; }
        .btn-primary { background: var(--accent); color: #fff; }

        /* TOGGLE SWITCH */
        .toggle-switch { position: relative; display: inline-block; width: 40px; height: 20px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #333; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--danger); } /* Vermelho = Banido */
        input:checked + .slider:before { transform: translateX(20px); }

        /* MODAL */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 999; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .modal-box { background: var(--card-bg); width: 100%; max-width: 400px; padding: 25px; border-radius: 12px; border: 1px solid var(--border); }
        
        .tab-content { display: none; animation: fadeIn 0.3s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="brand">
            <img src="img/logo.png" alt="Logo">
        </div>
        
        <div class="nav-item active" onclick="switchTab('dashboard', this)">
            <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
        </div>
        <div class="nav-item" onclick="switchTab('users', this)">
            <i class="fa-solid fa-users"></i> <span>Utilizadores</span>
        </div>
        <div class="nav-item" onclick="switchTab('reviews', this)">
            <i class="fa-solid fa-star"></i> <span>Reviews</span>
            <?php if($stats['pending'] > 0): ?>
                <span class="nav-badge"><?php echo $stats['pending']; ?></span>
            <?php endif; ?>
        </div>
        <div class="nav-item" onclick="switchTab('logs', this)">
            <i class="fa-solid fa-terminal"></i> <span>Logs</span>
        </div>

        <div style="margin-top: auto;">
            <a href="index.php" class="nav-item">
                <i class="fa-solid fa-arrow-left"></i> <span>Voltar ao Site</span>
            </a>
            <a href="logout.php" class="nav-item" style="color: var(--danger);">
                <i class="fa-solid fa-power-off"></i> <span>Sair</span>
            </a>
        </div>
    </nav>

    <main class="main-content">
        <div class="header-bar">
            <h1 class="page-title">Painel Administrativo</h1>
            <div style="display:flex; align-items:center; gap:10px; color:var(--text-muted);">
                <i class="fa-solid fa-shield-halved"></i> Admin: <?php echo $_SESSION['username']; ?>
            </div>
        </div>

        <?php if($feedback): ?>
            <div style="padding:15px; border-radius:8px; margin-bottom:20px; background:rgba(255,255,255,0.05); border-left:4px solid var(--<?php echo $feedback_type == 'error' ? 'danger' : ($feedback_type == 'warning' ? 'warning' : 'success'); ?>);">
                <?php echo $feedback; ?>
            </div>
        <?php endif; ?>

        <div id="dashboard" class="tab-content active">
            <div class="dashboard-grid">
                <div class="card">
                    <h3 style="margin:0; color:var(--text-muted); font-size:0.9rem;">TAXA DE APROVAÇÃO</h3>
                    <div class="stat-big"><?php echo $approval_rate; ?>%</div>
                    <div class="progress-circle">
                        <div class="progress-fill" style="width: <?php echo $approval_rate; ?>%;"></div>
                    </div>
                    <p style="font-size:0.8rem; margin-top:10px; color:var(--text-muted);">De <?php echo $total_reviews; ?> reviews totais</p>
                </div>

                <div class="card">
                    <h3 style="margin:0; color:var(--text-muted); font-size:0.9rem;">ATIVIDADE (7 DIAS)</h3>
                    <div class="chart-container">
                        <?php foreach($activity_data as $data): 
                            $h = ($data['count'] / $max_activity) * 100;
                        ?>
                            <div class="chart-bar-wrap">
                                <div class="chart-bar" style="height: <?php echo $h ?: 1; ?>%;" title="<?php echo $data['count']; ?> reviews"></div>
                                <span class="chart-label"><?php echo $data['date']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card" style="display:flex; flex-direction:column; justify-content:center; gap:15px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-muted);">Utilizadores</span>
                        <strong style="font-size:1.2rem;"><?php echo $stats['users']; ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <span style="color:var(--text-muted);">Pendentes</span>
                        <strong style="color:var(--warning); font-size:1.2rem;"><?php echo $stats['pending']; ?></strong>
                    </div>
                </div>
            </div>

            <div class="table-wrapper">
                <div style="padding:20px; border-bottom:1px solid var(--border);">
                    <h3 style="margin:0;"><i class="fa-solid fa-bell" style="color:var(--warning)"></i> Pendentes de Aprovação</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Jogo</th>
                            <th>User</th>
                            <th>Review</th>
                            <th>Nota</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($pending_reviews->num_rows > 0): ?>
                            <?php while($rev = $pending_reviews->fetch_assoc()): ?>
                            <tr>
                                <td style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($rev['game_name']); ?></td>
                                <td><?php echo htmlspecialchars($rev['username']); ?></td>
                                <td style="color:var(--text-muted); font-size:0.85rem; max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?php echo htmlspecialchars($rev['comment']); ?>
                                </td>
                                <td><span style="color:#ffcc00">★ <?php echo $rev['rating']; ?></span></td>
                                <td>
                                    <form method="POST" style="display:flex; gap:5px;">
                                        <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                                        <button type="submit" name="review_action" value="approve" class="btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                        <button type="button" onclick="openRejectModal(<?php echo $rev['id']; ?>)" class="btn-sm btn-danger"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:var(--text-muted);">Tudo limpo!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="users" class="tab-content">
            <form method="POST" id="usersForm">
                <div class="toolbar">
                    <input type="text" id="userSearch" class="filter-select" placeholder="Pesquisar..." onkeyup="filterTable('userTable', this.value)" style="width:200px;">
                    
                    <select class="filter-select" onchange="window.location.href='?filter_role='+this.value">
                        <option value="">Todos os Roles</option>
                        <option value="user" <?php echo ($filter_role == 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo ($filter_role == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>

                    <div class="bulk-actions" id="bulkActions">
                        <span style="font-weight:600; color:var(--accent);">Ações em Massa:</span>
                        <button type="button" onclick="submitBulkAction('ban')" class="btn-sm btn-danger">Banir Selecionados</button>
                        <button type="button" onclick="submitBulkAction('unban')" class="btn-sm btn-success">Desbanir</button>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table id="userTable">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" onclick="toggleAll(this)"></th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Banido?</th>
                                <th>Data Registo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = $users_list->fetch_assoc()): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_users[]" value="<?php echo $u['id']; ?>" class="user-check" onchange="checkBulk()"></td>
                                <td style="font-weight:600;">
                                    <img src="<?php echo $u['avatar'] ?: 'https://via.placeholder.com/20'; ?>" style="width:20px; border-radius:50%; vertical-align:middle; margin-right:5px;">
                                    <?php echo htmlspecialchars($u['username']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $u['role']=='admin'?'bg-admin':'bg-user'; ?>"><?php echo $u['role']; ?></span>
                                </td>
                                <td>
                                    <label class="toggle-switch">
                                        <input type="checkbox" <?php echo $u['banned'] ? 'checked' : ''; ?> onchange="toggleBan(<?php echo $u['id']; ?>)">
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            <form id="banForm" method="POST" style="display:none;">
                <input type="hidden" name="user_id" id="banUserId">
                <input type="hidden" name="toggle_ban" value="1">
            </form>
        </div>

        <div id="logs" class="tab-content">
            <div class="toolbar" style="justify-content:space-between;">
                <h3><i class="fa-solid fa-list"></i> Histórico de Auditoria</h3>
                <form method="POST">
                    <button type="submit" name="export_logs" class="btn-sm btn-primary"><i class="fa-solid fa-download"></i> Exportar CSV</button>
                </form>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Admin</th>
                            <th>Ação</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($l = $logs->fetch_assoc()): ?>
                        <tr>
                            <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo $l['created_at']; ?></td>
                            <td style="color:var(--accent); font-weight:600;"><?php echo htmlspecialchars($l['admin_name']); ?></td>
                            <td><span class="badge bg-user"><?php echo $l['action']; ?></span></td>
                            <td><?php echo htmlspecialchars($l['details']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="reviews" class="tab-content">
            <div class="table-wrapper">
                <div style="padding:20px; border-bottom:1px solid var(--border);"><h3 style="margin:0;"><i class="fa-solid fa-check-circle" style="color:var(--success)"></i> Reviews Aprovadas</h3></div>
                <table>
                    <thead>
                        <tr>
                            <th>Jogo</th>
                            <th>User</th>
                            <th>Review</th>
                            <th>Nota</th>
                            <th>Data</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $all_reviews = $conn->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.approved = 1 ORDER BY r.created_at DESC LIMIT 100");
                        if($all_reviews->num_rows > 0):
                            while($rev = $all_reviews->fetch_assoc()): 
                        ?>
                        <tr>
                            <td style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($rev['game_name']); ?></td>
                            <td><?php echo htmlspecialchars($rev['username']); ?></td>
                            <td style="color:var(--text-muted); font-size:0.85rem; max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars($rev['comment']); ?></td>
                            <td><span style="color:#ffcc00;">★ <?php echo $rev['rating']; ?></span></td>
                            <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('d/m/Y', strtotime($rev['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="review_id" value="<?php echo $rev['id']; ?>">
                                    <input type="hidden" name="review_action" value="reject">
                                    <button type="button" onclick="confirmDelete(<?php echo $rev['id']; ?>)" class="btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:var(--text-muted);">Nenhuma review aprovada ainda</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <div id="rejectModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top:0;">Motivo da Rejeição</h3>
            <form method="POST">
                <input type="hidden" name="review_id" id="modalReviewId">
                <input type="hidden" name="review_action" value="reject">
                <textarea name="reject_reason" style="width:100%; background:#000; border:1px solid #444; color:#fff; padding:10px; border-radius:6px; margin-bottom:15px;" placeholder="Ex: Linguagem ofensiva..." required></textarea>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="document.getElementById('rejectModal').style.display='none'" class="btn-sm" style="background:#444; color:#fff;">Cancelar</button>
                    <button type="submit" class="btn-sm btn-danger">Rejeitar Review</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tabs
        function switchTab(id, el) {
            document.querySelectorAll('.tab-content').forEach(d => d.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(d => d.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            el.classList.add('active');
        }

        // Filtro Tabela
        function filterTable(id, query) {
            query = query.toLowerCase();
            const rows = document.querySelectorAll(`#${id} tbody tr`);
            rows.forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        }

        // Bulk Actions
        function toggleAll(source) {
            document.querySelectorAll('.user-check').forEach(cb => cb.checked = source.checked);
            checkBulk();
        }
        function checkBulk() {
            const any = document.querySelector('.user-check:checked');
            const bar = document.getElementById('bulkActions');
            if(any) bar.classList.add('active'); else bar.classList.remove('active');
        }

        // Bulk Actions com Confirmação
        function submitBulkAction(action) {
            const checked = document.querySelectorAll('.user-check:checked');
            if(checked.length === 0) {
                alert('Selecione pelo menos um utilizador');
                return;
            }
            const msg = action === 'ban' 
                ? `Tens a certeza que queres banir ${checked.length} utilizador(es)?\n\nEsta ação é permanente até manual reverter.`
                : `Tens a certeza que queres desbanir ${checked.length} utilizador(es)?`;
            
            if(confirm(msg)) {
                document.querySelector(`button[value="${action}"]`).click();
            }
        }

        // Confirmar Delete de Review
        function confirmDelete(id) {
            if(confirm('Apagar esta review permanentemente?\n\nEsta ação não pode ser desfeita.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="review_id" value="${id}">
                    <input type="hidden" name="review_action" value="reject">
                    <input type="hidden" name="reject_reason" value="Eliminada pelo admin">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

</body>
</html>