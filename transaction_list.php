<?php
session_start();

// ---- Auth guard (uncomment & adjust for your system) ----
// if (!isset($_SESSION['usertype'])) { header('Location: login2.php'); exit; }

$isAdmin = (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'Admin');
include 'conn.php';

// ---- CSRF token ----
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

function csrf_ok(): bool {
    return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function redirect_alert(string $msg, string $url = 'transaction_list.php'): void {
    echo "<script>alert(" . json_encode($msg) . ");window.location.replace(" . json_encode($url) . ");</script>";
    exit;
}

function valid_dates(?string $borrowed, ?string $due, ?string $returned = null): ?string {
    if ($borrowed && $due && $due < $borrowed) return 'Due date cannot be earlier than borrowed date.';
    if ($borrowed && $returned && $returned < $borrowed) return 'Returned date cannot be earlier than borrowed date.';
    return null;
}

// ---- Fetch data ----
$availableBooksArray = [];
$r = mysqli_query($conn, "SELECT Book_ID, Title FROM book WHERE Status='Available'");
while ($b = mysqli_fetch_assoc($r)) $availableBooksArray[] = $b;

$allBooksArray = [];
$r = mysqli_query($conn, "SELECT Book_ID, Title, Status FROM book");
while ($b = mysqli_fetch_assoc($r)) $allBooksArray[] = $b;

$borrowersArray = [];
$r = mysqli_query($conn, "SELECT Borrower_ID, Name FROM borrower");
while ($b = mysqli_fetch_assoc($r)) $borrowersArray[] = $b;

$staffsArray = [];
$r = mysqli_query($conn, "SELECT Staff_ID, Name FROM staff WHERE status='Active'");
while ($s = mysqli_fetch_assoc($r)) $staffsArray[] = $s;

// =========================================================
// CREATE
// =========================================================
if (isset($_POST['create_submit'])) {
    if (!csrf_ok()) redirect_alert('Invalid session. Please retry.');

    $Book_ID            = mysqli_real_escape_string($conn, $_POST['Book_ID'] ?? '');
    $Borrower_ID        = mysqli_real_escape_string($conn, $_POST['Borrower_ID'] ?? '');
    $Borrowed_date      = mysqli_real_escape_string($conn, $_POST['Borrowed_date'] ?? '');
    $Time_Borrowed      = mysqli_real_escape_string($conn, $_POST['Time_Borrowed'] ?? '');
    $Borrowed_Staff_ID  = mysqli_real_escape_string($conn, $_POST['Borrowed_Staff_ID'] ?? '');
    $Due_date           = mysqli_real_escape_string($conn, $_POST['Due_date'] ?? '');

    if ($err = valid_dates($Borrowed_date, $Due_date)) redirect_alert($err);

    if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM book WHERE Book_ID='$Book_ID'")) == 0)
        redirect_alert('Error: Book ID does not exist.');
    if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM borrower WHERE Borrower_ID='$Borrower_ID'")) == 0)
        redirect_alert('Error: Borrower ID does not exist.');
    if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM staff WHERE Staff_ID='$Borrowed_Staff_ID'")) == 0)
        redirect_alert('Error: Borrowed Staff ID does not exist.');

    $statusRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT Status FROM book WHERE Book_ID='$Book_ID'"));
    if (!$statusRow || $statusRow['Status'] !== 'Available') redirect_alert('Book is not available.');

    $sql = "INSERT INTO transaction
            (Book_ID, Borrower_ID, Borrowed_date, Time_Borrowed, Borrowed_Staff_ID, Due_date, Status)
            VALUES ('$Book_ID','$Borrower_ID','$Borrowed_date','$Time_Borrowed','$Borrowed_Staff_ID','$Due_date','Ongoing')";

    if (mysqli_query($conn, $sql)) {
        mysqli_query($conn, "UPDATE book SET Status='Borrowed' WHERE Book_ID='$Book_ID'");
        redirect_alert('New Transaction Added');
    } else {
        redirect_alert('Error adding transaction: ' . mysqli_error($conn));
    }
}

// =========================================================
// EDIT
// =========================================================
if (isset($_POST['edit_submit'])) {
    if (!csrf_ok()) redirect_alert('Invalid session. Please retry.');

    $Transaction_ID     = mysqli_real_escape_string($conn, $_POST['Transaction_ID'] ?? '');
    $Book_ID            = mysqli_real_escape_string($conn, $_POST['Book_ID'] ?? '');
    $Borrower_ID        = mysqli_real_escape_string($conn, $_POST['Borrower_ID'] ?? '');
    $Borrowed_date      = mysqli_real_escape_string($conn, $_POST['Borrowed_date'] ?? '');
    $Time_Borrowed      = mysqli_real_escape_string($conn, $_POST['Time_Borrowed'] ?? '');
    $Borrowed_Staff_ID  = mysqli_real_escape_string($conn, $_POST['Borrowed_Staff_ID'] ?? '');
    $Due_date           = mysqli_real_escape_string($conn, $_POST['Due_date'] ?? '');
    $Returned_date      = !empty($_POST['Returned_date'])      ? "'".mysqli_real_escape_string($conn,$_POST['Returned_date'])."'"      : "NULL";
    $Time_Returned      = !empty($_POST['Time_Returned'])      ? "'".mysqli_real_escape_string($conn,$_POST['Time_Returned'])."'"      : "NULL";
    $Returned_Staff_ID  = !empty($_POST['Returned_Staff_ID'])  ? "'".mysqli_real_escape_string($conn,$_POST['Returned_Staff_ID'])."'"  : "NULL";

    if ($err = valid_dates($Borrowed_date, $Due_date, $_POST['Returned_date'] ?? null)) redirect_alert($err);

    $old = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Book_ID, Status FROM transaction WHERE Transaction_ID='$Transaction_ID'"));
    if (!$old) redirect_alert('Transaction not found.');
    if ($old['Status'] !== 'Ongoing') redirect_alert('Only ongoing transactions can be edited.');
    $oldBookID = $old['Book_ID'];

    if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM book WHERE Book_ID='$Book_ID'")) == 0)
        redirect_alert('Error: Book ID does not exist.');
    if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM borrower WHERE Borrower_ID='$Borrower_ID'")) == 0)
        redirect_alert('Error: Borrower ID does not exist.');
    if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM staff WHERE Staff_ID='$Borrowed_Staff_ID'")) == 0)
        redirect_alert('Error: Borrowed Staff ID does not exist.');

    if ($oldBookID !== $Book_ID) {
        $st = mysqli_fetch_assoc(mysqli_query($conn, "SELECT Status FROM book WHERE Book_ID='$Book_ID'"));
        if (!$st || $st['Status'] !== 'Available') redirect_alert('Selected new book is not available.');
    }

    if (!empty($_POST['Returned_Staff_ID'])) {
        $rs = mysqli_real_escape_string($conn, $_POST['Returned_Staff_ID']);
        if (mysqli_num_rows(mysqli_query($conn, "SELECT 1 FROM staff WHERE Staff_ID='$rs'")) == 0)
            redirect_alert('Error: Returned Staff ID does not exist.');
    }

    $sql = "UPDATE transaction SET
                Book_ID='$Book_ID',
                Borrower_ID='$Borrower_ID',
                Borrowed_date='$Borrowed_date',
                Time_Borrowed='$Time_Borrowed',
                Borrowed_Staff_ID='$Borrowed_Staff_ID',
                Due_date='$Due_date',
                Returned_date=$Returned_date,
                Time_Returned=$Time_Returned,
                Returned_Staff_ID=$Returned_Staff_ID
            WHERE Transaction_ID='$Transaction_ID'";

    if (mysqli_query($conn, $sql)) {
        if ($oldBookID !== $Book_ID) {
            mysqli_query($conn, "UPDATE book SET Status='Available' WHERE Book_ID='$oldBookID'");
            mysqli_query($conn, "UPDATE book SET Status='Borrowed'  WHERE Book_ID='$Book_ID'");
        }
        redirect_alert('Transaction Updated');
    } else {
        redirect_alert('Error updating transaction: ' . mysqli_error($conn));
    }
}

// =========================================================
// SUBMIT (mark completed)
// =========================================================
if (isset($_POST['submit_transaction_id'])) {
    if (!csrf_ok()) redirect_alert('Invalid session. Please retry.');
    $Transaction_ID = mysqli_real_escape_string($conn, $_POST['submit_transaction_id']);

    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Returned_date, Time_Returned, Returned_Staff_ID
         FROM transaction WHERE Transaction_ID='$Transaction_ID'"));

    if (!$row || empty($row['Returned_date']) || empty($row['Time_Returned']) || empty($row['Returned_Staff_ID'])) {
        redirect_alert('Cannot submit. Returned Date, Time Returned, and Returned Staff ID are REQUIRED. Please edit the transaction first.');
    }

    mysqli_query($conn, "UPDATE transaction SET Status='Completed' WHERE Transaction_ID='$Transaction_ID'");
    $bookRow = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Book_ID FROM transaction WHERE Transaction_ID='$Transaction_ID'"));
    if ($bookRow) {
        mysqli_query($conn, "UPDATE book SET Status='Available' WHERE Book_ID='{$bookRow['Book_ID']}'");
    }
    redirect_alert('Transaction marked as COMPLETED.');
}

// =========================================================
// CANCEL
// =========================================================
if (isset($_POST['cancel_transaction_id'])) {
    if (!csrf_ok()) redirect_alert('Invalid session. Please retry.');
    $Transaction_ID = mysqli_real_escape_string($conn, $_POST['cancel_transaction_id']);

    mysqli_query($conn, "UPDATE transaction SET Status='Cancelled' WHERE Transaction_ID='$Transaction_ID'");
    $bookRow = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT Book_ID FROM transaction WHERE Transaction_ID='$Transaction_ID'"));
    if ($bookRow) {
        mysqli_query($conn, "UPDATE book SET Status='Available' WHERE Book_ID='{$bookRow['Book_ID']}'");
    }
    redirect_alert('Transaction has been CANCELLED.');
}

// ---- Load ongoing transactions ----
$result = mysqli_query($conn, "SELECT * FROM transaction WHERE Status='Ongoing' ORDER BY Transaction_ID DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Tracker System</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    :root{
        background-color:#f4f6f9; 
        --panel:#fff; 
        --ink:#1f2a37; 
        --muted:#6b7280;
        --primary:#2563eb; 
        --primary-d:#1d4ed8; 
        --danger:#dc2626;
        --success:#16a34a; 
        --warn:#d97706; 
        --border:#e5e7eb;
    }

    *{
        box-sizing:border-box;
    }
    
    body{ 
        margin:0; 
        height:100vh; 
        overflow:hidden;
        font-family:"Segoe UI",Roboto,Arial,sans-serif;
        background:var(--bg); 
        color:var(--ink); 
        display:flex;
        flex-direction:column; 
    }

    .header{
        background: #064e3b;
        color: #fff; 
        padding:18px 24px; 
        display:flex; 
        align-items:center;
        justify-content:space-between; 
        box-shadow:0 2px 8px rgba(0,0,0,.15);
        flex-shrink: 0;
    }

    .header h1{ 
        margin:0; 
        font-size:1.4rem; 
        letter-spacing:.5px; 
    }

    .navigation{ 
        background: #064e3b; 
        padding: 10px 20px 15px 5px; 
        text-align:right; 
        flex-shrink: 0;
    }

    .navigation a{ 
        color: #fff;
        background: transparent;
        text-decoration: none; 
        font-size:.9rem;
        padding: 4px 16px;
        border-radius: 6px; 
        border: 1px solid #fff;
    }

    .navigation a:hover{ 
        color:#fff; 
        text-decoration:underline; 
    }

    /* Main */
    .main{ 
        display:flex; 
        flex: 1;
        min-height: 0; 
        gap: 0px; 
        padding: 0px; 
    }

    /* Sidebar design */
    .side-menu{ 
        width:210px; 
        background: #1e3a2f; 
        padding:14px; 
        overflow-y: auto; 
        flex-shrink: 0;
    }

    .side-menu .nav{ 
        display:flex; 
        flex-direction:column; 
        gap:4px; 
    }

    .side-menu .nav a{
        color:#cbd5e1; 
        text-decoration:none; 
        padding:9px 12px;
        border-radius:6px; 
        font-size:.92rem; 
        transition:.15s;
    }

    .side-menu .nav a:hover{ 
        background:#374151; 
        color:#fff; 
    }

    .content{ 
        flex:1; 
        background:var(--panel); 
        border:1px solid var(--border);
        border-radius:10px; 
        padding:20px; 
        box-shadow:0 1px 3px rgba(0,0,0,.05);
        display:flex; 
        flex-direction:column; 
        min-width:0; 
        background-color: #f0f4f1;
        overflow-y: auto; 
    }

    .center-header{ 
        text-align:center; 
        margin:0 0 16px; 
        color:#111827; 
    }

    .toolbar{ 
        display:flex; 
        justify-content:flex-end; 
        margin-bottom:12px; 
    }

    .btn{ 
        border:0; 
        cursor:pointer; 
        padding:9px 14px; 
        border-radius:6px;
        font-size:.9rem; 
        font-weight:600; 
        transition:.15s; 
    }

    .btn-primary{ 
        background: #059669; 
        color:#fff; 
    }

    .btn-primary:hover{ 
        background:var(--primary-d); 
    }

    .btn-warn{ 
        background:var(--warn); 
        color:#fff; 
    }

    .btn-warn:hover{ 
        filter:brightness(.92); 
    }
    
    .btn-danger{ 
        background:var(--danger); 
        color:#fff; 
    }

    .btn-danger:hover{ 
        filter:brightness(.92); 
    }

    .btn-ghost{ 
        background:#e5e7eb; 
        color:#111827; 
    }

    .btn-ghost:hover{ 
        background:#d1d5db; 
    }

    .btn-sm{ 
        padding:6px 10px; 
        font-size:.82rem; 
    }

    .table-wrapper{ 
        overflow-x:auto; 
        border-radius:8px; 
        border: 1px solid #d1e0d6; 
    }

    .book-table{ 
        width:100%; 
        border-collapse:collapse; 
        min-width:1050px; 
        font-size:.88rem; 
    }

    .book-table thead th{
        background: #e8f0ea; 
        color:#111827; 
        text-align: center;
        padding:10px 10px; 
        border-bottom:2px solid var(--border);
        font-size:.8rem; 
        text-transform:uppercase; 
        letter-spacing:.4px;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .book-table tbody td{ 
        padding:9px 10px; 
        border-bottom:1px solid var(--border); 
    }

    .book-table tbody tr:hover{ 
        background: #f7faf8; 
    }

    .book-table tbody tr:last-child td{ 
        border-bottom:0; 
    }

    .badge{ 
        display:inline-block; 
        padding:3px 8px; 
        border-radius:999px;
        font-size:.72rem; 
        font-weight:700; 
    }

    .badge-ongoing{ 
        background:#dbeafe; 
        color:#1e40af; 
    }

    .badge-overdue{ 
        background:#fee2e2; 
        color:#991b1b; 
    }

    .action-buttons{ 
        display:flex; gap:6px; 
        flex-wrap:wrap; 
        align-items:center; 
    }

    .inline-form{ 
        margin:0; 
        display:inline; 
    }

    /* Modal */
    .modal{ 
        display:none; 
        position:fixed; 
        inset:0; 
        z-index:1000;
        background:rgba(0,0,0,.55); 
        overflow:auto; 
    }

    .modal.open{ 
        display:block; 
    }

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

    .modal-inner h2{ 
        margin:0 0 16px; 
        text-align:center; 
    }

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

    .close-btn:hover { 
        color:#111827; 
    }

    .empty{ 
        text-align:center; 
        padding:30px; 
        color:var(--muted); 
        font-style:italic; 
    }

    /* For mobile */
    @media(max-width:768px){
        body {
            height: auto;
            overflow: auto;
        }
        .main{ 
            flex-direction:column; 
        }
        .side-menu{ 
            width:100%; 
            overflow-y: visible;
        }
        .content {
            overflow-y: visible; 
        }
    }
</style>
</head>
<body>

<header class="header">
    <h1>📚 Library Tracking System</h1>
</header>

<nav class="navigation">
    <a href="login2.php">Log Out</a>
</nav>

<main class="main">
    <aside class="side-menu">
        <nav class="nav">
            <a href="dashboard.php">Dashboard</a>
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
        <h2 class="center-header">Transactions</h2>

        <div class="toolbar">
            <button type="button" class="btn btn-primary" id="openCreateModal">+ Add Transaction</button>
        </div>

        <div class="table-wrapper">
            <table class="book-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Book</th>
                        <th>Borrower</th>
                        <th>Borrowed Date</th>
                        <th>Time</th>
                        <th>Staff</th>
                        <th>Due Date</th>
                        <th>Returned</th>
                        <th>Time Returned</th>
                        <th>Return Staff</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$result): ?>
                    <tr><td colspan="11" class="empty">Error loading transactions: <?= e(mysqli_error($conn)) ?></td></tr>
                <?php elseif (mysqli_num_rows($result) === 0): ?>
                    <tr><td colspan="11" class="empty">No ongoing transactions found.</td></tr>
                <?php else: ?>
                    <?php while ($row = mysqli_fetch_assoc($result)):
                        $isOverdue = !empty($row['Due_date']) && date('Y-m-d') > $row['Due_date'];
                    ?>
                        <tr>
                            <td><?= e($row['Transaction_ID']) ?></td>
                            <td><?= e($row['Book_ID']) ?></td>
                            <td><?= e($row['Borrower_ID']) ?></td>
                            <td><?= e($row['Borrowed_date']) ?></td>
                            <td><?= e($row['Time_Borrowed']) ?></td>
                            <td><?= e($row['Borrowed_Staff_ID']) ?></td>
                            <td>
                                <?= e($row['Due_date']) ?>
                                <?php if ($isOverdue): ?>
                                    <span class="badge badge-overdue">OVERDUE</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($row['Returned_date'] ?: '—') ?></td>
                            <td><?= e($row['Time_Returned'] ?: '—') ?></td>
                            <td><?= e($row['Returned_Staff_ID'] ?: '—') ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-ghost btn-sm open-modal"
                                        data-modal="editModal"
                                        data-id="<?= e($row['Transaction_ID']) ?>"
                                        data-book_id="<?= e($row['Book_ID']) ?>"
                                        data-borrower_id="<?= e($row['Borrower_ID']) ?>"
                                        data-borrowed_date="<?= e($row['Borrowed_date']) ?>"
                                        data-time_borrowed="<?= e($row['Time_Borrowed']) ?>"
                                        data-borrowed_staff_id="<?= e($row['Borrowed_Staff_ID']) ?>"
                                        data-due_date="<?= e($row['Due_date']) ?>"
                                        data-returned_date="<?= e($row['Returned_date']) ?>"
                                        data-time_returned="<?= e($row['Time_Returned']) ?>"
                                        data-returned_staff_id="<?= e($row['Returned_Staff_ID']) ?>"
                                    >Edit</button>

                                    <form method="POST" class="inline-form"
                                          onsubmit="return confirm('Submit this transaction as COMPLETED?');">
                                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="submit_transaction_id" value="<?= e($row['Transaction_ID']) ?>">
                                        <button type="submit" class="btn btn-warn btn-sm">Submit</button>
                                    </form>

                                    <form method="POST" class="inline-form"
                                          onsubmit="return confirm('Are you sure you want to CANCEL this transaction?');">
                                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="cancel_transaction_id" value="<?= e($row['Transaction_ID']) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ========== EDIT MODAL ========== -->
        <div class="modal" id="editModal">
            <div class="modal-inner">
                <span class="close-btn" data-close>&times;</span>
                <h2>Edit Transaction</h2>
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

                    <p>Transaction ID</p>
                    <input type="text" class="input-form" id="editTransaction_ID" name="Transaction_ID" readonly>

                    <p>Book ID</p>
                    <select class="input-form" id="editBook_ID" name="Book_ID" required>
                        <option value="">-- Select Book --</option>
                        <?php foreach ($allBooksArray as $book): ?>
                            <option value="<?= e($book['Book_ID']) ?>">
                                <?= e($book['Book_ID']." - ".$book['Title']) ?>
                                <?= $book['Status'] !== 'Available' ? ' ('.$book['Status'].')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p>Borrower ID</p>
                    <select class="input-form" id="editBorrower_ID" name="Borrower_ID" required>
                        <option value="">-- Select Borrower --</option>
                        <?php foreach ($borrowersArray as $b): ?>
                            <option value="<?= e($b['Borrower_ID']) ?>"><?= e($b['Borrower_ID']." - ".$b['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <p>Borrowed Date</p>
                    <input type="date" class="input-form" id="editBorrowed_date" name="Borrowed_date" required>

                    <p>Time Borrowed</p>
                    <input type="time" class="input-form" id="editTime_Borrowed" name="Time_Borrowed" required>

                    <p>Borrowed Staff ID</p>
                    <select class="input-form" id="editBorrowed_Staff_ID" name="Borrowed_Staff_ID" required>
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staffsArray as $s): ?>
                            <option value="<?= e($s['Staff_ID']) ?>"><?= e($s['Staff_ID']." - ".$s['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <p>Due Date</p>
                    <input type="date" class="input-form" id="editDue_date" name="Due_date" required>

                    <hr style="border:none;border-top:1px dashed var(--border);margin:18px 0;">
                    <p style="text-transform:none;letter-spacing:0;font-size:.9rem;color:#374151;">
                        Fill in the fields below <strong>only when returning</strong> the book.
                    </p>

                    <p>Returned Date</p>
                    <input type="date" class="input-form" id="editReturned_date" name="Returned_date">

                    <p>Time Returned</p>
                    <input type="time" class="input-form" id="editTime_Returned" name="Time_Returned">

                    <p>Returned Staff ID</p>
                    <select class="input-form" id="editReturned_Staff_ID" name="Returned_Staff_ID">
                        <option value="">-- Optional --</option>
                        <?php foreach ($staffsArray as $s): ?>
                            <option value="<?= e($s['Staff_ID']) ?>"><?= e($s['Staff_ID']." - ".$s['Name']) ?></option>
                        <?php endforeach; ?>
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
                <h2>Create Transaction</h2>
                <form method="POST" id="createForm">
                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">

                    <p>Book ID</p>
                    <select class="input-form" id="createBook_ID" name="Book_ID" required>
                        <option value="">-- Select Book --</option>
                        <?php foreach ($availableBooksArray as $book): ?>
                            <option value="<?= e($book['Book_ID']) ?>"><?= e($book['Book_ID']." - ".$book['Title']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <p>Borrower ID</p>
                    <select class="input-form" id="createBorrower_ID" name="Borrower_ID" required>
                        <option value="">-- Select Borrower --</option>
                        <?php foreach ($borrowersArray as $b): ?>
                            <option value="<?= e($b['Borrower_ID']) ?>"><?= e($b['Borrower_ID']." - ".$b['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <p>Borrowed Date</p>
                    <input type="date" class="input-form" id="createBorrowed_date" name="Borrowed_date" required>

                    <p>Time Borrowed</p>
                    <input type="time" class="input-form" id="createTime_Borrowed" name="Time_Borrowed" required>

                    <p>Borrowed Staff ID</p>
                    <select class="input-form" id="createBorrowed_Staff_ID" name="Borrowed_Staff_ID" required>
                        <option value="">-- Select Staff --</option>
                        <?php foreach ($staffsArray as $s): ?>
                            <option value="<?= e($s['Staff_ID']) ?>"><?= e($s['Staff_ID']." - ".$s['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <p>Due Date</p>
                    <input type="date" class="input-form" id="createDue_date" name="Due_date" required>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-ghost" data-close>Cancel</button>
                        <button type="submit" name="create_submit" class="btn btn-primary">Save Transaction</button>
                    </div>
                </form>
            </div>
        </div>

    </section>
</main>

<script>
(function () {
    // ---- Modal open/close helpers ----
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

    // ---- Open EDIT with row data ----
    document.querySelectorAll('.open-modal').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = this.dataset;
            const modal = document.getElementById(d.modal);
            if (!modal) return;

            // Populate fields
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val || '';
            };

            setVal('editTransaction_ID',    d.id);
            setVal('editBook_ID',           d.book_id);
            setVal('editBorrower_ID',       d.borrower_id);
            setVal('editBorrowed_date',     d.borrowed_date);
            setVal('editTime_Borrowed',     d.time_borrowed);
            setVal('editBorrowed_Staff_ID', d.borrowed_staff_id);
            setVal('editDue_date',          d.due_date);
            setVal('editReturned_date',     d.returned_date);
            setVal('editTime_Returned',     d.time_returned);
            setVal('editReturned_Staff_ID', d.returned_staff_id);

            openModal(d.modal);
        });
    });

    // ---- Open CREATE modal ----
    const createBtn = document.getElementById('openCreateModal');
    if (createBtn) {
        createBtn.addEventListener('click', function () {
            const form = document.getElementById('createForm');
            if (form) form.reset();
            openModal('createModal');
        });
    }

})();
</script>
</body>
</html>