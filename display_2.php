<?php

include 'conn.php';


$sql = "SELECT * FROM login";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    
	//output title/ header for table
    echo "<table width=100 cellpadding = 10 cellspacing=0 border=1>";
		echo "<tr><th>User Name</th><th>Password</th>
			  <th>User Type</th></tr>";

	// output data of each row		  
    //while($row = mysqli_fetch_assoc($result)) {
	while($row = $result->fetch_assoc()){
        //echo "username: " . $row["username"]. " - Password: " . $row["password"]. ";

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