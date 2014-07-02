<?php

// Janrain Random User Generator (J-RUG)
// tsmith@janrain.com

// echo ("<p>The server ID is: " . $_POST["serverID"]);

if (empty($_POST["serverID"])) { echo "<p>there is no server ID in the POST."; }

else { echo "The server id is: " . $_POST["serverID"]; }

?>

