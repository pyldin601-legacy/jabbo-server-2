<?php

class session {
	public function start() {
		session_set_cookie_params(time() + 2592000);
		session_start();
	}
	public function ro() {
		session_write_close();
	}
}

?>