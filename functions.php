<?php
require_once('krumo/class.krumo.php');
require_once('class.iCalReader.php');

function dsm($value, $key = FALSE) {
  if ($key) {
    print $key . ' =>';
  }
  krumo($value);
}
