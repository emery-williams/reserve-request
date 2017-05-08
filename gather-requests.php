<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Gather Requests</title>

<style type="text/css" media="print"> 

@page rotated { size: landscape}
table { page: rotated}
.noPrint { display:none; }

</style>

<script>
function Print() {
<?php if(!empty($_GET["print"])) { ?>
document.body.offsetHeight;
window.print();
<?php } ?>
}
</script>
</head>
<body onload="Print()" style="background-color:#ddd; margin:20px">

<?php

// Connect to the database
include("login.php");


// Include file with functions to query Alma API
require("alma-api.php");


// If debugging, display email on screen instead of sending it and email the webmaster address
define("DEBUG_EMAIL", false);


// Determine who should get the e-mails for each library:
$Recipient = array(
	//"Sawyer" => person who should get email in Sawyer Library,
	//"Schow" => person who should get email in Schow Science library
	);

// Set up a header for the e-mails
$headers = "From: INSERT YOUR EMAIL HERE\n" .
	"CC: INSERT YOUR EMAIL HERE\n" . // include if you want to get a copy of each email
	"MIME-Version: 1.0\n" .
	"Content-type: text/html\n";
	
// Set a subject
$subject = 'Reserve Request Form';

// Set styles for the information
$style = '
<style type="text/css">
table {
clear:both; 
margin:2px auto 20px; 
font-size:12pt; 
font-family:Times,serif;
border: 1px solid #000;
border-spacing: 0px;
border-collapse:collapse;
background-color:#fff;
}

table td {
padding:5px;
}

.headline {
font-size:18pt;
text-align:center
}
.columnHead {
font-size:10pt; 
font-style:italic; 
height:30pt; 
vertical-align:bottom;
}
.status {
font-size:.75em;
}
td a {
text-decoration:none;
}
td a:hover {
text-decoration:underline;
}

</style>
';

$emailed_on = DATETIME;
// echo 'emailed on: ' . $emailed_on;
$cutoff = date("Y-m-d H:i:s", strtotime("0 hours", strtotime(DATETIME)));
$course = sprintf("%d", $_GET["course"]);

// Allow users to review the information from a particular alert without re-sending e-mails


if(isset($Recipient[$_GET["library"]]) && preg_match(DATETIME_PATTERN, $_GET["review"])) {
	$review = true;
	$review_library = $_GET["library"];
	$review_date = $_GET["review"];
	
	// Create a review form to allow people to browse review e-mails
	$review_form = '<form method="get" action="' . $_SERVER['PHP_SELF'] . '"
		style="display:block; float:right"> E-mails: ';
	
	// library
	$review_form .= '<select name="library" onchange="this.form.submit()">';
	foreach ($Recipient as $library => $to) {
		$review_form .= '<option value="' . $library . '" ' .
			($library == $review_library ? 'selected="selected"' : '') . '>' . 
			$library . '</option>';
	}	
	$review_form .= '</select> ';
	

	// review by library, and sort by date
	if($review_library) {
	
	  try {
		  $query = "SELECT DISTINCT emailed_on FROM reserve_items WHERE 
				emailed AND 
				emailed_on != '0000-00-00 00:00:00' AND 
				library = :review_library
				ORDER BY emailed_on DESC";
		  //echo "query: " . $query . "<br />";			  
		  $stmt = $db->prepare($query);	
		  $stmt->bindValue(':review_library', $review_library);
		  $stmt->execute();
		  
		  // Get number of results
		  $numrows = $stmt->rowCount();
		  //echo $numrows . "<br />";
			  
		  $errorInfo = $stmt->errorInfo();
		  if (isset($errorInfo[2])) {
			  $error = $errorInfo[2];	
		  }
		  
	  } catch (Exception $e) {
		  $error = $e->getMessage();	
	  }
	  
	} else {
		
		// For debugging
		//echo "no review library";
		
	}
	
	if (isset($error)) {
		echo "<p>" . $error . "</p>";
	}
	
	// Get default date for menu
	if($numrows > 0) {
		$default_date = $stmt->fetch(PDO::FETCH_COLUMN);
	} else {
		$default_date = DATETIME;
	}
	//echo "default date: " . $default_date . "<br />";

	// Create menu with all date for staff to choose date
	$review_form .= '<select name="review" onchange="this.form.submit()">
		<option value="' . $default_date . '">Choose a date...</option>';
	
	for($i=1; $i < $numrows; $i++) {
		$email_date = $stmt->fetch(PDO::FETCH_COLUMN, $i);
		$review_form .= '<option value="' . $email_date . '" ' .
		($email_date == $review_date ? 'selected="selected"' : '') . '>' . 
		date("M jS, Y, g:i a", strtotime($email_date)) . '</option>';
	}	
	
	$review_form .= '</select> ';
	$review_form .= '</form>';
	
	echo '<div class="noPrint">' . $review_form .
		'<h3>E-mail sent ' . 
		date("M jS, Y, g:i a", strtotime($review_date)) . '</h3>
		</div>';
	
} else if($course) {	
	$review = true;
	
} else {
	$review = false;
}

