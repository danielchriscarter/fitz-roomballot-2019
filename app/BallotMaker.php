<?php

require_once("Database.php");
require_once("Shuffle.php");

class BallotMaker{
  public static function makeBallot($seed = null, $withErrata = false, $formatCSV = false){
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
      $ballotPosition = 1;

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

      $ballotOrder = [];
      $prettyNameIndices = [];
      foreach($ballotPriorities as $ballotPriority){
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
          }else if($ballotPriority == "THIRDYEAR"){
            //Don't include third years who have been 'pulled up' by third years abroad.
            $query .= "AND NOT `ballot_groups`.`id` IN (SELECT `groupid` FROM `ballot_individuals` WHERE `priority` IN ('SCHOLARTHIRDABROAD', 'THIRDYEARABROAD')) ";
          }
          $query .=  "OR (`priority` LIKE '".$scholarGroup[$ballotPriority]."' AND (`individual`=0 AND `size` > 1))
                      ORDER BY `ballot_groups`.`id`";
        }

        $groupIDs = Database::getInstance()->query($query);

        $groups = [];
        while($row = $groupIDs->fetch_assoc()){
          $groups[] = new Group($row['id']);
        }

        //Add to the array, using the pretty name as the key
        $ballotOrder[$ballotPriority] = $shuffler->shuffle($groups);
      } 
      
      if($withErrata){
        //Apply errata
        $errata = Database::getInstance()->fetch("ballot_errata");

        
        //Map of groupids -> errata index
        $errataGroup = [];
        $errataGroupFlag = [];

        foreach($errata as $index=>$error){
          $errataGroup[$error['groupid']] = $index;
        }

        foreach($ballotPriorities as $priority=>$ballotPriority){
          $priorityOrder = $ballotOrder[$ballotPriority];
          foreach($priorityOrder["groups"] as $priorityIndex=>$group){
            if(array_key_exists((string)$group->getID(), $errataGroup)){
              //There is an errata for this group. Find out where to put it. 
              $error = $errata[$errataGroup[(string)$group->getID()]];
              if($error['remove'] == '1'){
                //Remove from the ballot
                unset($ballotOrder[$ballotPriorities[$priority]]["groups"][$priorityIndex]);
              }else{
                //Move to a different position in the ballot
                //Find the right ballot group to move to

                $cumulative = 0;
                foreach($ballotPriorities as $moveToPriority=>$newBallotPriority){
                  $ballotGroupCount = count($ballotOrder[$newBallotPriority]["groups"]);
                  if($cumulative + $ballotGroupCount >= (int)$error['moveto']){
                    //Within this newBallotPriority
                    array_splice($ballotOrder[$newBallotPriority]["groups"], (int)$error['moveto'] - $cumulative, 0, [$group]);
                    //Remove the error from the table to prevent duplication
                    unset($errataGroup[(string)$group->getID()]);
                    //Remove the original mention of the person
                    unset($ballotOrder[$ballotPriorities[$priority]]["groups"][$priorityIndex]);
                    break;
                  }else{
                    $cumulative += $ballotGroupCount;
                  }
                }
              }
            }
          }
        }
      }

      //Print out ballot
?>
      <table class="table table-condensed table-bordered table-hover">
        <thead>
          <tr>
            <td>Position</td>
            <td>Group Members</td>
            <td>Group ID</td>
          </tr>
        </thead>

<?   foreach($ballotPriorities as $ballotPriority){
        $priorityOrder = $ballotOrder[$ballotPriority];
?>      <tr><td colspan="3"><h3><?= $prettyNames[$ballotPriority]; ?></h3></td></tr> <?
				foreach($priorityOrder["groups"] as $group){ ?>
          <tr>
            <td class="col-md-1" style="text-align: right;"><?= $ballotPosition++; ?></td>
            <td class="col-md-8"><?= join("<br />", array_map(function($member){
              return $member['name'];
            }, $group->getMemberList())); ?></td>
            <td class="col-md-3"><?= $group->getID(); ?></td>
          </tr>
<?      }
      }
?>
          </table>
<?}
}
