### Problem:

A system that work as the following:
Every 30 minutes it runs and calls github API to get the latest 5 updated repos and show this feed in Slack channel via Slack incoming webhook. 

Few things to consider:
 - Duplications are not allowed, can't feed the same repo that you sent to webhook previously. 
 - Customize the displayed name and the icon of the webhook to be showing full name and any picture.
