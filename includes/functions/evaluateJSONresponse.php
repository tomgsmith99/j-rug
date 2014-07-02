<?php

function evaluateJSONresponse($jsonResponse, $context = "none") {
    
    global $logFileHandle;

    if ($jsonResponse == FALSE) { echo "<p>The request failed."; }

  else {

    $jsonArray = json_decode($jsonResponse, true);

    if ($jsonArray["stat"] == "ok") { fwrite($logFileHandle, "<p>JSON request is OK.</p>"); }

    else {

      echo ("<p>Something went wrong with the API request.</p>");
      echo ("<p>The json response is: " . $jsonResponse);

      if ($context != "none") { fwrite($logFileHandle, "<p>The context is " . $context); }

      echo ("<p>----------------------------<p>");

    }
  }

  return $jsonArray;

}

?>

