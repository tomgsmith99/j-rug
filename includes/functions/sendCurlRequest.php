<?php

function sendCurlRequest ($apiEndpoint, $params, $captureServer) {

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

}