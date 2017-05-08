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

$id = sprintf("%d", $_REQUEST["id"]);
$confirmed = !empty($_POST["confirm"]);

if($id) {
			
	try {		
		$query = "SELECT * FROM reserve_items i
				JOIN reserve_courses c ON i.course_id = c.course_id
				WHERE i.id = :id";		
		//echo $query . '<br />';			
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':id', $id);
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
	echo "no id";
}

if (isset($error)) {
	echo "<p>" . $error . "</p>";
}


// Get number of results
$numrows = $stmt->rowCount();
//echo 'numrows: ' . $numrows;

if($numrows == 0) {
	echo '<p>Oops - the item was not found.  Perhaps you deleted it already?</p>
		<form method="get" action="./index.php"><p>
		<input type="hidden" name="u" value="' . time() . '" />
		<input type="submit" value="Continue" id="inputFocusTarget" /> 
		</p></form>';
		
} else {
	
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	extract($row, EXTR_PREFIX_ALL, 'r');

	$item_info = '<p class="bibInfo" ' .
		($confirmed ? '' : ' style="background-color:#dee4ee; padding: 0.5em!important; margin-bottom: 1em !important;"') . '>' . htmlspecialchars($r_author) . '. 
			<em>' . htmlspecialchars($r_title) . '</em><br />' . 
			htmlspecialchars($r_call_number) . ' &nbsp; &nbsp;
			Loan Period: ' . htmlspecialchars($r_loan_period) . ' &nbsp; &nbsp;
			Library: ' . htmlspecialchars($r_library) . '<br />
			Course: ' . htmlspecialchars($r_course_number) . ': ' .
				htmlspecialchars($r_course_title) . '
			</p>';
	
	
	if(!$confirmed) {
	
		echo '<p>Are you sure you want to remove this item?</p>
			<form method="post" action="' . $_SERVER['PHP_SELF'] . '"><p>
			<input type="hidden" name="id" value="' . htmlspecialchars($id) . '" />
			<input type="submit" name="confirm" value="Remove" id="inputFocusTarget" /> &nbsp; &nbsp;
			<input type="button" value="Cancel" onclick="location.href=\'./index.php?u=' . time() . '\'" />
			</p></form>' . 
			$item_info;
	
	} else {
	
		if($id) {
			
			try {		
				$query = "DELETE FROM reserve_items
					WHERE id = ':id'
					LIMIT 1";	
				//echo $query . '<br />';			
				$stmt = $db->prepare($query);	
				$stmt->bindValue(':id', $id);
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
			echo "no id";
		}
		
		if (isset($error)) {
			echo "<p>" . $error . "</p>";
		}
		
	
		echo '<p>The item has been removed from your reserve submissions list.  
			</p>
			<form method="get" action="./index.php"><p>
			<input type="hidden" name="u" value="' . time() . '" />
			<input type="submit" value="Continue" id="inputFocusTarget" /> 
			</p></form>' . 
			$item_info .
			'<p>Note: The physical item will be taken off reserve at the end of the term.  
			To have it taken off sooner, contact the Services Desk at ' . htmlspecialchars($r_library) . '.</p>';
	
	}

}
?>
</div>
</div>

<!-- End Page Editing Here -->
