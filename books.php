<?php
session_start();
$isAdmin = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Admin');
include 'conn.php';

// Function to safely escape output
function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// CREATE Book
if (isset($_POST['create_submit'])) {
    $Title = mysqli_real_escape_string($conn, $_POST['Title']);
    $Category = mysqli_real_escape_string($conn, $_POST['Category']);
    $Author = mysqli_real_escape_string($conn, $_POST['Author']);
    $Status = mysqli_real_escape_string($conn, $_POST['Status']);

    $sql = "INSERT INTO book (Title, Category, Author, Status) 
            VALUES ('$Title', '$Category', '$Author', '$Status')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('New Book Added');window.location.replace('books.php');</script>";
    } else {
        echo "<script>alert('Error adding book');</script>";
    }
}

// EDIT Book
if (isset($_POST['edit_submit'])) {
    $Book_ID = mysqli_real_escape_string($conn, $_POST['Book_ID']);
    $Title = mysqli_real_escape_string($conn, $_POST['Title']);
    $Category = mysqli_real_escape_string($conn, $_POST['Category']);
    $Author = mysqli_real_escape_string($conn, $_POST['Author']);
    $Status = mysqli_real_escape_string($conn, $_POST['Status']);

    $sql = "UPDATE book SET Title='$Title', Category='$Category', Author='$Author', Status='$Status' 
            WHERE Book_ID='$Book_ID'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Book Updated');window.location.replace('books.php');</script>";
    } else {
        echo "<script>alert('Error updating book');</script>";
    }
}

