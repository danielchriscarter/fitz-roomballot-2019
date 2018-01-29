<?php
/* This class makes Raven auth and DB access easier, provided
   Raven authentication is required in .htaccess
 */

require_once "Database.php";

class User {
  protected $crsid = NULL;
  protected $data = NULL;

  public function __construct($dbid = null){
    //https://wiki.cam.ac.uk/raven/Accessing_authentication_information
    if($dbid === null){ //Get logged in user
      $this->crsid = $_SERVER['REMOTE_USER'];    
    }else{ //Get user by DB query
      $queryString = 
        "SELECT `crsid`
         FROM `ballot_individuals` 
         WHERE `id`='".Database::getInstance()->escape($dbid)."'";
      $result = Database::getInstance()->query($queryString);

      if($result->num_rows > 0){ //User exists in DB
        $this->crsid = $result->fetch_assoc()['crsid'];
      }else{
        throw new Exception("Can't find user in DB");
      }
    }

    if($this->crsid == null){
      throw new Exception("No such user in existence");
    }

    //Get user data from DB (e.g group ID)
    $queryString = 
      "SELECT `ballot_individuals`.`id` as `id`,`searching`,`groupid`,`name` as `groupname`, `individual`, `requesting`, `size`
       FROM `ballot_individuals` 
       JOIN `ballot_groups` 
       ON `groupid` = `ballot_groups`.`id`
       WHERE `crsid`='$this->crsid'";

    $result = Database::getInstance()->query($queryString);

    if($result->num_rows > 0){ //User exists in DB
      $this->data = $result->fetch_assoc();
    }else{ //Create user in DB with individual group
      $db = Database::getInstance();

      //Populate data
      $this->data['id'] = random_int(0, PHP_INT_MAX);
      $this->data['searching'] = false;
      $this->data['groupid'] = null;
      $this->data['groupname'] = $this->crsid;
      $this->data['individual'] = true;
      $this->data['requesting'] = null;
      $this->data['size'] = 1;

      $insertSuccess = $db->insert("ballot_individuals", [
        "id"=>$this->getID(),
        "crsid"=>$this->crsid,
        "groupid"=>null,
        "searching"=>false
      ]);

      if(!$insertSuccess){
        throw new Exception("Error adding user to database");
      }

      //Create a new individual group for this user
      $groupId = random_int(0, PHP_INT_MAX);
      $result = Database::getInstance()->insert("ballot_groups", [
        "id" => $groupId,
        "name" => $this->crsid,
        "owner" => $this->getID(),
        "public" => false,
        "individual" => true,
        "size" => 0
      ]);

      if(!$result){
        throw new Exception("Error creating group for new user");
      }

      if(!$this->moveToGroup($groupId)){
        throw new Exception("Error moving new user to individual group");
      }
    }
  }

  public function getRequestingGroup(){
    if($this->getRequestingGroupId() != null){
      return Database::getInstance()->fetch("ballot_groups", "`id`='".$this->getRequestingGroupId()."'")[0];
    }
    return null;
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

  public function isIndividual(){
    return $this->data['individual'] === "1";
  }
  public function getId(){
    return intval($this->data['id']);
  }

  public function getEmail(){
    return $this->getCRSID()."@cam.ac.uk";
  }
  
  public function getGroupSize(){
    return intval($this->data['size']);
  }

  public function getRequestingGroupId(){
    if($this->data['requesting'] != ""){
      return intval($this->data['requesting']);
    }else{
      return null;
    }
  }

  public function moveToGroup($gid){
    //Decrement current group size, increment new group size, update group ID field
    //If group will be empty, remove it
    $db = Database::getInstance();
    
    $queries = [];
    
    $queries[] = "UPDATE `ballot_groups` SET `size` = `size`+1 WHERE `id`='$gid'";
    $queries[] = "UPDATE `ballot_individuals` SET `groupid`='$gid',`requesting`=NULL WHERE `id`='".$this->data['id']."'";

    if($this->data['groupid'] != null){
      $oldGroup = $db->fetch("ballot_groups", "`id`='".$this->data['groupid']."'");
      if(count($oldGroup) > 0){
          if(intval($oldGroup[0]['size']) == 1){
            $queries[] = "DELETE FROM `ballot_groups` WHERE `id`='".$this->data['groupid']."'";
          }else{
            $queries[] = "UPDATE `ballot_groups` SET `size`=`size`-1 WHERE `id`='".$this->data['groupid']."'";
          }
      }
    }

    return $db->transaction($queries);
  }

  public function sendEmail($subject, $body){
    mail(
      $this->getEmail(), 
      $subject,
      $body,
      "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8\r\n"
    );
  }

  public function getEscapedGroupName(){
    return htmlentities($this->getGroupName());
  }
}
