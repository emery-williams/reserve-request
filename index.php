<?php 

// Put access control/user ID files here
// You'll want a way to identify users and set value for $prof_username.

// Set PHP Variables
$pagetitle = "Course Reserve Request"; // Page Title

// Insert Header


// Connect to the database
include("login.php");


?>
<!-- Begin Page Editing Here -->

<?php
// Set times for recently submitted requests and "old" requests
$cutoff = date("Y-m-d H:i:s", strtotime("-6 weeks")); // Recently submitted (cannot be edited)
$last_term = date("Y-m-d H:i:s", strtotime("-5 months")); // "old" requests from last term

// Find out if the prof has items from last term
if (isset($prof_username) && isset($last_term)) {
	
	// For debugging
	// echo $prof_username . '<br />';
	// echo $last_term . '<br />';
	
	try {
		$query = 'SELECT * FROM reserve_items i
				JOIN reserve_courses c ON i.course_id = c.course_id
				WHERE
				i.username = :prof_username AND 
				i.submitted AND
				i.entered_on < :last_term
				ORDER BY c.course_number, i.author, i.title_sort';
				
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':prof_username', $prof_username);
		$stmt->bindValue(':last_term', $last_term);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
		
		$errorInfo = $stmt->errorInfo();
		if (isset($errorInfo[2])) {
			$error = $errorInfo[2];	
		}
	} catch (Exception $e) {
		$error = $e->getMessage();	
	}
} else {
	// For debugging
	// echo "no prof name or last term";
}

if (isset($error)) {
    echo "<p>" . $error . "</p>";
}

// Get number of results
$numrows = $stmt->rowCount();

// If there are results, then they have submitted items before
$has_old_items = ($num_rows > 0);

// For debugging
// echo "Has old items? " . isset($has_old_items);
?>

<!-- Start page content -->
<div>
<div>
<h1><?php echo $pagetitle; ?></h1>

<h4 style="padding-top:20px">Welcome to the reserve request system.</h4>  
<ul>
    <li><a href="begin.php">Begin new request</a> - Use to place physical items on reserve or to request streaming films. You can also start in the <a href="https://librarysearch.williams.edu">library catalog</a> and use the "Place item on course reserve" link found on the "Get It" tab.</li>
    <!-- Show this link only if they have old requests -->
	<?php if(isset($has_old_items)) echo '<li><a href="#review">Review previous requests</a> - Use to resubmit lists from previous years.</li>';?>
</ul>

</h4>

<h3 id="review" style="padding-top:40px">Previous Requests:</h3>
<ul><li>

<?php

// Display message if there are old items that may need to be resubmitted.
if(isset($has_old_items)) {
	echo 'Items in gray were submitted over 5 months ago and may no longer be on reserve.  
	Please re-submit them if you want them on reserve.</li><li>';
}

// Tell users that we have to change course names and numbers manually
echo 'To make changes to course names or numbers, please contact library staff';
echo '.</li></ul>'; 


/* ------------------------------------------------------------------------ */
// Get items that are associated with a course and have been submitted