// DELETE Book
if (isset($_POST['delete_submit'])) {
    $Book_ID = mysqli_real_escape_string($conn, $_POST['Book_ID']);

    $sql = "DELETE FROM book WHERE Book_ID='$Book_ID'";

    try {
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Book Deleted');window.location.replace('books.php');</script>";
        } else {
            echo "<script>alert('Error deleting book');</script>";
        }
    } catch (mysqli_sql_exception $ex) {
        // Typically a foreign key constraint (book is referenced by transactions)
        echo "<script>alert('Cannot delete this book because it is linked to existing transactions.');window.location.replace('books.php');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book List | Library Tracking System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .center-header{ 
            text-align: right; 
            color: #111827;
            margin: 0 0 16px;
        }

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
            grid-template-rows: auto auto 1fr; 
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

        /* Toolbar & Buttons */
        .toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        .btn { 
            border: 0; 
            cursor: pointer; 
            padding: 9px 14px; 
            margin: 5px;
            border-radius: 6px;
            font-size: .9rem; 
            font-weight: 600; 
            transition: .15s; 
            text-decoration: none;
        }

        .btn-primary { 
            background: var(--primary); 
            color: #fff; 
        }

        .btn-primary:hover { 
            background: var(--primary-d); 
        }

        .btn-ghost { 
            background: #e5e7eb; 
            color: #111827; 
        }

        .btn-ghost:hover { 
            background: #d1d5db; 
        }

        .btn-danger { 
            background: var(--danger); 
            color: #fff; 
        }

        .btn-danger:hover { 
            filter: brightness(.92); 
        }

        .btn-sm { 
            padding: 6px 10px; 
            font-size: .82rem; 
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

        /* Badges for Status */
        .badge { 
            display: inline-block; 
            padding: 4px 10px; 
            border-radius: 999px;
            font-size: .72rem; 
            font-weight: 700; 
        }
        .badge-available {
            background: #dcfce7;
            color: #166534;
        }
        .badge-borrowed {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Modal Styling */
        .modal{ 
            display:none; 
            position:fixed; 
            inset:0; 
            z-index:1000;
            background:rgba(0,0,0,.55); 
            overflow:auto; 
        }
        .modal.open{ display:block; }
        .modal-inner{ 
            background:#fff; 
            margin:5vh auto; 
            padding:24px;
            border-radius:12px; 
            width:92%; 
            max-width:560px;
            max-height:85vh; 
            overflow-y:auto;
            box-shadow:0 20px 50px rgba(0,0,0,.3); 
            position:relative; 
        }
        .modal-inner h2{ margin:0 0 16px; text-align:center; }
        .modal-inner p{ 
            margin:12px 0 4px; 
            font-size:.85rem; 
            color:var(--muted);
            font-weight:600; 
            text-transform:uppercase; 
            letter-spacing:.4px; 
        }
        .input-form{ 
            width:100%; 
            padding:9px 11px; 
            border:1px solid var(--border);
            border-radius:6px; 
            font-size:.92rem; 
            background:#fff; 
        }
        .input-form:focus{ 
            outline:2px solid #93c5fd; 
            border-color:var(--primary); 
        }
        .modal-actions{ 
            display:flex; 
            justify-content:flex-end; 
            gap:8px; 
            margin-top:18px; 
        }
        .close-btn { 
            position:absolute; 
            top:15px; 
            right:20px; 
            font-size:24px; 
            cursor:pointer; 
            color:#6b7280; 
        }
        .close-btn:hover { color:#111827; }

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
                <a href="books.php" class="active">Book Status</a>
                <a href="borrower.php">Borrower</a>
                <a href="staff.php">Staff</a>
                <a href="reports.php">Report</a>
                <a href="archives.php">Archive</a>
                <?php if ($isAdmin): ?>
                    <a href="admindisplay.php">User Management</a>
                <?php endif; ?>
            </nav>
        </aside> 

        <!-- Main Content -->
        <section class="content">
            <h2 class="center-header">Book List</h2>

            <div class="toolbar">
                <button type="button" class="btn btn-primary" id="openCreateModal">+ Add Book</button>
            </div>

            <div class="table-wrapper">
                <table class="book-table">
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
                        echo "<tr><td colspan='6' class='empty'>Error loading books.</td></tr>";
                    } elseif (mysqli_num_rows($result) === 0) {
                        echo "<tr><td colspan='6' class='empty'>No books found.</td></tr>";
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Determine badge color based on status
                            $badgeClass = ($row['Status'] === 'Available') ? 'badge-available' : 'badge-borrowed';
                            
                            echo "<tr>";
                            echo "<td>" . e($row['Book_ID']) . "</td>";
                            echo "<td>" . e($row['Title']) . "</td>";
                            echo "<td>" . e($row['Category']) . "</td>";
                            echo "<td>" . e($row['Author']) . "</td>";
                            echo "<td><span class='badge {$badgeClass}'>" . e($row['Status']) . "</span></td>";
                            echo "<td>
                                    <button type='button' class='btn btn-ghost btn-sm open-modal'
                                        data-modal='editModal'
                                        data-id='" . e($row['Book_ID']) . "'
                                        data-title='" . e($row['Title']) . "'
                                        data-category='" . e($row['Category']) . "'
                                        data-author='" . e($row['Author']) . "'
                                        data-status='" . e($row['Status']) . "'
                                    >Edit</button>
                                    <form method='POST' action='books.php' style='display:inline;'
                                        onsubmit=\"return confirm('Are you sure you want to delete this book? This cannot be undone.');\">
                                        <input type='hidden' name='Book_ID' value='" . e($row['Book_ID']) . "'>
                                        <button type='submit' name='delete_submit' class='btn btn-danger btn-sm'>Delete</button>
                                    </form>
                                  </td>";
                            echo "</tr>";
                        }
                    }
                    mysqli_close($conn);
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- ========== EDIT MODAL ========== -->
            <div class="modal" id="editModal">
                <div class="modal-inner">
                    <span class="close-btn" data-close>&times;</span>
                    <h2>Edit Book Information</h2>

                    <form method="POST" action="books.php">
                        <p>Book ID</p>
                        <input type="text" class="input-form" id="editBook_ID" name="Book_ID" readonly>
                        
                        <p>Title</p>
                        <input type="text" class="input-form" id="editTitle" name="Title" required>

                        <p>Category</p>
                        <input type="text" class="input-form" id="editCategory" name="Category" required>

                        <p>Author</p>
                        <input type="text" class="input-form" id="editAuthor" name="Author" required>

                        <p>Status</p>
                        <select class="input-form" id="editStatus" name="Status" required>
                            <option value="Available">Available</option>
                            <option value="Borrowed">Borrowed</option>
                        </select>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-ghost" data-close>Cancel</button>
                            <button type="submit" name="edit_submit" class="btn btn-primary">Save Changes</button> 
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========== CREATE MODAL ========== -->
            <div class="modal" id="createModal">
                <div class="modal-inner">
                    <span class="close-btn" data-close>&times;</span>
                    <h2>Add New Book</h2>

                    <form method="POST" action="books.php" id="createForm">
                        <p>Title</p>
                        <input type="text" class="input-form" name="Title" required>

                        <p>Category</p>
                        <input type="text" class="input-form" name="Category" required>

                        <p>Author</p>
                        <input type="text" class="input-form" name="Author" required>

                        <p>Status</p>
                        <select class="input-form" name="Status" required>
                            <option value="Available">Available</option>
                            <option value="Borrowed">Borrowed</option>
                        </select>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-ghost" data-close>Cancel</button>
                            <button type="submit" name="create_submit" class="btn btn-primary">Save Book</button>
                        </div>
                    </form>
                </div>
            </div>

        </section>
    </main>

    <script>
        // Modal open/close helpers
        const openModal  = (id) => document.getElementById(id).classList.add('open');
        const closeModal = (modal) => modal.classList.remove('open');

        // Close on X / Cancel buttons / backdrop
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', e => {
                if (e.target === modal) closeModal(modal);
            });
            modal.querySelectorAll('[data-close]').forEach(btn =>
                btn.addEventListener('click', () => closeModal(modal))
            );
        });

        // ESC to close
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.open').forEach(closeModal);
            }
        });

        // Open EDIT with row data
        document.querySelectorAll('.open-modal').forEach(btn => {
            btn.addEventListener('click', function () {
                const d = this.dataset;
                const modal = document.getElementById(d.modal);
                if (!modal) return;

                const setVal = (id, val) => {
                    const el = document.getElementById(id);
                    if (el) el.value = val || '';
                };

                setVal('editBook_ID',  d.id);
                setVal('editTitle',    d.title);
                setVal('editCategory', d.category);
                setVal('editAuthor',   d.author);
                setVal('editStatus',   d.status);

                openModal(d.modal);
            });
        });

        // Open CREATE modal
        const createBtn = document.getElementById('openCreateModal');
        if (createBtn) {
            createBtn.addEventListener('click', function () {
                const form = document.getElementById('createForm');
                if (form) form.reset();
                openModal('createModal');
            });
        }
    </script>
</body>
</html>