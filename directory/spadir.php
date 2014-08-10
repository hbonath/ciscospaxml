<?php
// load FreeBPX bootstrap environment, requires FreePBX 2.9 or higher
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
include_once('/etc/asterisk/freepbx.conf');
}
 
// set FreePBX globals
global $db;  // FreePBX asterisk database connector
global $amp_conf;  // array with Asterisk configuration
global $astman;  // AMI
 
$sql = "SELECT name,extension FROM users ORDER BY extension";
$results = $db->getAll($sql, DB_FETCHMODE_ORDERED);  // 2D array of all FreePBX users
$numrows = count($results);

// XML Output Below
header ("content-type: text/xml");

echo "<CiscoIPPhoneDirectory>\n";
echo "<Title>PBX Directory</Title>\n";
echo "<Prompt>Select a User</Prompt>\n";
	
if ($numrows >=32) {
	// set up variables for dealing with >32 entries
	$page = $_GET["page"];
	if (empty($page)) {
	        $page = 0;      // set first page by default
	}
	$count = $page * 32 ;
	for ($row=$count; $row <= $count+32; $row++) {
	    if (empty($results[$row][0])) {
                      // do nothing
            }
            else {
	    	echo "<DirectoryEntry>\n";
	    	echo "<Name>" . $results[$row][0] . "</Name>\n";
	    	echo "<Telephone>" . $results[$row][1] . "</Telephone>\n";
	    	echo "</DirectoryEntry>\n";
            }
	}
	echo "<SoftKeyItem>\n";
	echo "<Name>Dial</Name>\n";
	echo "<URL>SoftKey:Dial</URL>\n";
	echo "<Position>1</Position>\n";
	echo "</SoftKeyItem>\n";

	echo "<SoftKeyItem>\n";
	echo "<Name>EditDial</Name>\n";
	echo "<URL>SoftKey:EditDial</URL>\n";
	echo "<Position>2</Position>\n";
	echo "</SoftKeyItem>\n";

	if ($page > 0){
		echo "<SoftKeyItem>\n";
		echo "<Name>Prev</Name>\n";
		echo "<URL>SoftKey:Exit</URL>\n";
		echo "<Position>3</Position>\n";
		echo "</SoftKeyItem>\n";

	} else {
		echo "<SoftKeyItem>\n";
		echo "<Name>Exit</Name>\n";
		echo "<URL>SoftKey:Exit</URL>\n";
		echo "<Position>3</Position>\n";
		echo "</SoftKeyItem>\n";
	}	
	echo "<SoftKeyItem>\n";
	echo "<Name>Next</Name>\n";
	echo "<URL>http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?page=".++$page."</URL>\n";
	echo "<Position>4</Position>\n";
	echo "</SoftKeyItem>\n";
	
	// Placeholder for Search Function
	// echo "<SoftKeyItem>\n";
	// echo "<Name>Search</Name>\n";
	// echo "<URL>SoftKey:Update</URL>";
	// echo "<Position>5</Position>\n";
	// echo "</SoftKeyItem>\n";

} else {   // less than 32 entries
	foreach ($results as $row) {
            echo "<DirectoryEntry>\n";
            echo "<Name>" . $row[0] . "</Name>\n";
            echo "<Telephone>" . $row[1] . "</Telephone>\n";
            echo "</DirectoryEntry>\n";
	}
}    

echo "</CiscoIPPhoneDirectory>\n";
       
//END
?>
