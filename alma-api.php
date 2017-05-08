<?php

/* ---------------------------------------------------------------------------------
	getHoldingsInfo

	@args = (mms);
	Takes MMS ID and queries Alma API to get holdings info in XML format
	
	ES20160901
	
*/

function getHoldingsInfo($mms) {
  $ch = curl_init();
  $url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/{' . $mms . '}/holdings';
  $templateParamNames = array('{' . $mms . '}');
  $templateParamValues = array(urlencode($mms));
  $url = str_replace($templateParamNames, $templateParamValues, $url);
  $queryParams = '?' . urlencode('apikey') . '=' . urlencode('INSERT YOUR API KEY HERE');
  curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
  $response = curl_exec($ch);
  curl_close($ch);
  
  //var_dump($response);
  return $response;
 
}


/* ---------------------------------------------------------------------------------
	getAvailabilityInfo
	
	@args = (mms);
	Takes MMS ID and queries Alma API to get holdings and availability info in XML format
	
	ES20161103
	
*/
function getAvailabilityInfo($mms) {
  $ch = curl_init();
  $url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/bibs/{' . $mms . '}';
  $templateParamNames = array('{' . $mms . '}');
  $templateParamValues = array(urlencode($mms));
  $url = str_replace($templateParamNames, $templateParamValues, $url);
  $queryParams = '?' . urlencode('expand') . '=' . urlencode('p_avail') . '&' . urlencode('apikey') . '=' . urlencode('INSERT YOUR API KEY HERE');
  curl_setopt($ch, CURLOPT_URL, $url . $queryParams);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
  $response = curl_exec($ch);
  curl_close($ch);
  
  //var_dump($response);
  return($response);

}


/* ---------------------------------------------------------------------------------
	getCallNo

	@args = (bibInfo);
	Takes XML output from getHoldingsInfo and gets the call number for the item
	Order of prefernce for holdings: Sawyer, Schow, LSF
	
	must be preceded by $bibInfo = simplexml_load_string($xml); where $xml is the result of the getHoldingsInfo function
	
	Example:
	
	$xml = getHoldingsInfo($mms);
	$bibInfo = simplexml_load_string($xml);
	
	ES20160901
	
*/

function getCallNo($bibInfo) {

  if ($bibInfo) {
	  
	  foreach ($bibInfo as $holding) {
		  
		  // Get Library
		  $library = $holding->library['desc'];
		  // echo "Library: " . $library . "<br />";
		  
		  // Get location
		  $location = $holding->location['desc'];
		  // echo "Location: " . $location . "<br />";
		  
		  if ($library == 'Sawyer') {
		  
			// Try to get Sawyer call number first (will get books and DVDs)
			if (stripos($location, 'sawyer') !== FALSE) {
				// echo 'This is Sawyer<br />';
				$call_no = $holding->call_number;
				// echo "Sawyer Call number: " . $call_no . "<br />";
				break; 
			
			// Check to make sure it's not a CD call number
			} else if (stripos($location, 'compact disc') !== FALSE) {
				// echo 'This is a CD<br />';
				$call_no = $holding->call_number;
				// echo "CD Call number: " . $call_no . "<br />";
				break;
		    }
			  
		  // If no Sawyer holdings, get Schow call number  
		  } else if (stripos($location, 'schow') !== FALSE){
			  // echo 'This is Schow<br />';
			  $call_no = $holding->call_number;
			  // echo "Schow Call number: " . $call_no . "<br />";
			  break;
		  
		  // If no Schow holdings, get LSF call number  
		  } else if (stripos($location, 'lsf') !== FALSE){
			  // echo 'This is LSF<br />';
			  $call_no = $holding->call_number;
			  // echo "LSF Call number: " . $call_no . "<br />";
			  break;
			  
		  // If no holdings at Sawyer or Schow, item shouldn't be placed on reserve, 
		  // so don't 	provide call number.	  
		  } else {
			  //echo 'This is neither. <br />';
			  $call_no = '';
		  }
	  }	  
  } else {
	  //echo "no XML here :(";
	  //echo "We were not able to obtain call number information.  Please enter a call number manually. <br />";
  }
  
  return $call_no;
}


