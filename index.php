<?php

// Janrain Random User Generator (J-RUG)
// More documentation available in Google Docs: search for J-RUG
// tsmith@janrain.com

session_start();

include "includes/includes.php";

// $content is where I dump all of my HTML until I'm ready for output
$content = "";

// just sets up <HTML> and intro text for page
initializeContent();

// define global values
defineGlobals();

// This function attaches a file name to $logFileHandle
// and opens the file for input
openLogFile();

// check to see if we've already run this script
// and the user has indicated that he wants to  
// close the session
checkForClosedSession();

// ***** Start happy path **********//

// Check to see if a session has been initialized
// and if there is a value for baseURL
// if not, then get the critical data values:
// (client_id, client_secret, baseURL)
setCriticalValues();

// How many new users do we want to generate?
setNumberOfUsersAndVerbosity();

// Just renaming the session vars for readability
$baseURL = $_SESSION['baseURL'];
$client_id = $_SESSION['client_id'];
$client_secret = $_SESSION['client_secret'];

// these parameters are always the same for 
// all API calls in this script
$baseParameters  = "type_name=user";
$baseParameters .= "&";
$baseParameters .= "client_id=" . $client_id;
$baseParameters .= "&";
$baseParameters .= "client_secret=" . $client_secret;

/***************************/
// Begin main loop

