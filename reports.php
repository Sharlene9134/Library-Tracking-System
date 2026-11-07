<?php
session_start();
$isAdmin = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Admin');
include 'conn.php';

// Function to safely escape output
function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Determine which report to show (borrowed, overdue, returned, cancelled)
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'borrowed';
switch ($filter) {
    case 'borrowed':
        // Borrowed: ongoing transactions not yet pass due date
        $sql = "SELECT * FROM transaction WHERE Status = 'Ongoing' AND Due_date >= CURDATE()";
        $title = 'Borrowed';
        break;
    case 'overdue':
        // Overdue: ongoing transactions whose due date has passed
        $sql = "SELECT * FROM transaction WHERE Status = 'Ongoing' AND Due_date < CURDATE()";
        $title = 'Overdue';
        break;
    case 'returned':
        // Returned: completed transactions
        $sql = "SELECT * FROM transaction WHERE Status = 'Completed'";
        $title = "Returned";
        break;
    case 'cancelled':
        // Cancelled: transactions that were cancelled
        $sql = "SELECT * FROM transaction WHERE Status = 'Cancelled'";
        $title = 'Cancelled';
        break;
    default:
        $sql = "SELECT * FROM transaction";
        $title = 'All';
        break;
}
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Library Tracking System</title>
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
            margin-bottom: 5px;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .filter-btn {
            text-decoration: none;
            padding: 9px 20px;
            border-radius: 6px;
            font-size: .9rem;
            font-weight: 600;
            color: var(--muted);
            background: #e5e7eb;
            transition: .15s;
            border: 1px solid transparent;
        }

        .filter-btn:hover {
            background: #d1d5db;
            color: var(--ink);
        }

        /* Active Tab Colors */
        .filter-btn.active {
            color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-btn.active[href*="filter=borrowed"] { background: var(--primary); }
        .filter-btn.active[href*="filter=returned"] { background: var(--success); }
        .filter-btn.active[href*="filter=overdue"]  { background: var(--danger); }
        .filter-btn.active[href*="filter=cancelled"]{ background: var(--warn); }

        .report-title {
            text-align: center;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 10px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        /* Modernized Table Styling */
        .table-wrapper { 
            overflow-x: auto; 
            border-radius: 8px; 
            border: 1px solid var(--border); 
            background: var(--panel);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .book-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: .88rem; 
        }

        .book-table thead th {
            background: #f3f4f6; 
            color: #111827; 
            text-align: center;
            padding: 12px 16px; 
            border-bottom: 2px solid var(--border);
            font-size: .8rem; 
            text-transform: uppercase; 
            letter-spacing: .4px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .book-table tbody td { 
            text-align: center;
            padding: 12px 16px; 
            border-bottom: 1px solid var(--border); 
        }

        .book-table tbody tr:hover { 
            background: #f9fafb; 
        }

        .book-table tbody tr:last-child td { 
            border-bottom: 0; 
        }

        .empty{ 
            text-align:center; 
            padding:30px; 
            color:var(--muted); 
            font-style:italic; 
        }

        @media (max-width: 768px) {
            body { height: auto; overflow: auto; display: block; }
            .main { flex-direction: column; overflow: visible; }
            .side-menu { width: 100%; overflow-y: visible; }
            .content { overflow-y: visible; }
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
                <a href="reports.php" class="active">Report</a>
                <a href="archives.php">Archive</a>
                <?php if ($isAdmin): ?>
                    <a href="admindisplay.php">User Management</a>
                <?php endif; ?>
            </nav>
        </aside> 

        <!-- Main Content -->
        <section class="content">
            <h2 class="center-header">Reports</h2>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="reports.php?filter=borrowed" class="filter-btn <?php echo $filter === 'borrowed' ? 'active' : ''; ?>">Borrowed</a>
                <a href="reports.php?filter=returned" class="filter-btn <?php echo $filter === 'returned' ? 'active' : ''; ?>">Returned</a>
                <a href="reports.php?filter=overdue" class="filter-btn <?php echo $filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
                <a href="reports.php?filter=cancelled" class="filter-btn <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>

            <p class="report-title">Showing: <?php echo e($title); ?> Transactions</p>

            <div class="table-wrapper">
                <table class="book-table">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Book ID</th>
                            <th>Borrower ID</th>
                            <th>Borrowed Date</th>
                            <th>Time Borrowed</th>
                            <th>Borrowed Staff ID</th>
                            <th>Due Date</th>
                            <th>Returned Date</th>
                            <th>Time Returned</th>
                            <th>Returned Staff ID</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (!$result) {
                        echo "<tr><td colspan='10' class='empty'>Error loading transaction.</td></tr>";
                    } elseif (mysqli_num_rows($result) === 0) {
                        echo "<tr><td colspan='10' class='empty'>No transactions found for this report.</td></tr>";
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . e($row['Transaction_ID']) . "</td>";
                            echo "<td>" . e($row['Book_ID']) . "</td>";
                            echo "<td>" . e($row['Borrower_ID']) . "</td>";
                            echo "<td>" . e($row['Borrowed_date']) . "</td>";
                            echo "<td>" . e($row['Time_Borrowed']) . "</td>";
                            echo "<td>" . e($row['Borrowed_Staff_ID']) . "</td>";
                            echo "<td>" . e($row['Due_date']) . "</td>";
                            echo "<td>" . e($row['Returned_date'] ?: '—') . "</td>";
                            echo "<td>" . e($row['Time_Returned'] ?: '—') . "</td>";
                            echo "<td>" . e($row['Returned_Staff_ID'] ?: '—') . "</td>";
                            echo "</tr>";
                        }
                    }
                    mysqli_close($conn);
                    ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>