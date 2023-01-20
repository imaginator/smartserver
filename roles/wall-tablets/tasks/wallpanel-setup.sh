#!/bin/bash
source wallpanel-setup-functions.sh
wallpanel_version="0.10.4 Build 0"
export room=$1 # envsubst needs global variables

get_a_room $room
adb kill-server

# run as non-root
adb connect tablet-$room.imagilan:5555 && sleep 2
adb unroot
install_wallpanel_app
set_system_settings
set_wallpanel_as_launcher

# run as root 
adb root && sleep 5
set_wallpanel_settings $1
disable_lock_screen
# remove_unneeded_apps
adb reboot &
sleep 2
adb kill-server
