<?php

class Timetable {

  public static function HTMLtop() {
?>

    <div class="container">
      <div class="agenda">
        <div class="table-responsive">
          <table class="table table-condensed table-bordered table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Event</th>
              </tr>
            </thead>
            <tbody>
<?php
  }

  public static function HTMLrow($num, $day, $date, $time, $event) {
?>
              <tr>
                <td class="agenda-date" rowspan="1">
                  <div class="dayofmonth"><?php echo $num; ?></div>
                  <div class="dayofweek"><?php echo $day; ?></div>
                  <div class="shortdate text-muted"><?php echo $date; ?></div>
                </td>
                <td class="agenda-time">
                  <?php echo $time; ?>
                </td>
                <td class="agenda-events">
                  <div class="agenda-event">
                    <?php echo $event; ?>
                  </div>
                </td>
              </tr>
<?php
  }

  public static function HTMLbottom() {
?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
<?php
  }
}

?>
