<?php 

// Put access control/user ID files here
// You'll want a way to identify users and set value for $prof_username.

// Set PHP Variables
$pagetitle = "Course Reserve Request - Choose Course"; // Page Title

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

// Get info from previous page
$streaming = trim($_GET["streaming"]);
$english_subtitles = trim($_GET["english_subtitles"]);
$loan_period = trim($_GET["loan_period"]);
$library = trim($_GET["library"]);
$item_id = sprintf("%d", $_GET["item_id"]);

// Update the record we're working on

if($item_id) {
			
	try {		
		$query = "UPDATE reserve_items SET
				streaming = :streaming,
				english_subtitles = :english_subtitles,
				loan_period = :loan_period,
				library = :library
				WHERE id = :item_id 
				LIMIT 1";				
		//echo $query . '<br />';			
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':streaming', $streaming);
		$stmt->bindValue(':english_subtitles', $english_subtitles);
		$stmt->bindValue(':loan_period', $loan_period);
		$stmt->bindValue(':library', $library);
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

// Get information on this record, including course info if any
if($item_id) {
			
	try {		
		$query = "SELECT * FROM reserve_items i
				LEFT OUTER JOIN reserve_courses c
				ON i.course_id = c.course_id
				WHERE id = :item_id";			
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

// Use their previously selected contact info again
if($r_instructor) {
	$name = $r_instructor;
} else {
	$name = $prof_name;
}

if($r_email) {
	$email = $r_email;
} else {
	$email = $prof_email;
}


// Show the status bar
$arrow = '<img src="/assets/images/block-arrow.gif" hspace="3" />';
echo '<p class="statusBar">
	Item Information ' . $arrow . '
	Loan Terms ' . $arrow . '
	<b>Course</b> ' . $arrow . '
	Done
	</p>';

// Show form with information

$loanColor = 'ff9';
$libraryColor = 'cff';

echo '<form method="get" action="finish.php" onsubmit="return CheckCourseForm(this)">
	<div style="background-color:#dee4ee; padding: 0.5em!important;">
	<h3>Reserve Item Information</h3>
	<p>' . htmlspecialchars($r_author) . '. 
		<em>' . htmlspecialchars($r_title) . '</em><br />' . 
		htmlspecialchars($r_call_number) . ' &nbsp; &nbsp;
		Loan Period: ' . htmlspecialchars($r_loan_period) . ' &nbsp; &nbsp;
		Library: ' . htmlspecialchars($r_library) . '
		</p></div>

	<h4 style="padding-top:1em;">Choose a Course:</h4><p class="indent">';
	

$profName = preg_replace("/,.*$/", "", $prof_name);
$count = 0;

// Get previously used courses
if($prof_username) {
			
	try {		
		$query = "SELECT * FROM reserve_courses
				WHERE username = :prof_username
				ORDER BY course_number ASC";		
		//echo $query . '<br />';			
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
	echo "no prof username";
}

if (isset($error)) {
    echo "<p>" . $error . "</p>";
}

// Get number of results
$numrows = $stmt->rowCount();
//echo 'numrows: ' . $numrows;


// Display previously used courses
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	extract($row, EXTR_PREFIX_ALL, "c");
		
	echo '<label id="course' . $count . 'label">' . 
		'<input type="radio" name="course" 
		id="course' . $count . '" onfocus="UpdateLabel(this.name, ' . $count . ', \'' . $loanColor . '\')"
		value="reuse' . $c_course_id . '"/>' . 
		$c_course_number . ': ' . $c_course_title . '</label><br />';
	
	$count++;

}

if($numrows > 0) echo '<br />';


	
// Give them the option of entering a course from scratch
echo '<em>enter a course manually:</em><br />
	<span id="new">Course Dept, Number, Semester: <input type="text" name="new_course" id="new_course"
	alt="" onfocus="UseNewCourse(this.form); this.focus()" size="30" /><br />
	Course Title: <input type="text" name="new_course_title" id="new_course_title"
	alt="" onfocus="UseNewCourse(this.form); this.focus()" size="50" /></span></p>';

// Additional Comments (requested Feb 2015)
echo '<h4>Additional Comments</h4><p class="indent">
<textarea wrap="hard" rows="3" cols="50" name="comments"></textarea>
</p>';

// Get their contact info	
echo '<h4>Contact Info:</h4><span class="warn">If you are a faculty proxy, please enter the faculty member\'s contact information below.</span><br />
	<table class="form" style="width:600px">
	<tr><td> Name: </td><td> <input type="text" name="instructor" value="' . $name . '" size="60" /> </td></tr>
	<tr><td> E-Mail: </td><td> <input type="text" name="email" value="' . $email . '" size="60" /> </td></tr>
	<tr><td colspan="2">
		<input type="hidden" name="item_id" value="' . $r_id . '" />
		<input type="submit" value=" Finish " id="inputFocusTarget" /> </td></tr>
	</table>
	
	</form>';	

?>
</div>
</div>

<script language="javascript" type="text/javascript">
function UpdateLabel(labelName, labelNumber, labelColor) {

	for(i=0; i<100; i++) {
		var label = document.getElementById(labelName + i + "label");
		if(label) {
			label.style.backgroundColor='#fff';
		} else {
			break;
		}
	}
	
	var label = document.getElementById(labelName + labelNumber + "label");
	label.style.backgroundColor='#' + labelColor;
	
	var label = document.getElementById("new");
	label.style.backgroundColor='#fff';
	
	var new_course = document.getElementById("new_course");
	var new_course_title = document.getElementById("new_course_title");
	if(new_course.value != "") { new_course.alt = new_course.value; }
	new_course.value = "";
	if(new_course_title.value != "") { new_course_title.alt = new_course_title.value; }
	new_course_title.value = "";

}

function UseNewCourse(theForm) {
	
	for(i=0; i<100; i++) {
	
		var label = document.getElementById("course" + i + "label");
		if(label) {
			label.style.backgroundColor='#fff';
			theForm.course[i].checked = false;
		} else {
			break;
		}
	
		
	}
	
	var label = document.getElementById("new");
	label.style.backgroundColor='#ff9';
	
	if(theForm.new_course.value == "" && theForm.new_course.alt != "") {
		theForm.new_course.value = theForm.new_course.alt;
	}
	if(theForm.new_course_title.value == "" && theForm.new_course_title.alt != "") {
		theForm.new_course_title.value = theForm.new_course_title.alt;
	}

}

function CheckCourseForm(theForm) {
	
	// Check contact info
	if(theForm.instructor.value == "") {
		alert('You must enter your name.');
		theForm.instructor.focus();
		//theForm.instructor.style.backgroundColor = '#fcc';
		return false;
	}
	
	if(theForm.email.value == "") {
		alert('You must enter your e-mail.');
		theForm.email.focus();
		//theForm.email.style.backgroundColor = '#fcc';
		return false;
	}
	
	// Check if they've selected a course
	if(theForm.course) {
		if(theForm.course.length) {
			for(var i=0; i<theForm.course.length; i++) {
				if(theForm.course[i].checked) { 
					return true;
				}
			}
		} else {
			if(theForm.course.checked) {
				return true;
			}
		}
	}
	
	// If no course selected, make sure they entered course info
	if(theForm.new_course.value == "") {
		alert('You must enter a course number or choose a course.');
		theForm.new_course.focus();
		//theForm.new_course.style.backgroundColor = '#fcc';
		return false;
	}
	if(theForm.new_course_title.value == "") {
		alert('You must enter a course title or choose a course.');
		theForm.new_course_title.focus();
		//theForm.email.style.backgroundColor = '#fcc';
		return false;
	}

	return true;
}

</script>


<!-- End Page Editing Here -->

