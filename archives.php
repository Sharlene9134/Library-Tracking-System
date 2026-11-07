<?php
session_start();
$isAdmin = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Admin');
include 'conn.php';

// Function to safely escape output
function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Daily transactions
$daily_sql = "SELECT COUNT(*) AS count FROM transaction WHERE Borrowed_date = CURDATE()";
$daily_result = mysqli_query($conn, $daily_sql);
$daily_count = mysqli_fetch_assoc($daily_result)['count'] ?? 0;

// Weekly transactions
$weekly_sql = "SELECT COUNT(*) AS count FROM transaction WHERE YEARWEEK(Borrowed_date, 1) = YEARWEEK(CURDATE(), 1)";
$weekly_result = mysqli_query($conn, $weekly_sql);
$weekly_count = mysqli_fetch_assoc($weekly_result)['count'] ?? 0;

// Monthly transactions
$monthly_sql = "SELECT COUNT(*) AS count FROM transaction WHERE YEAR(Borrowed_date) = YEAR(CURDATE()) AND MONTH(Borrowed_date) = MONTH(CURDATE())";
$monthly_result = mysqli_query($conn, $monthly_sql);
$monthly_count = mysqli_fetch_assoc($monthly_result)['count'] ?? 0;

// Yearly transactions
$yearly_sql = "SELECT COUNT(*) AS count FROM transaction WHERE YEAR(Borrowed_date) = YEAR(CURDATE())";
$yearly_result = mysqli_query($conn, $yearly_sql);
$yearly_count = mysqli_fetch_assoc($yearly_result)['count'] ?? 0;

// Current Date
$currentDate = date("F d, Y"); 
$currentDay  = date("l");      
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive | Library Tracking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --bg: #f4f6f9; 
            --panel: #fff; 
            --ink: #1f2a37; 
            --muted: #6b7280;
            --primary: #2563eb; 
            --primary-d: #1d4ed8; 
            --border: #e5e7eb;
            --success: #16a34a; 
            --warn: #d97706; 
            --danger: #dc2626;
        }
        * { 
            box-sizing: border-box; 
            margin: 0;
            padding: 0;
        }

        body { 
            height: 100vh; 
            overflow: hidden;
            font-family: "Segoe UI", Roboto, Arial, sans-serif;
            background: var(--bg); 
            color: var(--ink); 
            display: grid;
            grid-template-rows: auto auto 1fr; /* Header, Nav, Main */
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #a1a1aa; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #71717a; }

        .header {
            background: #064e3b;
            color: #fff; 
            padding: 18px 24px; 
            display: flex; 
            align-items: center;
            justify-content: space-between; 
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }

        .header h1{ 
            font-size: 1.4rem; 
            letter-spacing: .5px; 
        }

        .navigation{ 
            background: #064e3b; 
            padding: 10px 20px 15px 5px; 
            text-align: right;
        }

        .navigation a{ 
            color: #e5e7eb; 
            background: transparent;
            text-decoration: none; 
            font-size: .9rem; 
            padding: 4px 16px;
            border-radius: 6px;
            border: 1px solid #fff;
        }

        .navigation a:hover{ 
            color: #fff; 
            text-decoration: underline; 
        }

        .main{ 
            display: flex; 
            flex: 1;
            min-height: 0; 
            overflow: hidden; 
        }

        .side-menu{ 
            width: 210px; 
            background: #1e3a2f; 
            padding: 14px; 
            flex-shrink: 0; 
            overflow-y: auto;
            height: 100%;
        }

        .side-menu .nav{ 
            display: flex; 
            flex-direction: column; 
            gap: 4px; 
        }

        .side-menu .nav a {
            color: #cbd5e1; 
            text-decoration: none; 
            padding: 9px 12px;
            border-radius: 6px; 
            font-size: .92rem; 
            transition: .15s;
        }

        .side-menu .nav a:hover, .side-menu .nav a.active { 
            background: #374151; 
            color: #fff; 
            font-weight: 600; 
        }

        .content { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            gap: 16px; 
            min-width: 0; 
            overflow-y: auto; 
            padding: 20px; 
        }

        .center-header{ 
            text-align: center; 
            color: #111827;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        /* Date Card */
        .date-container {
            display: flex;
            justify-content: center;
            margin: 10px 0 20px;
        }

        .date-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            padding: 18px 28px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            min-width: 260px;
        }

        .date-icon {
            font-size: 36px;
        }

        .date-day {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .date-full {
            font-size: 14px;
            margin: 2px 0 0;
            opacity: 0.9;
        }

        /* Modernized Stats Grid */
        .stats-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 16px;
            margin-top: 10px;
        }

        .stat-card {
            background: var(--panel); 
            padding: 24px 20px; 
            border-radius: 10px;
            border: 1px solid var(--border); 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex; 
            flex-direction: column; 
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        }

        .stat-title { 
            font-size: .85rem; 
            text-transform: uppercase; 
            letter-spacing: .5px;
            color: #4b5563; 
            font-weight: 600; 
        }

        .stat-value { 
            font-size: 2.5rem; 
            font-weight: 700; 
            color: #111827; 
            line-height: 1; 
        }
        
        /* Colored top borders for stats */
        .stat-card.daily   { border-top: 4px solid var(--primary); }
        .stat-card.weekly  { border-top: 4px solid var(--success); }
        .stat-card.monthly { border-top: 4px solid var(--warn); }
        .stat-card.yearly  { border-top: 4px solid var(--danger); }

        @media (max-width: 768px) {
            body { height: auto; overflow: auto; display: block; }
            .main { flex-direction: column; overflow: visible; }
            .side-menu { width: 100%; overflow-y: visible; }
            .content { overflow-y: visible; }
            .stats-grid { grid-template-columns: 1fr; }
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
                <a href="dashboard.php">Dashboard</a>
                <a href="transaction_list.php">Transactions</a>
                <a href="books.php">Book Status</a>
                <a href="borrower.php">Borrower</a>
                <a href="staff.php">Staff</a>
                <a href="reports.php">Report</a>
                <a href="archives.php" class="active">Archive</a>
                <?php if ($isAdmin): ?>
                    <a href="admindisplay.php">User Management</a>
                <?php endif; ?>
            </nav>
        </aside> 

        <!-- Main Content -->
        <section class="content">
            <h2 class="center-header">Archive Statistics</h2>

            <!-- Date Display -->
            <div class="date-container">
                <div class="date-card">
                    <div class="date-icon">📅</div>
                    <div class="date-text">
                        <p class="date-day"><?php echo e($currentDay); ?></p>
                        <p class="date-full"><?php echo e($currentDate); ?></p>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card daily">
                    <span class="stat-title">Daily Borrows</span>
                    <span class="stat-value"><?php echo e($daily_count); ?></span>
                </div>
                
                <div class="stat-card weekly">
                    <span class="stat-title">Weekly Borrows</span>
                    <span class="stat-value"><?php echo e($weekly_count); ?></span>
                </div>
                
                <div class="stat-card monthly">
                    <span class="stat-title">Monthly Borrows</span>
                    <span class="stat-value"><?php echo e($monthly_count); ?></span>
                </div>
                
                <div class="stat-card yearly">
                    <span class="stat-title">Yearly Borrows</span>
                    <span class="stat-value"><?php echo e($yearly_count); ?></span>
                </div>
            </div>
            
        </section>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>