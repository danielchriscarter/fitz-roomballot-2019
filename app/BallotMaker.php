<?php

require_once("Database.php");
require_once("Shuffle.php");

class BallotMaker{
  public static function makeBallot($seed = null, $formatCSV = false){
      $ballotPriorities = ["SCHOLAR%", "SECONDYEAR", "THIRDYEAR", "FIRSTYEAR"];
      $prettyNames = [
        "SCHOLAR%" => "Scholar's Individual Ballot",
        "SECONDYEAR" => "Second Year's and Third Years Abroad",
        "THIRDYEAR" => "Third Year's With Confirmed Fourth",
        "FIRSTYEAR" => "First Year's"
      ]; 
      $scholarGroup = [
        "SECONDYEAR" => "SCHOLARSECOND",
        "THIRDYEAR" => "SCHOLARTHIRD",
        "FIRSTYEAR" => "FIRSTYEAR"
      ];
      ?>

      <table class="table table-condensed table-bordered table-hover">
        <thead>
          <tr>
            <td>Position</td>
            <td>Group Members</td>
            <td>Group ID</td>
          </tr>
        </thead>

<?    $ballotPosition = 1;

      //Get database seed if it exists
      $result = Database::getInstance()->query("SELECT `seed` FROM `ballot_seed` WHERE `id`=0");
      if($result->num_rows > 0){
        $seed = $result->fetch_assoc()['seed'];
      }

      if($seed !== null){
        $shuffler = Shuffle::getInstance($seed);
      }else{
        $shuffler = Shuffle::getInstance();
      }
      echo "<h2>Seed: ".$shuffler->getSeed()."</h2>";

      foreach($ballotPriorities as $ballotPriority){ ?>
        <tr><td colspan="3"><h3><?= $prettyNames[$ballotPriority]; ?></h3></td></tr>
<?
        if($ballotPriority == "SCHOLAR%"){
          $query = "SELECT `ballot_groups`.`id` FROM `ballot_groups`
                    JOIN `ballot_individuals` ON `ballot_groups`.`owner`=`ballot_individuals`.`id`
                    WHERE `priority` LIKE '$ballotPriority'
                    AND (`individual`=1 OR `size`=1)
                    ORDER BY `ballot_groups`.`id`";
          
        }else{
          $query = "SELECT `ballot_groups`.`id` FROM `ballot_groups`
                    JOIN `ballot_individuals` ON `ballot_groups`.`owner`=`ballot_individuals`.`id`
                    WHERE `ballot_groups`.`id` IN (SELECT `groupid` FROM `ballot_individuals` WHERE `priority` LIKE '$ballotPriority') ";
          if($ballotPriority == "SECONDYEAR"){ //Also include third years abroad in here
            $query .= "OR `ballot_groups`.`id` IN (SELECT `groupid` FROM `ballot_individuals` WHERE `priority` LIKE 'THIRDYEARABROAD')
                       OR (`priority` LIKE 'SCHOLARTHIRDABROAD' AND (`individual`=0 AND `size`>1)) ";
          }
          $query .=  "OR (`priority` LIKE '".$scholarGroup[$ballotPriority]."' AND (`individual`=0 AND `size` > 1))
                      ORDER BY `ballot_groups`.`id`";
        }

        $groupIDs = Database::getInstance()->query($query);

        $groups = [];
        while($row = $groupIDs->fetch_assoc()){
          $groups[] = new Group($row['id']);
        }

        $ballotOrder = $shuffler->shuffle($groups);
        foreach($ballotOrder["groups"] as $group){ ?>
          <tr>
            <td class="col-md-1" style="text-align: right;"><?= $ballotPosition++; ?></td>
            <td class="col-md-8"><?= join("<br />", array_map(function($member){
              return $member['name']." (".$member['crsid'].")";
            }, $group->getMemberList())); ?></td>
            <td class="col-md-3"><?= $group->getID(); ?></td>
          </tr>
<?      }
  } ?>
          </table>
<?}
}
