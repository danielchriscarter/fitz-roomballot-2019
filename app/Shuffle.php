<?php

class Shuffle {

	// singleton design pattern
	private static $instance;
	private $seededValue;
	private function __construct($seed) {
		$seededValue = $seed;
	}
	private function __clone() {}
	private function __sleep() {}
	private function __wakeup() {}
	public static function getInstance() {
		if (!isset(self::$instance)) {
			$seed = self::getSeed();
			$seed = $seed * self::getSeed();
			self::$instance = new Shuffle($seed);
		}
		return self::$instance;
	}

	private static function getSeed() {
		$session = curl_init("https://www.random.org/integers/?num=1&min=100000000&max=1000000000&col=5&base=10&format=plain&rnd=new");
		curl_setopt($session, CURLOPT_HTTPGET, true);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($session);
		curl_close ($session);
		return $response;
	}
}

?>