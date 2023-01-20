#!/bin/bash

function get_a_room() {
  room=$1
  if [[ $(echo "$room" | grep -LE 'kitchen|lounge|study|hallwaylarge|bathroommain|bathroomguest|bedroom1|bedroom2|bedroom3') ]]; then
    echo "Missing room parameter"
    exit 1
  fi
  echo "Deploying to $room"
}

function install_wallpanel_app {
  echo "checking wallpanel versions"
  wallpanel_installed_version=$(adb shell dumpsys package xyz.wallpanel.app | grep versionName | awk -F"=" '{print $2}')
  echo "installed version: $wallpanel_installed_version"
  echo "desired version: $wallpanel_version"

  if [ "$wallpanel_version" == "$wallpanel_installed_version" ]; then
    echo "correct version installed"
  else
    echo "installing correct version"
    if [[ ! -f /tmp/WallPanelApp-prod-universal-release.apk ]]; then
      echo "downloading correct version"
      curl -L -o /tmp/WallPanelApp-prod-universal-release.apk https://github.com/TheTimeWalker/wallpanel-android/releases/download/v0.10.5/WallPanelApp-prod-universal-release.apk
    fi
    adb install -r /tmp/WallPanelApp-prod-universal-release.apk
  fi
}

function set_system_settings {
  echo "installing system settings"
  # for finding settings:
  # adb shell settings list system
  # adb shell settings list global
  # adb shell settings list secure
  # https://android.googlesource.com/platform/tools/tradefederation/+/54de8d285a4e253ea9f8b65d3e3070644f661aad/src/com/android/tradefed/targetprep/DeviceSetup.java

  adb shell appops set android TOAST_WINDOW deny # this would deny all toasts from Android System
  adb shell setprop persist.adb.tcp.port 5555    # keep adb via wifi enabled
  adb shell setprop persist.sys.timezone Europe/Berlin

  # global prefs
  adb shell settings put global bluetooth_on 0
  adb shell settings put global network_avoid_bad_wifi 0   # stay connected even if network issues
  adb shell settings put global stay_on_while_plugged_in 1 # OR'd values together (USB charging is actually "AC charging")
  adb shell settings put global wifi_on 1
  adb shell settings put global wifi_scan_always_enabled 1
  adb shell settings put global wifi_wakeup_available 1
  adb shell settings put global wifi_wakeup_enabled 1

  # secure prefs
  adb shell settings put secure double_tap_to_wake 1
  adb shell settings put secure double_tap_to_wake_up 1
  adb shell settings put secure screensaver_activate_on_dock 0
  adb shell settings put secure screensaver_activate_on_sleep 0
  adb shell settings put secure screensaver_enabled 0
  adb shell settings put secure wake_gesture_enabled 1

  # system prefs
  adb shell settings put system accelerometer_rotation 1
  adb shell settings put system screen_brightness_mode 1   # auto brightness mode (1=automatic)
  adb shell settings put system screen_off_timeout 30000   # in milliseconds
  adb shell settings put system volume_system 0

  # https://android.googlesource.com/platform/frameworks/base/+/master/cmds/svc/src/com/android/commands/svc/PowerCommand.java#46
  adb shell svc power stayon ac # from adb shell dumpsys battery
  adb shell svc wifi enable
}

function set_wallpanel_settings() {
  echo "installing wallpanel settings"
  # need root to write to /data
  room=$1
  echo $room
  echo "adding wallpanel prefs"
  adb shell am force-stop xyz.wallpanel.app # before updating prefs / since disable doesn't kill
  envsubst '$room' <../templates/xyz.wallpanel.app_preferences.xml >/tmp/xyz.wallpanel.app_preferences.xml
  adb shell mkdir -m 777 -p /data/data/xyz.wallpanel.app/shared_prefs
  adb push /tmp/xyz.wallpanel.app_preferences.xml /data/data/xyz.wallpanel.app/shared_prefs/xyz.wallpanel.app_preferences.xml
  adb shell chmod -R 777 /data/data/xyz.wallpanel.app/shared_prefs
  adb shell dumpsys deviceidle whitelist +xyz.wallpanel.app # magical wakelock powers
}

function disable_lock_screen {
  echo "disabling lock screen"
  # Disable lock screen
  adb shell /system/bin/sqlite3 /data/system/locksettings.db \"UPDATE locksettings SET value = \'1\' WHERE name = \'lockscreen.disabled\'\"
  adb shell /system/bin/sqlite3 /data/system/locksettings.db \"UPDATE locksettings SET value = \'0\' WHERE name = \'lockscreen.password_type\'\"
  adb shell /system/bin/sqlite3 /data/system/locksettings.db \"UPDATE locksettings SET value = \'0\' WHERE name = \'lockscreen.password_type_alternate\'\"
}

function remove_unneeded_apps {
  echo "removing cruf"
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
  adb shell pm disable com.cyanogenmod.trebuchet
  adb shell pm disable org.lineageos.audiofx
  adb shell pm disable org.lineageos.eleven
  adb shell pm disable org.lineageos.jelly
  adb shell pm disable org.lineageos.lockclock
  adb shell pm disable org.lineageos.recorder
  adb shell pm disable org.lineageos.setupwizard
  adb shell pm disable org.lineageos.snap
  adb shell pm disable org.lineageos.terminal
  adb shell pm disable org.lineageos.trebuchet
  #adb shell pm uninstall org.openhab.habdroid.beta
}

function set_wallpanel_as_launcher {
  echo "setting wallpanel as launcher"
  # Dumps the information about every activity that is shown in the launcher (i.e., has the launcher intent).
  # adb shell "cmd package query-activities -a android.intent.action.MAIN -c android.intent.category.LAUNCHER"
  adb shell am start -n xyz.wallpanel.app/.ui.activities.BrowserActivityNative
  adb shell cmd package set-home-activity xyz.wallpanel.app/.ui.activities.BrowserActivityNative
}
