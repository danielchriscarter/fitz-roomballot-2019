<?php

class News {

  // number of news items in a row (max 3)
  private static $rowCount = 0;

  public static function HTMLtop() {
?>

    <div class="container">
      <div class="row">
<?php
  }

  public static function HTMLrow($heading, $content) {
    if (self::$rowCount == 3) {
?>
      </div>
      <div class="row">
<?php
      self::$rowCount = 0;
    }
?>
        <div class="col-md-4">
          <h2><?php echo $heading; ?></h2>
          <?php echo $content; ?>
        </div>
<?php
    self::$rowCount += 1;
  }

  public static function HTMLbottom() {
?>
      </div>
    </div>
<?php
  }
}

?>
