<?php
$con=mysqli_connect("localhost","username","password","database");

if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}

$result = mysqli_query($con,"SELECT * FROM users");

header ("content-type: text/xml");
    echo "<CiscoIPPhoneDirectory>\n";
    echo "<Title>PBX Directory</Title>\n";
    echo "<Prompt>Select a User</Prompt>\n";
	while($row = mysqli_fetch_array($result)) {
	  echo "<DirectoryEntry>\n";
	  echo "<Name>" . $row['name'] . "</Name>\n";
	  echo "<Telephone>" . $row['extension'] . "</Telephone>\n";
	  echo "</DirectoryEntry>\n";
	}
    echo "</CiscoIPPhoneDirectory>\n";


mysqli_close($con);
?>
