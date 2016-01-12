#!/usr/bin/env php
<?php 

$trackdb = [];
$data = explode("\n", file_get_contents('/home/luc/p/php/spotify-shuffle/data/tracks-jojo'));
foreach ($data as $data_singular) {
	if ($data_singular == '') continue;
	$data_singular = explode("\t", $data_singular);
	$trackdb[$data_singular[1]] = [$data_singular[2], $data_singular[3], $data_singular[4], $data_singular[0]];
}

$tracks = [];

$fp = fopen('php://stdin', 'r');
while ($line = fgets($fp)) {
	if ($line[0] == '_') { // Do you see that smiley too?
		continue;
	}

	$tmp = explode(' ', trim($line), 2);
	$freq = $tmp[0];
	$tmp = explode("\t", $tmp[1]);
	$title = $tmp[0];
	$trackid = $tmp[1];

	if (isset($tracks[$trackid])) {
		$x = [$tracks[$trackid][0] + $freq, $title];
	}
	else {
		$x = [$freq, $title];
	}
	$tracks[$trackid] = array_merge($x, $trackdb[$trackid]);
}

foreach ($tracks as $trackid=>$track) {
	echo "$track[0]\t$track[2]\t$track[3]\t$track[4]\t$track[5]\t$track[1]\t$trackid\n";
}

