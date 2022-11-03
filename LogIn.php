<?php

require('mainLogIn.php');

# CODE TO BE PUT INTO WEBPAGE
    $test=new LogIn();
    $test->debug=FALSE;
    $test->settingDeleteUserShowButton=FALSE;
    $test->settingDeleteUserVerifyPassword=TRUE;
    $test->settingChangeStatusShowButton=FALSE;
    $test->settingChangeStatusVerifyPassword=TRUE;
    $test->var['valLogInTitle']='Log in';
    $test->var['valLogInSubtitle']='An example webpage for logging in.';
    $test->var['valFilenameLogInPHP']="LogIn.php";
    $test->var['linkLoginUserSuccess']="Success.html";
    $test->run();
?>