<?php
/* This class makes Raven auth and DB access easier, provided
   Raven authentication is required in .htaccess
 */

require_once "Database.php";

class User {
  protected $crsid = NULL;
  protected $data = NULL;

  public function __construct(){
    //https://wiki.cam.ac.uk/raven/Accessing_authentication_information
    $this->crsid = $_SERVER['REMOTE_USER'];    

    if($this->crsid == null){
      throw new Exception("No logged in user");
    }

    //Get user data from DB (e.g group ID)
    $queryString = 
      "SELECT `ballot_individuals`.`id` as `id`,`searching`,`groupid`,`name` as `groupname` 
       FROM `ballot_individuals` 
       JOIN `ballot_groups` 
       ON `groupid` = `ballot_groups`.`id`
       WHERE `crsid`='$this->crsid'";

    $result = Database::getInstance()->query($queryString);
    if($result->num_rows > 0){ //User exists in DB
      $this->data = $result->fetch_assoc();
    }else{ //Create user in DB with individual group
      
    }
  }

  public function getCRSID(){
    return $this->crsid;
  }

  public function getGroupName(){
    return $this->data['groupname'];    
  }

  public function getGroupId(){
    return intval($this->data['groupid']);
  }

  public function getId(){
    return intval($this->data['id']);
  }

  public function moveToGroup($gid){
    //Decrement current group size, increment new group size, update group ID field
    //If group will be empty, remove it
    $db = Database::getInstance();
    
    $oldGroup = $db->fetch("ballot_groups", "`id`='".$this->data['groupid']."'");
    $queries = [];
    
    $db->query("START TRANSACTION");
    if(count($oldGroup) > 0){
      if(intval($oldGroup[0]['size']) == 1){
        $queries[] = "DELETE FROM `ballot_groups` WHERE `id`='".$this->data['groupid']."'";
      }else{
        $queries[] = "UPDATE `ballot_groups` SET `size`=`size`-1 WHERE `id`='".$this->data['groupid']."'";
      }
    }

    $queries[] = "UPDATE `ballot_groups` SET `size` = `size`+1 WHERE `id`='$gid'";
    $queries[] = "UPDATE `ballot_individuals` SET `groupid`='$gid' WHERE `id`='".$this->data['id']."'";
    
    return $db->transaction($queries);
  }
}
