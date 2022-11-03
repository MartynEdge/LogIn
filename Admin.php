<?php

require('mainLogIn.php');

# Initialise the Admin LogIn class with required settings.
    $test=new LogIn();
    $test->debug=TRUE;
    $test->settingDeleteUserShowButton=TRUE;
    $test->settingDeleteUserVerifyPassword=FALSE;
    $test->settingChangeStatusShowButton=TRUE;
    $test->settingChangeStatusVerifyPassword=FALSE;
    $test->settingChangePasswordShowButton=TRUE;
    $test->settingLoginVerifyPassword=FALSE;
    $test->var['valFilenameLogInPHP']="Admin.php";
    $test->var['valLogInTitle']='Admin log in';
    $test->var['valLogInSubtitle']='An example webpage for logging in.';
    $test->var['linkLoginUserSuccess']="Success.html";
    $test->run();
?>