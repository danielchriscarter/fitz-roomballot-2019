<?php

require_once "Database.php";
require_once "lib/Michelf/MarkdownInterface.php";
require_once "lib/Michelf/Markdown.php";
require_once "lib/Michelf/SmartyPants.php";
require_once "News.php";
require_once "Timetable.php";
require_once "Groups.php";
require_once "User.php";

class Content {

    public static function makeContent($url) {
        switch ($url) {
            case "news":
                Content::news();
                break;
            case "timetable":
                Content::timetable();
                break;
            case "groups":
                Content::groups();
        }
    }

    private static function news() {
        News::HTMLtop();

        $queryString = "SELECT *  FROM `news` ORDER BY `id` DESC";
        $result = Database::getInstance()->query($queryString);
        while ($row = $result->fetch_assoc()) {
            $heading = $row["heading"];
            $content = Markdown::defaultTransform($row["content"]);
            $content = SmartyPants::defaultTransform($content);
            News::HTMLrow($heading, $content);
        }

        News::HTMLbottom();
    }

    private static function timetable() {
        Timetable::HTMLtop();

        $queryString = "SELECT *  FROM `timetable`";
        $result = Database::getInstance()->query($queryString);
        while ($row = $result->fetch_assoc()) {
            $dateObj = new DateTime($row["date"]);
            $day = $dateObj->format('l');
            $num = $dateObj->format('d');
            $date = $dateObj->format('F, Y');
            $event = Markdown::defaultTransform($row["event"]);
            $event = SmartyPants::defaultTransform($event);
            Timetable::HTMLrow($num, $day, $date, $row["time"], $event);
        }

        Timetable::HTMLbottom();
    }
    
