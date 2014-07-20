<?php
// load FreeBPX bootstrap environment, requires FreePBX 2.9 or higher
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
include_once('/etc/asterisk/freepbx.conf');
}
 
// set FreePBX globals
global $db;  // FreePBX asterisk database connector
global $amp_conf;  // array with Asterisk configuration
global $astman;  // AMI
 
$sql = "SELECT * FROM users";
$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);  // 2D array of all FreePBX users
$numrows = count($results);

if ( $numrows <= 32 ) {
	header ("content-type: text/xml");
	    echo "<CiscoIPPhoneDirectory>\n";
	    echo "<Title>PBX Directory</Title>\n";
	    echo "<Prompt>Select a User</Prompt>\n";
	foreach  ($results as $row) {
	    echo "<DirectoryEntry>\n";
	    echo "<Name>" . $row['name'] . "</Name>\n";
	    echo "<Telephone>" . $row['extension'] . "</Telephone>\n";
	    echo "</DirectoryEntry>\n";
	}
	    echo "</CiscoIPPhoneDirectory>\n";
} else {
        
	// Spit out an error
	// Need to add some logic on how to handle a result set larger than 32
	// there are some examples of adding a Next button that would call either a second page, or variable with page number
	
	header ("content-type: text/xml");
	    echo "<CiscoIPPhoneText>\n";
	    echo "<Title>ERROR</Title>\n";
	    echo "<Prompt>Result too large</Prompt>\n";
	    echo "<Text>Your PBX currently has more than 32 extensions, which is beyond the limit of this application.</Text>\n";
	    echo "</CiscoIPPhoneText>\n";
}
?>
