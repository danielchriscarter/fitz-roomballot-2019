<?php

require_once "app/ErrorHandler.php";
require_once "app/Maintenance.php";
require_once "app/Database.php";
require_once "app/Layout.php";
require_once "app/Content.php";

// buffer the output
ob_start();

// if no page is requested then serve up the home page
if (isset($_GET["q"])) {
    $url = $_GET["q"];
} else {
    $url = "home";
}

$queryString = "SELECT *  FROM `pages` WHERE `url` LIKE '" . $url . "'";
$result = Database::getInstance()->query($queryString);
$row = $result->fetch_assoc();

Layout::HTMLheader("Fitz JCR Housing Ballot System");
Layout::HTMLnavbar();

// check if the page requested actually exists or not
if (isset($row)) {
    Layout::HTMLcontent($row["title"], $row["content"]);

    // paint any other page content that is more than just text
    Content::makeContent($url);
} else {
    http_response_code(404);
    Layout::HTMLcontent("Fitz JCR Housing Ballot System", "The page requested does not exist.");
}

Layout::HTMLfooter();

// return the buffered content all at once
ob_flush();

?>
