#!/usr/bin/env php
<?php 
$songs = []; // empty array
$tracks = 346; // amount of songs in my collection
// Fill the array with zeros
for ($i = 0; $i < $tracks; $i++) {
	$songs[] = 0;
}
// In one of the tests, I played 18887 songs, so I simulated an equal number of plays.
for ($i = 0; $i < 18887; $i++) {
	$songs[mt_rand(0, $tracks)]++; // Increment random index
}
// Output the results
for ($i = 0; $i < $tracks; $i++) {
	echo "$songs[$i]\n";
}

