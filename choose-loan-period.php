<?php 

// Put access control/user ID files here
// You'll want a way to identify users and set value for $prof_username.

// Set PHP Variables
$pagetitle = "Course Reserve Request - Loan Terms"; // Page Title

// Insert Header


// Connect to the database
include("login.php");


?>

<!-- Begin Page Editing Here -->

<div>
<div>
<h1>Course Reserve Request</h1>

<?php

include("call_number_utilities.php");

// Get info posted from catalog
$author = trim(($_GET["author"]));
$title = trim(($_GET["title"]));
$call_number = trim(($_GET["call_no"]));
$item_id = sprintf("%d", $_GET["item_id"]);
$mms = sprintf("%d", $_GET["mms"]);
$isbn = trim(($_GET["isbn"]));
$format_manual = trim(($_GET["format1"]));
$format_catalog = trim(($_GET["format2"]));
if ($format_catalog != '') {
	$format = $format_catalog;
} else {
	$format = $format_manual;	
}


$location = ($format == "CD" ? "compact disc" : "");


// Add a sortable title
if ($title != '') {
	$title_sort = preg_replace("/^(the|a|an|la|le)\s+/i", "", $title);
}

// Create sortable call number
if ($call_number != '') {
	$call_number_sort = SortableLC($call_number);
}


// Make sure they supplied required information
if(empty($item_id)) {
	if ($format != 'electronicresource') {
		!empty($call_number) or die("Error: You must enter a call number.");
	}
	!empty($title) or die("Error: You must enter a title.");
}


// Insert or update a record if information was provided

// build statement of items to update


$sql = "SET author = :author,
	title = :title,
	title_sort = :title_sort,
	call_number = :call_number,
	call_number_sort = :call_number_sort,
	mms_id = :mms_id,
	isbn = :isbn,
	format = :format,
	streaming = '0',
	english_subtitles = '0',
	comments = ' ', ";

// echo $sql . '<br />';

