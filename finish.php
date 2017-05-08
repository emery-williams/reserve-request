<?php 

// Put access control/user ID files here
// You'll want a way to identify users and set value for $prof_username.

// Set PHP Variables
$pagetitle = "Course Reserve Request - Course"; // Page Title

// Insert Header


// Connect to the database
include("login.php");


?>
<!-- Begin Page Editing Here -->
<div>
<div>
<h1><?php echo $pagetitle; ?></h1>

<?php

include("call_number_utilities.php");

// Get info posted from previous screen
$comments = trim($_GET["comments"]);
$instructor = trim($_GET["instructor"]);
!empty($instructor) or die("Error: No contact name specified.");
$email = trim($_GET["email"]);
!empty($email) or die("Error: No contact e-mail specified.");

// Figure out which course id to use
$course_number = trim($_GET["new_course"]);
$course_title = trim($_GET["new_course_title"]);
$course = $_GET["course"];

// Create a new course or use an existing one.
if(!empty($course_number) || !empty($course_title)) {

	// Make sure they filled in both fields.
	(!empty($course_number) && !empty($course_title)) or 
		die("Error: Please enter a course number and title.");

	// Create a new course for this user from form info, or update an existing course
	$set = "SET
		course_number = :course_number,
		course_title = :course_title,
		instructor = :instructor,
		email = :email,
		username = :prof_username";
	
	// First check to see if this course number has been used by this person before
	if($prof_username && $course_number) {
			
		try {		
			$query = "SELECT course_id FROM reserve_courses WHERE 
					course_number = :course_number AND
					username = :prof_username";		
			//echo $query . '<br />';			
			$stmt = $db->prepare($query);	
			$stmt->bindValue(':course_number', $course_number);
			$stmt->bindValue(':prof_username', $prof_username);
			$stmt->execute();
					
			$errorInfo = $stmt->errorInfo();
			if (isset($errorInfo[2])) {
				$error = $errorInfo[2];	
			}
			
		} catch (Exception $e) {
			$error = $e->getMessage();	
		}
					  
	} else {
		
		// For debugging
		echo "no prof name and course number";
	}
	
	if (isset($error)) {
		echo "<p>" . $error . "</p>";
	}

	// Get number of results
	$numrows = $stmt->rowCount();
	//echo 'numrows: ' . $numrows;	
	
	if($numrows > 0) {

		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		$course_id = $result[course_id];
		//echo "course_id " . $course_id . "<br />";
		
		try {		
			$query = "UPDATE reserve_courses " . $set . "
				WHERE course_id = :course_id
				LIMIT 1";	
			//echo $query . '<br />';			
			$stmt = $db->prepare($query);	
			$stmt->bindValue(':course_number', $course_number);
			$stmt->bindValue(':course_title', $course_ititle);
			$stmt->bindValue(':instructor', $instructor);
			$stmt->bindValue(':email', $email);
			$stmt->bindValue(':prof_username', $prof_username);
			$stmt->bindValue(':course_id', $course_id);
			$stmt->execute();
					
			$errorInfo = $stmt->errorInfo();
			if (isset($errorInfo[2])) {
				$error = $errorInfo[2];	
			}
			
		} catch (Exception $e) {
			$error = $e->getMessage();	
		}
		
		if (isset($error)) {
		echo "<p>" . $error . "</p>";
		}
		
	} else {
		
		// Otherwise create a record from scratch
		try {		
			$query = "INSERT INTO reserve_courses " . $set;
			//echo $query . '<br />';			
			$stmt = $db->prepare($query);	
			$stmt->bindValue(':course_number', $course_number);
			$stmt->bindValue(':course_title', $course_ititle);
			$stmt->bindValue(':instructor', $instructor);
			$stmt->bindValue(':email', $email);
			$stmt->bindValue(':prof_username', $prof_username);
			$stmt->execute();
			$course_id = $db->lastInsertId();
			//echo "course_id" . $course_id . "<br />";
					
			$errorInfo = $stmt->errorInfo();
			if (isset($errorInfo[2])) {
				$error = $errorInfo[2];	
			}
			
		} catch (Exception $e) {
			$error = $e->getMessage();	
		}
		
		if (isset($error)) {
		echo "<p>" . $error . "</p>";
		}
			
	}
	
} else if(preg_match("/^reuse(\d+)$/", $course, $match)) {
	// Get the course from the database
	$course_id = $match[1];
	//echo "course id: " . $course_id . "<br />";
	
} else {
	die("Error: could not determine the selected course.");
}

