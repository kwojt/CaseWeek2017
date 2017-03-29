<?php
// Główna paczka
$time = microtime(true);

$rule = json_decode( file_get_contents( $argv[1]), true);
$base = json_decode( file_get_contents( $argv[2]), true);


$time = microtime(true) - $time;
echo "\nTime used: " . $time . "\n";
echo "\n";
