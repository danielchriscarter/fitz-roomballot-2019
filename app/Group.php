<?php
/* This class makes managing groups easier.
 * It should probably be using the Singleton pattern.
 */
require_once "Database.php";

class Group {
  private $data;

  public function __construct($id){
    //Get DB info from id supplied
    $id = intval($id);
    $query = "SELECT `ballot_groups`.`id` as `id`, `ballot_groups`.`name` as `name`, `owner`, `public`, `individual`, `size`, `crsid` FROM ballot_groups
              JOIN `ballot_individuals` ON `owner`=`ballot_individuals`.`id`
              WHERE `ballot_groups`.`id`='$id'";
    $result = Database::getInstance()->query($query);

    if($result->num_rows == 0){
      throw new Exception("Group $id not found");
    }
    
    $this->data = $result->fetch_assoc();
  }

  public static function createGroup($name, $owner, $individual = false){
    $groupId = random_int(0, PHP_INT_MAX);
    $result = Database::getInstance()->insert("ballot_groups", [
      "id" => $groupId,
      "name" => $individual ? $owner->getCRSID() : $name,
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

  public static function deleteGroup($group){
    $success = Database::getInstance()->delete("ballot_groups", "`id`='".$group->getID()."'");
    if(!$success){
      throw new Exception("Error deleting group");
    }
  }

  public function getUnsafeName(){
    return $this->data['name'];
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

  public function getOwnerID(){
    return intval($this->data['owner']);
  }

  public function getOwnerCRSID(){
    return $this->data['crsid'];
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
    $query = "SELECT `ballot_individuals`.`id` as `id`, `crsid`
              FROM `ballot_groups`
              JOIN `ballot_individuals`
              ON `groupid`=`ballot_groups`.`id` 
              WHERE `ballot_groups`.`id`='".$this->getID()."'";

    $result = Database::getInstance()->query($query);
    $rows = [];
    while($row = $result->fetch_assoc()){
      $rows[] = $row;
    }

    return $rows;
  }
}
?>