for ($i = 1; $i <= $numberOfUsers; $i++) {

  // Sleep for a while between each iteration
  // SLEEP_TIME is defined up top
  usleep(SLEEP_TIME);

  // This username is just a way to track each user
  // as he is being created and where we are in the
  // batch of users.
  $userName = "Random user #" . $i . " of " . $numberOfUsers;

  writeToOutput("-------------------------");

  writeToOutput($userName);

  writeToOutput("Generating demographic values for " . $userName . "...");

  /********************************************/
  // birthday
  // Let's say that 90% of the users are between 14 and 55 years of age
  // and 10% are between 56 and 85.

  $age = rand(14, 55);

  if (rand(1, 10) > 9) { $age = rand(56, 85); }

  $verboseContent .= "<p>Age: $age";

  $yob = date("Y") - $age;

  $month = rand(1, 12);
  if ($month < 10) { $month = "0" . $month; }

  $day = rand(1, 28);
  if ($day < 10) { $day = "0" . $day; }

  $birthday = $yob . $month . $day;

  $verboseContent .= "<p>birthday: " . $birthday;

  /*******************************************/
  // gender

  // Flip a coin. Is this user going to be male or female?

  $gender = "female";

  if (rand(0,1) == 0) { $gender = "male"; }

  $verboseContent .= "<p>Gender is: " . $gender;

  /*******************************************/
  // givenName

  $firstName = getFirstName($gender);
  $verboseContent .= "<p>First name is: " . $firstName;

  /*******************************************/
  // middleName
  // just a middle initial

  $middleName = $middleNames[rand(0, (sizeof($middleNames) - 1))];
  $verboseContent .= "<p>Middle initial is: " . $middleName;

  /*******************************************/
  // familyName

  $lastName = $lastNames[rand(0, (sizeof($lastNames) - 1))];
  $verboseContent .= "<p>Last name is: " . $lastName;

  $fullName = strtolower($firstName . "." . $middleName . "." . $lastName);

  // Get rid of apostrophe in O'Connor, for example
  $fullName = str_replace("'", "", $fullName);

  $userName .= " (" . $fullName . ")";

  /*******************************************/
  // email

  $emailProvider = $emailProviders[rand(0, (sizeof($emailProviders) - 1))];

  $email = strtolower($fullName . "@" . $emailProvider);

  $verboseContent .= "<p>Email address is: " . $email;

  /*******************************************/
  // primaryAddress/country
  
  // We're going to say that 50% of our users are from the U.S.
  // 30% are from "common countries"
  // 20% are from "uncommon countries"

  $country = "United States";

  $j = rand(1, 10);

  if ($j > 5 && $j < 9) { $country = $commonCountries[rand(0, (sizeof($commonCountries) - 1))]; }
  if ($j == 9 || $j == 10) { $country = $uncommonCountries[rand(0, (sizeof($uncommonCountries) - 1))]; }

  $country = urlencode($country); // take care of any spaces in country name

  $verboseContent .= "<p>Country: $country";

  if ($verbosity > 1) { writeToOutput($verboseContent); }

  writeToOutput("demographic data complete.");

  writeToOutput("-------------------------");

  /*******************************************/

  // Build the API call to create a user

  writeToOutput("Now building API call to create " . $userName . "...");

  // API call just for reference
  // https://yourdomain.com/entity.create?type_name=user&attributes={"firstName":"Bob","lastName":"Smith"}&client_id=12345678912345678912345678912345&client_secret=98765432198765432198765432198765

  $apiCall = "entity.create";

  // Parameters relevant to the entity.create API call
  $theseParameters  = "attributes={";
  $theseParameters .= "\"familyName\":\"$lastName\",\"givenName\":\"$firstName\",\"gender\":\"$gender\"";
  $theseParameters .= ",\"middleName\":\"$middleName\"";
  $theseParameters .= ",\"isAutogeneratedUser\":\"true\"";
  $theseParameters .= ",\"email\":\"$email\"";
  $theseParameters .= ",\"birthday\":\"$birthday\"";
  $theseParameters .= ",\"primaryAddress\":{\"country\":\"$country\"}";
  $theseParameters .= "}";

  // baseURL is essentially the domain name
  // defined by user or keyfile when script loads
  $url = $baseURL . $apiCall;

  // $baseParameters defined by user or keyfile when script loads
  $parameters = $baseParameters . "&" . $theseParameters;

  $verboseContent = "<p>url: " . $url;

  $verboseContent .= "<p>parameters: " . $parameters;

  if ($verbosity > 1) { writeToOutput($verboseContent); }
   
  $jsonResponse = do_post_request($url, $parameters);

  $jsonArray = json_decode($jsonResponse, true);

  if ($jsonArray["stat"] == "ok") {
    $uuid = $jsonArray["uuid"];

    writeToOutput($userName . " generated successfully!");
    writeToOutput($userName . "'s uuid is: " . $uuid);

  }
  else {

    writeToOutput("Something went wrong with creating " . $userName . ". The json response is: " . $jsonResponse);

    closeOutHTMLstream();
  }

  writeToOutput("----------------------------------------");
  writeToOutput("Now building API call to update " . $userName . " with profile ID...");

  /**********************************/

  // Creating the user's first profile
  // I am doing this *after* creating the core user because:
  // the value profiles.identifier is required to create a profile.
  // the value profiles.identifier must be unique across all janrain Capture instances.
  // So, I want to use the uuid returned from the initial user creation request
  // to create a unique value for profile.identifier

  $apiCall = "entity.update";

  $domain = "janrain"; // profiles.domain is required

  $identifier = "http://www.janrain.com/bogus/" . $uuid;

  $theseParameters  = "value={\"profiles\":[{\"domain\":\"$domain\",\"identifier\":\"$identifier\"}]}";
  $theseParameters .= "&";
  $theseParameters .= "uuid=" . $uuid;
  $theseParameters .= "&";
  $theseParameters .= "include_record=true";

  $url = $baseURL . $apiCall;

  $parameters = $baseParameters . "&" . $theseParameters;

  $verboseContent = "<p>url: " . $url;

  $verboseContent .= "<p>parameters: " . $parameters;

  if ($verbosity > 1) { writeToOutput($verboseContent); }

  $jsonResponse = do_post_request($url, $parameters);

  if ($verbosity > 1) { writeToOutput($jsonResponse); }

  $jsonArray = json_decode($jsonResponse, true);

  if ($jsonArray["stat"] == "ok") {
    $profileID = $jsonArray["result"]["profiles"][0]["id"];

    writeToOutput("Profile ID generated successfully for " . $userName);
    writeToOutput($userName . "'s profile ID is: " . $profileID);

  }
  else {

    writeToOutput("Something went wrong with creating the profile ID for " . $userName . ". The json response is: " . $jsonResponse);

    closeOutHTMLstream();

  }

  /***************************************/
  // Now let's add some interesting stuff

  writeToOutput("-------------------------------");

  writeToOutput("Now building API call for entity.update (to add psychographic data) to " . $userName . "...");

  $apiCall = "entity.update";

  $url = $baseURL . $apiCall;

  if ($verbosity > 1) { writeToOutput("<p>url: " . $url); }

  $theseParameters  = "attribute_name=profiles#" . $profileID;
  $theseParameters .= "&";
  $theseParameters .= "uuid=" . $uuid;
  $theseParameters .= "&";
  $theseParameters .= "value={\"profile\":{";

  // I just used the following API call for syntax reference as I was coding
  // https://janrain-se-demo.eval.janraincapture.com/entity.update?type_name=user&uuid=93f1ea84-1e7a-4583-a489-087884dc8dc5&client_id=am67ue2jpjjnmtd95m72ew8evafth4ax&client_secret=XYZ&attribute_name=profiles%2318178&value={"profile":{"movies":[{"movie":"Caddyshack"},{"movie":"ToyStory"},{"movie":"Avatar"}]}}

  // Psychographic info (interests, movies, books, music)

  // psychGraph array is defined up top
  foreach ($psychGraph as $categoryName => $itemName) {

    $count = rand(0, MAX_INTEREST_COUNT);

    if ($count > 0) {

      $theseParameters .= getPsychValues($categoryName, $itemName, $count);

      $theseParameters .= ",";

    }

    // Track total count in case total count is 0
    // in which case we don't need to update the user record
    $totalCount += $count;

  }

  // Trim off that last comma
  $theseParameters = rtrim($theseParameters, ",");

  $theseParameters .= "}}";

  if ($totalCount == 0) { writeToOutput("No psychographic info was generated for " . $userName); }

  else {

    $parameters = $baseParameters . "&" . $theseParameters;

    if ($verbosity > 1) { writeToOutput("parameters: " . $parameters); }
   
    $jsonResponse = do_post_request($url, $parameters);

    $jsonArray = json_decode($jsonResponse, true);

    if ($jsonArray["stat"] == "ok") {

      writeToOutput("Psychographic values added successfully!");
      writeToOutput("------------" . $userName . " complete-----------");

    }
    else {

      writeToOutput("Something went wrong with adding psychographic values to " . $userName . ". The json response is: " . $jsonResponse);

      closeOutHTMLstream();

    }
  }
}

