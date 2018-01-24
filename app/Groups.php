<?php

class Groups {

    public static function HTMLtop() {
?>
      <div class="container">
        <form action="" method="GET">
          <input type="submit" name="join" value="Join a Group" /> 
          <input type="submit" name="create" value="Create a Group" /> 
        </form>
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

    public static function HTMLgroupView($result){
      $first = true;
      while($row = $result->fetch_assoc()){
        if($first){ ?>
          <h2><?= $row['groupname']; ?></h2> 
          <a href='/groups?join&id=<?= $row['groupid'] ?>'>Request to Join</a>
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
    public static function HTMLjoin(){
      echo "Test";
    }

    public static function HTMLcreate(){
      echo "TestyTest";
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
                <td><a href='/groups?view=<?= $row['id'] ?>'><?= $row['name']; ?></a></td>
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
