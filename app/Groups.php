<?php
class Groups {

    public static function HTMLtop($user) { ?>
      <div class="container">
        <h2>Welcome, <?= $user->getCRSID(); ?></h2>
        <p>
<?        if(!$user->isIndividual()){
            $owner = $user->ownsGroup($user->getGroup()); ?>
            You are currently <?= $owner ? "owner" : "part" ?> of the group "<?= $user->getGroup()->getHTMLLink(); ?>"<br />

<?          if(!$owner || $user->getGroup()->getSize() == 1){ ?>
              <a href='?leave'>Leave this Group</a><br />
<?          }
          }else{ ?>
            You are currently balloting alone.<br />
<?
          }

          if($user->getRequestingGroup()){ ?>
            You are currently requesting access to the group "<?= $user->getRequestingGroup()->getHTMLLink(); ?>"<br />
<?        } 
          if(!isset($_GET['create'])){ ?>
            <a href='?create'>Create a new Group</a>
<?        } ?>
        </p>
<?  }

    public static function maxGroupSize(){
      return 9;
    }

    public static function getGroupQuery($id){
      if(is_numeric($id)){
        return "SELECT `ballot_individuals`.`id` as `id`, `groupid`, `name` as 'groupname', `crsid` FROM `ballot_groups` JOIN `ballot_individuals` ON `groupid`=`ballot_groups`.`id` WHERE `ballot_groups`.`id`='$id'";
      }else{
        return "";
      }
    }

    public static function HTMLgroupView($user, $group){
      $members = $group->getMemberList();
      if(count($members) == 0){ ?>
        <h2>No group found</h2>       
<?
      }else{ 
        $owner = $user->ownsGroup($group); ?>

        <h2><?= $group->getName(); ?></h2> 

<?      //Only show request link if not currently in the group, or requesting access
        if($user->getGroup() != $group && $user->getRequestingGroup() != $group){ ?>
          <a href='/groups?join&id=<?= $group->getID(); ?>'>Request to Join</a>
<?      }else if($owner){ 
          $public = $group->isPublic(); ?>
          You are owner of this group.<br />
          <b><a href='?join&id=<?= $group->getID(); ?>'>Share this link with others so they can join your group!</a></b><br />
          <a href='?change=<?= $group->getID(); ?>&public=<?= $public ? "0":"1" ?>'>Make group <?= $public ? "private" : "public" ?>.</a>
<?      } ?>

        <h3>Members</h3>
        <table class="table table-condensed table-bordered table-hover">
          <thead>
            <tr>
              <td>CRSid</td>
<?            if($owner){ ?>
                <td>Assign Ownership</td>
<?            } ?>
            </tr>
          </thead>
<?        foreach($members as $member){ ?>
            <tr>
              <td><?= $member['crsid']; ?></td>
<?              if($owner){
                  if($user->getCRSID() == $member['crsid']) {?>
                    <td></td>
<?                }else{ ?>
                    <td><a href='?assign=<?= $member['id']; ?>&group=<?= $group->getID(); ?>'>Assign Ownership</a></td>
<?                }
              }?>
            </tr>
<?        } ?>
        </table>

<?      if($owner){
          $requesting = $group->getRequestingList();
          if(count($requesting) != 0){ ?>
            <h3>Pending Join Requests</h3>
            <table class="table table-condensed table-bordered table-hover">
              <thead>
                <tr>
                  <td>CRSid</td>
                  <td>Accept Request</td>
                </tr>
              </thead>
<?          foreach($requesting as $member){ ?>
              <tr>
                <td><?= $member['crsid']; ?></td>
                <td><a href='?accept=<?= $member['id']; ?>&group=<?= $group->getID(); ?>'>Accept Request</a></td>
              </tr>
<?          }
          }
        }
      }
    }
    public static function HTMLjoin(){ ?>

<?
    }

    public static function HTMLcreate(){ ?>
      <h2>Create a New Group</h2>
      <form action="" method="POST">
        <input name="groupname" maxlength="100" type="text" placeholder="Group Name" /><br />
        <label for="public">Make this group visible publicly?</label> <input name="public" type="checkbox" value="false" /><br />
        <input type="submit" value="Create Group" />
      </form>
<?
    }

    public static function HTMLgroupList($result, $user = null){
        if($result->num_rows > 0 || ($user != null && ($user->isIndividual() || $user->getRequestingGroup() != null))){ ?>
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
            if($user != null){
              if(!$user->isIndividual()){ ?>
                <tr class='current-group'>
                  <td><?= $user->getGroup()->getHTMLLink(); ?></td>
                  <td><?= $user->getGroup()->getSize(); ?></td>
                  <td>
<?                  if(!$user->ownsGroup($user->getGroup())){ ?>
                      <a href='?leave'>Leave this group</a>
<?                  } ?>
                  </td>
                </tr>
<?            }

              if($user->getRequestingGroup() != null){ ?>
                <tr class='current-request'>
                  <td><?= $user->getRequestingGroup()->getHTMLLink(); ?></td>
                  <td><?= $user->getRequestingGroup()->getSize(); ?></td>
                  <td><a href='?cancel'>Cancel join request</a></td>
                </tr>
<?
              }
            }
            while ($row = $result->fetch_assoc()) { ?>
              <tr>
                <td><a href='/groups?view=<?= $row['id'] ?>'><?= htmlentities($row['name']); ?></a></td>
                <td><?= $row['size']; ?></td>
                <td><a href='/groups?join&id=<?= $row['id']; ?>'>Request to Join</a></td>
              </tr>
<?          } ?>
          </table>
<?      }
    }

    public static function HTMLbottom() {
?>
      </div>
<?
	}
}

?>
