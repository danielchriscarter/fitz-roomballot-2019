<?php
/* This class makes Raven auth and DB access easier, provided
   Raven authentication is required in .htaccess
 */

require_once "Database.php";
require_once "Group.php";
require_once "Version.php";

class User {
  protected $crsid = NULL;
  protected $data = NULL;
  protected $group = null;
  protected $requestingGroup = null;

  public function __construct($dbid = null) {
    // https://wiki.cam.ac.uk/raven/Accessing_authentication_information
    if($dbid === null) {
      // Get logged in user
      $this->crsid = $_SERVER['REMOTE_USER'];
    } else {
      // Get user by DB query
      $queryString = 
        "SELECT `crsid`
         FROM `ballot_individuals` 
         WHERE `id`='".Database::getInstance()->escape($dbid)."'";
      $result = Database::getInstance()->query($queryString);
      if ($result->num_rows > 0) {
        // User exists in DB
        $this->crsid = $result->fetch_assoc()['crsid'];
      } else {
        throw new Exception("Can't find user in database - do you have the correct link?");
      }
    }

    if ($this->crsid == null) {
      throw new Exception("No such user in existence - do you have the correct link?");
    }

    // Get user data from DB (e.g group ID)
    $queryString = 
      "SELECT *
       FROM `ballot_individuals` 
       WHERE `crsid`='$this->crsid'";
    $result = Database::getInstance()->query($queryString);
    if ($result->num_rows > 0) {
      // User exists in DB
      $this->data = $result->fetch_assoc();
    } else {
      // Create user in DB with individual group
      $db = Database::getInstance();

      //Populate data
      $this->data['id'] = random_int(0, PHP_INT_MAX);
      $this->data['name'] = $this->crsid;
      $this->data['searching'] = false;
      $this->data['groupid'] = null;
      $this->data['requesting'] = null;

      $insertSuccess = $db->insert("ballot_individuals", [
        "id"=>$this->data['id'],
        "name"=>$this->data['name'],
        "crsid"=>$this->crsid,
        "groupid"=>$this->data['groupid'],
        "searching"=>$this->data['searching']
      ]);

      if(!$insertSuccess){
        throw new Exception("Error adding user to database.");
      }
    }

    // Initialise groups
    if ($this->data['groupid'] != null) {
      $this->group = new Group($this->data['groupid']);
    } else {
      //Construct a new individual group for this user
      $newGroup = Group::createGroup($this->data['name'], $this, true);
      if(!$this->moveToGroup($newGroup)){
        Group::deleteGroup($newGroup);
        throw new Exception("Error moving new user to individual group.");
      }
    }
    if ($this->data['requesting'] != null) {
      $this->requestingGroup = new Group($this->data['requesting']);
    }
  }

  public function getCRSID() {
    return $this->crsid;
  }

  public function getName() {
    // Returns a user-facing name
    return $this->data['name'];
  }

  public function getBallotPriority() {
    // Returns the ballot priority of the user
    return Group::GroupPriority($this->data['priority']);
  }

  public function getBallotPriorityForDB() {
    // Returns the ballot priority string, for looking up in DB
    switch($this->data['priority']){
      //Third years abroad are treated like 2nd years
      case "SCHOLARTHIRDABROAD":
      case "THIRDYEARABROAD":
      case "SCHOLARSECOND":
      case "SECONDYEAR":
        return "'SCHOLARSECOND','SECONDYEAR', 'THIRDYEARABROAD', 'SCHOLARTHIRDABROAD'";
      case "SCHOLARTHIRD":
      case "THIRDYEAR":
        return "'SCHOLARTHIRD','THIRDYEAR', 'THIRDYEARABROAD', 'SCHOLARTHIRDABROAD'";
      case "FIRSTYEAR":
        return "'FIRSTYEAR'";
      default:
        return "";
    }
  }

  public function isScholar() {
    // Returns whether this user is a scholar.
    return $this->data['priority'] == "SCHOLARSECOND" || $this->data['priority'] == "SCHOLARTHIRD" || $this->data['priority'] == "SCHOLARTHIRDABROAD";
  }

  //
  // getGroupId is deprecated
  //  
  public function getGroupId() {
    return $this->getGroup()->getId();
  }

  public function isIndividual() {
    if($this->getGroup() == null) {
      // No group === individual
      return true;
    }
    return $this->getGroup()->isIndividual();
  }

  public function getID() {
    return intval($this->data['id']);
  }

  public function getEmail() {
    return $this->getCRSID()."@cam.ac.uk";
  }
  
  public function getGroupSize() {
    return intval($this->data['size']);
  }

  //
  // getRequestingGroupId is deprecated
  //
  public function getRequestingGroupId() {
    if ($this->getRequestingGroup() != null) {
      return $this->getRequestingGroup()->getId();
    } else {
      return null;
    }
  }

  public function getRequestingGroup() {
    return $this->requestingGroup;     
  }

  public function getGroup() {
    return $this->group; 
  }

  public function ownsGroup($group = null) {
    // Check if user owns group
    if ($group == null) {
      $group = $this->getGroup();
    }

    return $group->getOwnerID() == $this->getID();
  }

  public function canLeave() {
    // Returns whether the user can leave the group they are currently in
    // They may leave a "null" group
    if ($this->getGroup() == null) return true;
    return $this->isIndividual() || $this->getGroup()->getSize() == 1 || !$this->ownsGroup();
  }

  public function canJoin($group){
    // Returns whether the user can join a group
    if ($this->getGroup() != $group && $group->getSize() < Group::maxSize()) {
      return $this->getBallotPriority() == $group->getBallotPriority()
          //Allow third years abroad to ballot with both 2nd and 3rd years
          || ($this->getBallotPriority() == Group::THIRDYEARABROAD && (
               $group->getBallotPriority() == Group::SECONDYEAR
            || $group->getBallotPriority() == Group::THIRDYEAR
            )
          );
    }
  }

  public function moveToGroup($group) {
    // Decrement current group size, increment new group size, update group ID field
    // If group will be empty, remove it
    if (!$this->canLeave()) {
      //echo "Group owner ".$this->getCRSID()." can't leave their current group.<br />";
      return false;
    }
    if (!$this->canJoin($group)) {
      //echo "Cannot join a full group or a group with a different ballot priority than you<br />";
      return false;
    }

    $db = Database::getInstance();
    $queries = [];
    $queries[] = "UPDATE `ballot_groups` SET `size` = `size`+1 WHERE `id`='".$group->getID()."'";
    $queries[] = "UPDATE `ballot_individuals` SET `groupid`='".$group->getID()."',`requesting`=NULL WHERE `id`='".$this->getID()."'";
    if ($this->data['groupid'] != null) {
      $oldGroup = $db->fetch("ballot_groups", "`id`='".$this->data['groupid']."'");
      if (count($oldGroup) > 0) {
        if (intval($oldGroup[0]['size']) == 1) {
          $queries[] = "DELETE FROM `ballot_groups` WHERE `id`='".$this->data['groupid']."'";
        } else {
          $queries[] = "UPDATE `ballot_groups` SET `size`=`size`-1 WHERE `id`='".$this->data['groupid']."'";
        }
      }
    }
    

    if($db->transaction($queries)){
      // Update internal state
      $this->data['requesting'] = "";
      $this->data['groupid'] = $group->getID();
      $this->group = $group;
      return true;
    } else {
      return false;
    }
  }

  public function sendEmail($subject, $body) {
    mail(
      $this->getEmail(), 
      "=?UTF-8?B?" . base64_encode($subject) . "?=",
      $body,
      "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\nX-roomballot: " . Version::getVersion()
    );
  }
}
