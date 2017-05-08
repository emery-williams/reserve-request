<?php

/* ---------------------------------------------------------------------------------
SortableLC

	@args = (call number, location);
	Returns an alphabetically sortable call number based on lc and location.
	
	NCB 20060620
	
*/
function SortableLC($lc="", $location="") {

	// Standardize case
	$lc = trim(strtoupper($lc));
	
	// Remove trailing periods
	$lc = preg_replace("/\.$/", "", $lc);

	// Deal with non-lc numbers by location
	if(preg_match("/gov\sdoc/i", $location) || preg_match("/\:/i", $lc)) {
		$lc = "GOV DOC $lc";
		
	} else {
		// Make sure each item has at least one sub-class
		$lc = preg_replace("/^(\w+)$/", "$1.0", $lc);
		// HQ1180 -> HQ1180.0
		
		// Following code uses this example:
		//    BL 65.S8 W66 2005
			// Remove space between letters (part 1) and numbers (part 2)
		$lc = preg_replace("/^([A-Z]+)\s*(\d)/", "$1$2", $lc);
		// -> BL65.S8 W66 2005
		
		// Add space before each .
		$lc = preg_replace("/(\S)\./", "$1 .", $lc);
		// -> BL65 .S8 W66 2005
		
		// Add missing decimal points if necessary
		$lc = preg_replace("/\s(\w)/", " .$1", $lc); // Add decimal
		$lc = preg_replace("/\.(\d{4}\w*)$/", "$1", $lc); // Remove from year
		// -> BL65 .S8 .W66 2005
		
		// Add .0 to properly order BL65.S as BL65.0.S before BL65.1.S
		$lc = preg_replace("/^(\w+) (\.\D)/", "$1 .0 $2", $lc);
		// -> BL65 .0 .S8 .W66 2005
		
		// Format initial letters (part 1) with trailing 0s.
		$lc = preg_replace("/^([A-Z]+)(\d)/", "$1@000 $2", $lc); // add @000
		$lc = preg_replace("/^([A-Z]+)@/", "$1", $lc); // remove @
		$lc = preg_replace("/^([A-Z0]{3})0*\s/", "$1 ", $lc); // remove extra 0s
		// -> BL0 65 .0 .S8 .W66 2005
		
		// Format part two with leading 0s.
		$lc = preg_replace("/^([A-Z0]{3})\s(\d+\s\.)/", "$1 000000$2", $lc); // add 0s
		$lc = preg_replace("/^([A-Z0]{3})\s0*(\d{6}\s\.)/", "$1 $2", $lc); // remove extra
		// -> BL0 000065 .0 .S8 .W66 2005
		
		// Format decimal sections with trailing 0s
		$lc = preg_replace("/\.(\w+)/", ".$1@000000", $lc); // add @000000
		$lc = preg_replace("/@000000/", "000000", $lc); // remove @
		$lc = preg_replace("/\.(\w{6})0*/", "$1", $lc); // remove extra
		// -> BL0 000065 000000 S80000 W66000 2005
		
		// Add extra space before years to prevent sorting as sub-cat
		$lc = preg_replace("/\s(\d{4}\w?)$/", "  $1", $lc);
		// -> BL0 000065 000000 S80000 W66000  2005
		
		if(preg_match("/compact disc/i", $location)) {
			// CD call numbers should sort well this way, just give them the CD prefix.
			$lc = "CD $lc";
		} else {
			// LC numbers get the 0 prefix so they list first.
			$lc = "0 $lc";
		}	
	}
	
	return $lc;
}

?>