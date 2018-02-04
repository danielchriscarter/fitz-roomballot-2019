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
    $query = "SELECT *, `crsid` FROM ballot_groups
              JOIN `ballot_individuals` ON `owner`=`ballot_individuals`.`id`
              WHERE `ballot_groups`.`id`='$id'";
    $result = Database::getInstance()->query($query);

    if($result->num_rows == 0){
      throw new Exception("Group $id not found");
    }
    
    $this->data = $result->fetch_assoc();
  }

  public function getUnsafeName(){
    return $this->data['name'];
  }

  public function getName(){
    return htmlentities($this->data['name']);
  }

  public function getId(){
    return intval($this->data['id']);
  }

  public function getSize(){
    return intval($this->data['size']);
  }

  public function isIndividual(){
    return $this->data['individual'] == "1";
  }
?>
