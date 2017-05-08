<?php 

// Put access control/user ID files here
// You'll want a way to identify users and set value for $prof_username.

// Set PHP Variables
$pagetitle = "Course Reserve Request - Cancel"; // Page Title

// Insert Header


// Connect to the database
include("login.php");


?>
<!-- Begin Page Editing Here -->

<div>
<div>
<h1>Course Reserve Request Cancelled</h1>

<?php

// Get item info
$item_id = sprintf("%d", $_GET["item_id"]);
//echo "item id: " . $item_id . "<br />";

// Cancel the request by dissociating it from a course and marking as unsubmitted
if($item_id) {
	
	try {
		$query = "UPDATE reserve_items SET
			submitted = '0',
			course_id = '0'
			WHERE id = :item_id
			LIMIT 1";
		//echo $query . "<br />";		
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
	echo "no prof name or item id";
	
}

if (isset($error)) {
    echo "<p>" . $error . "</p>";
}


// Get information on this record, including course info if any
if($item_id) {
	
	// For debugging
	//echo $item_id . '<br />';
	
	try {
		$query = "SELECT * FROM reserve_items i
			LEFT OUTER JOIN reserve_courses c
			ON i.course_id = c.course_id
			WHERE id = :item_id";
				
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':item_id', $item_id);
		$stmt->execute();
		
		// Get number of results
		$numrows = $stmt->rowCount();
		//echo "numrows: " . $numrows . "<br />";

		$errorInfo = $stmt->errorInfo();
		if (isset($errorInfo[2])) {
			$error = $errorInfo[2];	
		}
	} catch (Exception $e) {
		$error = $e->getMessage();	
	}
} else {
	
	// For debugging
	//echo "no item id";
}

if (isset($error)) {
    echo "<p>" . $error . "</p>";
}


// If stored info was found, assign it to variables

if($numrows > 0) {

	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	//echo 'cancelled item:<br />';
	//echo '<pre>';
	//print_r($result);
	//echo '</pre>';
	$author = $result[author];
	//echo $author . '<br />';
	$title = $result[title];
	//echo $title . '<br />';
	$call_no = $result[call_number];
	//echo $call_no . '<br />';
	$loan_period = $result[loan_period];
	//echo $loan_period . '<br />';
	$library = $result[library];
	//echo $library . '<br />';

} else {
	// For debugging
	// echo "no results found.";	
}

// Show the status bar
$arrow = '<img src="/assets/images/block-arrow.gif" hspace="3" />';
echo '<p class="statusBar">
	Item Information ' . $arrow . '
	Loan Terms ' . $arrow . '
	Course ' . $arrow . '
	Done ' . $arrow . '
	<b>Cancel</b>
	</p>';
	
echo '<div style="background-color:#dee4ee; padding: 0.5em!important; margin-bottom: 0.5em !important;">' . htmlspecialchars($author) . '. 
		<em>' . htmlspecialchars($title) . '</em><br />' . 
		htmlspecialchars($call_no) . ' &nbsp; &nbsp;
		Loan Period: ' . htmlspecialchars($loan_period) . ' &nbsp; &nbsp;
		Library: ' . htmlspecialchars($library) . '
		</div>';

// Show form with information
echo '<form method="get" action="choose-loan-period.php" onsubmit="return CheckForm(this)">
	<p>Your request has been cancelled.  If you want to put this item on reserve after all, 
	<input type="submit" value="choose a loan period" />
	
	<input type="hidden" name="item_id" value="' . htmlspecialchars($item_id) . '" />.
	</p>
	
	
	</form>';
	
echo '	<h3>What next?</h3>
	<ul>
		<li><a href="./?u=' . time() . '">Review Your Requests</a></li>
		<li><a href="INSERT YOUR CATALOG URL HERE">Add another item from the library catalog</a></li>
	</ul>'
		
?>
</div>
</div>

<!-- End Page Editing Here -->
