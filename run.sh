#!/usr/bin/env bash

f=/path/file;
let i=0;
while :; do
	if [ ! -f $f$i ]; then
		./spotify-shuffle-stats.php -num20 > $f$i;
		sleep 0.6;
		xdotool click 1;
		sleep 0.5;
	else
		echo skipping $i;
	fi;
	let i=$i+1;
done

