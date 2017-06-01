#!/bin/bash
# script that will send an email to EMAIL when disk use in partition PART is bigger than %MAX


# Sends email to recipients. Recommend a warning level and then an error level.

##########
MAX=95
EMAIL=someone@where.com,some@where2.com
SERVER=some.server.com
##########

USE=$(df / | grep / | awk '{ print $5}' | sed 's/%//g')
if [ $USE -gt $MAX ]; then
  printf "ERROR: $SERVER - Disk percent used: $USE\r\n - stop working on the site!\n\n " | mail -s "Running out of disk space" $EMAIL
fi



##########
MAX=81
EMAIL=someone.maker@where.com
SERVER=some.server.com
##########

USE=$(df / | grep / | awk '{ print $5}' | sed 's/%//g')
if [ $USE -gt $MAX ]; then
  printf "WARNING: Server - $SERVER - Disk percent used: $USE\r\n - Need to clear some space for backups!\n\n " | mail -s "Running out of disk space" $EMAIL
fi
