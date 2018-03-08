<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../app/Database.php");
require_once("../app/Shuffle.php");
require_once("../app/Group.php");

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
echo "\nPosition,Person,Group ID\n";

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

foreach($ballotPriorities as $ballotPriority){
  echo $prettyNames[$ballotPriority]."\n";
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

  $ballotOrder = $shuffler->shuffle($groups);
  foreach($ballotOrder["groups"] as $group){
      foreach($group->getMemberList() as $person){
        echo "$ballotPosition, ".$person['name'].", ".$group->getID()."\n";
      }
      $ballotPosition++;
      echo "\n";
  }
}
