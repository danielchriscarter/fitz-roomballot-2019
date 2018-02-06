<?php
class Groups {

    public static function HTMLtop($user) { ?>
      <div class="container">
        <h2>Welcome, <?= $user->getName(); ?></h2>
        <p>
<?        if($user != null && !$user->isIndividual()){
            $owner = $user->ownsGroup(); ?>
            You are currently <?= $owner ? "owner" : "part" ?> of the group "<?= $user->getGroup()->getHTMLLink(); ?>"<br />
            To leave this group, you need to assign ownership to someone else.

<?          if($user->canLeave()){ ?>
              <a href='?leave'>Leave this Group</a><br />
<?          }
          }else{ ?>
            You are currently balloting alone.<br />
<?
          }

          if($user->getRequestingGroup()){ ?>
            You are currently requesting access to the group "<?= $user->getRequestingGroup()->getHTMLLink(); ?>"<br />
<?        } 
          if(!isset($_GET['create']) && $user->canLeave()){ ?>
            <a href='?create'>Create a new Group</a>
<?        } ?>
        </p>
<?  }

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
        if($user->getGroup() != $group && $user->getRequestingGroup() != $group && $user->canJoin($group)){ ?>
          <a href='/groups?join&id=<?= $group->getID(); ?>'>Request to Join</a>
<?      }else if($owner){ 
          $public = $group->isPublic(); ?>
          You are owner of this group.<br />
          <a href='?change=<?= $group->getID(); ?>&public=<?= $public ? "0":"1" ?>'>Make group <?= $public ? "private" : "public" ?>.</a>
<?      }
        
        if($owner){
          $link = "https://roomballot.fitzjcr.com/groups?join&id=".$group->getID(); ?>
          <h3>Share this link with others so they can join your group!</h3> 
          <textarea autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" class="sharelink" id="sharelink" onkeydown="return false;"><?= $link; ?></textarea>
          <button id="copybutton">Copy Link To Clipboard</button>
          <script>
            var link = document.getElementById("sharelink");
            var button = document.getElementById("copybutton");

            button.onclick = function(e){
              link.select();
              if(document.execCommand('copy')){
                this.innerHTML = "Link copied!";
                window.getSelection().removeAllRanges();
              }
            }

            button.style.height = link.style.height = (link.scrollHeight-10)+'px'; 
          </script>
<?      } ?>
        <h3>Members</h3>
        <table class="table table-condensed table-bordered table-hover">
          <thead>
            <tr>
              <td>CRSid</td>
              <td>Name</td>
<?            if($owner){ ?>
                <td>Assign Ownership</td>
<?            } ?>
            </tr>
          </thead>
<?        foreach($members as $member){ ?>
            <tr>
              <td><?= $member['crsid']; ?></td>
              <td><?= $member['name']; ?></td>
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
                  <td>Name</td>
                  <td>Accept Request</td>
                </tr>
              </thead>
<?          foreach($requesting as $member){ ?>
              <tr>
                <td><?= $member['crsid']; ?></td>
                <td><?= $member['name']; ?></td>
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
        <input name="groupname" maxlength="100" type="text" placeholder="Group Name" style="width: 100%; margin-bottom: 5px;" /><br />
        <input name="public" type="checkbox" value="false" style="vertical-align: top;"/> <label for="public" style="vertical-align: top;" >Make this group visible publicly?</label>  (This will allow anyone to request access)<br />

        <input type="submit" value="Create Group" />
        <br /><br />
        Note: rude or inappropriate group names may be subject to disciplinary action.
      </form>
<?
    }

    public static function HTMLgroupList($result, $user = null){
        if($result->num_rows > 0 || ($user != null && (!$user->isIndividual() || $user->getRequestingGroup() != null))){ ?>
          <h2>Groups Visible to You</h2>
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
                <tr class='bg-success'>
                  <td><?= $user->getGroup()->getHTMLLink(); ?></td>
                  <td><?= $user->getGroup()->getSize(); ?></td>
                  <td>
<?                  if($user->canLeave()){ ?>
                      <a href='?leave'>Leave this group</a>
<?                  } ?>
                  </td>
                </tr>
<?            }

              if($user->getRequestingGroup() != null){ ?>
                <tr class='bg-warning'>
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