// Run this process for each library, sending an e-mail alert to each contact person if necessary
foreach ($Recipient as $library => $to) {

	// Skip if we're reviewing and this isn't the library we want
	if($review && $review_library != $library && empty($course)) continue;
	
	// Build a message
	$message = "";
		
	// If we're reviewing a course, search for the course
	if($review && $course) {
	
	  try {
		  $query = "SELECT * FROM reserve_items i
				JOIN reserve_courses c ON i.course_id = c.course_id
				WHERE i.course_id = :course
				ORDER BY i.username, i.course_id, i.loan_period, i.call_number_sort, i.author, i.title_sort";
		  echo "query: " . $query . "<br />";			  
		  $stmt = $db->prepare($query);	
		  $stmt->bindValue(':course', $course);
		  $stmt->execute();
			  
		  $errorInfo = $stmt->errorInfo();
		  if (isset($errorInfo[2])) {
			  $error = $errorInfo[2];	
		  }
		  
	  } catch (Exception $e) {
		  $error = $e->getMessage();	
	  }
	 
	// If we're reviewing, pick up a previous alert  
	} else if ($review) {
		
		try {
		  $query = "SELECT * FROM reserve_items i
				JOIN reserve_courses c ON i.course_id = c.course_id
				WHERE i.library = :library AND
				i.emailed_on = :review_date
				ORDER BY i.username, i.course_id, i.loan_period, i.call_number_sort, i.author, i.title_sort";
		  //echo "query: " . $query . "<br />";			  
		  $stmt = $db->prepare($query);	
		  $stmt->bindValue(':library', $library);
		  $stmt->bindValue(':review_date', $review_date);
		  $stmt->execute();
			  
		  $errorInfo = $stmt->errorInfo();
		  if (isset($errorInfo[2])) {
			  $error = $errorInfo[2];	
		  }
		  
		} catch (Exception $e) {
			$error = $e->getMessage();	
		}
	
	// Select items that were submitted over 2 hours ago and haven't been e-mailed yet	
	} else {
		
		try {
		  $query = "SELECT * FROM reserve_items i
				JOIN reserve_courses c ON i.course_id = c.course_id
				WHERE i.submitted AND 
				!i.emailed AND 
				i.library = :library AND
				i.entered_on <= :cutoff
				ORDER BY i.username, i.course_id, i.loan_period, i.call_number_sort, i.author, i.title_sort";
		  echo "query: " . $query . "<br />";			  
		  $stmt = $db->prepare($query);	
		  $stmt->bindValue(':library', $library);
		  $stmt->bindValue(':cutoff', $cutoff);
		  $stmt->execute();
			  
		  $errorInfo = $stmt->errorInfo();
		  if (isset($errorInfo[2])) {
			  $error = $errorInfo[2];	
		  }
		  
		} catch (Exception $e) {
			$error = $e->getMessage();	
		}
		
	}
	
	if (isset($error)) {
		echo "<p>" . $error . "</p>";
	}

	
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		extract($row, EXTR_PREFIX_ALL, 'i');
		
		// Show a header for each new course and reserve period
		if($i_course_id != $old_course_id || $i_loan_period != $old_loan_period) {
		
			// End the last table if necessary
			if($message) $message .= '</table>';
			
			// Start the table with info on the course and prof
			$message .= '<table width="100%" border="1" cellpadding="6" cellspacing="0"
					style="' . ($old_course_id ? 'page-break-before:always; ' : '') . '">
					<tr class="headline"><td colspan="5">' .
					$library . ' Library Reserve List</td></tr>
					<tr><td colspan="2">Course #: <a href="' . HTTP . $_SERVER['PHP_SELF'] . '?course=' . 
						$i_course_id . '" title="view all requests for this course"><b>' . 
						htmlspecialchars($i_course_number) . '</b></a></td>
					<td colspan="3">Course Title: <b>' . htmlspecialchars($i_course_title) . '</b></td></tr>
					<tr><td colspan="2">Instructor: <b>' . htmlspecialchars($i_instructor) . 
						'</b> (' . $i_email . ')</td>
					<td colspan="3">Date: <b>' . date("M jS, Y, g:i a", strtotime(DATETIME)) . '</b></td></tr>
					<tr><td colspan="5">Loan Period: <b>' . htmlspecialchars($i_loan_period) . '</b></td></tr>
					<tr class="columnHead">
					<td width="10%">Call Number</td>
					<td width="15%">Author</td>
					<td width="30%">Title</td>
					<td width="15%">Notes</td>
					<td width="30%">Staff Use</td>
					</tr>';
		}

		
		// Get subtitle information for each item
		if ($i_english_subtitles == 1) {
			$i_subtitles = 'English';
		}
		else if ($i_other_subtitles == 1) {	
			$i_subtitles = $i_language;
		}
		
		else if ($i_other_subtitles == 0 && $i_english_subtitles == 0) {
			$i_subtitles = 'no';
		}
		
		// Get availability info from Alma
		if ($i_mms_id) {
			$xml = getAvailabilityInfo($i_mms_id);
			$bib = simplexml_load_string($xml);
			$status = getAvailability($bib);
		} else {
			$status = 'No MMS ID available.';	
		}
			
		// message from new catalog
		
		// Is the item a sound recording?
		if(preg_match("/^sound/i", $i_format) || (empty($i_format) && preg_match("/^CD /i", $i_call_number))) {
			$sound_recording = true;
		} else {
			$sound_recording = false;
		}
		
		$message .= '<tr><td>' . 
		
		// build link to call number search
		
		'<a href="http://librarysearch.williams.edu/primo_library/libweb/action/search.do?vid=01WIL&fn=BrowseRedirect&searchField=' .
		($sound_recording ? 'callnumber' : 'callnumber.0') . '&searchTxt=' . 
			urlencode($i_call_number) . '" target="_blank">' . htmlspecialchars($i_call_number) . '</a></td>' . 
			
		// build link to author search
		'<td><a href="http://librarysearch.williams.edu/primo_library/libweb/action/dlSearch.do?institution=01WIL&vid=01WIL&search_scope=everything_scope&query=creator,contains,' . 
			urlencode($i_author) . '" target="_blank">' . htmlspecialchars($i_author) . '</a></td>' . 
			
		// build link to title search
		'<td><a href="http://librarysearch.williams.edu/primo_library/libweb/action/dlSearch.do?institution=01WIL&vid=01WIL&search_scope=everything_scope&query=title,contains,' . 
			urlencode($i_title_sort) . '" target="_blank">' . htmlspecialchars($i_title) . '</a></td>' .  
			
		// Other info
		'<td class="status">' . ($i_streaming == '1' ? '<strong>Streaming requested with ' . $i_subtitles . 
		' subtitles</strong>' : '') . '&nbsp;</td>
		<td>' . ($review ? $status : '') . ' &nbsp;</td></tr>' . 
		($i_comments ? '<tr class="columnHead"><td></td><td><strong>Comments</strong></td><td colspan=4>' . 
		$i_comments . '</td></tr>' : '');	
			
		
		// Store the information from this record for comparison with the next
		extract($row, EXTR_PREFIX_ALL, "old");
		
		
	} // end while
	
	// Now send e-mails if we have any results
	if($message) {
	
		// Close the last table and add styles to the message
		$message = $style . $message . '</table>';
		
		if($review) {
			// If we're reviewing, output the message and quit
			die($message . '</body></html>');
		}
		
		// Add a link to this page, so that users can generate e-mails between automatic ones
		$message = '
			<p><a href="' . HTTP . $_SERVER['PHP_SELF'] . '?library=' . $library . '&review=' .
				urlencode($emailed_on) . '&print=1">Print This E-mail</a></p>
			<p><a href="' . HTTP . $_SERVER['PHP_SELF'] . '?library=' . $library . '&review=' .
				urlencode($emailed_on) . '">View This E-mail Online</a></p>
			<p><a href="' . HTTP . '/forms/reserves/check-status.php?lib=' . $library . 
				'">View All Outstanding Requests</a> (new)</p>				
			' . $message . '
			<p><a href="' . HTTP . $_SERVER['PHP_SELF'] . '">Generate Reserve Request E-mails</a>
				between automatic sends during busy periods.</p>';
		
		// Override the recipient if we are debugging
		if(DEBUG_EMAIL) $to = 'wcl.webmaster@williams.edu';
		
		// Try to mail
		$emailed_on = DATETIME;
		// echo 'emailed on: ' . $emailed_on;
		if(mail($to, $subject, $message, $headers)) {
			// On successful mailing mark the records as e-mailed
			echo '<p>Alert for ' . $library . ' sent.</p>';
			
			$query = "UPDATE reserve_items i SET 
					i.emailed = '1',
					i.emailed_on = '" . $emailed_on . "'
				WHERE " . $where;
			// echo $query;
			Query($query);
			
			if(DEBUG_EMAIL) echo $message;
			
		} else {
			echo '<p>Send failed!</p>';
		}
		
	} else {
		echo '<p>No new requests for ' . $library . '</p>';
	}
	
	
} // end foreach library


?>
</body>
</html>
