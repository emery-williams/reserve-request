<?php 

// Put access control/user ID files here
// You'll want a way to identify users and set value for $prof_username.

// Set PHP Variables
$pagetitle = "Course Reserve Request - Item Info"; // Page Title

// Insert Header


// Connect to the database
include("login.php");

// Include file with functions to query Alma API
include("alma-api.php");

?>
<!-- Begin Page Editing Here -->
<div>
<div>
<h1>Course Reserve Request</h1>

<?php

// Get info posted from catalog
$author = trim(htmlspecialchars($_GET["author"]));
$title = trim(htmlspecialchars($_GET["title"]));
$mms = trim(htmlspecialchars($_GET["mms"]));

$isbn = trim(htmlspecialchars($_GET["isbn"]));
$isbn = str_replace ('-', '', $isbn); // strip dashes
// echo "isbn: " . $isbn;


//--- Use mms to get info from Alma API ---//

// Get holdings info in XML format
$xml = getHoldingsInfo($mms);
$bib = simplexml_load_string($xml);

// Get call number
$call_no = getCallNo($bib);

// Get format
$format = getFormat($bib, $isbn);

// Remove semicolons from titles
$title = trim(preg_replace("/\s*\:\s*$/", "", $title));

// If a reserve id was supplied, use stored information.
$item_id = sprintf("%d", $_GET["item_id"]);

if ($item_id == '0') {
	$item_id = '';	
}

// For debugging
// echo 'item id: ' . $item_id; 

if($item_id) {
	
	try {
		$query = 'SELECT author, title, call_number, mms_id
				FROM reserve_items
				WHERE
				id = :item_id AND 
				username = :prof_username
				LIMIT 1';
				
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':prof_username', $prof_username);
		$stmt->bindValue(':item_id', $item_id);
		$stmt->execute();
		
		// Get number of results
		$numrows = $stmt->rowCount();
		//echo $numrows;
			
		$errorInfo = $stmt->errorInfo();
		if (isset($errorInfo[2])) {
			$error = $errorInfo[2];	
		}
	} catch (Exception $e) {
		$error = $e->getMessage();	
	}
	
} else {
	
	// For debugging
	// echo "no prof name or item id";
	
}

if (isset($error)) {
    echo "<p>" . $error . "</p>";
}

// If stored info was found, assign it to variables
if($numrows > 0) {

	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	$author = $result[author];
	// echo $author . '<br />';
	$title = $result[title];
	// echo $title . '<br />';
	$call_no = $result[call_number];
	// echo $call_no . '<br />';
	$mms = $result[mms_id];
	// echo $mms . '<br />';

} else {
	// For debugging
	// echo "no results found.";	
}
	

// Show the status bar
$arrow = '<img src="block-arrow.gif" hspace="3" />';
echo '<p class="statusBar">
	<strong>Item Information</strong> ' . $arrow . '
	Loan Terms ' . $arrow . '
	Course ' . $arrow . '
	Done
	</p>';
	

// Show form with information

echo '<form method="get" action="choose-loan-period.php" onsubmit="return CheckForm(this)">

	<h3>Enter An Item From Our Collection*</h3>
	<table style="width:100%; table-layout:fixed;">';
	
	// If the item is an e-book, don't ask for a call number
	if ($format != 'electronicresource') {
		echo '<tr><td style="width:15%;"> Call # </td><td> <input type="text" name="call_no" value="' . $call_no . '" size="60" onchange="ClearHiddenInfo(this.form)" /> <em>required</em></td></tr>';
	}
	
	// If we have an isbn or format, clear them if they change the call number or title
	echo '<tr><td> Author </td><td> <input type="text" name="author" value="' . $author . '" size="60" /> enter as <em>last name, first name</em></td></tr>
	<tr><td> Title </td><td> <input type="text" name="title" value="' . $title . '" size="60" onchange="ClearHiddenInfo(this.form)" /> <em>required</em></td></tr>';
	
	// If there is no format passed through URL, ask for format
	if ($format == '') {
		echo '<tr><td> Format </td><td> <input type="radio" name="format1" value="Book" ' . 
		($format && $isbn != '' ? 'checked="checked"' : '') . '>Book&nbsp;&nbsp;
		<input type="radio" name="format1" value="videorecording">Video/DVD&nbsp;&nbsp;
		<input type="radio" name="format1" value="soundrecording">Audio';
	}
echo	
	'<tr><td colspan="2">
		<input type="hidden" name="item_id" value="' . $item_id . '" />
		<input type="submit" value=" Next " id="inputFocusTarget" /> </td></tr>
	</table>
	
	<input type="hidden" name="isbn" value="' . $isbn . '" />
	<input type="hidden" name="mms" value="' . $mms . '" />
	<input type="hidden" name="format2" value="' . $format . '" />
	
	</form>';

?>

<p class="small" style="padding-top:20px">*To place a personal item on reserve, please bring it to the main desk in Sawyer or Schow.</p>
</div>
</div>

<script language="javascript" type="text/javascript">
function CheckForm(theForm) {

	if(theForm.call_no.value == "") {
		alert('Please enter a call number.');
		theForm.call_no.focus();
		return false;
	}

	if(theForm.title.value == "") {
		alert('Please enter a title.');
		theForm.title.focus();
		return false;
	}
	
	if(theForm.format1.value == "") {
		alert('Please choose a format.');
		theForm.format1[0].focus();
		return false;
	}

	return true;
}


function ClearHiddenInfo(theForm) {
	if(theForm.isbn) { theForm.isbn.value = ''; }
	if(theForm.format) { theForm.format.value = ''; }
}

</script>


<!-- End Page Editing Here -->