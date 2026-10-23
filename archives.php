<?php
session_start();
$isAdmin = ($_SESSION['usertype'] === 'Admin');
include 'conn.php';
$sql = "SELECT * FROM transaction";
$result = mysqli_query($conn, $sql);

// Daily transactions
$daily_sql = "SELECT COUNT(*) AS count FROM transaction WHERE Borrowed_Date = CURDATE()";
$daily_result = mysqli_query($conn, $daily_sql);
$daily_count = mysqli_fetch_assoc($daily_result)['count'];

// Weekly transactions
$weekly_sql = "SELECT COUNT(*) AS count FROM transaction WHERE YEARWEEK(Borrowed_Date, 1) = YEARWEEK(CURDATE(), 1)";
$weekly_result = mysqli_query($conn, $weekly_sql);
$weekly_count = mysqli_fetch_assoc($weekly_result)['count'];

// Monthly transactions
$monthly_sql = "SELECT COUNT(*) AS count FROM transaction WHERE YEAR(Borrowed_Date) = YEAR(CURDATE()) AND MONTH(Borrowed_Date) = MONTH(CURDATE())";
$monthly_result = mysqli_query($conn, $monthly_sql);
$monthly_count = mysqli_fetch_assoc($monthly_result)['count'];

// Yearly transactions
$yearly_sql = "SELECT COUNT(*) AS count FROM transaction WHERE YEAR(Borrowed_Date) = YEAR(CURDATE())";
$yearly_result = mysqli_query($conn, $yearly_sql);
$yearly_count = mysqli_fetch_assoc($yearly_result)['count'];


// Current Date
$currentDate = date("F d, Y"); 
$currentDay  = date("l");      
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Tracker System</title>
    <link rel="stylesheet" href="assets/css/style.css">
<style>
    /* Modal overall style */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5); 
}

/* Inner modal content */
.modal-inner {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%; 
    max-width: 500px; 
    max-height: 80vh; 
    overflow-y: auto;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.button-group {
    display: flex;
    gap: 10px;     
    flex-wrap: wrap;
}
.button-group .button-login1 {
    flex: 1;        
    min-width: 100px; 
}
.stat {
    display: inline-block;
    width: 22%;
    margin: 1%;
    padding: 20px;
    background-color: #f1f1f1;
    text-align: center;
    border-radius: 10px;
}

.stat-title {
    font-size: 16px;
    font-weight: bold;
    color: black;
    background-color: #A9A9A9;
    border-radius: 5px;
}

.stat-value {
    font-size: 28px;
    font-weight: bold;
    color: black;
    margin-top: 5px;
    background-color: #808080;
    border-radius: 5px;
}
.center-header{
    text-align: center;
    margin-bottom: 20px;
}
.date-container {
    display: flex;
    justify-content: center;
    margin: 20px 0 30px;
}

.date-card {
    display: flex;
    align-items: center;
    gap: 15px;
    background: linear-gradient(135deg, #2a72d6, #4a90e2);
    color: #fff;
    padding: 18px 28px;
    border-radius: 14px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
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
/* Let main grow with content */
.main {
    display: flex;
    align-items: stretch;
    min-height: calc(100vh - 120px);
}

/* Remove fixed height to table can expand */
.content {
    flex: 1;
    padding: 15px;
    border: 2px solid black;
    border-radius: 10px;
}
.table-wrapper {
    overflow-x: auto;
    max-width: 100%;
}
body {
    margin: 0;
    min-height: 100vh;
}
.side-menu {
    width: 200px;
    background: #2c3e50;
    padding: 20px;
}
.table-wrapper {
    overflow-x: auto;
}
</style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <h1>Library Tracking System</h1>
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
                <a href="books.php">Book status</a>
                <a href="borrower.php">Borrower</a>
                <a href="staff.php">Staff</a>
                <a href="reports.php">Report</a>
                <a href="archives.php">Archive</a>
                <?php
				if ($isAdmin) {
					echo "<a href='admindisplay.php'>User Management</a>";
				}
				?>
            </nav>
        </aside> 

        <!-- Main Content -->
        <section class="content">
            <h2 class="center-header"> Archive</h2>

            <div class="date-container">
                <div class="date-card">
                    <div class="date-icon">📅</div>
                    <div class="date-text">
                        <p class="date-day"><?php echo $currentDay; ?></p>
                        <p class="date-full"><?php echo $currentDate; ?></p>
                    </div>
                </div>
            </div>

            <div class="stat">
                <div class="stat-title">Daily</div>
                    <div class="stat-value"><?php echo $daily_count; ?></div>
                </div>
                <div class="stat">
                    <div class="stat-title">Weekly</div>
                    <div class="stat-value"><?php echo $weekly_count; ?></div>
                </div>
                <div class="stat">
                    <div class="stat-title">Monthly</div>
                    <div class="stat-value"><?php echo $monthly_count; ?></div>
                </div>
                <div class="stat">
                    <div class="stat-title">Yearly</div>
                    <div class="stat-value"><?php echo $yearly_count; ?></div>
            </div>
            
            </section>
    </main>

    <script src="assets/js/script.js"></script>
</body>
</html>