    private static function groups(){
        //Load in user
        $user = new User();

        if(isset($_GET['join'])){
          if(isset($_GET['id'])){?>
            <div class='container'>
<?

            //Do join request operation
            $success = Database::getInstance()->update("ballot_individuals", "`id`='".$user->getId()."'", [
              "requesting"=>intval($_GET['id'])
            ]);
            $gpinfo = Database::getInstance()->fetch("ballot_groups", "`id`='$_GET[id]'");
            $owner = new User($gpinfo[0]['owner']);
            $owner->sendEmail(
              $user->getCRSID()." has requested to join your ballot group '".$owner->getGroupName()."'",
              "Click <a href='https://roomballot.fitzjcr.com/groups?accept=".$user->getId()."&group=".$owner->getGroupId()."'>here to accept this request</a>"
            );
            if($success){ ?>
              <b>You have succesfully requested to join <a href='?view=<?= $_GET['id']; ?>'><?= htmlentities($gpinfo[0]['name']); ?></a></b>
<?
            }else{ ?>
              <b>There was a problem requesting access to this group</b>
<?
            }
          }
        }else if(isset($_GET['cancel'])){ ?>
          <div class='container'>
<?
          //Do join request operation
          $success = Database::getInstance()->update("ballot_individuals", "`id`='".$user->getId()."'", [
            "requesting"=>null
          ]);

          if($success){ ?>
            <b>You have succesfully canceled your join request</b>
<? 
          }else{
?>
            <b>There was an error cancelling your join request</a>
<?
          }
        }else if(isset($_GET['accept'])){
          Groups::HTMLtop($user);

          $toAccept = new User($_GET['accept']);
          $groupID = intval($_GET['group']);

          //Check, within the query, whether all of the requirements are true.
          $dbQuery =
            "SELECT * FROM `ballot_individuals`
             JOIN `ballot_groups` ON `requesting`=`ballot_groups`.`id`
             WHERE `owner`='".$user->getId()."' AND `requesting`='$groupID' AND `ballot_individuals`.`id`='".$toAccept->getId()."'";

          $result = Database::getInstance()->query($dbQuery);
         
          if($result->num_rows > 0){
            //Move user to group
            if($toAccept->moveToGroup($groupID)){ 
              $toAccept->sendEmail(
                "You've been accepted into the ballot group '".$user->getEscapedGroupName()."'",
                "Click <a href='https://roomballot.fitzjcr.com/groups?view=".$user->getGroupId()."'>here</a> to go to the group."
              );
            ?>
              <b>You have accepted <?= $toAccept->getCRSID(); ?> into your group.</b>             
<?
            }
          }else{ ?>
            <b>There was a problem accepting the request - they may no longer be requesting access, or you might not have permission to accept requests</b>     
<?
          }
        }else if(isset($_GET['leave'])){ ?>
          <div class='container'>
<?
          //User can only leave groups they're not a part of
          if(!$user->ownsGroup($user->getGroupId())){
            //Create individual group
            $groupId = random_int(0, PHP_INT_MAX);
            $result = Database::getInstance()->insert("ballot_groups", [
              "id" => $groupId,
              "name" => $user->getCRSID(),
              "owner" => $user->getID(),
              "public" => false,
              "individual" => true,
              "size" => 0
            ]);

            //Move user to this group
            if($result && $user->moveToGroup($groupId)){ ?>
              <b>You're now balloting alone. <a href='/groups'>Go back to Groups page</a></b>
<?
            }else{ 
              echo Database::getInstance()->error(); ?>
              <b>There was a problem leaving the group, please try again</b>
<?
            }
          }else{ ?>
            <b>Group owner can't leave the group. You need to assign ownership to someone else.</b>
<?
          }
        }else if(isset($_GET['create'])){
          if(isset($_POST['groupname'])){ ?>
            <div class='container'>
<?
            //Do create operation

            $newGroupId = random_int(0, PHP_INT_MAX);
            //Create group
            $result = Database::getInstance()->insert("ballot_groups", [
              "id" => $newGroupId,
              "name" => $_POST['groupname'],
              "owner" => $user->getID(),
              "public" => isset($_POST['public']) && $_POST['public'] ? true : false,
              "individual" => false,
              "size" => 0
            ]);

            //Place user in group
            if($result && $user->moveToGroup($newGroupId)){
              echo "<b>Group Created! <a href='?view=$newGroupId'>Go to group</a></b>";
            }else{
              echo "<b>There was a problem creating the group.</b>";
            }
          }else{
            Groups::HTMLtop($user);
            Groups::HTMLcreate();
          }
        }else if(isset($_GET['view'])){ //Show group information
          Groups::HTMLtop($user);

          $queryString = Groups::getGroupQuery($_GET['view']);
          $result = Database::getInstance()->query($queryString);

          Groups::HTMLgroupView($user, $result);
        }else if(isset($_GET['assign'])){ ?>
          <div class='container'>    
<?
          //Do some checks
          $groupID = intval($_GET['group']);
          $newOwner = new User($_GET['assign']);
          
          if(!$user->ownsGroup($groupID)){
            echo "<b>You don't own this group and so cannot assign owners</b>";
          }else if($newOwner->getGroupId() != $groupID){
            echo "<b>".$newOwner->getCRSID()." is not a member of this group and so cannot become owner</b>";
          }else{
            //Set group owner to newOwner's ID
            $result = Database::getInstance()->update("ballot_groups", "`id`='$groupID' AND `owner`='".$user->getID()."'", ["owner"=>$newOwner->getID()]);
            if($result){
              echo "<b>You are no longer owner of ".$user->getEscapedGroupName()."</b>";
              $newOwner->sendEmail(
                $user->getCRSID()." has given you ownership of '".$user->getGroupName()."'",
                "Click <a href='https://roomballot.fitzjcr.com/groups?view=".$user->getGroupId()."'>here to view the group</a>"
              );
            }else{
              echo "<b>There was an error removing you as owner</b>";
            }
          }
        }else{ //Show public groups
          Groups::HTMLtop($user);

          $queryString = "SELECT * FROM `ballot_groups` WHERE `public`=TRUE AND `id`!='".$user->getGroupID()."' AND `id`!='".$user->getRequestingGroupId()."' AND `size`< ".Groups::maxGroupSize();
          $result = Database::getInstance()->query($queryString);
          Groups::HTMLgroupList($result, $user);
        }

        Groups::HTMLbottom();
    }
}

?>
