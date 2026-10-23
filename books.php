<?php
session_start();
$isAdmin = ($_SESSION['usertype'] === 'Admin');
include 'conn.php';

// CREATE Book
if (isset($_POST['create_submit'])) {
    $Title = $_POST['Title'];
    $Category = $_POST['Category'];
    $Author = $_POST['Author'];
    $Status = $_POST['Status'];

    $sql = "INSERT INTO book (Title, Category, Author, Status) 
            VALUES ( '$Title', '$Category', '$Author', '$Status')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('New Book Added');window.location.replace('books.php');</script>";
    } else {
        echo "<script>alert('Error adding book');</script>";
    }
}

// EDIT Book
if (isset($_POST['edit_submit'])) {
    $Book_ID = $_POST['Book_ID'];
    $Title = $_POST['Title'];
    $Category = $_POST['Category'];
    $Author = $_POST['Author'];
    $Status = $_POST['Status'];

    $sql = "UPDATE book SET Title='$Title', Category='$Category', Author='$Author', Status='$Status' 
            WHERE Book_ID='$Book_ID'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Book Updated');window.location.replace('books.php');</script>";
    } else {
        echo "<script>alert('Error updating book');</script>";
    }
}
$sql = "SELECT * FROM book";
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
            <h2 class="center-header">Book List</h2>

            <button type="button" class="button-login1" id="openCreateModal">Add Book</button>
            <table class="book-table" width="100%" cellpadding="10" cellspacing="0">
                <thead>
                    <tr>
                        <th>Book ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php

                $sql = "SELECT * FROM book";
                $result = mysqli_query($conn, $sql);

                if (!$result) {
                    echo "<tr><td colspan='6'>Error loading books.</td></tr>";
                }

                if (mysqli_num_rows($result) > 0) {

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo "<tr>";
                        echo "<td>{$row['Book_ID']}</td>";
                        echo "<td>{$row['Title']}</td>";
                        echo "<td>{$row['Category']}</td>";
                        echo "<td>{$row['Author']}</td>";
                        echo "<td>{$row['Status']}</td>";

                        echo "<td>
                                <button type='button' class='button3 open-modal'
                                    data-modal='editModal'
                                    data-id='{$row['Book_ID']}'
                                    data-title='{$row['Title']}'
                                    data-category='{$row['Category']}'
                                    data-author='{$row['Author']}'
                                    data-status='{$row['Status']}'
                                >Edit</button>
                            </td>";

                        echo "</tr>";
                    }
                } 
                else {
                    echo "<tr><td colspan='6'>No books found.</td></tr>";
                }

                mysqli_close($conn);
                ?>
                </tbody>
            </table>
                <div class="modal" id="editModal">
                    <div class="modal-inner">
                        <span class="close-btn">&times;</span>
                        <h2>Edit Information</h2>

                        <form method="POST" action="books.php">
                            <p>Book ID</p>
                            <input type="text" class="input-form" id="editBook_ID" name="Book_ID" readonly>
                            
                            <p>Title</p>
                            <input type="text" class="input-form" id="editTitle" name="Title">

                            <p>Category</p>
                            <input type="text" class="input-form" id="editCategory" name="Category">

                            <p>Author</p>
                            <input type="text" class="input-form" id="editAuthor" name="Author">

                            <p>Status</p>
                            <select class="input-form" id="editStatus" name="Status" required>
                                <option value="">-- Select Status --</option>
								<option value="Available">Available</option>
								<option value="Borrowed">Borrowed</option>
                            </select>

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
                        <h2>Add Book</h2>

                        <form method="POST" action="books.php">

                            <p>Title</p>
                            <input type="text" class="input-form" name="Title">

                            <p>Category</p>
                            <input type="text" class="input-form" name="Category">

                            <p>Author</p>
                            <input type="text" class="input-form" name="Author">

                            <p>Status</p>
                            <input type="text" class="input-form" name="Status">

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
                    document.getElementById('editBook_ID').value = this.getAttribute('data-id');
                    document.getElementById('editTitle').value = this.getAttribute('data-title');
                    document.getElementById('editCategory').value = this.getAttribute('data-category');
                    document.getElementById('editAuthor').value = this.getAttribute('data-author');
                    document.getElementById('editStatus').value = this.getAttribute('data-status');
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