// Update or insert if they supplied call number and title
// Don't require a call number for e-books ($format == 'electronicresource')
if ($format != 'electronicresource') {

	if(!empty($call_number) && !empty($title)) {
		
		if($item_id) {
			
			try {		
				$query = "UPDATE reserve_items " . $sql . "
						WHERE id = :item_id
						LIMIT 1";				
				//echo $query . '<br />';			
				$stmt = $db->prepare($query);	
				$stmt->bindValue(':author', $author);
				$stmt->bindValue(':title', $title);
				$stmt->bindValue(':title_sort', $title_sort);
				$stmt->bindValue(':call_number', $call_number);
				$stmt->bindValue(':call_number_sort', $call_number_sort);
				$stmt->bindValue(':mms_id', $mms);
				$stmt->bindValue(':isbn', $isbn);
				$stmt->bindValue(':format', $format);
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
			
			try {
				$query = "INSERT INTO reserve_items " . $sql .  
						"username = :prof_username,
						entered_on = '" . DATETIME . "'";
				// echo $query . '<br />';			
				$stmt = $db->prepare($query);	
				$stmt->bindValue(':author', $author);
				$stmt->bindValue(':title', $title);
				$stmt->bindValue(':title_sort', $title_sort);
				$stmt->bindValue(':call_number', $call_number);
				$stmt->bindValue(':call_number_sort', $call_number_sort);
				$stmt->bindValue(':mms_id', $mms);
				$stmt->bindValue(':isbn', $isbn);
				$stmt->bindValue(':format', $format);
				$stmt->bindValue(':prof_username', $prof_username);
				$stmt->execute();
				$item_id = $db->lastInsertId();
		
				// For debugging
				// echo 'Item id: ' . $item_id . '<br />';
				
				$errorInfo = $stmt->errorInfo();
				if (isset($errorInfo[2])) {
					$error = $errorInfo[2];	
				}
				
			} catch (Exception $e) {
				$error = $e->getMessage();	
			}
			
		}
	}

} 

else {
	if(!empty($title)) {
		if($item_id) {
			try {
				$query = "UPDATE reserve_items " . $sql . "
						WHERE id = :item_id
						LIMIT 1";	
				$stmt = $db->prepare($query);	
				$stmt->bindValue(':author', $author);
				$stmt->bindValue(':title', $title);
				$stmt->bindValue(':title_sort', $title_sort);
				$stmt->bindValue(':call_number', $call_number);
				$stmt->bindValue(':call_number_sort', $call_number_sort);
				$stmt->bindValue(':mms_id', $mms);
				$stmt->bindValue(':isbn', $isbn);
				$stmt->bindValue(':format', $format);
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
			try {
				$query = "INSERT INTO reserve_items " . $sql . ",
						username = :prof_username,
						entered_on = '" . DATETIME . "'";
				$stmt = $db->prepare($query);	
				$stmt->bindValue(':author', $author);
				$stmt->bindValue(':title', $title);
				$stmt->bindValue(':title_sort', $title_sort);
				$stmt->bindValue(':call_number', $call_number);
				$stmt->bindValue(':call_number_sort', $call_number_sort);
				$stmt->bindValue(':mms_id', $mms);
				$stmt->bindValue(':isbn', $isbn);
				$stmt->bindValue(':format', $format);
				$stmt->bindValue(':prof_username', $prof_username);
				$stmt->execute();
				$item_id = $db->lastInsertId();
				
				// For debugging
				// echo 'Item id: ' . $item_id . '<br />';
				
				$errorInfo = $stmt->errorInfo();
				if (isset($errorInfo[2])) {
					$error = $errorInfo[2];	
				}
				
			} catch (Exception $e) {
				$error = $e->getMessage();	
			}
		}
	}
}

// Get the loan period, library and format information from the record

if($item_id) {
	
	// For debugging
	//echo $item_id . '<br />';
	
	try {
		$query = "SELECT * FROM reserve_items
			WHERE id = :item_id";
				
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

// Get number of results
$numrows = $stmt->rowCount();
//echo 'numrows: ' . $numrows;

$row = $stmt->fetch(PDO::FETCH_ASSOC);
extract($row, EXTR_PREFIX_ALL, 'r');

// Show the status bar
$arrow = '<img src="/assets/images/block-arrow.gif" hspace="3" />';
echo '<p class="statusBar">
	Item Information ' . $arrow . '
	<strong>Loan Terms</strong> ' . $arrow . '
	Course ' . $arrow . '
	Done
	</p>';

// Show form with information

$loanColor = 'ff9';
$libraryColor = 'cff';


echo '<form method="get" action="choose-course.php">

	<div style="background-color:#dee4ee; padding: 0.5em!important; margin-bottom: 1em !important;">
	<h3>Reserve Item Information</h3>
	<p>' . htmlspecialchars($r_author) . '. 
		<em>' . htmlspecialchars($r_title) . '</em>' .
		($r_call_number ? '<br />' . htmlspecialchars($r_call_number): '') . '
		</p></div>';

if ($format == 'videorecording' || $r_format == 'videorecording') {
	echo '<h4 style="padding-top:1em;">The library may investigate streaming rights so that the content can be distributed online through Glow.</h4>
	  Are you interested in streaming this film for course use?
	<p class="indent">
		<input type="radio" name="streaming" value="1">Yes&nbsp;
		<input type="radio" name="streaming" value="0" checked="checked">No
	</p>';
	echo
	'<div id="subtitles">
	If a non-English language film, do you want English subtitles included?<br />
	<p class="indent">
	<input type="radio" name="english_subtitles" value="1"> Yes&nbsp;&nbsp;
	<input type="radio" name="english_subtitles" value="0" checked="checked"> No
	</p>
	If you want subtitles included in a language <strong>other than English</strong>, please contact library staff.
	</div>';
	
}
	
// Remove loan period option for e-books ($format == 'electronicresource')
if ($format != 'electronicresource') {
	echo '<h4>Choose a Loan Period: (default: ' . $r_loan_period . ')</h4>
		
		<p class="indent">
			<label id="loan_period0label"' . ($r_loan_period == "24 hour" ? 
				' style="background-color:#' . $loanColor . '"' : '') . '>
				<input type="radio" name="loan_period" 
				id="loan_period0" onchange="UpdateLabel(this.name, 0, \'' . $loanColor . '\')"
				value="24 hour" '.
				($r_loan_period == "24 hour" ? 'checked="checked"' : '') . ' />24 hour&nbsp;&nbsp;</label>
				
			<label id="loan_period1label"' . ($r_loan_period == "4 hour" ? 
				' style="background-color:#' . $loanColor . '"' : '') . '>
				<input type="radio" name="loan_period" 
				id="loan_period1" onchange="UpdateLabel(this.name, 1, \'' . $loanColor . '\')"
				value="4 hour" '.
				($r_loan_period == "4 hour" ? 'checked="checked"' : '') . ' />4 hour&nbsp;&nbsp;</label>
			
			<label id="loan_period4label"' . ($r_loan_period == "4 hour Lib Only" ? 
				'style="background-color:#' . $loanColor . '"' : '') . '>
				<input type="radio" name="loan_period" 
				id="loan_period4" onchange="UpdateLabel(this.name, 4, \'' . $loanColor . '\')"
				value="4 hour Lib Only" '.
				($r_loan_period == "4 hour Lib Only" ? 'checked="checked"' : '') . ' />4 hr Library Use Only</label>
				
			</p>';
			
// Set loan period to 'none' for e-books ($format == 'electronicresource')	
} else {
	$r_loan_period = 'E-book (None)';
	echo '<input type="hidden" name="loan_period" value="' . $r_loan_period. '" />';
	
}

echo '<h4>Choose a Library:</h4>
	
	<p class="indent">
		<label id="library0label"' . ($r_library == 'Sawyer' ? 
			' style="background-color:#' . $libraryColor . '"' : '') . '>
			<input type="radio" name="library" 
			id="library0" onchange="UpdateLabel(this.name, 0, \'' . $libraryColor . '\')"
			value="Sawyer" '.
			($r_library == 'Sawyer' ? 'checked="checked"' : '') . ' />Sawyer&nbsp;&nbsp;</label>
		<label id="library1label"' . ($r_library == 'Schow' ? 
			' style="background-color:#' . $libraryColor . '"' : '') . '>
			<input type="radio" name="library" 
			id="library1" onchange="UpdateLabel(this.name, 1, \'' . $libraryColor . '\')"
			value="Schow" '.
			($r_library == 'Schow' ? 'checked="checked"' : '') . ' />Schow</label>
	
	</p>
	
		<input type="hidden" name="item_id" value="' . $r_id . '" />
		<input type="submit" value=" Next " id="inputFocusTarget" /> </td></tr>
	
	</form>';

