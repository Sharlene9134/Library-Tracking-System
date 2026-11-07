<?php
session_start();
$isAdmin = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Admin');
include 'conn.php';

// Function to safely escape output
function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// CREATE Borrower
if (isset($_POST['create_submit'])) {
    $Name = mysqli_real_escape_string($conn, $_POST['Name']);
    $Contact = mysqli_real_escape_string($conn, $_POST['Contact']);
    $Address = mysqli_real_escape_string($conn, $_POST['Address']);
    $Course = mysqli_real_escape_string($conn, $_POST['Course']);
    $Section = mysqli_real_escape_string($conn, $_POST['Section']);

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
    $Borrower_ID = mysqli_real_escape_string($conn, $_POST['Borrower_ID']);
    $Name = mysqli_real_escape_string($conn, $_POST['Name']);
    $Contact = mysqli_real_escape_string($conn, $_POST['Contact']);
    $Address = mysqli_real_escape_string($conn, $_POST['Address']);
    $Course = mysqli_real_escape_string($conn, $_POST['Course']);
    $Section = mysqli_real_escape_string($conn, $_POST['Section']);

    $sql = "UPDATE borrower SET Name='$Name', Contact='$Contact', Address='$Address', Course='$Course', Section='$Section' 
            WHERE Borrower_ID='$Borrower_ID'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Borrower Updated');window.location.replace('borrower.php');</script>";
    } else {
        echo "<script>alert('Error updating borrower');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrower List | Library Tracking System</title>
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
                <a href="books.php">Book Status</a>
                <a href="borrower.php" class="active">Borrower</a>
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
            <h2 class="center-header">Borrower List</h2>

            <div class="toolbar">
                <button type="button" class="btn btn-primary" id="openCreateModal">+ Add Borrower</button>
            </div>

            <div class="table-wrapper">
                <table class="book-table">
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
                        echo "<tr><td colspan='7' class='empty'>Error loading borrowers.</td></tr>";
                    } elseif (mysqli_num_rows($result) === 0) {
                        echo "<tr><td colspan='7' class='empty'>No borrowers found.</td></tr>";
                    } else {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . e($row['Borrower_ID']) . "</td>";
                            echo "<td>" . e($row['Name']) . "</td>";
                            echo "<td>" . e($row['Contact']) . "</td>";
                            echo "<td>" . e($row['Address']) . "</td>";
                            echo "<td>" . e($row['Course']) . "</td>";
                            echo "<td>" . e($row['Section']) . "</td>";
                            echo "<td>
                                    <button type='button' class='btn btn-ghost btn-sm open-modal'
                                        data-modal='editModal'
                                        data-id='" . e($row['Borrower_ID']) . "'
                                        data-name='" . e($row['Name']) . "'
                                        data-contact='" . e($row['Contact']) . "'
                                        data-address='" . e($row['Address']) . "'
                                        data-course='" . e($row['Course']) . "'
                                        data-section='" . e($row['Section']) . "'
                                    >Edit</button>
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
                    <h2>Edit Borrower Information</h2>

                    <form method="POST" action="borrower.php">
                        <p>Borrower ID</p>
                        <input type="text" class="input-form" id="editBorrower_ID" name="Borrower_ID" readonly>

                        <p>Name</p>
                        <input type="text" class="input-form" id="editName" name="Name" required>

                        <p>Contact</p>
                        <input type="tel" class="input-form" id="editContact" name="Contact" pattern="[0-9]{11}" required>

                        <p>Address</p>
                        <input type="text" class="input-form" id="editAddress" name="Address" required>

                        <p>Course</p>
                        <input type="text" class="input-form" id="editCourse" name="Course" required>

                        <p>Section</p>
                        <input type="text" class="input-form" id="editSection" name="Section" required>

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
                    <h2>Add New Borrower</h2>

                    <form method="POST" action="borrower.php" id="createForm">
                        <p>Name</p>
                        <input type="text" class="input-form" name="Name" required>

                        <p>Contact</p>
                        <input type="tel" class="input-form" name="Contact" pattern="[0-9]{11}" required>

                        <p>Address</p>
                        <input type="text" class="input-form" name="Address" required>

                        <p>Course</p>
                        <input type="text" class="input-form" name="Course" required>

                        <p>Section</p>
                        <input type="text" class="input-form" name="Section" required>

                        <div class="modal-actions">
                            <button type="button" class="btn btn-ghost" data-close>Cancel</button>
                            <button type="submit" name="create_submit" class="btn btn-primary">Save Borrower</button>
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

                setVal('editBorrower_ID', d.id);
                setVal('editName',        d.name);
                setVal('editContact',     d.contact);
                setVal('editAddress',     d.address);
                setVal('editCourse',      d.course);
                setVal('editSection',     d.section);

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