$content .= "<form method = 'POST' action = '" . $_SERVER['PHP_SELF'] . "'>";

$content .= "<p>What would you like to do now?";

$content .= "<table border = '0'><tr><td valign = 'top'>";

$batchString = "<p><input type = 'radio' name = 'numberOfUsers' value = '1' checked> Generate another single user";

if ($numberOfUsers > 1) {

  $batchString = "<p><input type = 'radio' name = 'numberOfUsers' value = '" . $numberOfUsers . "' checked> Run another batch of " . $numberOfUsers . " users";

}

$content .= $batchString;

$content .= "</td><td>";

$content .= "<p><input type = 'radio' name = 'verbosity' value = '1' checked> Low verbosity (just the basics)";
$content .= "<p><input type = 'radio' name = 'verbosity' value = '2'> High verbosity (shows JSON chatter)";

$content .= "</td></tr></table>";

$content .= "<p><input type = 'radio' name = 'numberOfUsers' value = '0'> Run another batch, but let me choose how many users (and verbosity)";

$content .= "<p><input type = 'radio' name = 'numberOfUsers' value = '-1'> Close this session"; 

$content .= "<p><input type = 'submit' name = 'submit' value = 'submit'>";

/**********************************/
// Close out the HTML stream and cough up the page.

closeOutHTMLstream();

/************************************/
// End of main script


/************************************************************/
// Begin function definitions

function defineGlobals() {

  global $psychGraph;
  global $logFileHandle;
  global $psychArrayNames;
  global $interests;
  global $movies;
  global $books;
  global $music;
  global $baseParameters;

  define("KEY_FILE_PATH", "/Users/tom/Documents/misc/misc.txt");
  define("LOG_FILE_PATH", "/Applications/MAMP/htdocs/populateCapture/logs/captureLog.txt");
  define("MAX_ITERATIONS", 100);
  define("SLEEP_TIME", 250000); // in milliseconds. For example: 500000 = .5 secs
  define("MAX_INTEREST_COUNT", 5);

  $psychGraph = array (
      "interests" => "interest",
      "movies" => "movie",
      "books" => "book",
      "music" => "music"
  );

  $psychArrayNames = array (
    "interests" => $interests,
    "movies" => $movies,
    "books" => $books,
    "music" => $music
  );

}

