import sys
import os
import subprocess
import traceback
import glob

import pexpect 
from pexpect.exceptions import EOF, TIMEOUT

from datetime import datetime

from config import config

sys.path.insert(0, "/opt/shared/python")

from smartserver.logfile import LogFile


class CmdExecuter: 
    START_TIME_STR_FORMAT = "%Y.%m.%d_%H.%M.%S"

    env_path = "/sbin:/usr/sbin:/usr/local/sbin:/usr/local/bin:/usr/bin:/bin"

    cmd_processlist = "/usr/bin/ps -alx"

    process_mapping = {
        "system_update_check": "system_update_check",
        "systemctl": "service_restart",
        "rpm": "system_update",
        "yum": "system_update",
        "apt": "system_update",
        "dnf": "system_update",
        "zypper": "system_update",
        "ansible-playbook": "deployment_update",
    }

    def __init__(self,logger,handler):
        self.logger = logger
        self.handler = handler
        
        self.last_jobs_modified = round(datetime.timestamp(datetime.now()),3)
        
        self.killed_job = False
        self.killed_logfile = None
        
        self.current_cmd_type = None
        self.current_started = None
        self.current_logfile = None
        self.current_child = None
        
    def getJobs(self):
        jobs = []
        
        files = glob.glob(u"{}*.log".format(config.job_log_folder))
        files.sort(key=os.path.getmtime, reverse=True)
        for name in files:
            filename = os.path.basename(name)
            data = filename.split("-")
            
            job = {}
            job["timestamp"] = datetime.timestamp(datetime.strptime(data[0],"%Y.%m.%d_%H.%M.%S"));
            job["start"] = data[0];
            job["duration"] = data[1];
            job["state"] = data[2];
            job["type"] = data[3];
            job["user"] = data[4].split(".")[0]
            
            jobs.append(job)

        return jobs
        
    def getLastJobsModifiedAsTimestamp(self):
        return self.last_jobs_modified
      
    def getCurrentJobStarted(self):
        return self.current_started
      
    def getCurrentJobLogfile(self):
        return self.current_logfile
      
    def getCurrentJobCmdType(self):
        return self.current_cmd_type
      
    def isRunning(self):
        return self.isDaemonJobRunning() or self.isExternalProcessRunning()
      
    def isDaemonJobRunning(self):
        return self.current_child != None
      
    def isExternalProcessRunning(self):
        return self.getActiveCmdType() != None

    def isKilledJob(self):
        return self.killed_job
      
    def getKilledLogfile(self):
        return self.killed_logfile
       
    def setKilledJobState(self):
        self.killed_job = True
        self.killed_logfile = self.current_logfile

    def resetKilledJobState(self):
        self.killed_job = False
        self.killed_logfile = None

    def restoreLock(self,cmd_type,start_time,file_name):
        self.current_cmd_type = cmd_type
        self.current_started = start_time
        self.current_logfile = file_name
        
    def lock(self, cmd_type):
        if self.current_child != None:
            return False
        else:
            self.current_cmd_type = cmd_type
            self.current_started = datetime.now()
            self.resetKilledJobState()
            return True

    def unlock(self, exit_code):
        self.last_jobs_modified = datetime.timestamp(datetime.now())

        self.current_cmd_type = None
        self.current_started = None
        self.current_logfile = None
        self.current_child = None

    def finishRun(self,job_log_file,exit_status,start_time,start_time_str,cmd_type,username):
        duration = datetime.now() - start_time
        status_msg = "success" if exit_status == 0 else "failed"
        finished_log_name = u"{}-{}-{}-{}-{}.log".format(start_time_str,round(duration.total_seconds(),1),status_msg, cmd_type,username)
        finished_log_file = u"{}{}".format(config.job_log_folder,finished_log_name)

        os.rename(job_log_file, finished_log_file)
        self.current_logfile = finished_log_name
        self.unlock(exit_status)
        
    def runFunction(self,cmd_type, _cmd, lf):
        name = _cmd["function"]
      
        msg = u"Start function '{}' - '{}'".format(cmd_type, name)
        self.logger.info(msg)
        lf.write(u"{}\n".format(msg))

        function = self.handler
        for part in name.split("."):
            function = getattr(function, part )
        function()
        
        return 0

    def runCmd(self,cmd_type, _cmd, lf):
        start_time = datetime.now()
        
        cmd = _cmd["cmd"]
        interaction = _cmd["interaction"]
        cwd = _cmd["cwd"]
        env = _cmd["env"]

        msg = u"Start cmd '{}' - '{}'".format(cmd_type, cmd)
        self.logger.info(msg)
        lf.write(u"{}\n".format(msg))
        
        if env is None:
            env = {}
        env["PATH"] = CmdExecuter.env_path

        self.current_child = pexpect.spawn(cmd, timeout=3600, cwd=cwd, env=env)
        self.current_child.logfile_read = lf
        
        if interaction is not None:
            patterns = list(interaction.keys())
            responses = list(interaction.values())
        else:
            patterns = None
            responses = None
            
        while self.current_child.isalive():
            try:
                index = self.current_child.expect(patterns)
                self.current_child.sendline(responses[index])
            except TIMEOUT:
                break
            except EOF:
                break

        self.current_child.close()
        exit_status = self.current_child.exitstatus

        duration = datetime.now() - start_time
        lf.getFile().write("\n")
        if exit_status == 0:
            lf.write("The command '{}' exited with 0 (successful) after {}.\n".format(cmd,duration.total_seconds()))
        else:
            lf.write("The command '{}' exited with {} (unsuccessful) after {}.\n".format(cmd,exit_status,duration.total_seconds()))
            
        return exit_status
      
    def processCmdBlock(self,cmd_block,lf):
        exit_status = 1
        
        cmd_type = cmd_block["cmd_type"]
        try:
            for _cmd in cmd_block["cmds"]:
                if "function" in _cmd:
                    step = _cmd["function"]
                    exit_status = self.runFunction(cmd_type,_cmd,lf)
                else:
                    step = _cmd["cmd"]
                    exit_status = self.runCmd(cmd_type,_cmd,lf)
                
                if exit_status != 0:
                    break

        except ValueError:
            #self.logger.warn(traceback.format_exc())
            pass
        except Exception as e:
            exit_status = 1
            self.logger.error(traceback.format_exc())
            ex_type, ex_value, ex_traceback = sys.exc_info()
            lf.write("The command '{}' - '{}' exited with '{}: {}'.\n".format(cmd_type,step,type(e).__name__,ex_value))
            
        return exit_status

  
    def runCmdBlock(self, cmd_block):
        username = cmd_block["username"]
        cmd_type = cmd_block["cmd_type"]
        
        start_time = datetime.now()
        start_time_str = start_time.strftime(CmdExecuter.START_TIME_STR_FORMAT)
        job_log_name = u"{}-{}-{}-{}-{}.log".format(start_time_str,0,"running", cmd_type,username)
        job_log_file = u"{}{}".format(config.job_log_folder,job_log_name)
        exit_status = 1
        with open(job_log_file, 'w') as f:
            self.current_logfile = job_log_name
            step = None

            lf = LogFile(f)
            
            exit_status = self.processCmdBlock(cmd_block,lf)
                     
        #if os.path.isfile(job_log_file):
        if cmd_type != "system_reboot":
            self.finishRun(job_log_file, exit_status,start_time,start_time_str,cmd_type,username)

        return exit_status
 
    def killProcess(self):
        child = self.current_child
        if child is not None:
            self.setKilledJobState()
            subprocess.call(['sudo', 'kill', str(child.pid)])
        return state()

    def getActiveCmdType(self):
        result = subprocess.run([ CmdExecuter.cmd_processlist ], shell=True, check=False, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, cwd=None )
        stdout = result.stdout.decode("utf-8")
        active_cmd_type = None
        for term in CmdExecuter.process_mapping:
            if "{} ".format(term) in stdout:
                active_cmd_type = CmdExecuter.process_mapping[term]
                break
        return active_cmd_type