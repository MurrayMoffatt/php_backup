#!/bin/bash
# Run the backup script to backup all MySQL databases on the hosting service, download the .zip files and email them to me,
# delete the .zip files and tidy up.

# Change these variables to fit in with your setup
outputPath="/home/murray/cron/clientdbs"
website="http://example.com"
email="email@example.com"

cd "$(dirname "$0")" || exit;

echo "Starting to create database backup files"
outputFile="${outputPath}/$(date +%Y%m%d).txt"
logFile="${outputPath}/log.txt"
wget --append-output="$logFile" --output-document="$outputFile" "${website}/backup.php?verbose=1"
exitCode=$?
if [ $exitCode -ne 0 ]; then
  echo "wget returned error code $exitCode"
else
  echo "Finished creating database backup files"
fi
echo

echo "Starting to download backups"
printf -v today '%(%Y-%m-%d)T' -1
databaseList=$(cat "databaselist.txt")
for db in $databaseList
do
  dbFilename="${db}_${today}.zip"
  wget --wait=10 --random-wait --timestamping "${website}/${dbFilename}"
  exitCode=$?
  if [ $exitCode -ne 0 ]; then
    echo "wget returned error code $exitCode"
  else
    echo "Emailing ${dbFilename}"
    mail --subject="MySQL Backup - ${today} - ${db}" --attach="$dbFilename" "$email" <<< "Backup of MySQL dabase $db made on $(date '+%-d %B %Y')"
  fi
  echo
done
echo "Finished downloading backups"
echo

echo "Starting to delete backups from server"
wget --append-output="$logFile" --output-document=->>"$outputFile" "${website}/delete-backups.php"
exitCode=$?
if [ $exitCode -ne 0 ]; then
  echo "wget returned error code $exitCode"
else
  echo "Finished deleting backups from server"
fi
echo

echo "Starting to delete .zip files"
rm -v "${outputPath}"/*.zip
echo "Finished deleting .zip files"
echo

echo "Deleting log files > 30 days"
find "${outputPath}/" -type f -name "20*.txt" -mtime +30 -exec rm -v {} \;
echo "Deletion finished"
echo

/sbin/shutdown -h --no-wall +3
