<?php 

// Put access control/user ID files here
// You'll want a way to identify users and set value for $prof_username.

// Connect to the database
include("login.php");


if(gettype($_GET["item"]) != "array" || count($_GET["item"]) < 1) {
	// Make sure they selected an item
	die("Error: You must select at least one item to resubmit.");

} else if(count($_GET["item"]) == 1) {
	// If they chose just one item, send them through the submission process
	$item = sprintf("%d", $_GET["item"][0]);
	$url = HTTP . dirname($_SERVER['PHP_SELF']) . 
		'/choose-loan-period.php?item_id=' . $item;
	header("Location: $url"); exit;
}


// Set PHP Variables
$pagetitle = "Resubmit Reserve Requests"; // Page Title

// Insert Header

?>

<!-- Begin Page Editing Here -->
<div>
<div>
<h1><?php echo $pagetitle; ?></h1>


<?php

// Get item IDs to update reserve_items table
$set = "";
foreach($_GET["item"] as $item) {
	$set .= ($set ? "' OR i.id = '" : '') . sprintf("%d", $item);
}
$set = "(i.id = '" . $set . "')";

// For debugging
// echo 'Set: ' . $set . '<br /><br />';

//$where = "i.username = :prof_username AND  i.submitted AND " . $set;
	

// Show confirmation screen for items that have been resubmitted 

