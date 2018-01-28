<?php
class Groups {

    public static function HTMLtop($user) {
?>
      <div class="container">
        <h2>Welcome, <?= $user->getCRSID(); ?></h2>
        <p>
<?
          if(!$user->isIndividual()){
?>
            You are currently part of the group <a href='?view=<?= $user->getGroupId(); ?>'>"<?= $user->getGroupName(); ?>"</a><br />
            <a href='?leave'>Leave this Group</a><br />
<?
          }else{
?>
            You are currently balloting alone.<br />
<?
          }

          if($user->getRequestingGroupId()){ ?>
            <a href='?view=<?= $user->getRequestingGroupId(); ?>'>You are currently requesting access to this group</a><br />
<?
          }
?>
          <a href='?create'>Create a new Group</a>
        </p>
<?php
    }

    public static function maxGroupSize(){
      return 9;
    }

    public static function getGroupQuery($id){
      if(is_numeric($id)){
        return "SELECT `groupid`, `name` as 'groupname', `crsid` FROM `ballot_groups` JOIN `ballot_individuals` ON `groupid`=`ballot_groups`.`id` WHERE `ballot_groups`.`id`='$id'";
      }else{
        return "";
      }
    }

    public static function HTMLgroupView($user, $result){
      if($result->num_rows == 0){ ?>
        <h2>No group found</h2>       
<?
      }
      $first = true;
      while($row = $result->fetch_assoc()){
        if($first){ ?>
          <h2><?= htmlentities($row['groupname']); ?></h2> 
<?
          if($user->getGroupId() != intval($row['groupid'])){
?>
            <a href='/groups?join&id=<?= $row['groupid'] ?>'>Request to Join</a>
<?
          }
?>
          <h3>Members</h3>
          <table class="table table-condensed table-bordered table-hover">
            <thead>
              <tr>
                <td>CRSid</td>
              </tr>
            </thead>
            <tr>
              <td><?= $row['crsid']; ?></td>
            </tr>
<?
        }else{ ?>
          <tr>
            <td><?= $row['crsid']; ?></td>
          </tr>
<?
        }
      } ?>
      </table>
<?
    }
    public static function HTMLjoin(){ ?>

<?
    }

    public static function HTMLcreate(){ ?>
      <h2>Create a New Group</h2>
      <form action="" method="POST">
        <input name="groupname" type="text" placeholder="Group Name" /><br />
        <label for="public">Make this group visible publically?</label> <input name="public" type="checkbox" value="false" /><br />
        <input type="submit" value="Create Group" />
      </form>
<?
    }


    public static function HTMLgroupList($result){
        if($result->num_rows > 0){ ?>
          <h2>Public Groups</h2>
          <table class="table table-condensed table-bordered table-hover" >
            <thead>
              <tr>
                <td>Name</td>
                <td>Size</td>
                <td>Request</td>
              </tr>
            </thead>
<?
            while ($row = $result->fetch_assoc()) {
?>
              <tr>
                <td><a href='/groups?view=<?= $row['id'] ?>'><?= htmlentities($row['name']); ?></a></td>
                <td><?= $row['size']; ?></td>
                <td><a href='/groups?join&id=<?= $row['id']; ?>'>Request to Join</a></td>
              </tr>
<?
            }
?>
          </table>
<?
        }
    }

    public static function HTMLbottom() {
?>
      </div>
<?php
	}
}

?>
