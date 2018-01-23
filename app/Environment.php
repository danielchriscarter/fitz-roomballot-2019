<?php

class Environment {

    // app enviromental variables
    const app_hostname = "roomballot.fitzjcr.com";

    // set the following to true and add a message to disable the site to visitors
    const maint_mode = false;
    const maint_message = "Site down for build mode. The system is under active development by the JCR Website Officer, Charlie Jonas, and has not yet reached the testing phase. Details about how to register for beta testing will be circulated in due course. You can help: https://github.com/CHTJonas/roomballot";

    // database enviromental variables
    const db_host = "localhost";     // database host machine
    const db_user = "fitzjcr";              // database username
    const db_pass = "ProFs2WkdrpdDgqtQLzt435tgkZZgGjjVmvm6pXDVxfM7h5phjksprzRwswcy2dH";              // database password
    const db_name = "fitzjcr/roomballot";              // database name
    const db_prefix = "";            // table prefix

    // Raven authentication module enviromental variables
    const raven_cookieKey = "";       // this needs to be 32+ random characters

}

?>
