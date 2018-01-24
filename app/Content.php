<?php

require_once "Database.php";
require_once "lib/Michelf/MarkdownInterface.php";
require_once "lib/Michelf/Markdown.php";
require_once "lib/Michelf/SmartyPants.php";
require_once "News.php";
require_once "Timetable.php";
require_once "Groups.php";

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
        Groups::HTMLtop();

        if(isset($_GET['join'])){
          if(isset($_GET['id'])){
            //Do join operation
          }else{
            Groups::HTMLjoin();
          }
        }else if(isset($_GET['create'])){
          if(isset($_GET['name'])){
            //Do create operation
          }else{
            Groups::HTMLcreate();
          }
        }else if(isset($_GET['view'])){ //Show group information
          $queryString = Groups::getGroupQuery($_GET['view']);
          $result = Database::getInstance()->query($queryString);
          Groups::HTMLgroupView($result);
        }else{ //Show public groups
          $queryString = "SELECT * FROM `ballot_groups` WHERE `public`=TRUE AND `size`< ".Groups::maxGroupSize();
          $result = Database::getInstance()->query($queryString);
          Groups::HTMLgroupList($result);
        }

        Groups::HTMLbottom();
    }
}

?>
