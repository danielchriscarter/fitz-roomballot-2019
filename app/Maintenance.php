<?php

require_once "Environment.php";

class Maintenance {

  public static function maint() {
    if (Environment::maint_mode == true && empty(Environment::maint_message) == false) {
      ob_clean();
      http_response_code(503);
      Maintenance::layout("503 Service Unavailable", Environment::maint_message);
      die();
    }
  }

  private static function layout($title, $maintMessage) {
?>
<!doctype html>
<html lang="en-GB">
  <head>
    <meta charset="utf-8">
    <title>An error has occurred</title>
    <link href="include/img/icon.ico" rel="shortcut icon">
    <style type="text/css">
      @import url(https://fonts.googleapis.com/css?family=Droid+Sans);
      html{font-size:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;font-family:sans-serif;color:#222;}
      body{font-family:'Droid Sans', sans-serif;font-size:11pt;color:#555;line-height:25px;margin:0;}
      a{color:#00e;}
      a:visited{color:#551a8b;}
      a:hover{color:#72ADD4;}
      a:focus{outline:thin dotted;}
      a:hover,a:active{outline:0;}
      hr{display:block;height:1px;border:0;border-top:1px solid #ccc;margin:1em 0;padding:0;}
      .wrapper{padding: 3% 6%; margin:0 auto 5em;}
      @media (min-width: 768px) {
        html{font-size:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;font-family:sans-serif;color:#222;}
        body{font-size:10pt;}
        a{color:#00e;}
        .wrapper{max-width: 768px}
        a,a:visited{color:#2972A3;}
        .row-fluid [class*="span"]{float:left;width:100%;margin-left:2.0744680851064%;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;}
        .row-fluid [class*="span"]:first-child{margin-left:0;}
        .row-fluid .span3{width:99.946808510638%;}
        .row-fluid .span2{width:57.393617021277%;}
        .row-fluid .span1{width:40.372340425532%;}
        .pull-right{float:right;}
        .ellipsis{overflow: hidden;white-space: nowrap;text-overflow: ellipsis;-o-text-overflow: ellipsis;}
      }
    </style>
  </head>
  <body>
  <div class="wrapper">
    <div role="main" class="main">
      <div class="row-fluid">
        <div class="span3">
          <div class="span1 ellipsis">
            <h1><?php echo $title; ?></h1>
          </div>
        </div>
        <h2>&nbsp;</h2>
        <hr>

        <h3>What does this mean?</h3>

        <p>
          The webmaster or another administrator has put the site into maintenance mode. This means that public access to the web front-end has been disabled.
        </p>

        <h3>What's going on?</h3>

        <p>
          It is likely that the database is being updated to reflect the latest available information, or that the underlying codebase is being upgraded to the newest version.
        </p>

        <h3>What are the technical details?</h3>

        <p>
          <?php echo $maintMessage; ?>
        </p>
      </div>
    </div>
  </div>
  </body>
</html>
<?php
  }
}

Maintenance::maint();

?>
