<?php

function uploadUserBatch($mode, $userData, $typeOfUser = "social") {
    
  global $logFileHandle;

  fwrite($logFileHandle, "<p>*************************************************</p>");

  fwrite($logFileHandle, "<p>Uploading batch of $typeOfUser users...");
  
  $jsonString = json_encode($userData);
  
  if ($mode == "dataOnly") {
    fwrite($logFileHandle, "<p>$jsonString</p>");
    $jsonResponse = '{"stat":"OK"}';
     
  }

    else {
        $apiEndpoint = "entity.bulkCreate";

        $params["all_attributes"] = json_encode($userData);
        $params["client_id"] = $_SESSION["client_id"];
        $params["client_secret"] = $_SESSION["client_secret"];

        $captureServer = $_SESSION["captureServer"];

        $jsonResponse = sendCurlRequest($apiEndpoint, $params, $captureServer);
    }

  return $jsonResponse;

}