<?php

    session_start();

    ini_set("display_errors",0); # 0 for no debug info. 1 for debug info.
    ini_set("display_startup_errors",0); # 0 for no debug info. 1 for debug info.
    error_reporting(E_ALL);

    class LogIn{

        public $var=array();
        public $debug=FALSE;
        public $loginHostname='localhost';
        public $loginDatabase='Project_LogIn';
        public $loginUsername='your_linux_username'; # YOU MUST STORE YOUR OWN VALUE HERE
        public $loginPassword='my_new_secret_sql_password'; # YOU MUST STORE YOUR OWN VALUE HERE
        public $settingDeleteUserShowButton=FALSE;
        public $settingDeleteUserVerifyPassword=TRUE;
        public $settingChangeStatusVerifyPassword=TRUE;
        public $settingChangeStatusShowButton=FALSE;
        public $settingChangePasswordShowButton=FALSE;
        public $settingChangePasswordVerifyPassword=TRUE;
        public $settingLoginVerifyPassword=TRUE;

        private $debugText='';
        private $connection=NULL;
        private $loginInfo=array();
        private $valid=array(); # TRUE when appropriate functions (keys) have executed successfully.
        
        function __construct(){
            $this->var['valFilenameLogInHTML']='template_LogIn.html';
            $this->var['valFilenameRegisterHTML']='template_Register.html';
            $this->var['valFilenameDeleteUserHTML']='template_DeleteUser.html';
            $this->var['valFilenameErrorHTML']='template_Error.html';
            $this->var['valFilenameChangeStatusHTML']='template_ChangeStatus.html';
            $this->var['valFilenameChangePasswordHTML']='template_ChangePassword.html';
            $this->var['valFilenameLogInPHP']='LogIn.php';
            
            $this->var['valLogInHeading1']='Log In';
            $this->var['valLogInChangeStatus']='';
            $this->var['valLogInChangePassword']='';
            $this->var['valLogInDeleteUser']='';
            $this->var['valLogInPasswordPlaceholder']='Password';

            $this->var['valRegisterHeading1']='Registration';
            
            $this->var['valDeleteUserHeading1']='Delete User';
            $this->var['valDeleteUserPassword']="";

            $this->var['valChangeStatusHeading1']='Change Status';
            $this->var['valChangeStatusPassword']='';
            $this->var['valChangeStatusResponse']='';

            $this->var['valChangePasswordHeading1']='Change Password';
            $this->var['valChangePasswordResponse']='';

            $this->var['valDebugText']='';
            
            $this->var['valRegisterError']='';

            $this->var['htmlLogInDeleteUser']="<button type='submit' name='Go' value='DeleteUserPage'>Delete user</button>";
            $this->var['htmlDeleteUserPassword']="<input type='text' name='pass1' class='mainInput' placeholder='Password'><br>";
            $this->var['htmlLogInChangeStatus']="<button type='submit' name='Go' value='ChangeStatusPage'>Change status</button>";
            $this->var['htmlChangeStatusPassword']="<input type='text' name='pass1' class='mainInput' placeholder='Password'><br>";
            $this->var['htmlLogInChangePassword']="<button type='submit' name='Go' value='ChangePasswordPage'>Change password</button>";
            $this->var['htmlChangeStatusResponse']="<p class='success'>Status changed successfully.</p>";
            $this->var['htmlChangePasswordResponse']="<p class='success'>Password changed successfully.</p>";
            
        }

        function HTML($filenameHTML) {
            $loginHTML=file_get_contents($filenameHTML);
            
            # Add debug info.
            if ($this->debug==TRUE){
                $this->debugText.=print_r($_POST,TRUE)."<br>";
                $this->debugText=str_replace("\n","<br>",$this->debugText);
            }
            $this->var['valDebugText']=$this->debugText;
            
            # Add settings
            # - Delete user - Show button
            if ($this->settingDeleteUserShowButton){
                $this->var['valLogInDeleteUser']=$this->var['htmlLogInDeleteUser'];
            }
            # - Delete user - Verify password
            if ($this->settingDeleteUserVerifyPassword){
                $this->var['valDeleteUserPassword']=$this->var['htmlDeleteUserPassword'];
            }
            # - Change status - Show button
            if ($this->settingChangeStatusShowButton){
                $this->var['valLogInChangeStatus']=$this->var['htmlLogInChangeStatus'];
            }
            # - Change status - Verify password
            if ($this->settingChangeStatusVerifyPassword){
                $this->var['valChangeStatusPassword']=$this->var['htmlChangeStatusPassword'];
            }
            # - Change password - Show button
            if ($this->settingChangePasswordShowButton){
                $this->var['valLogInChangePassword']=$this->var['htmlLogInChangePassword'];
            }
            # - Login - Verify password
            if (!$this->settingLoginVerifyPassword){
                $this->var['valLogInPasswordPlaceholder']="ADMIN - NO PASSWORD REQUIRED";
            }
            

            # Replace variables in LogIn webpage
            foreach ($this->var as $varKey=>$varValue){
                $search="*".$varKey."*";
                $loginHTML=str_replace($search,$varValue,$loginHTML);
            }

            return $loginHTML;
        }

        function _POSTED($var){
            # Checks for the existence of a POST variable.
            # If exists, it is returned.
            # If not, returns NULL.
            if (!isset($_POST[$var])){
                return NULL;
            }
            
            return $_POST[$var];
        }

        function run(){
            $user=$this->_sanitise($this->_POSTED('user')); 
            $pass1=$this->_sanitise($this->_POSTED('pass1')); 
            $pass2=$this->_sanitise($this->_POSTED('pass2')); 
            $pass3=$this->_sanitise($this->_POSTED('pass3')); 
            $status=$this->_sanitise($this->_POSTED('status')); 
            $Go=$this->_sanitise($this->_POSTED('Go'));
            

            if ($Go){
                if ($Go=='NewUser') echo $this->HTML($this->var['valFilenameRegisterHTML']);
                if ($Go=='LogInUser') $this->_login_user($user,$pass1,$this->settingLoginVerifyPassword);
                if ($Go=='LogInWebPage') echo $this->HTML($this->var['valFilenameLogInHTML']);
                if ($Go=='Register') $this->_register_user($user,$pass1,$pass2);
                if ($Go=='DeleteUserPage') echo $this->HTML($this->var['valFilenameDeleteUserHTML']);
                if ($Go=='DeleteUser') $this->_delete_user($user,$pass1,$this->settingDeleteUserVerifyPassword);
                if ($Go=='ChangeStatusPage') echo $this->HTML($this->var['valFilenameChangeStatusHTML']);
                if ($Go=='ChangeStatus') $this->_change_status($user,$pass1,$status,$this->settingChangeStatusVerifyPassword);
                if ($Go=='ChangePasswordPage') echo $this->HTML($this->var['valFilenameChangePasswordHTML']);
                if ($Go=='ChangePassword') $this->_change_password($user,$pass1,$pass2,$pass3,$this->settingChangePasswordVerifyPassword);
            }else{
                echo $this->HTML($this->var['valFilenameLogInHTML']);
            }
        }

        function _login_user($username,$pass1,$verify_password=TRUE){
            

                # Verify the username exists.
                if ($verify_password){
                    $result=$this->_verify_username_and_password($username,$pass1);
                    if ($this->valid['verify_username_and_password']==FALSE){
                        # Couldn't verify username - function did not execute correctly,
                        # or password is incorrect.
                        $this->_show_simple_error_message("usernamePasswordError");
                        return;
                    }
                }else{
                    $result=$this->_verify_username($username);
                    if ($this->valid['verify_username']==FALSE){
                        # Couldn't verify username - function did not execute correctly.
                        $this->_show_simple_error_message("usernameLoginError","lu1");
                        return;
                    }
                }

                                

                if (!$result and $verify_password){
                    # Username does not exist. NOT ADMIN.
                    $this->_show_simple_error_message("usernamePasswordError");
                    return;
                }elseif (!$result and !$verify_password) {
                    # Username does not exist. IS ADMIN.
                    $this->_show_simple_error_message("usernameNotFoundError");
                    return;
                }


                # LOG IN SUCCESSFUL
                session_start();
                $_SESSION["loggedin"] = true;
                $_SESSION["username"] = $username; 
                header("Location: ".$this->var['linkLoginUserSuccess']);
        }
        function _register_user($user,$pass1,$pass2){
            # THIS USES MYSQL TO REGISTER AND VERIFY USERS.
            # THE MYSQL DATABASES MUST BE SET UP FIRST, WITH PRIVILEDGES
            # GRANTED SO THAT THIS PROGRAM CAN ACCESS THE DATABASE. TYPE INTO MYSQL...
            #   GRANT ALL ON Projects_Login.* TO 'your_linux_username'@'localhost' IDENTIFIED BY 'my_new_secret_sql_password';
            #   CREATE DATABASE Project_LogIn;
            #   USE Project_LogIn;
            #   CREATE TABLE users (username VARCHAR(128), password VARCHAR(128), status VARCHAR(128)) ENGINE InnoDB;
            # TO LATER ACCESS MYSQL...
            #   mysql -u your_linux_username -p
            # YOU WILL THEN BE PROMPTED FOR NEW MYSQL DATABASE PASSWORD. TYPE YOUR PASSWORD, E.G.: my_new_secret_sql_password

            if (!$user || !$pass1 || !$pass2){
                $this->_show_simple_error_message("usernamePasswordError");
                return;
            }
            if ($pass1!=$pass2){
                # Passwords do not match. Error and exit.
                $this->_show_simple_error_message("passwordsNoMatch");
                return;
            }

            $result=$this->_verify_username($user);
            if (!$this->valid['verify_username']){
                # The function to verify the username failed. Report an error.
                $this->_show_simple_error_message("serverConnectionError",'3');
                return;
            }
            if ($result){
                # The username exists. Report message. Exit.
                $this->_show_simple_error_message("usernameAlreadyExists");
                return;
            }
            $info=array("username"=>$user,"password"=>$pass1);
            $this->_add_user($info);

        }

        function _connect_to_sql_database(){

            $this->connection=new mysqli($this->loginHostname,$this->loginUsername,$this->loginPassword,$this->loginDatabase);
            if ($this->connection->connect_error){
                $this->_show_simple_error_message("serverConnectionError",'1');
                $this->connection=NULL;
                $this->valid['sql_connection']=FALSE;
                return FALSE;
            }
            $this->valid['sql_connection']=TRUE;
            return TRUE;
        }

        function _show_simple_error_message($errorCode,$uniqueCode=''){
            $errorMessage='';
            if ($uniqueCode!=''){
                $uniqueCode=" [".$uniqueCode."]";
            }
            switch ($errorCode){
                case "serverConnectionError":
                    $errorMessage='<p class="error" >Server connection error'.$uniqueCode.'. Please try again later.</p>';
                    break;
                case "usernameAlreadyExists":
                    $errorMessage='<p class="error" >Username already exists'.$uniqueCode.'. Please try a different username.</p>';
                    break;
                case "usernameRegisterError":
                    $errorMessage='<p class="error" >Unable to register user'.$uniqueCode.'. Please try again later.</p>';
                    break;
                case "usernameDeleteError":
                    $errorMessage='<p class="error" >Unable to delete user'.$uniqueCode.'. Please try again later.</p>';
                    break;
                case "usernameStatusError":
                    $errorMessage='<p class="error" >Unable to change status'.$uniqueCode.'.</p>';
                    break;
                case "usernameNotFoundError":
                    $errorMessage='<p class="error" >Username not found'.$uniqueCode.'.</p>';
                    break;
                case "usernamePasswordError":
                    $errorMessage='<p class="error" >Username or password is incorrect'.$uniqueCode.'.</p>';
                    break;
                case "usernameLoginError":
                    $errorMessage='<p class="error" >Unable to log in'.$uniqueCode.'.</p>';
                    break;
    
                case "passwordsNoMatch":
                    $errorMessage='<p class="error" >Passwords do not match'.$uniqueCode.'. Please try again.</p>';
                    break;
    
                default:
                    break;
            }
            $this->var['valErrorMessage']=$errorMessage;
            echo $this->HTML($this->var['valFilenameErrorHTML']);
            die();
        }

        function _retrieve_all_users(){
            if ($this->connection==NULL) return;
            

            # Get column info
            $querySelectAll="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='Project_LogIn' AND TABLE_NAME='users';";
            $resultSelectAll=$this->connection->query($querySelectAll);
            $columnsCount=$resultSelectAll->num_rows;
            $columns=array();
            for ($i=0; $i<$columnsCount; ++$i){
                $dictInfo=$resultSelectAll->fetch_array(MYSQLI_ASSOC);
                $columns[]=$dictInfo['COLUMN_NAME'];
            }

            $querySelectAll="SELECT * FROM users;";
            $resultSelectAll=$this->connection->query($querySelectAll);
            
            if (!$resultSelectAll){
                $this->_show_simple_error_message("serverConnectionError",'2');
            }
            $rowsCount=$resultSelectAll->num_rows;
            
            # Retrieve all rows from MySQL.
            for ($i=0; $i<$rowsCount; ++$i){
                $row=$resultSelectAll->fetch_array(MYSQLI_ASSOC);
                foreach ($columns as $column){
                    $this->loginInfo[$column][]=$row[$column];
                }
            }

            $resultSelectAll->close();
            $this->valid['loginInfo']=TRUE; # TRUE indicates 'loginInfo' has been correctly populated.
            return $rowsCount;
            
        }

        function _delete_user($username,$pass1,$verify_password=TRUE){
            # Verify the username exists.
            if ($verify_password){
                $result=$this->_verify_username_and_password($username,$pass1);
                if ($this->valid['verify_username_and_password']==FALSE){
                    # Couldn't verify username - function did not execute correctly,
                    # or password is incorrect.
                    $this->_show_simple_error_message("usernameDeleteError","8");
                    return;
                }
            }else{
                $result=$this->_verify_username($username);
                if ($this->valid['verify_username']==FALSE){
                    # Couldn't verify username - function did not execute correctly.
                    $this->_show_simple_error_message("usernameDeleteError","6");
                    return;
                }
            }
            if ($result==FALSE){
                # Username verification function did execute correctly,
                # but username does not exist.
                $this->_show_simple_error_message("usernameDeleteError","7");
                return;
            }

            $this->_connect_to_sql_database();
            if (!$this->valid['sql_connection']){
                # Failed to connect to MySQL. Exit.
                $this->valid['delete_user']=FALSE;
                return;
            }
            
            # Set SQL query
            $query="DELETE FROM users WHERE username = '".$username."';";
            $result=$this->connection->query($query);
            
            if (!$result){
                $this->_show_simple_error_message("usernameDeleteError");
                return;
            }
                        
            $this->valid['delete_user']=TRUE;
            $this->_close_sql_connection();
            return;
            
        }

        function _change_status($username,$pass1,$new_status,$verify_password=TRUE){
            # Verify the username exists.
            if ($verify_password){
                $result=$this->_verify_username_and_password($username,$pass1);
                if ($this->valid['verify_username_and_password']==FALSE){
                    # Couldn't verify username - function did not execute correctly,
                    # or password is incorrect.
                    $this->_show_simple_error_message("usernameStatusError","9");
                    return;
                }
            }else{
                $result=$this->_verify_username($username);
                if ($this->valid['verify_username']==FALSE){
                    # Couldn't verify username - function did not execute correctly.
                    $this->_show_simple_error_message("usernameDeleteError","10");
                    return;
                }
            }

            if ($result==FALSE){
                # Username verification function did execute correctly,
                # but username does not exist.
                $this->_show_simple_error_message("usernameStatusError","11");
                return;
            }

            $this->_connect_to_sql_database();
            if (!$this->valid['sql_connection']){
                # Failed to connect to MySQL. Exit.
                $this->valid['status_change']=FALSE;
                return;
            }
            
            # Set SQL query
            $query="UPDATE users SET status = '".$new_status."' WHERE username = '".$username."';";
            $result=$this->connection->query($query);
            
            if (!$result){
                $this->_show_simple_error_message("usernameStatusError");
                return;
            }
                        
            $this->valid['change_status']=TRUE;
            $this->_close_sql_connection();

            # Send a confirmation page
            $this->var['valChangeStatusResponse']=$this->var['htmlChangeStatusResponse'];
            echo $this->HTML($this->var['valFilenameChangeStatusHTML']);

            return;
            
        }

        function _change_password($username,$pass1,$pass2,$pass3,$verify_password=TRUE){
            # Verify the username exists.
            if ($verify_password){
                $result=$this->_verify_username_and_password($username,$pass1);
                if ($this->valid['verify_username_and_password']==FALSE){
                    # Couldn't verify username - function did not execute correctly,
                    # or password is incorrect.
                    $this->_show_simple_error_message("usernamePasswordError");
                    return;
                }
            }else{
                $result=$this->_verify_username($username);
                if ($this->valid['verify_username']==FALSE){
                    # Couldn't verify username - function did not execute correctly.
                    $this->_show_simple_error_message("usernamePasswordError","cp2");
                    return;
                }
            }
            if ($result==FALSE){
                # Username verification function did execute correctly,
                # but username does not exist.
                $this->_show_simple_error_message("usernameNotFoundError","cp3");
                return;
            }
            if ($pass2!=$pass3){
                # Password mismatch
                $this->_show_simple_error_message("passwordsNoMatch");
                return;
            }

            $this->_connect_to_sql_database();
            if (!$this->valid['sql_connection']){
                # Failed to connect to MySQL. Exit.
                $this->valid['change_password']=FALSE;
                return;
            }
            
            # Set SQL query
            $password_hash=password_hash($pass2,PASSWORD_DEFAULT);
            $query="UPDATE users SET password = '".$password_hash."' WHERE username = '".$username."';";
            $result=$this->connection->query($query);
            
            if (!$result){
                $this->_show_simple_error_message("usernameChangePasswordError","cp4");
                return;
            }
                        
            $this->valid['change_password']=TRUE;
            $this->_close_sql_connection();

            $this->var['valChangePasswordResponse']=$this->var['htmlChangePasswordResponse'];
            echo $this->HTML($this->var['valFilenameChangePasswordHTML']);

            return;
            
        }

 
        function _add_user($dictInfo){
            $this->_connect_to_sql_database();
            if (!$this->valid['sql_connection']){
                # Failed to connect to MySQL. Exit.
                $this->valid['add_user']=FALSE;
                return;
            }
            
            $keys=array();
            $values=array();
            
            foreach ($dictInfo as $key=>$value){
                $keys[]=$key;
                # SUBSTITUTE THE PASSWORD FIELD WITH A HASHED PASSWORD.
                if ($key=='password')
                {
                    $value_pwd=$value;
                    $value_hash=password_hash($value,PASSWORD_DEFAULT);
                    $value=$value_hash;
                }
                $values[]="'".$value."'";
            }
            $sqlColumns=join(',',$keys);
            $sqlValues=join(',',$values);

            # Set user info
            $query="INSERT INTO users (".$sqlColumns.") VALUES (".$sqlValues.");";
            $result=$this->connection->query($query);
            
            if (!$result){
                $this->_show_simple_error_message("usernameRegisterError",'4');
            }
                        
            $this->valid['add_user']=TRUE; # TRUE indicates 'loginInfo' has been correctly populated.
            $this->_close_sql_connection();
            return;
            
        }

        function _close_sql_connection(){
            $this->connection->close();
            $this->valid['sql_connection']=FALSE;
        }

        function _verify_username($username){
            # This will connect to the MySQL database, retrieve all usernames,
            # then search for the given username.
            
            # CONNECT TO SQL
            $this->_connect_to_sql_database();
            if (!$this->valid['sql_connection']){
                # Failed to connect to MySQL. Exit.
                $this->valid['verify_username']=FALSE;
                return;
            }
        
            # CHECK FOR USERNAME MATCH
            $rowsCount=$this->_retrieve_all_users();
            if (!$this->valid['loginInfo']){
                # Failed to retrieve users info. Exit.
                $this->valid['verify_username']=FALSE;
                return;
            }
            $usernameFound=FALSE;
            
            for ($index=0;$index<$rowsCount;$index++){
                $sqlUsername=$this->loginInfo['username'][$index];
                $sqlPassword=$this->loginInfo['password'][$index];
                if ($sqlUsername==$username){
                    $usernameFound=TRUE;
                    break;
                }
            }
            
            $this->_close_sql_connection();
            $this->valid['verify_username']=TRUE;
            return $usernameFound;
        }

        function _verify_username_and_password($username,$password){
            # This will connect to the MySQL database, retrieve all usernames,
            # then search for the given username.
            
            # CONNECT TO SQL
            $this->_connect_to_sql_database();
            if (!$this->valid['sql_connection']){
                # Failed to connect to MySQL. Exit.
                $this->valid['verify_username_and_password']=FALSE;
                return;
            }
        
            # CHECK FOR USERNAME MATCH
            $rowsCount=$this->_retrieve_all_users();
            if (!$this->valid['loginInfo']){
                # Failed to retrieve users info. Exit.
                $this->valid['verify_username_and_password']=FALSE;
                return;
            }
            $usernameFound=FALSE;
            
            for ($index=0;$index<$rowsCount;$index++){
                $sqlUsername=$this->loginInfo['username'][$index];
                $sqlPasswordHash=$this->loginInfo['password'][$index];
                if ($sqlUsername==$username){
                    $usernameFound=TRUE;
                    break;
                }
            }
            
            $this->_close_sql_connection();
            if (!$usernameFound){
                # Username not found. Exit.
                $this->valid['verify_username_and_password']=FALSE;                
                return $usernameFound;
            }
            
            # Compare hashed-inputted password and hashed-sql password.
            if (!password_verify($password, $sqlPasswordHash)){
                # Passwords do not match. Exit.
                $this->valid['verify_username_and_password']=FALSE;                
                return $usernameFound;
            }
            
            $this->valid['verify_username_and_password']=TRUE;
            return $usernameFound;
        }

        function _sanitise($text){
            # This will return only letters or numbers.
            # Maximum length is 20 characters.
            $output=preg_replace( '/[^0-9a-zA-Z]/', '', $text );
            return substr($output,0,20);
        }

    }

?>