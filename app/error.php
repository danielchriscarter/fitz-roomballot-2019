<?php

error_reporting(-1);
ini_set("display_errors", 1);
set_error_handler("Error::trigger");

class Error {

    public static function trigger($errorNumber, $errorMsg) {
?>
<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <title>Error <?php echo $errorNumber; ?></title>
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
                    <h1>Error <?php echo $errorNumber; ?></h1>
                </div>
                <div class="span2 ellipsis">
                    <h2 class="pull-right">Houston, We Have A Problem.</h2>
                </div>
            </div>
            <h2><?php echo $errorMsg; ?></h2>
            <hr>

            <h3>What does this mean?</h3>

            <p>
                Something went wrong on our servers while we were processing your request.
                The requested resource could not be found but may be available again in the future.
                This occurrence has been logged, and a highly trained team of monkeys has been
                dispatched to deal with your problem. We're really sorry about this, and will
                work hard to get this resolved as soon as possible.
            </p>
        </div>
    </div>
</div>
</body>
</html>
<?php
        die();
    }

?>