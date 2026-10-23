<?php
include('conn.php');

if (isset($_POST['submit'])) {

    $username = mysqli_real_escape_string($conn, $_POST['un']);
    $password = mysqli_real_escape_string($conn, $_POST['pw']);

    /* OPTIONAL: check for duplicate username */
    $check = mysqli_query($conn, 
        "SELECT 1 FROM login WHERE username = '$username' LIMIT 1"
    );

    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Username already exists');</script>";
    } else {

        $sql = "
            INSERT INTO login (username, userpass, usertype, usercreated_at)
            VALUES ('$username', '$password', 'User', NOW())
        ";

        if (mysqli_query($conn, $sql)) {
            echo "<script>
                alert('Account successfully created');
                window.location.replace('login2.php');
            </script>";
            exit();
        } else {
            echo "<script>alert('Error adding record');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .a{
            color: blue;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="Login">
        <h2>Register</h2>

        <form action="insert2.php" method="POST">
            <label>Username</label>
            <input class="login-form" type="text" name="un" required>

            <label>Password</label>
            <input class="login-form" type="password" name="pw" required>

            <button class="button-login1" type="submit" name="submit">
                Create Account
            </button>

            <p>
                Already have an account?
                <a href="login2.php" class="a">Login</a>
            </p>
        </form>
    </div>
</div>

</body>
</html>