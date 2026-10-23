<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "lts";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "SELECT * FROM login";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    
	//output title/ header for table
    echo "<table width=100 cellpadding = 10 cellspacing=0 border=1>";
		echo "<tr><th>User Name</th><th>Password</th>
			  <th>User Type</th></tr>";

	// output data of each row		  
    while($row = mysqli_fetch_assoc($result)) {
        //echo "username: " . $row["uname"]. " - Password: " . $row["upass"]. " - Type: " . $row["utype"]. "<br>";

			echo "<tr>";
			echo "<td> $row[username] </td>";
			echo "<td> $row[userpass] </td>";
			echo "</tr>";


    }
} else {
    echo "0 results";
}

mysqli_close($conn);
?>