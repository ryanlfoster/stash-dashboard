stash-dashboard
===============

A web dashboard using Atlassian Stash REST API to visualize collaboration patterns

installation
=============
1. Run composer install
2. rename config.php.dist to config.php and enter Stash URL and credentials
3. Make sure that the data directory is writable for the webserver
4. For best results the first time you run the dashboard, run activity.php from commandline. Before the cache 
gets filled, the script may take a while to complete.