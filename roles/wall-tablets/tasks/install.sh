#!/bin/bash
set -x

export room=$1

if [[ $(echo "$room" | grep -LE 'kitchen|lounge|study|hallwaylarge|bathroommain|bathroomguest|bedroom1|bedroom2|bedroom3') ]]; then 
  echo "Missing room parameter"
  exit 1
fi

echo "Deploying to $room"

echo "running ADB non-root commands"
adb kill-server
adb connect tablet-$room.imagilan:5555 && sleep 2

if [[ ! -f /tmp/WallPanelApp-prod-universal-release.apk ]] ; then
  curl -L -o /tmp/WallPanelApp-prod-universal-release.apk https://github.com/TheTimeWalker/wallpanel-android/releases/download/v0.10.4/WallPanelApp-prod-universal-release.apk
fi

adb shell am force-stop                 xyz.wallpanel.app                                                #since disable doesn't kill
#adb uninstall                           xyz.wallpanel.app
#adb install -r                          /tmp/WallPanelApp-prod-universal-release.apk
adb shell dumpsys deviceidle whitelist +xyz.wallpanel.app   

adb shell appops set android TOAST_WINDOW deny                                              # this would deny all toasts from Android System
adb shell setprop persist.adb.tcp.port 5555                                                 # keep adb via wifi enabled
adb shell setprop persist.sys.timezone Europe/Berlin
adb shell settings put global stay_on_while_plugged_in 3
adb shell settings put global bluetooth_on 0
adb shell settings put global wifi_on 1
adb shell settings put global wifi_sleep_policy 2
adb shell settings put system accelerometer_rotation 1
adb shell settings put system screen_brightness_mode 0                                      # auto brightness mode
adb shell settings put system screen_brightness_mode_automatic 1                            # auto brightness mode
adb shell settings put system screen_brightness 255
adb shell settings put system screen_off_timeout 70000                                      # milliseconds (now shut off here rather than rule)
adb shell settings put system user_rotation 1
adb shell settings put system volume_system 0
adb shell svc power stayon true
adb shell svc wifi enable

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
adb shell pm disable com.android.contacts
adb shell pm disable com.android.deskclock
adb shell pm disable com.android.email
adb shell pm disable com.android.documentsui # file manager
adb shell pm disable com.android.exchange
adb shell pm disable com.android.gallery3d
adb shell pm disable com.android.managedprovisioning
adb shell pm disable com.android.onetimeinitializer
adb shell pm disable com.android.providers.calendar
adb shell pm disable com.android.smspush
adb shell pm disable org.lineageos.eleven
adb shell pm disable org.lineageos.lockclock
adb shell pm disable org.lineageos.setupwizard
adb shell pm disable org.lineageos.audiofx
adb shell pm disable org.lineageos.recorder

# on 
adb unroot && sleep 2

# Dumps the information about every activity that is shown in the launcher (i.e., has the launcher intent).
# adb shell "cmd package query-activities -a android.intent.action.MAIN -c android.intent.category.LAUNCHER"

adb shell am start -n                   xyz.wallpanel.app/.ui.activities.BrowserActivityNative
adb shell cmd package set-home-activity xyz.wallpanel.app/.ui.activities.BrowserActivityNative

#adb reboot
adb disconnect tablet-$room.imagilan
