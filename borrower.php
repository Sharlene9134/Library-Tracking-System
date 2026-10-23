<?php
session_start();
$isAdmin = ($_SESSION['usertype'] === 'Admin');
include 'conn.php';



// CREATE Borrower
if (isset($_POST['create_submit'])) {
    $Name = $_POST['Name'];
    $Contact = $_POST['Contact'];
    $Address = $_POST['Address'];
    $Course = $_POST['Course'];
    $Section = $_POST['Section'];

    $sql = "INSERT INTO borrower (Name, Contact, Address, Course, Section) 
            VALUES ('$Name', '$Contact', '$Address', '$Course', '$Section')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('New Borrower Added');window.location.replace('borrower.php');</script>";
    } else {
        echo "<script>alert('Error adding borrower');</script>";
    }
}

// EDIT Borrower
if (isset($_POST['edit_submit'])) {
    $Borrower_ID = $_POST['Borrower_ID'];
    $Name = $_POST['Name'];
    $Contact = $_POST['Contact'];
    $Address = $_POST['Address'];
    $Course = $_POST['Course'];
    $Section = $_POST['Section'];

    $sql = "UPDATE borrower SET Name='$Name', Contact='$Contact', Address='$Address', Course='$Course', Section='$Section' 
            WHERE Borrower_ID='$Borrower_ID'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Borrower Updated');window.location.replace('borrower.php');</script>";
    } else {
        echo "<script>alert('Error updating borrower');</script>";
    }
}
$sql = "SELECT * FROM borrower";
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
            <h2 class="center-header">Borrower List</h2>

            <button type="button" class="button-login1" id="openCreateModal">Add Borrower</button>
            <table class="book-table" width="100%" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>Borrower ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Course</th>
                        <th>Section</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php

                $sql = "SELECT * FROM borrower";
                $result = mysqli_query($conn, $sql);

                if (!$result) {
                    echo "<tr><td colspan='7'>Error loading borrowers.</td></tr>";
                }

                if (mysqli_num_rows($result) > 0) {

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo "<tr>";
                        echo "<td>{$row['Borrower_ID']}</td>";
                        echo "<td>{$row['Name']}</td>";
                        echo "<td>{$row['Contact']}</td>";
                        echo "<td>{$row['Address']}</td>";
                        echo "<td>{$row['Course']}</td>";
                        echo "<td>{$row['Section']}</td>";

                        echo "<td>
                                <button type='button' class='button3 open-modal'
                                    data-modal='editModal'
                                    data-id='{$row['Borrower_ID']}'
                                    data-name='{$row['Name']}'
                                    data-contact='{$row['Contact']}'
                                    data-address='{$row['Address']}'
                                    data-course='{$row['Course']}'
                                    data-section='{$row['Section']}'
                                >Edit</button>
                            </td>";

                        echo "</tr>";
                    }
                } 
                else {
                    echo "<tr><td colspan='6'>No borrowers found.</td></tr>";
                }

                mysqli_close($conn);
                ?>
                </tbody>
            </table>

                <div class="modal" id="editModal">
                    <div class="modal-inner">
                        <span class="close-btn">&times;</span>
                        <h2>Edit Information</h2>

                        <form method="POST" action="borrower.php">
                            <p>Borrower ID</p>
                            <input type="text" class="input-form" id="editBorrower_ID" name="Borrower_ID" readonly>

                            <p>Name</p>
                            <input type="text" class="input-form" id="editName" name="Name">

                            <p>Contact</p>
                            <input type="tel" class="input-form" id="editContact" name="Contact" pattern="[0-9]{11}">
                            <!-- <input type="text" class="input-form" id="editContact" name="Contact"> -->

                            <p>Address</p>
                            <input type="text" class="input-form" id="editAddress" name="Address">

                            <p>Course</p>
                            <input type="text" class="input-form" id="editCourse" name="Course">

                            <p>Section</p>
                            <input type="text" class="input-form" id="editSection" name="Section">
                            <br>
                            <button type="button" class="button5 close-modal-btn">Cancel</button>
                            <button type="submit" name="edit_submit" class="button6">Save</button> 
                        </form>
                    </div>
                </div>

            <!-- Create pop-up form-->
                    <div class="modal" id="createModal">
                        <div class="modal-inner">
                            <span class="close-btn">&times;</span>
                            <h2>Create Borrower</h2>

                            <form method="POST" action="borrower.php">
                                <p>Borrower_ID</p>
                                <input type="text" class="input-form" id="editBorrower_ID" name="Borrower_ID">

                                <p>Name</p>
                                <input type="text" class="input-form" id="createName" name="Name">

                                <p>Contact</p>
                                <input type="tel" class="input-form" id="createContact" name="Contact" pattern="[0-9]{11}">

                                <p>Address</p>
                                <input type="text" class="input-form" id="createAddress" name="Address">

                                <p>Course</p>
                                <input type="text" class="input-form" id="createCourse" name="Course">

                                <p>Section</p>
                                <input type="text" class="input-form" id="createSection" name="Section">
                                <br>
                                <button type="button" class="button5 close-modal-btn">Cancel</button>
                                <button type="submit" name="create_submit" class="button6">Save</button> 
                            </form>
                        </div>
                    </div>
            </section>
    </main>

    <script src="assets/js/script.js"></script>
    <script>
        // Populate data and Modals
        document.querySelectorAll('.open-modal').forEach(button => {
            button.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal');
                const modal = document.getElementById(modalId);
                if (modalId === 'editModal') {
                    document.getElementById('editBorrower_ID').value = this.getAttribute('data-id');
                    document.getElementById('editName').value = this.getAttribute('data-name');
                    document.getElementById('editContact').value = this.getAttribute('data-contact');
                    document.getElementById('editAddress').value = this.getAttribute('data-address');
                    document.getElementById('editCourse').value = this.getAttribute('data-course');
                    document.getElementById('editSection').value = this.getAttribute('data-section');
                } else if (modalId === 'createModal') {
                    // Clear form fields for create modal
                    document.querySelector('#createModal form').reset();
                }
            });
        });
        // Open Create Modal
        document.getElementById('openCreateModal').addEventListener('click', function() {
        document.getElementById('createModal').style.display = 'block';
        document.querySelector('#createModal form').reset(); // Clear fields
        });
    </script>
</body>
</html>