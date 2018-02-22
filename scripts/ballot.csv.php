<?php
require_once "../app/Database.php";
require_once "../app/Shuffle.php";
require_once "../app/Group.php";

$ballotPriorities = ["SCHOLAR%", "SECONDYEAR", "THIRDYEAR", "FIRSTYEAR"];
$prettyNames = [
	"SCHOLAR%" => "Scholars' Individual Ballot",
	"SECONDYEAR" => "Second Years'",
	"THIRDYEAR" => "Third Years' With Confirmed Fourth",
	"FIRSTYEAR" => "First Years'"
];
$scholarGroup = [
	"SECONDYEAR" => "SCHOLARSECOND",
	"THIRDYEAR" => "SCHOLARTHIRD",
	"FIRSTYEAR" => "FIRSTYEAR"
];
?>
Position, Group Members, Group ID
<?
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

echo $shuffler->getSeed()."\n";
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
							WHERE `priority` LIKE '$ballotPriority'
							OR (`priority`='".$scholarGroup[$ballotPriority]."' AND (`individual`=0 OR `size` > 1))
							ORDER BY `ballot_groups`.`id`";
	}

	$groupIDs = Database::getInstance()->query($query);

	$groups = [];
	while($row = $groupIDs->fetch_assoc()){
		$groups[] = new Group($row['id']);
	}

	$ballotOrder = $shuffler->shuffle($groups);
	foreach($ballotOrder["groups"] as $group){ 
		foreach($group->getMemberList() as $member){
			echo "$ballotPosition, ".$member['name']." (".$member['crsid']."), ".$group->getID()."\n";
    }
		$ballotPosition++;
  }
} ?>
