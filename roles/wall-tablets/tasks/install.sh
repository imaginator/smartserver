#!/bin/bash

wallpanel_version="0.10.4 Build 0"


export room=$1

if [[ $(echo "$room" | grep -LE 'kitchen|lounge|study|hallwaylarge|bathroommain|bathroomguest|bedroom1|bedroom2|bedroom3') ]]; then 
  echo "Missing room parameter"
  exit 1
fi

# download the APK
if [[ ! -f /tmp/WallPanelApp-prod-universal-release.apk ]] ; then
  curl -L -o /tmp/WallPanelApp-prod-universal-release.apk https://github.com/TheTimeWalker/wallpanel-android/releases/download/v0.10.4/WallPanelApp-prod-universal-release.apk
fi

echo "Deploying to $room"
echo "running ADB non-root commands"
adb kill-server
adb connect tablet-$room.imagilan:5555 && sleep 2
wallpanel_installed_version=$(adb shell dumpsys package xyz.wallpanel.app | grep versionName | awk -F"=" '{print $2}')
echo "installed version: $wallpanel_installed_version"
echo "desired version: $wallpanel_version"

if [ "$wallpanel_version" == "$wallpanel_installed_version" ] ; then
  echo "correct version installed"
else
   echo "installing correct version"
   adb install -r /tmp/WallPanelApp-prod-universal-release.apk
fi

# if [[ ! -f /tmp/doorbird.apk ]] ; then
#   curl -L -o /tmp/doorbird.apk https://d.apkpure.com/b/APK/com.doorbird.doorbird?version=latest
# fi
# adb install -r                          /tmp/doorbird.apk

adb shell am force-stop                 xyz.wallpanel.app                                                #since disable doesn't kill
#adb uninstall                           xyz.wallpanel.app
adb shell dumpsys deviceidle whitelist +xyz.wallpanel.app

# for finding settings:
# adb shell settings list system
# adb shell settings list global
# adb shell settings list secure
# https://android.googlesource.com/platform/tools/tradefederation/+/54de8d285a4e253ea9f8b65d3e3070644f661aad/src/com/android/tradefed/targetprep/DeviceSetup.java

# adb shell settings put system screen_brightness 10            # do this manually
# adb shell settings put system user_rotation 1 # just leave it on auto rotate
adb shell appops set android TOAST_WINDOW deny                  # this would deny all toasts from Android System
adb shell setprop persist.adb.tcp.port 5555                     # keep adb via wifi enabled
adb shell setprop persist.sys.timezone Europe/Berlin
adb shell settings put global bluetooth_on 0
adb shell settings put global stay_on_while_plugged_in 7        # OR'd values together (USB charging is actually "AC charging") 
adb shell settings put global wifi_on 1
adb shell settings put secure double_tap_to_wake 1
adb shell settings put secure double_tap_to_wake_up 1
adb shell settings put secure screensaver_activate_on_dock 0
adb shell settings put secure screensaver_activate_on_sleep 0
adb shell settings put secure screensaver_enabled 0
adb shell settings put secure wake_gesture_enabled 1
adb shell settings put system accelerometer_rotation 1
adb shell settings put system screen_brightness_mode 1          # auto brightness mode (1=automatic)
adb shell settings put system screen_off_timeout 30000          # milliseconds
adb shell settings put system volume_system 0
adb shell svc power stayon true
adb shell svc power stayon usb
adb shell svc wifi enable
adb shell settings put global wifi_wakeup_available 1
adb shell settings put global wifi_wakeup_enabled 1

# needed to write to /data
echo "running ADB root commands"   
adb root &&  adb connect tablet-$room.imagilan:5555 && sleep 2

# add prefs
envsubst '$room' < ../templates/xyz.wallpanel.app_preferences.xml > /tmp/xyz.wallpanel.app_preferences.xml

adb shell mkdir -m 777 -p                                        /data/data/xyz.wallpanel.app/shared_prefs
adb push  /tmp/xyz.wallpanel.app_preferences.xml                 /data/data/xyz.wallpanel.app/shared_prefs/xyz.wallpanel.app_preferences.xml
adb shell chmod -R 777                                           /data/data/xyz.wallpanel.app/shared_prefs

# Disable lock screen
adb shell /system/bin/sqlite3 /data/system/locksettings.db \"UPDATE locksettings SET value = \'1\' WHERE name = \'lockscreen.disabled\'\"
adb shell /system/bin/sqlite3 /data/system/locksettings.db \"UPDATE locksettings SET value = \'0\' WHERE name = \'lockscreen.password_type\'\"
adb shell /system/bin/sqlite3 /data/system/locksettings.db \"UPDATE locksettings SET value = \'0\' WHERE name = \'lockscreen.password_type_alternate\'\"

# remove cruft 
adb shell pm disable com.android.calculator2
adb shell pm disable com.android.calendar
adb shell pm disable com.android.camera2
adb shell pm disable com.android.contacts
adb shell pm disable com.android.deskclock
adb shell pm disable com.android.documentsui # file manager
adb shell pm disable com.android.email
adb shell pm disable com.android.exchange
adb shell pm disable com.android.gallery3d
adb shell pm disable com.android.managedprovisioning
adb shell pm disable com.android.onetimeinitializer
adb shell pm disable com.android.providers.calendar
adb shell pm disable com.android.smspush
adb shell pm disable org.lineageos.audiofx
adb shell pm disable org.lineageos.eleven
adb shell pm disable org.lineageos.lockclock
adb shell pm disable org.lineageos.recorder
adb shell pm disable org.lineageos.setupwizard
adb shell pm disable org.lineageos.terminal
adb shell pm disable org.lineageos.trebuchet
adb shell pm disable com.cyanogenmod.trebuchet
adb shell pm disable org.lineageos.snap
adb shell pm uninstall org.openhab.habdroid.beta


# on 
adb unroot && sleep 5

# Dumps the information about every activity that is shown in the launcher (i.e., has the launcher intent).
# adb shell "cmd package query-activities -a android.intent.action.MAIN -c android.intent.category.LAUNCHER"

adb shell am start -n                   xyz.wallpanel.app/.ui.activities.BrowserActivityNative
adb shell cmd package set-home-activity xyz.wallpanel.app/.ui.activities.BrowserActivityNative

adb reboot
adb disconnect tablet-$room.imagilan
