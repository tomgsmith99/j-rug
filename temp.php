<?php

// Janrain Random User Generator (J-RUG)
// tsmith@janrain.com

// define("KEY_FILE_PATH", "/Users/tom/.janrain-capture");

$filePath = "/Users/tom/.janrain-capture";

if (is_readable("/Users/tom/.janrain-capture")) { echo "the file is readable."; }
else { echo "the file is not readable."; }

$fileHandle = fopen($filePath, "rb");

if ($fileHandle === FALSE) { echo "file handle is bad."; }
else { echo "<p>file handle is good."; }

if (fread($fileHandle, filesize($fileHandle))) {
    echo ("<p>Can read file Handle.");

}

exit;

?>

