<?php

function getIdentifier($domain) {

  $userID = rand(100, 999) . rand(100, 999) . rand(100, 999) . rand(100, 999);

  if ($domain == "facebook.com") { $identifier = "http://www.facebook.com/profile.php?id=" . $userID; }

  elseif ($domain == "twitter.com") { $identifier = "http://twitter.com/account/profile?user_id=" . $userID; }

  else { $identifier = "http://www." . $domain . "/myprofile.php?id=" . $userID; }

  return $identifier;

}
