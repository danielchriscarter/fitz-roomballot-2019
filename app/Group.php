<?php
/* This class makes managing groups easier.
 * It should probably be using the Singleton pattern.
 */
require_once "Database.php";

class Group {
  const SCHOLARSECOND = 1;
  const SCHOLARTHIRD = 2;
  const SCHOLARTHIRDABROAD = 3;
  const SECONDYEAR = 4;
  const THIRDYEARABROAD = 5; /* Second years and third years abroad can now ballot together */
  const THIRDYEAR = 6;
  const FIRSTYEAR = 7;

  private $data;

  public function __construct($id){
    //Get DB info from id supplied
    $id = intval($id);
    $query = "SELECT `ballot_groups`.`id` as `id`, `ballot_groups`.`name` as `name`, 
                     `owner`, `public`, `individual`, `size`, `crsid`,`priority`,
                     `ballot_individuals`.`name` as `ownername` 
              FROM ballot_groups
              JOIN `ballot_individuals` ON `owner`=`ballot_individuals`.`id`
              WHERE `ballot_groups`.`id`='$id'";
    $result = Database::getInstance()->query($query);

    if($result->num_rows == 0){
      throw new Exception("This group was not found (with ID $id). Has it been deleted?");
    }
    
    $this->data = $result->fetch_assoc();
  }

  public static function GroupPriority($string){
    switch($string){
      case "SCHOLARSECOND":
      case "SECONDYEAR":
        return Group::SECONDYEAR;
      case "SCHOLARTHIRDABROAD":
      case "THIRDYEARABROAD":
        return Group::THIRDYEARABROAD;
      case "SCHOLARTHIRD":
      case "THIRDYEAR":
        return Group::THIRDYEAR;
      case "FIRSTYEAR":
        return Group::FIRSTYEAR;
    }
  }
  public static function createGroup($name, $owner, $individual = false){
    $groupId = random_int(0, PHP_INT_MAX);
    $result = Database::getInstance()->insert("ballot_groups", [
      "id" => $groupId,
      "name" => $individual ? $owner->getCRSID()." ".$groupId : $name,
      "owner" => $owner->getID(),
      "public" => false,
      "individual" => $individual,
      "size" => 0
    ]);

    if($result){
      return new Group($groupId);
    }else{
      throw new Exception("Error creating group");
    }
  }

  public static function maxSize(){
    //Returns the maximum group size
    return 9;
  }

  public static function deleteGroup($group){
    $success = Database::getInstance()->delete("ballot_groups", "`id`='".$group->getID()."'");
    if(!$success){
      throw new Exception("Error deleting group");
    }
  }

  public function getUnsafeName(){
    return $this->data['name'];
  }

  public function getBallotPriority(){
    //Returns the ballot priority of this group
    return Group::GroupPriority($this->data['priority']);
  }

  public function getName(){
    return htmlentities($this->data['name']);
  }

  public function getID(){
    return intval($this->data['id']);
  }

  public function getSize(){
    return intval($this->data['size']);
  }

  public function isIndividual(){
    return $this->data['individual'] == "1";
  }
  
  public function isPublic(){
    return $this->data['public'] == "1";
  }

  public function setPublic(bool $public){
    $result = Database::getInstance()->update("ballot_groups", "`id`='".$this->getID()."'", [
      "public" => $public 
    ]);

    return $result;
  }

  public function getOwnerID(){
    return intval($this->data['owner']);
  }

  public function getOwnerCRSID(){
    return $this->data['crsid'];
  }

  public function getOwnerName(){
    return $this->data['ownername'];
  }
  
  public function getURL(){
    return "https://roomballot.fitzjcr.com/groups?view=".$this->getID();
  }

  public function getHTMLLink($string = null){
    $link = "<a href='".$this->getURL()."'>";
    $link .= $string == null ? $this->getName() : $string;
    $link .= "</a>";

    return $link;
  }

  public function getMemberList(){
    $query = "SELECT `ballot_individuals`.`id` as `id`, `ballot_individuals`.`name` as `name`, `crsid`
              FROM `ballot_groups`
              JOIN `ballot_individuals`
              ON `groupid`=`ballot_groups`.`id` 
              WHERE `ballot_groups`.`id`='".$this->getID()."'
              ORDER BY `name`";
    $result = Database::getInstance()->query($query);
    $rows = [];
    while($row = $result->fetch_assoc()){
      $rows[] = $row;
    } 
    return $rows;
  }

  public function getRequestingList(){
    $query = "SELECT `ballot_individuals`.`id` as `id`, `crsid`, `ballot_individuals`.`name`
              FROM `ballot_groups`
              JOIN `ballot_individuals`
              ON `requesting`=`ballot_groups`.`id` 
              WHERE `ballot_groups`.`id`='".$this->getID()."'
              ORDER BY `name`"; 
    $result = Database::getInstance()->query($query);
    $rows = []; 
    while($row = $result->fetch_assoc()){
      $rows[] = $row;
    } 
    return $rows;
  }
}
?>