?>
</div>
</div>

<script language="javascript" type="text/javascript">
function UpdateLabel(labelName, labelNumber, labelColor) {

	for(i=0; i<10; i++) {
		var label = document.getElementById(labelName + i + "label");
		if(label) {
			label.style.backgroundColor='#fff';
		} else {
			break;
		}
	}
	
	var label = document.getElementById(labelName + labelNumber + "label");
	label.style.backgroundColor='#' + labelColor;

}

jQuery(document).ready(function(){
	// Hide subtitles fields unless "yes" is checked for streaming
	var streaming = jQuery("input:radio[name=streaming]:checked");
		if(streaming.val() == "0"){
			//console.log("streaming value: " + streaming.val());
			jQuery("#subtitles").hide();
		}
		else if(streaming.val() == "1"){
			//console.log("streaming value: " + streaming.val());
			jQuery("#subtitles").show();
		}
	var radio = jQuery("input:radio[name=streaming]");
	// var streaming = jQuery("input:radio[name=streaming]:checked");
	// console.log("streaming value: " + streaming.val());
	radio.change(function(){
		var streaming = jQuery("input:radio[name=streaming]:checked");
		if(streaming.val() == "0"){
			//console.log("radio value: " + streaming.val());
			jQuery("#subtitles").hide();
		}
		// show fields if "yes" is checked
		else if(streaming.val() == "1"){
			//console.log("radio value: " + streaming.val());
			jQuery("#subtitles").show();
		}
	});
	
	// Ask about non-English subtitle if they don't want English subtitles
	var subtitles = jQuery("input:radio[name=english_subtitles]:checked");
		if(subtitles.val() == "0"){
			// console.log("subtitles value: " + subtitles.val());
			jQuery("#non_english").show();
		}
		else if(subtitles.val() == "1"){
			// console.log("subtitles value: " + subtitles.val());
			jQuery("#non_english").hide();
			// jQuery("input:radio[name=other_subtitles]").val(0);
		}
	var subtitle_radio = jQuery("input:radio[name=english_subtitles]");
	// var streaming = jQuery("input:radio[name=streaming]:checked");
	// console.log("streaming value: " + streaming.val());
	subtitle_radio.change(function(){
		var subtitles = jQuery("input:radio[name=english_subtitles]:checked");
		if(subtitles.val() == "0"){
			// console.log("subtitles value: " + subtitles.val());
			jQuery("#non_english").show();
		}
		else if(subtitles.val() == "1"){
			// console.log("subtitles value: " + subtitles.val());
			jQuery("#non_english").hide();
			//jQuery("input:radio[name=other_subtitles]").val(0);
		}
	});
	
	// Ask for the language if non-English subtitles is checked
	var other = jQuery("input:radio[name=other_subtitles]:checked");
		if(other.val() == "0"){
			console.log("other value: " + other.val());
			jQuery("#language").hide();
		}
		else if(other.val() == "1"){
			console.log("other value: " + other.val());
			jQuery("#language").show();
		}
	var other_radio = jQuery("input:radio[name=other_subtitles]");
	// var streaming = jQuery("input:radio[name=streaming]:checked");
	// console.log("streaming value: " + streaming.val());
	other_radio.change(function(){
		var other = jQuery("input:radio[name=other_subtitles]:checked");
		if(other.val() == "0"){
			console.log("other value: " + other.val());
			jQuery("#language").hide();
		}
		else if(other.val() == "1"){
			console.log("other value: " + other.val());
			jQuery("#language").show();
		}
	});
});
</script>


<!-- End Page Editing Here -->