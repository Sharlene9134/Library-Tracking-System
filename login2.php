<?php
session_start();

include('conn.php');

/* INITIAL ADMIN SETUP (runs once) */
$checkAdmin = mysqli_query($conn, 
    "SELECT 1 FROM login WHERE usertype = 'Admin' LIMIT 1"
);

if (mysqli_num_rows($checkAdmin) === 0) {
    mysqli_query($conn, "
        INSERT INTO login (username, userpass, usertype, created_at)
        VALUES ('admin', 'admin123', 'Admin', NOW())
    ");
}

/* REGISTER BUTTON */
if (isset($_POST['register'])) {
    header("Location: insert2.php");
    exit();
}

/* LOGIN BUTTON */
if (isset($_POST['submit'])) {

    if (!empty($_POST['username']) && !empty($_POST['userpass'])) {

        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $userpass = mysqli_real_escape_string($conn, $_POST['userpass']);

        /* GET USER INCLUDING userID */
        $sql = "
            SELECT userID, username, usertype
            FROM login
            WHERE username = '$username'
              AND userpass = '$userpass'
            LIMIT 1
        ";

        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) === 1) {

            $row = mysqli_fetch_assoc($result);

            $_SESSION['username'] = $row['username'];
            $_SESSION['usertype'] = $row['usertype'];
            $_SESSION['userID']   = $row['userID']; 

            /* INSERT LOGIN RECORD */
            mysqli_query($conn, "
                INSERT INTO logrec (userID, login_at)
                VALUES ({$row['userID']}, NOW())
            ");

            /* REDIRECT BASED ON USERTYPE */
            if ($row['usertype'] === 'Admin') {
                header("Location: admindisplay.php");
                exit();
            } else {
                header("Location: index.php");
                exit();
            }

        } else {
            echo "<script>alert('Incorrect username or password');</script>";
        }

    } else {
        echo "<script>alert('Please provide username and password');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Library Tracking System</title>
	<link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
	<div class="login-container">
		<div class="Login">
			<h2>Log In</h2>

			<form method="POST">
				<label>Username</label>
				<input class="login-form" type="text" name="username">
				<br>
				<label>Password</label>
				<input class="login-form" type="password" name="userpass">
				<br>
				<button class="button-login1" type="submit" name="submit">Log In</button>
				<button class="button-login1" type="register" name="register">Register</button>
			</form>
		</div>
	</div>
</body>
</html>