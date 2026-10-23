<?php
session_start();
include("conn.php");


if (!isset($_SESSION['username']) || $_SESSION['usertype'] !== 'Admin') {
    header("Location: index.php"); 
    exit();
}
$isAdmin = ($_SESSION['usertype'] === 'Admin');

// EDIT Borrower
if (isset($_POST['edit_submit'])) {
    $userpass = $_POST['userpass'];
    $usertype = $_POST['usertype'];


    $sql = "UPDATE borrower SET  userpass='$userpass', usertype='$usertype'
            WHERE username='$username'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('User Updated');window.location.replace('admindisplay.php');</script>";
    } else {
        echo "<script>alert('Error updating user');</script>";
    }
}
$sql = "SELECT * FROM login";
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
        .center-header{
            text-align: center;
            margin-bottom: 20px;
        }
		.navigation a{
			text-align: right;
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
        <!-- Main Content -->

        <section class="content">
            <h2 class="center-header">User List</h2>
            <div style="margin-bottom:15px; text-align:center;">
                <button class="button3" onclick="showTable('loginTable')">Show Login</button>
                <button class="button3" onclick="showTable('logrecTable')">Show Log Records</button>
            </div>
            <div id="loginTable">
            <table class="book-table" width="100%" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>UserID</th>
                        <th>UserName</th>
                        <th>Password</th>
                        <th>UserType</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                include("conn.php");
                $sql = "SELECT * FROM login";
                $result = mysqli_query($conn, $sql);

                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>{$row['userID']}</td>";
                    echo "<td>{$row['username']}</td>";
                    echo "<td>{$row['userpass']}</td>";
                    echo "<td>{$row['usertype']}</td>";
                    echo "<td>{$row['usercreated_at']}</td>";
                    echo "<td>
                        <button type='button' class='button3 open-modal'
                            data-modal='editModal'
                            data-userID='{$row['userID']}'
                            data-username='{$row['username']}'
                            data-userpass='{$row['userpass']}'
                            data-usertype='{$row['usertype']}'
                            data-created_at='{$row['usercreated_at']}'>
                        Edit</button>
                    </td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
            </div>
            <div id="logrecTable" style="display:none;">
            <table class="book-table" width="100%" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>UserID</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                include("conn.php");

                $sql = "
                    SELECT 
                        login.userID,
                        logrec.login_at
                    FROM logrec
                    INNER JOIN login ON login.userID = logrec.userID
                    ORDER BY logrec.login_at DESC
                ";

                $result = mysqli_query($conn, $sql);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>{$row['userID']}</td>";
                        echo "<td>{$row['login_at']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='2'>No login records found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
            </div>
				  <div class="modal" id="editModal">
                    <div class="modal-inner">
                        <span class="close-btn">&times;</span>
                        <h2>Edit Information</h2>

                        <form method="POST" action="borrower.php">
                            <p>UserID</p>
                            <input type="text" class="input-form" id="edituserID" name="userID" readonly>

                            <p>Username</p>
                            <input type="text" class="input-form" id="editusername" name="username" readonly>

                            <p>Password</p>
                            <input type="text" class="input-form" id="edituserpass" name="userpass">

                            <p>User Type</p>
                            <select class="input-form" id="editusertype" name="usertype" required>
                                <option value="">-- Select UserType --</option>
								<option value="Admin">Admin</option>
								<option value="User">User</option>
                            </select>

                            <p>Created at</p>
                            <input type="datetime-local" class="input-form" id="editusercreated_at" name="usercreated_at" readonly>
                            <br><br>
                            <button type="submit" class="button3" name="edit_submit">Save Changes</button>
                        </form>
                    </div>
                </div>
    </main>
    <script src="assets/js/script.js"></script>
	<script>
        
        function showTable(tableId) {
            document.getElementById('loginTable').style.display = 'none';
            document.getElementById('logrecTable').style.display = 'none';
            document.getElementById(tableId).style.display = 'block';
        }
	        // Populate data and Modals
        document.querySelectorAll('.open-modal').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                if (modalId === 'editModal') {
                    document.getElementById('edituserID').value = this.getAttribute('data-userID');
                    document.getElementById('editusername').value = this.getAttribute('data-username');
                    document.getElementById('edituserpass').value = this.getAttribute('data-userpass');
                    document.getElementById('editusertype').value = this.getAttribute('data-usertype');
                    document.getElementById('editusercreated_at').value = this.getAttribute('data-usercreated_at');
                    document.getElementById('edituserlogin_at').value = this.getAttribute('data-userlogin_at');
                }
            });
        });
	</script>
</body>
</html>