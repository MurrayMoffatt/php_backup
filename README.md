# php_backup
Backup MySQL databases on a web server

## Problem
I had a need to backup MySQL databases that were stored on a web server. I wanted the backups to be run at 10pm every night and to be emailed to me. The hosting platform had its own limited management console (eg it didn't run something standard like cPanel) and didn't offer me a way to schedule the running of scripts.

## Solution
I decided on a solution that was controlled via my Linux PC at home.

Using the *Wake System from S5* settings in my Intel NUC's BIOS, I set the machine to automatically wake up at 10pm every night.

I then set up a cron job to run at 10:03pm every night which ran a bash shell script (backup.sh).

The backup.sh shell script performed a number of actions:
* Run a PHP script on the web server called *backup.php* that looped through a list of databases creating an .sql file that contains CREATE TABLE and INSERT statements for each table and then zips the .sql files.
* Download the .zip files and emailed them to me.
* Run a PHP script on the web server called *delete-backups.php* that deleted the .zip files from the server.
* Finally do some tidying up of old log files that were used to allow me to monitor everything to ensure it was working properly.

## Requirements
The PHP scripts were designed to run on PHP 7 or later, the *Zip* extension is required.
The bash script requires *wget* to run the PHP scripts on the web server and download the .zip files, a working *mail* command in order to email the .zip files to me (personally I like to use [mSMTP}(https://marlam.de/msmtp/) and [mailutils](https://mailutils.org)).

## Installation
### Web Server Setup
Edit the backup.php script to insert the details of your databases that you want to backup. This information is kept in an array called $cfg and requires the following information for each database:

```php
$i++;
$cfg[$i]['db_name']  = 'databasename1';
$cfg[$i]['host']     = 'host1';
$cfg[$i]['user']     = 'user1';
$cfg[$i]['password'] = 'password1';
```

Repeat the above block for each successive database you want backed up.

Upload the two .php scripts to your web server.

**Note:** The .php scripts require write access in to the directory they're placed in in order to create and then delete the .zip files. How you set this up will be dependant on your hosting platform.

### Local Machine Setup
Create a directory to store all the local files in, I used ```/home/murray/cron/clientdbs```, and put the backup.sh and databaselist.txt files here (this directory is also used to store the log files and working .zip files).

Edit the backup.sh file and change the three lines near the top of the script to match your system setup and requirements:
```sh
# Change these variables to fit in with your setup
outputPath="/home/murray/cron/clientdbs"
website="http://example.com"
email="email@example.com"
```

Edit the databaselist.txt file so that it includes a list of all of the database names (eg the same names that you used in the backup.php script), one per line:
```
databasename1
databasename2
databasename3
```

Make the backup.sh file executable:
```sh
chmod +x backup.sh
```

**Note:** At the end of the backup.sh script I have a ```/sbin/shutdown -h --no-wall +3``` command that will turn off my PC. If you would prefer your PC to keep running then remove this command.

You should be able to manually run the backup.sh shell script in order to test it (remove the shutdown command at the end of the backup.sh shell script so your PC doesn't shutdown while you're testing!)

Once you're happy everything is working then you can create a cron job to run the backup.sh script and set it to run at whatever time you desire. My crontab entry looks like this (obviously change the email address and path to match your setup):

```
MAILTO=myemail@example.com
3 22 * * * /home/murray/cron/clientdbs/backup
```

Finally you can set the BIOS of your PC to automatically turn on at the desired time (make sure that the wakeup time is a few minutes earlier than when the cron job runs, eg PC wakeup at 10:00pm and cron job runs at 10:03pm).

## Log Files
Each time the backups are performed the output of the wget commands that run the PHP scripts are appended to a file called log.txt.

Also a file with the filename of the current date will be created (eg 20221225.txt) which shows the output of the PHP scripts (any errors from the PHP scripts should appear here).

The backup.sh shell script will automatically delete the dated log files that are older than 30 days.