function openLogFile() {

  global $content;
  global $logFileHandle;

  // This starts the log file fresh with every batch
  $logFileHandle = fopen(LOG_FILE_PATH, "w");

  if ($logFileHandle == FALSE) { $content .= "<p>Warning: error opening log file."; }
  else {

    fwrite($logFileHandle, "Successfully started log file.\n");
    fwrite($logFileHandle, "------------------------------\n");
    fwrite($logFileHandle, "Attempt begun at: " . date(DATE_RFC822) . "\n");
  }
}

function checkForClosedSession() {
  global $content;

  if ($_POST["numberOfUsers"] == -1) {

    $content .= "<p>****Session was ended.****";

    session_destroy();

    $content .= "<form method = 'POST' action = '" . $_SERVER['PHP_SELF'] . "'>";

    $content .= "<input type = 'submit' name = 'submit' value = 'Reload fresh'>";

    $content .= "</form>";

    closeOutHTMLstream();
  }
}

function setCriticalValues() {

  if (empty($_SESSION['baseURL'])) {

    if (empty($_POST['baseURL'])) {

      if (loadCriticalValuesFromLocalFile() == FALSE) {

        getCriticalValuesFromUser(); // presents HTML form to user and exits script

      }
    }

    else { put_POSTdataIntoSession(); }
    
  }
}

// Returns a complete JSON string
// Expects the category name ("books")
// the item name ("book")
// and the number of items you want back
function getPsychValues($categoryName, $itemName, $count) {

  global $psychArrayNames;
  global $interests;
  global $books;
  global $movies;
  global $music;

  $thisArray = $psychArrayNames[$categoryName];

  $alreadyPicked;

  $thisJSONstring = "\"$categoryName\":[";

  for ($i = 1; $i <= $count; $i++) {

    // choose a random item from the relevant array
    $thisItem = $thisArray[rand(0, (sizeof($thisArray) - 1))];

    // check to see if this item has already been chosen for this user
    if (array_search($thisItem, $alreadyPicked) == FALSE) {

      // The item is not in the array already, so we'll push it into the array. 
      array_push($alreadyPicked, $thisItem);

      $thisJSONstring .= "{\"$itemName\":\"";

      $thisJSONstring .= urlencode($thisItem);

      $thisJSONstring .= "\"},";

    }

    else { writeToOutput("Found a duplicate interest: " . $thisItem); } // just for some error checking

  }

  // Trim off that last comma
  $thisJSONstring = rtrim($thisJSONstring, ",");

  $thisJSONstring .= "]";

  return $thisJSONstring;

}

// This function handles the POST requests
// borrowed from
// http://wezfurlong.org/blog/2006/nov/http-post-from-php-without-curl/

function do_post_request($url, $data, $optional_headers = null)
{
  $params = array('http' => array(
              'method' => 'POST',
              'content' => $data
            ));

  if ($optional_headers !== null) {
    $params['http']['header'] = $optional_headers;
  }

  $ctx = stream_context_create($params);
  
  $fp = @fopen($url, 'rb', false, $ctx);
  
  if (!$fp) {
    throw new Exception("Problem with $url, $php_errormsg");
  }
  
  $response = @stream_get_contents($fp);
  
  if ($response === false) {
    throw new Exception("Problem reading data from $url, $php_errormsg");
  }
  
  return $response;
}

// Picks a random first name from the appropriate array
function getFirstName($gender) {

	global $femaleFirstNames;
	global $maleFirstNames;

	$listOfFnames = $femaleFirstNames;

	if ($gender == "male") { $listOfFnames = $maleFirstNames; }

	$fnamePos = rand(0, (sizeof($listOfFnames) - 1) );

	return $listOfFnames[$fnamePos];

}

function writeToOutput($someString) {
  global $content;
  global $logFileHandle;

  $content .= "<p>" . $someString . "</p>\n";

  fwrite($logFileHandle, $someString . "\n");

}