if (isset($prof_username) && isset($last_term)) {
	
	// For debugging
	//echo $prof_username . '<br />';
	//echo isset($last_term) . '<br />';
	
	try {
		$query = 'SELECT * FROM reserve_items i
				JOIN reserve_courses c ON i.course_id = c.course_id
				WHERE
				i.username = :prof_username AND 
				i.submitted
				ORDER BY c.course_number, i.author, i.title_sort';
				
		$stmt = $db->prepare($query);	
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
	echo "no prof name or last term";
}

if (isset($error)) {
    echo "<p>" . $error . "</p>";
}

// Get number of results
$numrows = $stmt->rowCount();

// Are there previously submitted items?
if($numrows == 0) { // no
	echo '<p>No previous requests to view.</p>';
} else { // yes
	
	// Introduce course variables
	$old_course = "";
	$old_course_id = 0;

	// Start form
	echo '<form method="get" action="resubmit.php">';
	
	// display previously submitted items 
	echo '<table>';
	
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		extract($row, EXTR_PREFIX_ALL, 'r');
		
		// Separate requests by course by comparing to previous row
		if($old_course != $r_course_number) {
		
			$box_count = 0;
	
			if($old_course) echo '<tr><td colspan="2" style="padding-bottom:30px">
				<input type="submit" value="Put Marked Items On Reserve Again" ' .
				($disable ? 'disabled="disabled"' : '') . ' />
				</td></tr>';
		
			// Set variables to compare to, so we can create a section for each course
			$old_course = $r_course_number;
			
			echo '<tr><th colspan="2">' . $r_course_number . ': ' . $r_course_title . '</th></tr>';
			
			
			echo '<tr><td colspan="2">
				<label><input type="checkbox" onclick="CheckAll(this.form, this, ' . 
				$r_course_id . ')" /> mark all in this course</label>';
				
			echo '</td></tr>';
			
		}
		
		// Allow users to delete items if it's not a recent request
		$delete_link = '';
		// Was it submitted before the cutoff date, or is it a new request that has not yet been emailed to library staff?
		if(($r_entered_on < $cutoff) || $r_emailed == 0) {
				$delete_link = ' <a class="subtle" title="remove this item from the list"
					href="delete.php?id=' . urlencode($r_id) . '">' .
					'<img src="icon-delete.gif" align="top" alt="x" /></a>';
		}
		
		// Allow users to edit items if it's not a recent request
		$edit_link = '';
		// Was it submitted before the cutoff date, or is it a new request that has not yet been emailed to library staff?
		if(($r_entered_on < $cutoff) || $r_emailed == 0) {
				$edit_link = ' <a class="subtle" title="edit this reserve item"
					href="choose-loan-period.php?item_id=' . urlencode($r_id) . '">' .
					'<img src="icon-edit.gif" align="top" alt="x" /></a>';
		}
		
		// Display each item from the course
		echo '<tr><td style="vertical-align: top; width:20%;!important"><input type="checkbox" ' .
			($r_entered_on >= $cutoff ? '' : 'id="course' . $r_course_id . 'item' . ($box_count++) . '" ') . 
			' name="item[]" value="' . $r_id . '" ' .
			($r_entered_on >= $cutoff ? ' disabled="disabled"' : '') . ' />' .
			'</td>
			<td ' . ($r_entered_on < $last_term ? 'style="color:#999"' : '') . 
			'>' . htmlspecialchars($r_author) . ' <em>' .
			htmlspecialchars($r_title) . '</em>' . ($r_format ? ' (' . $r_format . '), ' : ', ') . 
			'Call Number: ' . $r_call_number .'.<br />' .
			$r_loan_period . ' at ' . $r_library . '. ';
			
			// Tell users to contact us if they need to edit recent requests
			
			// Was the item submitted since the cutoff date?
			if ($r_entered_on >= $cutoff) {
				echo ' <span style="font-size:.75em;color:red;">Request ';
				// Has it already been emailed to library staff?
				if ($r_emailed) { // yes
					echo 'received. To edit this request, please contact library staff ';
					echo '.';
				} else { // no
					echo 'pending';
				}
				echo '</span>';
			}
			echo '' . 

			// If date submitted is before "last term," display it
			($r_entered_on < $last_term ? ' (' . date("M j 'y", strtotime($r_entered_on)) . ')' : '') .
			$edit_link . '&nbsp;' . $delete_link;
			
			// indicate if streaming was requested for the item
			if ($r_streaming) {	
				echo '<br /><p class="indent">Streaming requested';
				
				// indicate if subtitles were requested
				if ($r_english_subtitles == 1) {
					$subtitles = 'English';
					echo ' with ' . $subtitles . ' subtitles';
				}

				echo '.</p>';
			}
			echo '</td></tr>';
	}
	 
	echo '<tr><td colspan="2">
		<input type="submit" value="Put Marked Items On Reserve Again" ' .
				($disable ? 'disabled="disabled"' : '') . ' />
		</td></tr></table></form>';

}

?>

<p>When resubmitting multiple items, the previous loan period and course information will be used.  To modify or update course or loan period information, select one item at a time.</p>
 
</div>
</div>



<script language="javascript" type="text/javascript">
function CheckAll(theForm, checker, id) {
	//alert(theForm);
	//alert(checker.checked);
	//alert(id);
	
	var i = 0;
	
	while(document.getElementById("course" + id + "item" + i)) {
		document.getElementById("course" + id + "item" + i).checked = checker.checked;
		i++;
	}

}
</script>

<!-- End Page Editing Here -->