if(isset($_GET["finish"])) {

	
	if ($_GET["streaming"]) {
		
		// Get IDs for items with "streaming = yes" 
		$streaming_yes = (array_keys($_GET["streaming"], "1"));
		$streaming_number = count($streaming_yes);
		// For debugging
		// echo "Streaming yes:<br />";
		// echo 'Items with streaming requested' . print_r($streaming_yes);
		// echo '<br />';
		
		// put the IDs together into a WHERE statement for database update
		$yes = "";
		foreach($streaming_yes as $streaming_item) {
			
			// For debugging
			// echo $streaming_item . '<br />';	
			
			$yes .= ($yes ? "' OR i.id = '" : '') . sprintf("%d", $streaming_item);
		}
		
		$yes = "(i.id = '" . $yes . "')";
		
		// Update the streaming options.  We need to do this every year because copyright.
		if (isset($prof_username) && isset($streaming_number) && $yes != '') {
			
			// For debugging
			// echo 'Updating streaming. <br />';
			// echo 'Username: ' . $prof_username . '<br />';
			// echo 'Number of items with streaming requested: ' . $streaming_number . '<br />';
			// echo 'Where statement: ' . $yes . '<br /><br />';
		
			try {
				$query = "UPDATE reserve_items i
						SET 
							i.streaming = '1'
						WHERE " . $yes . "
						LIMIT :streaming_number";	
				$stmt = $db->prepare($query);	
				$stmt->bindParam(':streaming_number', $streaming_number, PDO::PARAM_INT);
				$stmt->execute();
				$stmt->closeCursor();
						
				$errorInfo = $stmt->errorInfo();
				if (isset($errorInfo[2])) {
					$error = $errorInfo[2];	
				}
			} catch (Exception $e) {
				$error = $e->getMessage();	
			}
			
			if (isset($error)) {
				echo '<p>' . $error . '</p>';
			} 

		} else {
		// For debugging
		// echo "no prof name or items that need streaming";
		}
		
		if ($_GET["english_subtitles"]) {
			
			// For debugging
			// echo "SUBTITLES<br />";
			
			// get IDs that want subtitles
			$subtitles_yes = (array_keys($_GET["english_subtitles"], "1"));
			$subtitles_number = count($subtitles_yes);
			// For debugging
			// echo "Subtitles yes:<br />";
			// echo 'Items with subtitles requested' . print_r($subtitles_yes);
			// echo "<br />";
			
			// put the IDs together into a WHERE statement for database update
			$subtitles = "";
			foreach($subtitles_yes as $subtitles_item) {
				$subtitles .= ($subtitles ? "' OR i.id = '" : '') . sprintf("%d", $subtitles_item);
			}
			
			$subtitles = "(i.id = '" . $subtitles . "')";
			
			// Update the subtitles.
			if (isset($prof_username) && isset($subtitles_number) && $subtitles_yes != '') {
			
				// For debugging
				// echo 'Updating subtitles. <br />';
				// echo 'Username: ' . $prof_username . '<br />';
				// echo 'Number of items with subtitles requested: ' . $subtitles_number . '<br />';
				// echo 'Where statement: '  . $subtitles . '<br /><br />';
			
				try {
					$query = "UPDATE reserve_items i
							SET 
								i.english_subtitles = '1'
							WHERE " . $subtitles . "
							LIMIT :subtitles_number";	
					$stmt = $db->prepare($query);	
					$stmt->bindParam(':subtitles_number', $subtitles_number, PDO::PARAM_INT);
					$stmt->execute();
					$stmt->closeCursor();
							
					$errorInfo = $stmt->errorInfo();
					if (isset($errorInfo[2])) {
						$error = $errorInfo[2];	
					}
				} catch (Exception $e) {
					$error = $e->getMessage();	
				}
				
				if (isset($error)) {
					echo '<p>' . $error . '</p>';
				} 

			} else {
			// For debugging
			// echo "no films with subtitles";
			}
		
		}
			
		
	}
	
	
	// Get items that are associated with a course and have been submitted
	$count = count($_GET["item"]);
	
	if (isset($prof_username) && isset($count) && $set != '') {
	
		// Reset streaming, subtitles and language options.  We need to ask every year because copyright.
		
		// For debugging
		// echo 'Updating items. <br />';
		// echo 'Username: ' . $prof_username . '<br />';
		// echo 'Set: ' . $set . '<br /><br />';
	
		try {
			$query = "UPDATE reserve_items i
					SET 
						i.submitted = '1',
						i.emailed = '0', 
						i.entered_on = '" . DATETIME . "' 
					WHERE
					i.username = :prof_username AND " . $set . "
					LIMIT :count";		
			$stmt = $db->prepare($query);	
			$stmt->bindValue(':prof_username', $prof_username);
			$stmt->bindParam(':count', $count, PDO::PARAM_INT);
			$stmt->execute();
			$stmt->closeCursor();
					
			$errorInfo = $stmt->errorInfo();
			if (isset($errorInfo[2])) {
				$error = $errorInfo[2];	
			}
		} catch (Exception $e) {
			$error = $e->getMessage();	
		}
		
		if (isset($error)) {
			echo '<p>' . $error . '</p>';
		} 
		
	} else {
		// For debugging
		// echo "required variables missing";
	}	
	

	echo '<p>These items have been submitted to reserves, and will be processed as soon as possible.*
	</p>';
	
	if ($streaming_yes) {
		  echo '<p>If you requested that the library investigate streaming rights, 
		  please see the <strong><a href="http://libguides.williams.edu/faculty-information/instruction#s-lg-box-11531315">
		  streaming information page</a></strong> 
		  for more information about streaming films.</p>';	
	}
	
	echo
	'<h3>What next?</h3>
	<ul>
		<li><a href="./">Review Your Requests</a></li>
		<li><a href="INSERT CATALOG URL HERE">Add another item from the catalog</a></li>
		<li><a href="INSERT LOGOUT FILE HERE">All Done - Log Out</a></li>
		</ul>
		
	<p>&nbsp;</p>
	<p class="small">* During busy periods, it may take up to 
	two weeks to process some requests.</p>';
	
} else { // Display items for resubmission
	
	if (isset($prof_username) && $set != '') {
	
	// Reset streaming, subtitles and language options.  We need to ask every year because copyright.
	
	// For debugging
		// echo 'Resetting streaming, etc. <br />';
		// echo $prof_username . '<br />';
		// echo $set . '<br /><br />';
	
		try {
			$query = "UPDATE reserve_items i
					SET 
						i.streaming = '0',
						i.english_subtitles = '0', 
						i.other_subtitles = '0', 
						i.language = ''
					WHERE
					i.username = :prof_username AND 
					i.submitted AND " . $set;		
			$stmt = $db->prepare($query);	
			$stmt->bindValue(':prof_username', $prof_username);
			$stmt->execute();
			$stmt->closeCursor();
					
			$errorInfo = $stmt->errorInfo();
			if (isset($errorInfo[2])) {
				$error = $errorInfo[2];	
			}
		} catch (Exception $e) {
			$error = $e->getMessage();	
		}
		
		if (isset($error)) {
			echo '<p>' . $error . '</p>';
		} 
		
	} else {
		// For debugging
		// echo "no prof name or items";
	}	
	
	// Now get items that are associated with a course and have been submitted
	

	if (isset($prof_username) && $set != '') {
	
		// For debugging
		// echo 'Getting items to resubmit <br />';
		// echo $prof_username . '<br />';
		// echo $set . '<br /><br />';
	
		
		try {
			$query = "SELECT * FROM reserve_items i
					JOIN reserve_courses c ON i.course_id = c.course_id
					WHERE
					i.username = :prof_username AND 
					i.submitted AND" . $set . 
					"ORDER BY i.title_sort DESC";	
			$stmt = $db->prepare($query);	
			$stmt->bindValue(':prof_username', $prof_username);
			$stmt->execute();
			//$result = $stmt->fetchAll();
					
			$errorInfo = $stmt->errorInfo();
			if (isset($errorInfo[2])) {
				$error = $errorInfo[2];	
			}
		} catch (Exception $e) {
			$error = $e->getMessage();	
		}
		
		
		if (isset($error)) {
			echo "<p>Error: " . $error . "</p>";
		} 
		
		// echo '<pre>';
		// var_dump($result);
		// echo '</pre>';	
	
	} else {
		// For debugging
		// echo "no prof name or items";
	}	



	$cutoff = date("Y-m-d H:i:s", strtotime("-3 weeks"));
	
	echo '<h3>The following items will be placed on reserve again:</h3>
	
		<form method="get" action="resubmit.php" onsubmit="return CheckForm(this)">
		<input type="hidden" name="finish" value="1" />';
	
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		extract($row, EXTR_PREFIX_ALL, 'r');
	
		echo '<div style="border:1px solid #e9edf4;padding:0.4em"><input type="hidden" name="item[]" value="' . $r_id . '"  />
			<strong>Item info: </strong>' . ($r_author != '' ? htmlspecialchars($r_author) . '.' : '') .  
			'<em>' . htmlspecialchars($r_title) . '</em><br /> 
			<strong>Call number: </strong>' .htmlspecialchars($r_call_number) . ' &nbsp; &nbsp;
			<strong>Loan Period: </strong>' . htmlspecialchars($r_loan_period) . ' &nbsp; &nbsp;
			<strong>Library: </strong>' . htmlspecialchars($r_library) . '<br />
			<strong>Course: </strong>' . htmlspecialchars($r_course_number) . ': ' .
				htmlspecialchars($r_course_title); 
			if ($r_format == 'videorecording') {
				echo '<br /><br />';
				echo 'The library may investigate streaming rights for this film so it can be distributed online through
				Glow.  Are you interested in streaming this film for course use?
				<br />
					<input type="radio" name="streaming[' . $r_id . ']" value="1">Yes&nbsp;
					<input type="radio" name="streaming[' . $r_id . ']" value="0">No<br />';
				
				// Get subtitle info
				echo '
				<div id="subtitles[' . $r_id . ']">
					<br />
					If a non-English language film, do you want English subtitles included?<br />
						<span class="indent">
							<input type="radio" name="english_subtitles[' . $r_id . ']" value="1"> Yes&nbsp;&nbsp;
							<input type="radio" name="english_subtitles[' . $r_id . ']" value="0" checked="checked"> No<br /><br />
						</span>
					If you want subtitles included in a language <strong>other than English</strong>, please contact library staff';
				echo '.';
						
				echo '</div>';
				
				//jQuery to show/hide subtitle preferences
				echo '<script>
				jQuery(document).ready(function(){
					jQuery(\'[id="subtitles[' . $r_id . ']"]\').hide();
					// Hide subtitles fields unless "yes" is checked for streaming
					/*
					var streaming' . $r_id . ' = jQuery(\'input:radio[name="streaming[' . $r_id . ']"]:checked\');
					if(streaming' . $r_id . '.val() == "0"){
						console.log("streaming value: " + streaming' . $r_id . '.val());
						jQuery(\'[id="subtitles[' . $r_id . ']"]\').hide();
					}
					else if(streaming' . $r_id . '.val() == "1"){
						console.log("streaming value: " + streaming' . $r_id . '.val());
						jQuery(\'[id="subtitles[' . $r_id . ']"]\').show();
					}
					*/
					var radio = jQuery(\'input:radio[name="streaming[' . $r_id . ']"]\');
					// console.log("streaming value: " + streaming' . $r_id . '.val());
					radio.change(function(){
						var streaming' . $r_id . ' = jQuery(\'input:radio[name="streaming[' . $r_id . ']"]:checked\');
						if(streaming' . $r_id . '.val() == "0"){
							console.log("streaming value: " + streaming' . $r_id . '.val());
							jQuery(\'input:radio[name="english_subtitles[' . $r_id . ']"][value="0"]\').attr(\'checked\', true);
							jQuery(\'[id="subtitles[' . $r_id . ']"]\').hide();
							
						}
						else if(streaming' . $r_id . '.val() == "1"){
							console.log("streaming value: " + streaming' . $r_id . '.val());
							jQuery(\'[id="subtitles[' . $r_id . ']"]\').show();
						}
					});
				});';
				echo '</script>';
			}
			echo '</div><br />';
		
	}
	
	echo '<p><input type="submit" value="Finish" /></p></form>
	
	<p>When resubmitting multiple items, the previous loan period 
	and course information will be used.  To modify or update 
	course or loan period information, select one item at a time.</p>';

}

?>


<script language="javascript" type="text/javascript">
function CheckForm(theForm) {

	if(theForm.streaming['<?php echo $r_id?>'].value == "") {
		alert('Please indicate if you want streaming for your film titles.');
		theForm.call_no.focus();
		return false;
	}

	if(theForm.english_subtitles['<?php echo $r_id?>'].value == "") {
		alert('Please indicate if you want subtitles for your streamed film titles.');
		theForm.call_no.focus();
		return false;
	}
	
	return true;
}

</script>

</div>
</div>

<!-- End Page Editing Here -->