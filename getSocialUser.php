<?php

function getSocialUser() {

  global $twitterFollowers;
  global $facebookFriends;
  global $relationshipStatus;
  global $politicalViews;
  
  global $logFileHandle;
  
  $thisUser = getUserBasics();
  
  $yob = substr($thisUser["birthday"], 0, 4);

  $age = date("Y") - $yob;

  $userValues["domain"] = getDomain();

  fwrite($logFileHandle, "<p>The domain is " . $userValues["domain"]);

  $userValues["identifier"] = getIdentifier($userValues["domain"]);

  /******************* Facebook ************************/

  if ($userValues["domain"] == "facebook.com") {

    if ($age > 18 && rand(1, 100) > 20) {
        $userValues["profile"]["relationshipStatus"] = getRelationshipStatus($age);
    }
    
    // if (!empty($relStatus)) { $userValues["profile"]["relationshipStatus"] = $relStatus; }

    // get political views
    if ( $thisUser["primaryAddress"]["country"] == "United States") { 
        
        if (rand(1, 100) > 61) {
            $polViews = getRandomValue($politicalViews);
        
            $userValues["profile"]["politicalViews"] = skewPoliticalViews($polViews, $age, $thisUser["gender"], $thisUser["primaryAddress"]["stateAbbreviation"]);  

        }
    }

    $range = getRandomValue($facebookFriends);

    $numberOfFBfriends = rand($range["min"], $range["max"]);

    fwrite($logFileHandle, "<p>The number of friends is: " . $numberOfFBfriends);

    $userValues["friends"] = getFriendsOrFollowersArray($numberOfFBfriends, $userValues["domain"]);

    //*************** Interests, Movies, Books, Music **************/

// parameters: &attribute_name=profiles%23181683&uuid=ea1bc321-4320-4fbb-8f07-fb939b90cf46&value={"profile":{"interests":[{"interest":"scrapbooking"},{"interest":"cats"},{"interest":"dogs"}],"movies":[{"movie":"Platoon"},{"movie":"Star+Trek+Into+Darkness"}],"books":[{"book":"Call+It+Sleep"},{"book":"The+Death+of+the+Heart"},{"book":"The+French+Lieutenant%27s+Woman"}],"music":[{"music":"Britney+Spears"}]}}

    $psychInfoTypes = array("interests" => "interest", "movies" => "movie", "books" => "book", "music" => "music");
    
    foreach ($psychInfoTypes as $categoryName => $itemName) {
        
        $numberOfItems = getNumberOfItems($itemName);
        
        $itemString = $itemName . "s";
        if ($itemName == "music") { $itemString = $itemName . " artists"; }
    
        fwrite($logFileHandle, "<p>The number of $itemString is: " . $numberOfItems);
        
        if ($numberOfItems > 0) {
            
            $userValues["profile"][$categoryName] = getItems($itemName, $numberOfItems);
        }
    }
  }

  /********************* Twitter ***********************/

  if ($userValues["domain"] == "twitter.com") {

    $range = getRandomValue($twitterFollowers);

    $numberOfTWfollowers = rand($range["min"], $range["max"]);

    fwrite($logFileHandle, "<p>The number of followers is: " . $numberOfTWfollowers);

    $userValues["followers"] = getFriendsOrFollowersArray($numberOfTWfollowers, $userValues["domain"]);

  }

  $thisUser["profiles"] = array();

  $thisUser["profiles"][] = $userValues;

  return $thisUser;

}

function getRelationshipStatus($age) {
    
    global $relationshipStatus;
    global $logFileHandle;
    
    fwrite($logFileHandle, "<p>The age is " . $age);

    $relStatus = getRandomValue($relationshipStatus);
    
    fwrite($logFileHandle, "<p>The relationship status is " . $relStatus);

    if ($age < 27 && ($relStatus != "single" && $relStatus != "it's complicated") ) { $relStatus = getRandomValue($relationshipStatus); }
    
    if ($age < 50 && $relStatus == "widowed") { $relStatus = getRandomValue($relationshipStatus); }
    
    if ($age > 75 && $relStatus != "widowed") { $relStatus = getRandomValue($relationshipStatus); }
    
    fwrite($logFileHandle, "<p>The new relationship status is " . $relStatus);
    
    return $relStatus;

}
    

