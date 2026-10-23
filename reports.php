<?php
session_start();
$isAdmin = ($_SESSION['usertype'] === 'Admin');
include 'conn.php';

// Determine which report to show (cancelled, ontime, overdue)
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
        break;
}
$result = mysqli_query($conn, $sql);
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
        .center-header{
            text-align: center;
            margin-bottom: 20px;
        }
        .button-login1{
            text-align: center;
            border-radius: 5px;
        }
        .button-login1.active{
            background-color: #2a72d6;
            color: #fff;
            padding: 8px 12px;
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
            <h2 class="center-header">Report List</h2>

            <div class="button-group">
                <a href="reports.php?filter=borrowed" type="button" class="button-login1 <?php echo $filter === 'borrowed' ? 'active' : ''; ?>">Borrowed</a>
                <a href="reports.php?filter=returned" type="button" class="button-login1 <?php echo $filter === 'returned' ? 'active' : ''; ?>">Returned</a>
                <a href="reports.php?filter=overdue" type="button" class="button-login1 <?php echo $filter === 'overdue' ? 'active' : ''; ?>">Overdue</a>
                <a href="reports.php?filter=cancelled" type="button" class="button-login1 <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>
            <p style="text-align:center; margin-top:10px; font-weight:600;">Showing: <?php echo $title; ?> transactions</p>
            <table class="book-table" width="100%" cellpadding="10" cellspacing="0">
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
                    echo "<tr><td colspan='10'>Error loading transaction.</td></tr>";
                } elseif (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>{$row['Transaction_ID']}</td>";
                        echo "<td>{$row['Book_ID']}</td>";
                        echo "<td>{$row['Borrower_ID']}</td>";
                        echo "<td>{$row['Borrowed_date']}</td>";
                        echo "<td>{$row['Time_Borrowed']}</td>";
                        echo "<td>{$row['Borrowed_Staff_ID']}</td>";
                        echo "<td>{$row['Due_date']}</td>";
                        echo "<td>{$row['Returned_date']}</td>";
                        echo "<td>{$row['Time_Returned']}</td>";
                        echo "<td>{$row['Returned_Staff_ID']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='10'>No transactions found.</td></tr>";
                }

                mysqli_close($conn);
                ?>
                </tbody>
            </table>

            <!-- <div class="add-transac">
                <button id="openModal">Add book</button>
            </div> -->
            </section>
    </main>

    <script src="assets/js/script.js"></script>
    <script>
        // Populate data and Modals (safe-bind only if elements exist)
        const openModalButtons = document.querySelectorAll('.open-modal');
        if (openModalButtons && openModalButtons.length) {
            openModalButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const modalId = this.getAttribute('data-modal');
                    const modal = document.getElementById(modalId);
                    if (modalId === 'editModal') {
                        if (document.getElementById('editBorrower_ID')) document.getElementById('editBorrower_ID').value = this.getAttribute('data-id');
                        if (document.getElementById('editName')) document.getElementById('editName').value = this.getAttribute('data-name');
                        if (document.getElementById('editContact')) document.getElementById('editContact').value = this.getAttribute('data-contact');
                        if (document.getElementById('editAddress')) document.getElementById('editAddress').value = this.getAttribute('data-address');
                        if (document.getElementById('editCourse')) document.getElementById('editCourse').value = this.getAttribute('data-course');
                        if (document.getElementById('editSection')) document.getElementById('editSection').value = this.getAttribute('data-section');
                    } else if (modalId === 'createModal') {
                        // Clear form fields for create modal
                        const createForm = document.querySelector('#createModal form');
                        if (createForm) createForm.reset();
                    }
                });
            });
        }

        // Open Create Modal (safe bind)
        const openCreateBtn = document.getElementById('openCreateModal');
        if (openCreateBtn) {
            openCreateBtn.addEventListener('click', function() {
                const createModal = document.getElementById('createModal');
                if (createModal) createModal.style.display = 'block';
                const createForm = document.querySelector('#createModal form');
                if (createForm) createForm.reset(); // Clear fields
            });
        }
    </script>
</body>
</html>