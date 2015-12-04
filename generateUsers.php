<?php

// Janrain Random User Generator (J-RUG)
// tsmith@janrain.com

// To-do

// Review messaging: is it better to build the html on the PHP side or the JS side?
// Break the 500 user barrier
// Organize includes
// Check size of output directory
// Add more detail to the output file

// Improve reality of emailVerified field: for FB, Yahoo, and Google,
//    it should match the time of account creation
// re-do names to take advantage of new functions
// check out the "date created" field - is it set to GMT?

// Done
// Adjust menu so that "session" re-sets when a user clicks 
//  Load local key file or Enter credentials.
// Review logging and output
// Add front-end UI
// clean up responses (echo) from sendCurlRequest
// review Authentication process
// take advantage of new evaluateJSONresponse function

session_start();

include "includes/includes.php";

// These are core functions
include "getUserBasics.php";
include "getSocialUser.php";
include "uploadUserBatch.php";
include "getDomain.php";
include "getIdentifier.php";
include "getTotalUserCountInDB.php";

// define global values
defineGlobals();

$batchStartTime = time();

$outputFileName = $batchStartTime . ".html";

$thisArray["batchStartTime"] = $batchStartTime;
        
$outputFilePath["local"] = createOutputFile($outputFileName);
$outputFilePath["web"] = OUTPUT_WEB_PATH . $outputFileName;

$logFileHandle = getOutputFileHandle($outputFilePath["local"]);

fwrite ($logFileHandle, "<p>The value of Capture Server is: " . $_SESSION["captureServer"]);
fwrite ($logFileHandle, "<p>The client ID is: " . $_SESSION['client_id']);

$totalNumberOfUsers = $_POST["numberOfUsers"];

fwrite($logFileHandle, "<p>***************************************</p>");
fwrite($logFileHandle, "<p>Starting batch.....");
fwrite($logFileHandle, "<p>The total number of users is: " . $totalNumberOfUsers);
fwrite($logFileHandle, "<p>The number of social users is: " . intval($totalNumberOfUsers * SOCIAL_RATIO));
fwrite($logFileHandle, "<p>The number of traditional users is: " . intval($totalNumberOfUsers * TRAD_RATIO));

// Just renaming the session vars for readability
$captureServer = $_SESSION["captureServer"];
$client_id = $_SESSION['client_id'];
$client_secret = $_SESSION['client_secret'];

if ($totalNumberOfUsers > MAX_BATCH_SIZE) {
    // Split a large batch into smaller batches
    
    $numberOfBatches = intval ($totalNumberOfUsers / IDEAL_BATCH_SIZE);
    
    for ($i = 1; $i <= $numberOfBatches; $i++) {
        $content .= createBatch(IDEAL_BATCH_SIZE, $i);
        sleep(1);
    }

}
else { $content = createBatch($totalNumberOfUsers); }

function createBatch ($batchSize, $i = 1) {
    
    global $logFileHandle;
    global $thisArray;
    
    fwrite($logFileHandle, "<p>----------------------------------------------</p>");
    fwrite($logFileHandle, "<p>Sub-batch number $i");

    $numberOfSocialUsers = intval($batchSize * SOCIAL_RATIO);
    $numberOfTradUsers = intval($batchSize * TRAD_RATIO);
    
    /******************* Start Social Users ********************************/
    /***********************************************************************/

    fwrite($logFileHandle, "<p>----------------------------------------------</p>");

    fwrite($logFileHandle, "<p>Now creating data for social users...</p>");
    
    for ($i = 1; $i <= $numberOfSocialUsers; $i++) {

        fwrite($logFileHandle, "<p>--------------------------------------------</p>");

        fwrite($logFileHandle, "<p>The user number is " . $i . " of " . $numberOfSocialUsers);

        $socialUserBatch[] = getSocialUser();

    }
    
    $jsonResponse = uploadUserBatch("dataOnly", $socialUserBatch);

    $jsonArray = evaluateJSONresponse($jsonResponse, "createSocialUserBatch");

    if ($jsonArray["stat"] == "ok") {
      fwrite($logFileHandle, "<p>The social batch uploaded successfully.");

      $content = $jsonResponse;
    } else { exit; } // I don't want to create trad users if the 
                    // social user batch did not upload successfully.
    
    /******************* Start Traditional Users ********************************/
    /***************************************************************************/

    fwrite($logFileHandle, "<p>******************************************</p>");

    fwrite($logFileHandle, "<p>Starting to generate data for trad users...</p>");

    $userBatch = array();

    for ($i = 1; $i <= $numberOfTradUsers; $i++) {

      // creates an array of randomly generated
      // demographic values
      $userBatch[] = getUserBasics("trad");

    }
    
    $jsonResponse = uploadUserBatch("dataOnly", $userBatch, "trad");

    $jsonArray = evaluateJSONresponse($jsonResponse, "createTradUserBatch");

    if ($jsonArray["stat"] == "ok") {
      fwrite($logFileHandle, "<p>The trad batch uploaded successfully.");

      $content .= $jsonResponse;

      $thisArray["status"] = "OK";
    }
    else { fwrite($logFileHandle, "<p>The trad batch did not load."); }
    
    return $content;

}

fwrite($logFileHandle, $content);

$outputString = "<p>Batch of $totalNumberOfUsers users generated successfully. Click <a href = '";
$outputString .= $outputFilePath["web"];
$outputString .= "' target = '_newtab'>here</a> to review it.</p>";

$thisArray["html"] = $outputString;

$params["client_id"] = $client_id;
$params["client_secret"] = $client_secret;
$params["type_name"] = "user";

$thisArray["newTotalCount"] = getTotalUserCountInDB($params, $captureServer);

$thisArray["newAutoCount"] = getTotalUserCountInDB($params, $captureServer, "autogen");

$thisArray["batchFinishTime"] = time();

$thisArray["batchFinishTimeString"] = date("F j, Y, g:i a", $thisArray["batchFinishTime"]);

echo json_encode($thisArray);

exit;

/************************************/
// End of main script


/************************************************************/

// Begin function definitions

function defineGlobals() {

  define("OUTPUT_DIR_PATH", "/Users/tom/Documents/code/SE-Projects/j-rug/output/");
  define("OUTPUT_WEB_PATH", "/j-rug/output/");

  define("SOCIAL_RATIO", .713);
  define("TRAD_RATIO", .287);
  
  define("MAX_BATCH_SIZE", 500);
  define("IDEAL_BATCH_SIZE", 300);

}

function createOutputFile() {
    
    $outputFile = OUTPUT_DIR_PATH . time() . ".html";
    
    return $outputFile;
}

function getOutputFileHandle ($outputFile) {
    
    $logFileHandle = fopen($outputFile, "w");

    fwrite($logFileHandle, "<html>\n<head>\n</head>\n<body>\n");
    fwrite($logFileHandle, "<h3>J-RUG: Janrain Random User Generator</h3>\n");
    fwrite($logFileHandle, "<p>Batch attempt started at: " . date(DATE_RFC822));
    
    return $logFileHandle;
}