<?php

class AudioClass {

	private $ffmpeg		= "/usr/local/bin/ffmpeg";
	private $mediainfo	= "/usr/local/bin/mediainfo";
	private $curl 		= "/usr/local/bin/curl --silent --globoff";

	public function get_audio_length($file) {
		$stream = shell_exec( $this->mediainfo . " -f " . escapeshellarg($file) );
		preg_match("/Duration.+:.(\d+)\n/", $stream, $stream_grep);
		return (int) $stream_grep[1];
	}

}

?>