<?php
session_start();

// ---- Auth guard (uncomment & adjust for your system) ----
// if (!isset($_SESSION['usertype'])) { header('Location: login2.php'); exit; }

$isAdmin = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Admin');
include 'conn.php';

function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// ---- Fetch Dashboard Statistics ----
$stats = [
    'total_books'     => 0,
    'available_books' => 0,
    'ongoing_tx'      => 0,
    'overdue_tx'      => 0,
    'borrowers'       => 0
];

// 1. Book Stats
$r = mysqli_query($conn, "SELECT Status, COUNT(*) as count FROM book GROUP BY Status");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $stats['total_books'] += $row['count'];
        if ($row['Status'] === 'Available') $stats['available_books'] = $row['count'];
    }
}

// 2. Ongoing Transactions & Overdue
$today = date('Y-m-d');
$r = mysqli_query($conn, "SELECT Due_date FROM transaction WHERE Status='Ongoing'");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $stats['ongoing_tx']++;
        if (!empty($row['Due_date']) && $today > $row['Due_date']) {
            $stats['overdue_tx']++;
        }
    }
}

// 3. Borrower Count
$r = mysqli_query($conn, "SELECT COUNT(*) as count FROM borrower");
if ($r) {
    $row = mysqli_fetch_assoc($r);
    $stats['borrowers'] = $row['count'] ?? 0;
}

// ---- Fetch Recent Overdue Transactions for Quick View ----
$overdueQuery = "
    SELECT t.Transaction_ID, t.Book_ID, t.Borrower_ID, t.Due_date, b.Title 
    FROM transaction t
    JOIN book b ON t.Book_ID = b.Book_ID
    WHERE t.Status = 'Ongoing' AND t.Due_date < '$today'
    ORDER BY t.Due_date ASC
    LIMIT 5
