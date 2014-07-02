<?php

function getDomain() {

  global $domainList;
  
  return (getRandomValue($domainList, max(array_keys($domainList))));

}
