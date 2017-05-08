<?php
/*
To create a database connection anywhere in the site, include this file.
Include using include_once() to prevent duplication of connections.

This directory should also include an .htaccess file with the following contents:

	<FILESMATCH "\.php$">
		 order deny,allow
		 deny from all
	</FILESMATCH>

This will prevent unauthorized access of this file and the password within.

*/

// First define some constants for use throughout the site.
define("DATETIME", date("Y-m-d H:i:s", time()) );
define("IP", $_SERVER['REMOTE_ADDR']);

// Commonly used patterns for validation
define("DATETIME_PATTERN", "/^\d{4}\-\d{2}\-\d{2}\s\d{2}\:\d{2}\:\d{2}$/");
define("DATE_PATTERN", "/^\d{4}\-\d{2}\-\d{2}$/");
define("TIME_PATTERN", "/^\d{2}\:\d{2}\:\d{2}$/");

define("HTTP", "http://" . $_SERVER['HTTP_HOST']);
define("HTTPS", "https://" . $_SERVER['HTTP_HOST']);
define("LIB", "");

define("WEBMASTER_EMAIL", "INSERT YOUR EMAIL HERE");
define("WEBMASTER_USERNAME", "INSERT YOUR USERNAME HERE");


define("PAGE", (!empty($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['PHP_SELF']));
define("URL", PAGE . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') );
define("FULL_URL", HTTP . URL);

// Connect to the database
$mysql = 'mysql:host=YOUR DATABASE HOST HERE;dbname=YOUR DATABASE NAME HERE';

try {
	$db = new PDO($mysql, 'YOUR DATABASE USERNAME HERE', 'YOUR DATABASE PASSWORD HERE');
} catch (PDOException $e) {
	$error = $e->getMessage();	
}

if (isset($error)) {
    //echo $error;
	
	if($_SESSION["username"] == WEBMASTER_USERNAME) {
	
		// If the webmaster screws up, display error
		echo $error;
		echo "<pre>" . $query . "</pre>";
			
	} else {
		
		// Hide error info from the user and notify the webmaster
		$message = "The following SQL generated an error:\n\n" . 
			$query . "\n\nError: " . $error . "\n\nPage: " . $_SERVER['REQUEST_URI'] . 
			(!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '') .
			"\n\nIP: " . IP .
			(!empty($_SESSION["username"]) ? "\n\nUsername: " . $_SESSION["username"] : "");
		
		mail(WEBMASTER_EMAIL, 'SQL Error', $message, "From: " . WEBMASTER_EMAIL);

	}
	
} else {
    //echo 'Connection successful!';	
}

?>