// Get the item ID
$item_id = sprintf("%d", $_GET["item_id"]);
//echo "item id: " . $item_id . "<br />";

// Update the course information
if($item_id) {
	try {		
		$query = "UPDATE reserve_items SET
			entered_on = '" . DATETIME . "',
			course_id = :course_id,
			comments = :comments,
			submitted = '1',
			emailed = '0'
			WHERE id = :item_id LIMIT 1";		
		//echo $query . '<br />';			
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':course_id', $course_id);
		$stmt->bindValue(':comments', $comments);
		$stmt->bindValue(':item_id', $item_id);
		$stmt->execute();
				
		$errorInfo = $stmt->errorInfo();
		if (isset($errorInfo[2])) {
			$error = $errorInfo[2];	
		}
		
	} catch (Exception $e) {
			$error = $e->getMessage();	
	}
					  
} else {
	
	// For debugging
	echo "no item id";
}

if (isset($error)) {
	echo "<p>" . $error . "</p>";
}


// Get info from the database to display to the user
if($item_id) {
	try {		
		$query = "SELECT * FROM reserve_items i
			JOIN reserve_courses c ON i.course_id = c.course_id
			WHERE i.id = :item_id";		
		//echo $query . '<br />';			
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':item_id', $item_id);
		$stmt->execute();
				
		$errorInfo = $stmt->errorInfo();
		if (isset($errorInfo[2])) {
			$error = $errorInfo[2];	
		}
		
	} catch (Exception $e) {
			$error = $e->getMessage();	
	}
					  
} else {
	
	// For debugging
	echo "no item id";
}

if (isset($error)) {
	echo "<p>" . $error . "</p>";
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
extract($row, EXTR_PREFIX_ALL, 'r');

// Determine subtitles and language
if ($r_english_subtitles == 1) {
	$language = 'English';	
} else {
	$language = 'no';
}


// Show the status bar
$arrow = '<img src="/assets/images/block-arrow.gif" hspace="3" />';
echo '<p class="statusBar">
	Item Information ' . $arrow . '
	Loan Terms ' . $arrow . '
	Course ' . $arrow . '
	<b>Done</b>
	</p>';

// Show form with information

$loanColor = 'ff9';
$libraryColor = 'cff';

echo '<form method="get" action="cancel.php">

	<div style="background-color:#dee4ee; padding: 0.5em!important;">
	<h3>Reserve Item Information</h3>
	<p>' . htmlspecialchars($r_author) . '. 
		<em>' . htmlspecialchars($r_title) . '</em><br />' . 
		htmlspecialchars($r_call_number) . ' &nbsp; &nbsp;
		Loan Period: ' . htmlspecialchars($r_loan_period) . ' &nbsp; &nbsp;
		Library: ' . htmlspecialchars($r_library) . '<br />
		Course: ' . htmlspecialchars($r_course_number) . ': ' .
			htmlspecialchars($r_course_title) . '</p></div>';
			
	
	echo '	
	<p style="padding-top:1em;">This item has been submitted to reserves, and will be processed as soon as possible.*
	
	&nbsp; 
	
	<input type="hidden" name="item_id" value="' . $item_id . '" />
	<input type="submit" value=" Cancel Request " /></p>';
	
	if ($r_streaming == 1) {
		  echo '<p>You requested that the library investigate streaming rights for this film.<br /><br />Please see the <strong><a href="http://libguides.williams.edu/faculty-information/instruction#s-lg-box-11531315">
		   streaming information page</a></strong> for more information about streaming films.</p>';	
	}
	
	echo '
	<h3>What next?</h3>
	<ul>
		<li><a href="./?u=' . time() . '">Review Your Requests</a></li>
		<li><a href="http://librarysearch.williams.edu/primo_library/libweb/action/dlSearch.do?institution=01WIL&vid=01WIL&search_scope=alma_scope">Add another item from the catalog</a></li>
		<li><a href="/logout.php">All Done - Log Out</a></li></ul>';
	




?>
<p>&nbsp;</p>
<p class="small">* During busy periods, it may take up to two weeks to process some requests.</p>
</div>
</div>

<!-- End Page Editing Here -->