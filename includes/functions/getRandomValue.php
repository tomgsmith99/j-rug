<?php

function getRandomValue($array, $maxInt = 100) {

  $j = rand(1, $maxInt);

  foreach ($array as $index => $value) {

    if ($index >= $j) { break; }

  }

  return $value;

}