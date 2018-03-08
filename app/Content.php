<?php

require_once "Database.php";
require_once "Environment.php";
require_once "lib/Michelf/MarkdownInterface.php";
require_once "lib/Michelf/Markdown.php";
require_once "lib/Michelf/SmartyPants.php";
require_once "News.php";
require_once "Timetable.php";
require_once "Groups.php";
require_once "Rooms.php";
require_once "Group.php";
require_once "User.php";
require_once "BallotMaker.php";
require_once "Shuffle.php";

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
            break;
        case "rooms":
            Content::rooms(true);
            break;
        case "houses":
            Content::rooms(false);
            break;
        case "order":
            Content::order();
            break;
        case "admin":
            Content::admin();
            break;
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

  private static function rooms($room = true){
    if(isset($_GET['url'])){
      $db = Database::getInstance();
      $roomname = $db->escape($_GET['url']);

      $roomQuery = "SELECT * FROM `rooms` WHERE `url`='$roomname'";
      $imageQuery = "SELECT *, `room_images`.`description` as `description` FROM `room_images`
               JOIN `rooms` on `roomid`=`rooms`.`id`
               WHERE `rooms`.`url`='$roomname'";
      $quoteQuery = "SELECT * FROM `room_quotes`
               JOIN `rooms` on `roomid`=`rooms`.`id`
               WHERE `rooms`.`url`='$roomname'";

      $roomResult = $db->query($roomQuery);
      if($roomResult->num_rows > 0){
        $room = $roomResult->fetch_assoc();
        $imageResult = $db->query($imageQuery);
        $quoteResult = $db->query($quoteQuery);

        Rooms::HTMLroomView($room, $imageResult, $quoteResult);
      }
    }else{
      //Display a picker for rooms / houses
      if($room){
        Rooms::HTMLroomSelector();
      }else{
        $houses = Database::getInstance()->fetch("rooms", "`house`=1", "`maxsize`"); 
        ?>
        <div class="container">
        <table class="table table-condensed table-bordered table-hover">
          <thead>
            <tr>
              <td>House Address</td>
              <td>Size</td>
            </tr>
          </thead>
<?        foreach($houses as $house){ ?>
            <tr>
              <td><a href='/houses/<?= $house['url']; ?>'><?= $house['name']; ?></a></td>
              <td><?= $house['maxsize']; ?></td>
            </tr>
<?        } ?>
        </table>
        </div>
<?    }
    }
  }

  private static function order(){
    $noErrors = isset($_GET['noerrors']);

    if(!$noErrors && count(Database::getInstance()->fetch("ballot_errata")) > 0){
      Groups::HTMLsuccess("The ballot you are viewing below is with ammendments. <a href='?noerrors'>Click here to view the ballot as it was originally drawn.</a>");
    }

    $seedQuery = Database::getInstance()->fetch("ballot_seed");
    if(count($seedQuery) == 1){ ?>
      <div class="container">
        <?= BallotMaker::makeBallot(null, !$noErrors); ?>
      </div>
<?  }else{
      Groups::HTMLerror("The ballot has not been drawn yet. See <a href='/timetable'>the Ballot Timetable</a> for when this will happen.");
    }
  }

  private static function admin(){
    $user = new User();
    if($user->getCRSID() != Environment::admin_crsid){
      Groups::HTMLerror("You do not have admin permission");
      return;
    } ?>
    <div class="container">      
<?
    //Generate groups for every user

    if(isset($_GET["action"])){
      if($_GET["action"] == "dbfix"){
        $query = "SELECT * FROM `ballot_individuals` WHERE `groupid` IS NULL";
        $result = Database::getInstance()->query($query);

        echo "Generating ".$result->num_rows." individual groups...<br />"; 
        $success = 0;
        while($row = $result->fetch_assoc()){
          //creating the user is enough to create a group
          try{
            $user = new User($row['id']);
            $success += 1;
          }catch(Exception $e){
            echo "Error: ".$e->getMessage()."<br />\n";
            var_dump($row);
            echo "\n";
          }
        }

        echo "Generated $success groups<br />";

        $query = "SELECT `ballot_groups`.`id` as `id`, `size`, `actual` FROM `ballot_groups`
                  LEFT JOIN (
                    SELECT `groupid`, count(*) as `actual` FROM `ballot_individuals`
                    GROUP BY `groupid`
                  ) `groupcounts` ON `groupid`=`ballot_groups`.`id`
                  WHERE `actual`  != `size`
                  OR `actual` IS NULL";
        $result = Database::getInstance()->query($query);

        echo "<br />";
        echo "Found ".$result->num_rows." miscounted group sizes...<br />";
        while($row = $result->fetch_assoc()){
          $id = intval($row['id']);
          $actualSize = intval($row['actual']);
          $size = intval($row['size']);

          echo "$id has $actualSize members, not $size.<br />";

          if($actualSize == 0){
            Group::deleteGroup(new Group($id));
            echo "Deleted it<br />";
          }else if($actualSize > Group::maxSize()){
            echo "<b>Take manual action</b><br />";
          }else{
            $res = Database::getInstance()->query("UPDATE `ballot_groups` SET `size`='$actualSize' WHERE `id`='$id'");
            if($res){
              echo "Reset counter to $actualSize<br />";
            }
          }
        }

        echo "<br />Done!";
      }else if($_GET["action"] == "ballot"){
        if(isset($_GET['seed'])){
          $seed = intval($_GET['seed']);
          if(isset($_GET['final'])){
            //Force-write seed to database - if it already exists, INSERT will fail
            $result = Database::getInstance()->query("INSERT INTO `ballot_seed` (`id`, `seed`) VALUES (0, $seed)", true);
?>          <a href="/scripts/ballot.csv.php">Get ballot spreadsheet</a><br /> <?
            BallotMaker::makeBallot();
          }else{
            BallotMaker::makeBallot($seed); //Simulate ballot
          }
        }else{
          $shuffler = Shuffle::getInstance();
          echo "<h1>Seed: ".$shuffler->getSeed()."</h1>";
          echo "<a href='/admin?action=ballot&seed=".$shuffler->getSeed()."'>Run Mock Ballot</a><br />";
          echo "<b><a href='/admin?action=ballot&seed=".$shuffler->getSeed()."&final'>Run Final Ballot</a></b>";
        }
      }
    }else{ ?>
      <a href="?action=dbfix">Check and fix DB (null groups, incorrect counts)</a><br />
      <a href="?action=ballot">Perform a ballot (or a mock ballot)</a></br />
      <a href="/scripts/ballot.csv.php">Get ballot spreadsheet</a>
<?    }

?>
    </div>
<?
  }

  private static function groups(){
    //TODO: Replace this spaghetti with a templating engine.

    //Load in user
    try{
      $user = new User();
    }catch(Exception $e){
      Groups::HTMLerror($e->getMessage().". How did you get in here?!");
      return;
    }

    if(count($_GET) > 1){ //At least one request ?>
      <div class='container'><a href='/groups'>⇦ Group Ballot Home</a></div>
<?  }

    if(Environment::db_read_only){
      Groups::HTMLwarning("The group ballot is currently read only. You will not be able to make any changes.");
    }

    if(isset($_GET['join'])){
      if(isset($_GET['id'])){
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

      try{
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
      }catch(Exception $e){
        Groups::HTMLerror($e->getMessage()." <a href='/groups'>Go to group ballot home</a>");
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
      try{
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
      }catch(Exception $e){
        Groups::HTMLerror($e->getMessage()." <a href='/groups'>Go back to ballot home</a>");
      }
    }else if(isset($_GET['assign'])){
      if(isset($_GET['confirm'])){
        try{
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
        }catch(Exception $e){
          Groups::HTMLerror($e->getMessage()." <a href='/groups'>Go back to group ballot home</a>");
        }
      }else{
        Groups::HTMLtop($user); ?>
        <b>If you click confirm, you will no longer be owner of this group.</b><br />
        <a href='/groups?assign=<?= $_GET['assign']; ?>&group=<?= $_GET['group']; ?>&confirm'>Confirm.</a>
<?    }
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
