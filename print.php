<?php 
// Connect to the database
include("login.php");

// Get the course id
$course = sprintf("%d", $_GET["course_id"]);

// Get the options for sorting the list
$SortOption = array(
	"call" => "i.call_number_sort",
	"author" => "i.author",
	"title" => "i.title_sort",
	"terms" => "i.loan_period, i.library");

// Sort by call number as default
if(isset($SortOption[$_REQUEST["sort"]])) {
	$sort = $_REQUEST["sort"];
} else {
	$sort = "call";
}

if ($course) {
		
	try {
		$query = "SELECT * FROM reserve_courses WHERE
			course_id = :course";
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':course', $course);
		$stmt->execute();
		
		// Get number of results
		$numrows = $stmt->rowCount();
		
		$errorInfo = $stmt->errorInfo();
		if (isset($errorInfo[2])) {
			$error = $errorInfo[2];	
		}
	
	} catch (Exception $e) {
		$error = $e->getMessage();	
	}
	
} else {
	// For debugging
	// echo "no course";
}

if (isset($error)) {
    echo "<p>" . $error . "</p>";
}

if ($numrows > 0) {
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	extract($row, EXTR_PREFIX_ALL, 'c');	
}

// Set PHP Variables
$pagetitle = htmlspecialchars($c_course_number) . 
($c_course_title != "?" ? ": " . htmlspecialchars($c_course_title) : " Reserve List");

// Insert Header
?>
<!-- Begin Page Editing Here -->

<h1><?php echo $pagetitle; ?></h1>


<?php

echo '<p>' . htmlspecialchars($c_instructor) . '</p>';

// Get items that are associated with a course and have been submitted
if ($course) {
		
	try {
		$query = "SELECT * FROM reserve_items i
				JOIN reserve_courses c ON i.course_id = c.course_id
				WHERE i.course_id = :course
				ORDER BY " . $SortOption[$sort] . ", i.call_number_sort, i.author, i.title_sort";
		$stmt = $db->prepare($query);	
		$stmt->bindValue(':course', $course);
		$stmt->execute();
		
		// Get number of results
		$numrows = $stmt->rowCount();
		
		$errorInfo = $stmt->errorInfo();
		if (isset($errorInfo[2])) {
			$error = $errorInfo[2];	
		}
	
	} catch (Exception $e) {
		$error = $e->getMessage();	
	}
	
} else {
	// For debugging
	// echo "no course";
}

if (isset($error)) {
    echo "<p>" . $error . "</p>";
}


if($numrows == 0) {
	echo '<p>No items to view.</p>';
} else {
	
	echo '<table>';
	echo '<tr>
		<th><a href="' . $_SERVER['PHP_SELF'] . '?course_id=' . urlencode($course) . 
			'" title="sort by call number">Call #</a></th>
		<th><a href="' . $_SERVER['PHP_SELF'] . '?course_id=' . urlencode($course) . 
			'&amp;sort=author" title="sort by author">Author</a></th>
		<th><a href="' . $_SERVER['PHP_SELF'] . '?course_id=' . urlencode($course) . 
			'&amp;sort=title" title="sort by title">Title</a></th>
		<th><a href="' . $_SERVER['PHP_SELF'] . '?course_id=' . urlencode($course) . 
			'&amp;sort=terms" title="sort by reserve terms">Reserve Terms</a></th>
		</tr>';
	echo '</table>';

}
?>

<!-- End Page Editing Here -->
