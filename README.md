# Log In

This is a simple user log in website written in PHP.

![Screenshot](./screenshot.png)

![Admin screenshot](./screenshot_admin.png)

It has the following features...

1. Combines HTML, CSS and PHP.
2. Uses MySQL to store passwords.
3. Stores the password as a hash.
4. Sanitises user information using a regular expression.

## Quick start

Start your apache2 and MySQL services using the following commands...

* service apache2 start
* service mysql start

Create the MySQL database using these commands...

* GRANT ALL ON Projects_Login.* TO 'your_linux_username'@'localhost' IDENTIFIED BY 'my_new_secret_sql_password';
*   CREATE DATABASE Project_LogIn;
*   USE Project_LogIn;
*   CREATE TABLE users (username VARCHAR(128), password VARCHAR(128), status VARCHAR(128)) ENGINE InnoDB;

Edit the variables in mainLogIn.php to grant access to the new MySQL database ...

* $loginUsername='your_linux_username';
* $loginPassword='my_new_secret_sql_password'; 
        
To access the MySQL database yourself...
* mysql -u your_linux_username -p
