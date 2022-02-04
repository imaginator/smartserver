import pyinotify
import os
from datetime import datetime

class FileWatcher(pyinotify.ProcessEvent):
    def __init__(self, logger, callback = None):
        super().__init__(pyinotify.Stats())
        
        self.logger = logger
        
        self.callback = callback

        wm = pyinotify.WatchManager()
        notifier = pyinotify.ThreadedNotifier(wm, default_proc_fun=self)
        notifier.start()

        self.wm = wm
        
        self.modified_time = {}

        self.watched_files = {}
        self.watched_parents = {}
        
        self.watched_directories = {}

    def process_default(self, event):
        #print(event)
      
        if event.path in self.watched_parents:
            if event.mask & pyinotify.IN_DELETE:
                pass
            elif event.mask & ( pyinotify.IN_CREATE | pyinotify.IN_MOVED_TO ):
                if event.pathname in self.modified_time:
                    self.logger.info("New path '{}' watched".format(event.pathname))
                    self.addPath(event.pathname)
                    if not event.dir and self.callback:
                        #print("callback")
                        self.callback(event.pathname, pyinotify.IN_CLOSE_WRITE)

        elif event.path in self.watched_directories:
            if event.mask & ( pyinotify.IN_CREATE | pyinotify.IN_DELETE ):
                self.modified_time[event.path] = datetime.timestamp(datetime.now())
                if self.callback:
                    self.callback(event.path, event.mask)
      
        elif event.path in self.watched_files:
            if event.mask & pyinotify.IN_CLOSE_WRITE:
                self.modified_time[event.path] = datetime.timestamp(datetime.now())
                if self.callback:
                    self.callback(event.path, event.mask)
            else:
                pass
        
    def addWatcher(self,path):
        path = path.rstrip("/")

        parent = os.path.dirname(path)
        if parent not in self.watched_parents:
            self.watched_parents[parent] = True
            self.wm.add_watch(parent, pyinotify.IN_CREATE | pyinotify.IN_DELETE | pyinotify.IN_MOVED_TO, rec=False, auto_add=True)
            
        if os.path.exists(path):
            self.addPath(path)
        else:
            self.modified_time[path] = 0
            
    def addPath(self, path):
        #print("addPath")

        file_stat = os.stat(path)
        self.modified_time[path] = file_stat.st_mtime

        isfile = os.path.isfile(path)
        if isfile:
            self.watched_files[path] = True
            self.wm.add_watch(path, pyinotify.IN_DELETE_SELF | pyinotify.IN_CLOSE_WRITE, rec=False, auto_add=True)
        else:
            self.watched_directories[path] = True
            self.wm.add_watch(path, pyinotify.IN_CREATE | pyinotify.IN_DELETE, rec=True, auto_add=True)
            
    def getModifiedTime(self,path):
        return self.modified_time[path.rstrip("/")]
