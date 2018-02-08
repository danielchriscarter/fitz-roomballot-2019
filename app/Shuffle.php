<?php

class Shuffle {

  // singleton design pattern
  private static $instance;
  private $seededValue;
  private function __construct($seed) {
    $this->seededValue = $seed;
  }
  private function __clone() {}
  private function __sleep() {}
  private function __wakeup() {}
  public static function getInstance($seed = null) {
    if (!isset(self::$instance)) {
      if($seed == null){
        $seed = self::fetchSeed();
        $seed = $seed * self::fetchSeed();
      }
      self::$instance = new Shuffle($seed);
    }
    return self::$instance;
  }

  public function shuffle($groups) {
    mt_srand($this->seededValue);
    // https://en.wikipedia.org/wiki/Fisher–Yates_shuffle
    $n = count($groups)-1;
    for ($i = 0; $i <  $n - 2; $i++) {
      $j = mt_rand($i, $n);
      $temp = $groups[$i];
      $groups[$i] = $groups[$j];
      $groups[$j] = $temp;
    }
    return array(
      "groups" => $groups,
      "seed" => $this->seededValue);
  }

  public function getSeed(){
    return $this->seededValue;
  }

  private static function fetchSeed() {
    $session = curl_init("https://www.random.org/integers/?num=1&min=100000000&max=1000000000&col=5&base=10&format=plain&rnd=new");
    curl_setopt($session, CURLOPT_HTTPGET, true);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($session);
    curl_close ($session);
    return $response;
  }
}

?>