function getFriendsOrFollowersArray($count, $domain) {

  $followers = array();

  for ($i = 1; $i <= $count; $i++) {

    $thisFollower = getIdentifier($domain);

    // This statement pushes the Follower or Friend
    // as a name/value pair ("identifier" => "http://twitter.com/account/profile?user_id=76767676767") 
    // onto the $followers stack
    $followers[] = array("identifier" => $thisFollower);

  }

  return $followers;

}

function skewPoliticalViews($polViews, $age, $gender, $state) {
    global $politicalViews;
    global $logFileHandle;

    fwrite($logFileHandle, "<p>Original pol view: " . $polViews);
    
    fwrite($logFileHandle, "<p>age: $age, gender: $gender, state: $state");

    // This block reduces the number of conservative females
    if ( ($gender == "female") && ( $polViews == "Republican" || $polViews == "Conservative" ) ) {
        if (rand(1, 100) > 50) { $polViews = getRandomValue($politicalViews); }        
    }
    
    if ( $age < 37 &&  ( $polViews == "Republican" || $polViews == "Conservative" ) ) {
        if (rand(1, 100) > 50) { $polViews = getRandomValue($politicalViews); }                
    }
    
    if ( ($age >= 13 && $age <= 24) && ( $polViews != "Liberal" && $polViews != "Democrat" ) ) {
        if (rand(1, 100) > 50) { $polViews = getRandomValue($politicalViews); }                
    }
    
    if ( $state == "TX" && ($polViews == "Liberal" || $polViews == "Democrat" )) {
        if (rand(1, 100) > 60) { $polViews = getRandomValue($politicalViews); }                
    }
    
    if ( $state == "CA" && ($polViews == "Republican" || $polViews == "Conservative" )) {
        if (rand(1, 100) > 75) { $polViews = getRandomValue($politicalViews); }                
    }
    
    fwrite($logFileHandle, "<p>New political views: $polViews");
    
    return $polViews;
}

function getNumberOfItems($kindOfItem) {
    
    global $numberOfInterests;
    global $numberOfBooks;
    global $numberOfMovies;
    global $numberOfMusicArtists;

    if ($kindOfItem == "interest") { $numberOfItems = getRandomValue($numberOfInterests); }
    if ($kindOfItem == "book") { $numberOfItems = getRandomValue($numberOfBooks); }
    if ($kindOfItem == "movie") { $numberOfItems = getRandomValue($numberOfMovies); }
    if ($kindOfItem == "music") { $numberOfItems = getRandomValue($numberOfMusicArtists); }

    return $numberOfItems;  
}

function getSingleItem($kindOfItem) {
    
    global $interests;
    global $books;
    global $movies;
    global $music;

    if ($kindOfItem == "interest") { $item = getRandomValue($interests); }
    if ($kindOfItem == "book") { $j = array_rand($books); $item = $books[$j]; }
    if ($kindOfItem == "movie") { $j = array_rand($movies); $item = $movies[$j]; }
    if ($kindOfItem == "music") { $j = array_rand($music); $item = $music[$j]; }

    return $item;
    
}

function getItems($kindOfItem, $numberOfItems) {
    
    global $logFileHandle;

    $temp = array(); // This is the array I use to track which items have 
                     // already been picked.
    
    $theseItems = array();  // This is the array I use to store the "JSON-ready"
                            // values.

    fwrite($logFileHandle, "<ul>");

    for ($i = 1; $i <= $numberOfItems; $i++) {
        
        $thisItem = getSingleItem($kindOfItem);

        fwrite($logFileHandle, "<li>$thisItem</li>");
        
        // Check to see if the item has already
        // been chosen
        if (in_array($thisItem, $temp)) {
            fwrite($logFileHandle, "<p>Found a duplicate value.");
        }
        else { 
            $temp[] = $thisItem;
            $theseItems[] = array($kindOfItem => $thisItem);
            
        }
    }
    
    fwrite($logFileHandle, "</ul>");

    return $theseItems;
}