<?php
$OPEN = False;

require_once "../app/Database.php";

function HTMLerror(string $string){ ?>
  <div class="container">
    <div class="alert alert-danger">
    <?= $string; ?>
    </div>
  </div>
<? }

function HTMLsuccess(string $string){ ?>
  <div class="container">
    <div class="alert alert-success">
    <?= $string; ?>
    </div>
  </div>
<? }
?>

<!DOCTYPE html>
<head>
  <title>Fitzwilliam JCR Room Ballot Registration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css"></link>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
</head>
<body>


  <div class="container">
    <h1>Fitzwilliam JCR Room Ballot Registration</h1>

<? if($OPEN) { ?>
    <p>Please enter your details below</p>
    <form method="POST">
      Full Name: <input type="text" name="name" /> <br />
      Current year: <select name='year'> <br />
	<option value="">Please select</option>
	<option value="FIRSTYEAR">First year</option>
	<option value="SECONDYEAR">Second year</option>
	<option value="THIRDYEAR">Third year</option>
	<option value="THIRDYEARABROAD">Third year (currently living abroad)</option>
      </select> <br />
      <br />
     
      <p>Please give the name of your proxy below (in case you are not able to attend the ballot)</p>
      <input type="text" name="proxy" /><br />
      <br />
      
      <input type="checkbox" name="consent" />
      <label for="consent">I consent to my data being used for the Fitzwilliam JCR Room Ballot</label><br />

      <input type="checkbox" name="scholar" />
      <label for="consent">Please tick this box if you are an academic or organ scholar</label><br />

      <input type="submit" name="submit" value="Submit" />

<? }
   else {
       HTMLerror("Ballot registration is now closed");
   }
?>
  </div>
<?

$SCHOLAR = array(
    "FIRSTYEAR" => "FIRSTYEAR",
    "SECONDYEAR" => "SCHOLARSECOND",
    "THIRDYEAR" => "SCHOLARTHIRD",
    "THIRDYEARABROAD" => "SCHOLARTHIRDABROAD"
);
   
if(isset($_POST["submit"])) {
    if(isset($_POST["consent"])) {
        $crsid = $_SERVER['REMOTE_USER'];
        if(isset($crsid) && $crsid != "") {
            if(isset($_POST["name"]) && $_POST["name"] != "") {
                if(isset($_POST["year"]) && $_POST["year"] != "") {
                    if(isset($_POST["proxy"]) && $_POST["proxy"] != "") {
                        $query = "SELECT * FROM `ballot_individuals` WHERE crsid ='".$crsid."';";
                        $result = Database::getInstance()->query($query);
                        if($result->num_rows == 0) {
                            $year = $_POST["year"];
                            if(isset($_POST["scholar"])) {
                                $year = $SCHOLAR[$year];
                            }
                            $query = "INSERT INTO ballot_individuals VALUES(".mt_rand().", '".$_POST["name"]."', '".$crsid."', NULL, 0, NULL, '".$year."', '".$_POST["proxy"]."');";
                            $res = Database::getInstance()->query($query);
                            if($res){
                                HTMLsuccess("You have now registered for the ballot");
                            }
                            else {
                                HTMLerror("An error has occurred. Please contact <a href='mailto:jcr.website@fitz.cam.ac.uk'>jcr.website@fitz.cam.ac.uk</a> to report this error.");
                            }
                        }
                        else {
                            HTMLerror("You have already registered for the room ballot");
                        }
                    }
                    else {
                        HTMLerror("You must nominate a proxy");
                    }
                }
                else {
                    HTMLerror("You must select your current year");
                }
            }
            else {
                HTMLerror("You must enter your name");
            }
        }
        else {
            throw new Exception("Non-authenticated user");
        }
    }
    else {
        HTMLerror("You must agree to the terms of the ballot before you can use this system");
    }

}
    

?>
</body>
</html>