";
$overdueResult = mysqli_query($conn, $overdueQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Library Tracking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Reuse matching CSS variables from transaction_list.php */
        :root {
            --bg: #f4f6f9; --panel: #fff; --ink: #1f2a37; --muted: #6b7280;
            --primary: #2563eb; --primary-d: #1d4ed8; --border: #e5e7eb;
            --success: #16a34a; --warn: #d97706; --danger: #dc2626;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: "Segoe UI", Roboto, Arial, sans-serif;
               background: var(--bg); color: var(--ink); }

        .header {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: #fff; padding: 18px 24px; display: flex; align-items: center;
            justify-content: space-between; box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .header h1 { margin: 0; font-size: 1.4rem; letter-spacing: .5px; }

        .navigation { background: #111827; padding: 8px 24px; text-align: right; }
        .navigation a { color: #e5e7eb; text-decoration: none; font-size: .9rem; }
        .navigation a:hover { color: #fff; text-decoration: underline; }

        .main { display: flex; align-items: stretch; min-height: calc(100vh - 110px); gap: 16px; padding: 16px; }
        .side-menu { width: 210px; background: #1f2937; border-radius: 10px; padding: 14px; flex-shrink: 0; }
        .side-menu .nav { display: flex; flex-direction: column; gap: 4px; }
        .side-menu .nav a {
            color: #cbd5e1; text-decoration: none; padding: 9px 12px;
            border-radius: 6px; font-size: .92rem; transition: .15s;
        }
        .side-menu .nav a:hover, .side-menu .nav a.active { background: #374151; color: #fff; font-weight: 600; }

        .content { flex: 1; display: flex; flex-direction: column; gap: 16px; min-width: 0; }

        /* Welcome Banner */
        .welcome-banner {
            background: var(--panel); padding: 24px; border-radius: 10px;
            border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .welcome-banner h2 { margin: 0 0 8px; color: #111827; }
        .welcome-banner p { margin: 0; color: var(--muted); }

        /* Stats Grid */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;
        }
        .stat-card {
            background: var(--panel); padding: 20px; border-radius: 10px;
            border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,.05);
            display: flex; flex-direction: column; gap: 8px;
        }
        .stat-title { font-size: .85rem; text-transform: uppercase; letter-spacing: .4px;
                      color: var(--muted); font-weight: 600; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #111827; line-height: 1; }
        
        .stat-card.primary { border-left: 4px solid var(--primary); }
        .stat-card.success { border-left: 4px solid var(--success); }
        .stat-card.warn    { border-left: 4px solid var(--warn); }
        .stat-card.danger  { border-left: 4px solid var(--danger); }

        /* Overdue Section */
        .overdue-section {
            background: var(--panel); padding: 20px; border-radius: 10px;
            border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .overdue-section h3 { margin: 0 0 16px; color: #111827; display: flex; justify-content: space-between; align-items: center; }
        
        .table-wrapper { overflow-x: auto; border-radius: 8px; border: 1px solid var(--border); }
        .book-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .book-table thead th {
            background: #f3f4f6; color: #111827; text-align: left;
            padding: 10px 10px; border-bottom: 2px solid var(--border);
            font-size: .8rem; text-transform: uppercase; letter-spacing: .4px;
        }
        .book-table tbody td { padding: 9px 10px; border-bottom: 1px solid var(--border); }
        .book-table tbody tr:hover { background: #f9fafb; }
        .book-table tbody tr:last-child td { border-bottom: 0; }

        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px;
                 font-size: .72rem; font-weight: 700; background: #fee2e2; color: #991b1b; }
                 
        .empty { text-align: center; padding: 30px; color: var(--muted); font-style: italic; }

        @media (max-width: 768px) {
            .main { flex-direction: column; }
            .side-menu { width: 100%; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <h1>📚 Library Tracking System</h1>
    </header>

    <!-- Navigation -->
    <nav class="navigation">
        <a href="login2.php">Log Out</a>
    </nav>

    <main class="main">
        <!-- Sidebar -->
        <aside class="side-menu">
            <nav class="nav">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="transaction_list.php">Transactions</a>
                <a href="books.php">Book Status</a>
                <a href="borrower.php">Borrower</a>
                <a href="staff.php">Staff</a>
                <a href="reports.php">Report</a>
                <a href="archives.php">Archive</a>
                <?php if ($isAdmin): ?>
                    <a href="admindisplay.php">User Management</a>
                <?php endif; ?>
            </nav>
        </aside> 

        <section class="content">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <h2>Welcome to the Dashboard</h2>
                <p>Here is a quick overview of your library's current status.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <span class="stat-title">Total Books</span>
                    <span class="stat-value"><?= e($stats['total_books']) ?></span>
                </div>
                <div class="stat-card success">
                    <span class="stat-title">Available Books</span>
                    <span class="stat-value"><?= e($stats['available_books']) ?></span>
                </div>
                <div class="stat-card warn">
                    <span class="stat-title">Ongoing Transactions</span>
                    <span class="stat-value"><?= e($stats['ongoing_tx']) ?></span>
                </div>
                <div class="stat-card danger">
                    <span class="stat-title">Overdue Books</span>
                    <span class="stat-value"><?= e($stats['overdue_tx']) ?></span>
                </div>
            </div>

            <!-- Overdue Quick View -->
            <div class="overdue-section">
                <h3>
                    Top Overdue Transactions
                    <a href="transaction_list.php" style="font-size: 0.85rem; color: var(--primary); text-decoration: none;">View All &rarr;</a>
                </h3>
                
                <div class="table-wrapper">
                    <table class="book-table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Book Title</th>
                                <th>Borrower ID</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$overdueResult): ?>
                                <tr><td colspan="5" class="empty">Error loading data: <?= e(mysqli_error($conn)) ?></td></tr>
                            <?php elseif (mysqli_num_rows($overdueResult) === 0): ?>
                                <tr><td colspan="5" class="empty">No overdue books! Everything is on track.</td></tr>
                            <?php else: ?>
                                <?php while ($row = mysqli_fetch_assoc($overdueResult)): ?>
                                    <tr>
                                        <td><?= e($row['Transaction_ID']) ?></td>
                                        <td><?= e($row['Title']) ?></td>
                                        <td><?= e($row['Borrower_ID']) ?></td>
                                        <td><?= e($row['Due_date']) ?></td>
                                        <td><span class="badge">OVERDUE</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <?php mysqli_close($conn); ?>
</body>
</html>