/* ---------------------------------------------------------------------------------
	getFormat

	@args = (bibInfo, isbn);
	Takes XML output from getHoldingsInfo and gets the call number for the item
	Order of preference for holdings: Sawyer, Schow, LSF
	
	must be preceded by $bibInfo = simplexml_load_string($xml); where $xml is the result of the getHoldingsInfo function
	
	Example:
	
	$xml = getHoldingsInfo($mms);
	$bibInfo = simplexml_load_string($xml);
	
	ES20160901
	
*/

function getFormat($bibInfo, $isbn) {

  if ($bibInfo) {
	  
	  foreach ($bibInfo as $holding) {
		  
		  // Get Library
		  $library = $holding->library['desc'];
		  // echo "Library: " . $library . "<br />";
		  
		  // Get location
		  $location = $holding->location['desc'];
		  // echo "Location: " . $location . "<br />";
		  
		  // Start with Sawyer
		  if ($library == 'Sawyer') {
		  
			// Check for CDs and DVDs at Sawyer
		    if (stripos($location, 'compact disc') !== FALSE) {
				// echo 'This is a CD<br />';
				$format = 'CD';
				// echo "Format: " . $format . "<br />";
				break;
		    } else if (stripos($location, 'dvd') !== FALSE) {
				// echo 'This is Sawyer<br />';
				$format = 'videorecording';
				// echo "Format: " . $format . "<br />";
				break; 
			}
		  
		  // Now check for LSF formats
		  } else if (stripos($location, 'lsf video') !== FALSE){
			  // echo 'This is LSF<br />';
			  $format = 'videorecording';
			  // echo "Format: " . $format . "<br />";
			  break;
			  
		  // If none of the above, check for an ISBN (but do CDs first (see above) because CDs have ISBNs)
		  } else if ($isbn){
			  // echo 'There is an isbn<br />';
			  $format = 'book';
			  // echo "Format: " . $format . "<br />";
			  break;
			  
		  // We're still not sure, so send empty variable and make user choose.	  
		  } else {
			  //echo 'This is neither. <br />';
			  $call_no = '';
		  }
	  }	  
  } else {
	  //echo "no XML here :(";
	  //echo "We were not able to obtain call number information.  Please enter a call number manually. <br />";
  }
  
  return $format;
}

/* ---------------------------------------------------------------------------------
	getAvailability
	@args = (availInfo);
	Takes XML output from getAvailabilityInfo, and gets the availability and locations for all holdings
	
	must be preceded by $availInfo = simplexml_load_string($xml); where $xml is the result of the getAvailabilityInfo function
	
	Example:
	
	$xml = getAvailabilityInfo($mms);
	$availInfo = simplexml_load_string($xml);
	
	ES20161103
	
*/

function getAvailability($availInfo) {
	if ($availInfo) {
	$results = $availInfo->xpath("//datafield[@tag='AVA']"); 
	
		$availability = '<br />';
		foreach ($results as $result) {
		
			// print the members of the array 
			// echo '<pre>';
			// print_r($result); 
			// echo '<br />';
			 
			$library = $result->xpath(".//subfield[@code='b']");
			// echo "Library: " . $library[0] . "<br />";
			
			$location = $result->xpath(".//subfield[@code='c']");
			// echo "Location: " . $location[0] . "<br />";
			
			$call_no = $result->xpath(".//subfield[@code='d']");
			// echo "Call number: " . $call_no[0] . "<br />"; 
			
			$avail= $result->xpath(".//subfield[@code='e']");
			// echo "Availability: " . $avail[0] . "<br />";
			
			// echo '</pre>';
			
			$availability .= '<strong>' . $avail[0] . '</strong> at ' . $location[0] . '<br />';
		}
	}
	
	return $availability;
}
?>