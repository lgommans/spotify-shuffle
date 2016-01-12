#!/usr/bin/env php
<?php 

$dbsend = 'dbus-send --reply-timeout=500 --print-reply=literal';
$dest = 'org.mpris.MediaPlayer2.spotify';

function getCurrentSong() {
	// Returns song info in [key=>value] format
	global $dest, $dbsend;

	$data = shell_exec("$dbsend --dest=$dest /org/mpris/MediaPlayer2 org.freedesktop.DBus.Properties.Get string:'org.mpris.MediaPlayer2.Player' string:'Metadata'");

	$data = str_replace("array [\n", "array [", $data); // Going through it line-by-line does not quite work if some dumb value spans multiple lines (for crying out loud, couldn't they just have used JSON?!)

	// Get rid of the terribly excessive whitespace
	while (strpos($data, '  ') !== false || strpos($data, "\t\t") !== false) {
		$data = str_replace('  ', "\t", $data);
		$data = str_replace("\t\t", "\t", $data);
	}
	// Values are now tab-separated instead of random-amount-of-spaces-separated

	// Parse it line by line
	$data = explode("\n", $data);
	$props = [];
	foreach ($data as $line) {
		if (strpos($line, 'mpris') !== false || strpos($line, 'xesam') !== false) {
			$line = explode("\t", $line);
			$line[1] = str_replace(['xesam:', 'mpris:'], '', $line[1]);
			if ($line[3] == 'array [') {
				$line[3] = $line[4];
			}
			else {
				if (substr($line[3], 0, 7) == 'double ' || substr($line[3], 0, 6) == 'int32 ' || substr($line[3], 0, 7) == 'uint64 ') {
					$line[3] = explode(' ', $line[3], 2)[1];
				}
			}
			$props[$line[1]] = $line[3];
		}
	}
	return $props;
}

function nextSong() {
	// Goes to the next song
	global $dest, $dbsend;
	// To save you time, --print-reply is completely useless and yet still required here:
	// If you want to optimize this command and are thinking of removing the argument, well, it's just going to completely fail. I'm sorry that I have to be the one to break this to you.
	shell_exec("$dbsend --dest=$dest /org/mpris/MediaPlayer2 org.mpris.MediaPlayer2.Player.Next");
}

function loops() {
	return false; // disabled
	// Returns whether it will loop at the end
	global $dest, $dbsend;
	$data = shell_exec("$dbsend --dest=$dest /org/mpris/MediaPlayer2 org.freedesktop.DBus.Properties.Get string:'org.mpris.MediaPlayer2.Player' string:'LoopStatus' 2>&1");
	if (strpos($data, 'org.freedesktop.DBus.Error.NoReply') !== false) {
		echo "_Error communicating with Spotify. Looks like the shit broke.\n";
		exit(2);
	}
	return strpos($data, 'None') === false;
}

// Init!

if ($argc < 2) {
	// Actually, that super long parameter is useless. Oh wow, you're reading this? Hi there! Good job! Now you know the secret.
	echo "Hello! To indicate you read this message, use --yeah-i-read-this-message
You are supposed to run this after you hit the 'shuffle play' button, or
whatever setup you wanna do. A track must currently be playing, and if you
want it to ever end, it must not be looping.

Want to do only a certain number of plays? Use -numX as first parameter where X
is the number of plays. Now just do as I say, do not use '-num X' or '--num=X'
that will all fail. Sorry, I was too lazy.
";
	exit(1);
}

$loop = -1;
if (substr($argv[1], 0, 4) == '-num') {
	$loop = intval(substr($argv[1], 4));
}

if ($loop < 0 and loops()) {
	echo "_Are you sure you want to loop until the end while Spotify is on repeat? You must be mad. Please comment this out if you really want to do this.";
	exit(3);
}

// Start!
$freq = [];
$prevsong = false;
$changetime = 170; //ms
while ($loop != 0) {
	$song = getCurrentSong();
	if ($song['trackid'] === $prevsong) {
		// The song did not change after nextSong() was called last. This means we're at the end and the last nextSong() made the music stop.
		if ($loop >= 0) {
			if ($changetime > 1500) { // 1500 is rather large, but necessary for remote playback. When playing locally, 500 or so should do fine.
				echo "_Err: see the warnings above. I've had enough; I'm giving up. You can go hit next by yourself.\n";
				break;
			}
			$changetime *= 1.05; // some extra changing time
			echo "_Warn: uhm, the track did not change just now, but I'm not yet through with my -numX. I'll increase the latency to ${changetime}ms and continue like nothing happened.\n";
			usleep(1000 * $changetime); // 200ms
			continue;
		}
		break;
	}
	$loop--;
	$prevsong = $song['trackid'];

	$oldfreq = 0;
	if (isset($freq[$song['trackid']])) {
		$oldfreq = $freq[$song['trackid']]['freq'];
	}
	$freq[$song['trackid']] = ['freq' => $oldfreq + 1, 'otherprops' => $song];
	nextSong();
	usleep(1000 * $changetime); // 200ms
}

if ($argv[1] == '-dumpdb') {
	foreach ($freq as $trackid=>$track) {
		echo "$trackid\t" . $track['otherprops']['trackNumber'] . "\t" . $track['otherprops']['autoRating'] . "\t" . $track['otherprops']['length'] . "\t" . $track['otherprops']['discNumber'] . "\t"
		. $track['otherprops']['artist'] . "\t" . $track['otherprops']['album'] . "\n";
	}
	exit(0);
}

foreach ($freq as $trackid=>$track) {
	echo $track['freq'] . ' ' . $track['otherprops']['title'] . "\t$trackid\n";
}

