<?php

require_once "Database.php";
require_once "lib/Michelf/MarkdownInterface.php";
require_once "lib/Michelf/Markdown.php";
require_once "lib/Michelf/SmartyPants.php";
require_once "News.php";
require_once "Timetable.php";
require_once "Groups.php";
require_once "Group.php";
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
        //TODO: Replace this spaghetti with a templating engine.

        //Load in user
        $user = new User();

        if(count($_GET) > 1){ //At least one request ?>
          <div class='container'><a href='/groups'>⇦ Group Ballot Home</a></div>
<?      }

        if(isset($_GET['join'])){
          if(isset($_GET['id'])){?>
            <div class='container'>
<?
            try{
              $group = new Group($_GET['id']);
              $owner = new User($group->getOwnerID());

              if($user->getGroup() == $group){
                Groups::HTMLerror("You can't request access to your own group! ".$group->getHTMLLink("Go to group page."));
              }else if(!$user->canJoin($group)){
                Groups::HTMLerror("You are not able to join this group - is it full, or are you in a different year? ".$group->getHTMLLink("Go to group page."));
              }else if($user->getRequestingGroup() == $group){
                Groups::HTMLerror("You are already requesting access to this group. ".$group->getHTMLLink("Go to group page."));
              }else{
                //Do join request operation
                $success = Database::getInstance()->update("ballot_individuals", "`id`='".$user->getId()."'", [
                  "requesting"=>$group->getID()
                ]);

                if($success){
                  $owner->sendEmail(
                    $user->getName()." has requested to join your ballot group '".$group->getUnsafeName()."'",
                    "<a href='https://roomballot.fitzjcr.com/groups?accept=".$user->getId()."&group=".$owner->getGroup()->getID()."'>Click here to accept this request</a>"
                  );

                  Groups::HTMLsuccess("You have succesfully requested to join ".$group->getHTMLLink());
                  if(!$user->canLeave()){ 
                    Groups::HTMLsuccess("You will not be able to join the new group while you own your current one.");
                  }
                }else{ 
                  Groups::HTMLerror("There was a problem requesting access to this group. ".$group->getHTMLLink("Go to this group's page"));
                }
              }
            }catch(Exception $e){
              Groups::HTMLerror($e->getMessage());
            }
          }
        }else if(isset($_GET['cancel'])){
          //Do cancel request operation
          $success = Database::getInstance()->update("ballot_individuals", "`id`='".$user->getID()."'", [
            "requesting"=>null
          ]);

          if($success){ 
            Groups::HTMLsuccess("You have succesfully cancelled your join request. <a href='/groups'>Go back to ballot home.</a>");
          }else{
            Groups::HTMLwarning("There was an error cancelling your join request. <a href='/groups'>Go back to ballot home.</a>");
          }
        }else if(isset($_GET['accept'])){
          Groups::HTMLtop($user);

          $toAccept = new User($_GET['accept']);
          $group = new Group($_GET['group']);

          //Check, within the query, whether all of the requirements are true.
          $dbQuery =
            "SELECT * FROM `ballot_individuals`
             JOIN `ballot_groups` ON `requesting`=`ballot_groups`.`id`
             WHERE `owner`='".$user->getID()."' AND `requesting`='".$group->getID()."' AND `ballot_individuals`.`id`='".$toAccept->getID()."'";

          $result = Database::getInstance()->query($dbQuery);
          if($result->num_rows > 0){
            //Move user to group
            if($toAccept->moveToGroup($group)){
              $toAccept->sendEmail(
                "You've been accepted into the ballot group '".$group->getUnsafeName()."'",
                $group->getHTMLLink("Click here to view the group")
              );
              Groups::HTMLsuccess("You have accepted ".$toAccept->getName()." into your group. ".$group->getHTMLLink("View the group."));
            }else{
              Groups::HTMLerror("There was a problem accepting the request - are they owner of a different group? Are they in your year? ".$group->getHTMLLink("Go to the group page."));
            }
          }else{
            Groups::HTMLerror("There was a problem accepting the request - they may no longer be requesting access, or you might not have permission to accept requests. <a href='/groups'>Go to ballot home.</a>");
          }
        }else if(isset($_GET['leave'])){
          //User can only leave groups (>1) they're not owner for
          if($user->canLeave()){
            //Create individual group
            try{
              $newGroup = Group::createGroup($user->getCRSID(), $user, true);

              //Move user to this group
              if($user->moveToGroup($newGroup)){
                Groups::HTMLsuccess("You're now balloting alone. <a href='/groups'>Go back to Group Ballot home</a>");
              }else{
                Group::deleteGroup($newGroup);
                Groups::HTMLerror("There was a problem leaving the group, please refresh to try again.");
              }
            }catch(Exception $e){
              Groups::HTMLerror("There was a problem creating an individual ballot entry. Please refresh to try again.");
            }
          }else{
            Groups::HTMLerror("Group owner can't leave the group. You need to assign ownership to someone else. ".$user->getGroup()->getHTMLLink("Go to group page."));
          }
        }else if(isset($_GET['create'])){
          if(isset($_POST['groupname'])){
            //Do create operation
            try{
              $group = Group::createGroup($_POST['groupname'], $user);
              //Place user in group
              if($user->moveToGroup($group)){
                Groups::HTMLsuccess("Group Created! ".$group->getHTMLLink("Go to the new group"));
              }else{
                Group::deleteGroup($group);
                Groups::HTMLerror("There was a problem moving you to the group. <a href='/groups?create'>Go back to Create form</a>");
              }
            }catch(Exception $e){
              Groups::HTMLerror("There was a problem creating the new group - the name may already exist. <a href='/groups?create'>Go back to Create form</a>");
            }
          }else{
            Groups::HTMLtop($user);
            Groups::HTMLcreate();
          }
        }else if(isset($_GET['view'])){ //Show group information
          Groups::HTMLtop($user);

          try{
            $group = new Group($_GET['view']);
            Groups::HTMLgroupView($user, $group);
          }catch(Exception $e){
            Groups::HTMLerror($e->getMessage());
          }

        }else if(isset($_GET['change'])){
          $group = new Group($_GET['change']);
          if($user->ownsGroup($group)){
            if(isset($_GET['public'])){
              if($group->setPublic($_GET['public'] == "1")){
                Groups::HTMLsuccess("You have succesfully changed group publicity options. ".$group->getHTMLLink("Go back to group"));
              }else{
                Groups::HTMLerror("There was a problem changing publicity. Please refresh to try again.");
              }
            }
          }
        }else if(isset($_GET['assign'])){
          if(isset($_GET['confirm'])){
            //Do some checks
            $group = new Group($_GET['group']);
            $newOwner = new User($_GET['assign']);

            if(!$user->ownsGroup($group)){
              Groups::HTMLerror("You don't own this group and so cannot assign owners. <a href='/groups'>Go to ballot home.</a>");
            }else if($newOwner->getGroup() != $group){
              Groups::HTMLerror($newOwner->getName()." is not a member of this group and so cannot become owner. <a href='/groups'>Go to ballot home</a>");
            }else{
              //Set group owner to newOwner's ID
              $result = Database::getInstance()->update("ballot_groups", "`id`='".$group->getID()."' AND `owner`='".$user->getID()."'", ["owner"=>$newOwner->getID()]);
              if($result){
                Groups::HTMLsuccess("You are no longer owner of ".$group->getName().". ".$group->getHTMLLink("Go back to group."));

                $newOwner->sendEmail(
                  $user->getName()." has given you ownership of '".$group->getUnsafeName()."'",
                  $group->getHTMLLink("Click here to view the group")
                );
              }else{
                Groups::HTMLerror("There was an error removing you as owner. Please refresh to try again.");
              }
            }
          }else{
            Groups::HTMLtop($user); ?>
            <b>If you click confirm, you will no longer be owner of this group.</b><br />
            <a href='/groups?assign=<?= $_GET['assign']; ?>&group=<?= $_GET['group']; ?>&confirm'>Confirm.</a>
<?        }
        }else{ //Show public groups
          Groups::HTMLtop($user);
          $queryString = "SELECT `ballot_groups`.`id` as `id`, `ballot_groups`.`name` as `name`, `size` FROM `ballot_groups` 
                          JOIN `ballot_individuals` ON `ballot_groups`.`owner`=`ballot_individuals`.`id`
                          WHERE `public`=TRUE AND 
                          `ballot_groups`.`id`!='".$user->getGroup()->getID()."' 
                          AND `priority` IN (".$user->getBallotPriorityForDB().")
                          AND `size` < ".Group::maxSize();
          if($user->getRequestingGroup()){
            $queryString .= " AND `ballot_groups`.`id`!='".$user->getRequestingGroup()->getID()."'";
          }

          $result = Database::getInstance()->query($queryString);
          Groups::HTMLgroupList($result, $user);
        }

        Groups::HTMLbottom();
    }
}

?>
