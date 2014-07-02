<?php

function uploadUserBatch($userData, $typeOfUser = "social") {
    
  global $logFileHandle;

  fwrite($logFileHandle, "<p>*************************************************</p>");

  fwrite($logFileHandle, "<p>Uploading batch of $typeOfUser users...");

  $apiEndpoint = "entity.bulkCreate";

  $params["all_attributes"] = json_encode($userData);
  $params["client_id"] = $_SESSION["client_id"];
  $params["client_secret"] = $_SESSION["client_secret"];
  
  $captureServer = $_SESSION["captureServer"];

  $jsonResponse = sendCurlRequest($apiEndpoint, $params, $captureServer);

  return $jsonResponse;

}