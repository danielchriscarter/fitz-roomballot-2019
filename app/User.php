<?php
/* This class makes Raven auth easier, provided
   Raven authentication is required in .htaccess
 */

class User {
  protected $crsid = NULL;

  public function __construct(){
    //https://wiki.cam.ac.uk/raven/Accessing_authentication_information
    $this->crsid = $_SERVER['REMOTE_USER'];    
  }

  public function getCRSID(){
    if($this->crsid !== NULL){
      return $this->crsid;
    }

    return NULL;
  }
}