function initializeContent() {
  global $content;
  $content = "<html><title>Janrain Random User Generator (J-RUG)</title><body>";
  $content .= "<p>Janrain Random User Generator (J-RUG)</p>";
  $content .= "<p>This application:";
  $content .= "<ul>";
  $content .= "<li>generates new users with random demographic and psychographic information (max batch size of 100 is hard-coded at the moment).</li>";
  $content .= "<li>has been written to work with the SE demo Capture instance, but it should also work with any other Capture instance (so use with caution), with the requirement that the schema includes a boolean field called 'isAutogeneratedUser' at the root level.</li>";
  $content .= "</ul>";
}

function closeOutHTMLstream() {
  global $content;

  $content .= "</body></html>";

  echo $content;

  exit();

}

function setNumberOfUsersAndVerbosity() {
  global $numberOfUsers;
  global $verbosity;

  if (empty($_POST['numberOfUsers'])) {

    getNumberOfUsersAndVerbosityFromUser(); // presents HTML form to user and exits script

  }
  else {

    if ($_POST['numberOfUsers'] > MAX_ITERATIONS) { $numberOfUsers = 1; }
    else { $numberOfUsers = $_POST['numberOfUsers']; }

    $verbosity = $_POST['verbosity'];
  }
}

function getNumberOfUsersAndVerbosityFromUser () {
  global $content;

  $content .= "<form method = 'POST' action = '" . $_SERVER['PHP_SELF'] . "'>";

  $content .= "<p>How many users do you want to generate? Max is " . MAX_ITERATIONS;

  $content .= "<p><input type = 'text' name = 'numberOfUsers' value = '1'>";

  $content .= "<p>Verbosity:";

  $content .= "<p><input type = 'radio' name = 'verbosity' value = '1' checked> Low (just the basics)";
  $content .= "<p><input type = 'radio' name = 'verbosity' value = '2'> High (shows JSON chatter)";
  $content .= "<p>";
  $content .= "<input type = 'submit' name = 'submit' value = 'submit'>";
  $content .= "</form>";

  closeOutHTMLstream();

}

function put_POSTdataIntoSession() {
  $_SESSION['baseURL'] = $_POST["baseURL"];
  $_SESSION['client_id'] = $_POST["client_id"];
  $_SESSION['client_secret'] = $_POST["client_secret"];

  // Add a / to the end of the baseURL if user didn't do so in form.
  if (substr($_SESSION['baseURL'], -1) != "/") { $_SESSION['baseURL'] .= "/"; }
}

function loadCriticalValuesFromLocalFile() {
  global $keyFileHandle;
  global $content;

  $keyFileHandle = fopen(KEY_FILE_PATH, "r");

  if ($keyFileHandle == FALSE) {

    writeToOutput("***Warning: No local key file found at: " . KEY_FILE_PATH);

    $status = FALSE;
  }
  else {

    $status = TRUE;

    writeToOutput("***Successfully loaded client_id, client_secret, and baseURL from local file.***");

    writeToOutput("---------------------------");

    $thisArray = json_decode(fread($keyFileHandle, filesize(KEY_FILE_PATH)), TRUE);

    $_SESSION['baseURL'] = $thisArray["baseURL"];
    $_SESSION['client_id'] = $thisArray["client_id"];
    $_SESSION['client_secret'] = $thisArray["client_secret"];

  }

  return $status;

}

function getCriticalValuesFromUser() {
  global $content;

  $content .= "<p>You can supply your own values here: \n";

  $content .= "<form action = '" . $_SERVER['PHP_SELF'] . "' method = 'POST'>";
  $content .= "<p>base URL: <input type = 'text' size = '40' name = 'baseURL' value = 'http://capture.janrain.com/'>";
  $content .= "<p>client_id: <input type = 'text' size = '45' name = 'client_id' value = '12345678912345678912345678912345'>";
  $content .= "<p>client_secret: <input type = 'text' size = '45' name = 'client_secret' value = '98765432198765432198765432198765'>";
  $content .= "<p><input type = 'submit' name = 'submit' value = 'submit'>";
  $content .= "</form>";

  closeOutHTMLstream();
}

?>

