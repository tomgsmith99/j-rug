<?php

// Janrain Random User Generator (J-RUG)
// tsmith@janrain.com

session_start();

include 'includes/functions/errorChecking.php';

$keyFilePath = "/Users/tom/.janrain-capture";

$output = "<div class='divTable'>";

/**** Look for a value for $keyFilePath ***/

$output .= establishErrorCategory("Value for key file path?");

if (empty($keyFilePath)){ exitWithOutput($output, "None"); }
else { $output .= keepGoing($keyFilePath); }

/**** Make sure file exists ***/

$output .= establishErrorCategory("File exists?");

if (file_exists($keyFilePath)){ $output .= keepGoing(); }
else { exitWithOutput($output, "No"); }

/**** Can file be opened? ***/

$output .= establishErrorCategory("Can file be opened?");

$keyFileHandle = fopen($keyFilePath, "r");

if ($keyFileHandle == FALSE) { exitWithOutput($output, "No"); }
else { $output .= keepGoing(); }

/*********************************************************************/
/***** Is the file valid JSON? ***/

$output .= establishErrorCategory("Is the file valid JSON?");

$thisArray = json_decode(fread($keyFileHandle, filesize($keyFilePath)), TRUE);

if (json_last_error() == JSON_ERROR_NONE) { $output .= keepGoing(); }
else { exitWithOutput($output, "No"); }

/*********************************************************************/
/***** How many capture servers are in the file? ***/

$output .= establishErrorCategory("How many instances found?");

$output .= keepGoing(sizeof($thisArray["clients"]));

/********************************************************************/
/****** Ask the user to choose a server ******/

if (sizeof($thisArray["clients"]) == 1) {

    // only one set of values, so just use this set of values.
    $_SESSION['captureServer']['value'] = $thisArray["apid_uri"];
    $_SESSION['client_id'] = $thisArray["client_id"];
    $_SESSION['client_secret'] = $thisArray["client_secret"];

}
else {
    
    $_SESSION['listOfServers'] = $thisArray["clients"];

    foreach ($thisArray["clients"] as $key => $values) {
        
        $output .= "<div class='divRow'>";
                
        $output .= "<div class= 'serverNameCell'>" . $key . "</div>";

        $output .= "<div class = 'divCell'>";
        
        $output .= "<button class = 'client' id = '$key'>Select</button>";
        
        $output .= "</div>";
        
        $output .= "</div>";

    }
}

$output .= "</div>";

$thisArray["html"] = $output;

$thisArray["foundServers"] = "OK";

echo json_encode($thisArray);

exit;