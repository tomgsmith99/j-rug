<?php

session_start();

// include "includes/includes.php";

// define global values
defineGlobals();

// Just renaming the session vars for readability
$captureServer = $_SESSION["captureServer"];
$client_id = $_SESSION['client_id'];
$client_secret = $_SESSION['client_secret'];

  $params["type_name"] = "user";
  
  if ($apiEndpoint == "entity.bulkCreate") {
    $params["include_record"] = "true";
    $params["timeout"] = 120;
  }
  
  $url = $captureServer . $apiEndpoint;

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_POST, true);

  curl_setopt($ch, CURLOPT_VERBOSE, true);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  curl_setopt($ch, CURLOPT_FAILONERROR, true);

  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

  $jsonResponse = curl_exec($ch);

  if(curl_exec($ch) === false) {
    return "{'CurlError': '" . curl_error($ch) . "'}";
  }
  else { return $jsonResponse; }
  
  curl_close($ch);

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