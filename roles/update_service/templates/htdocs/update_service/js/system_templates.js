mx.UpdateServiceTemplates = (function( ret ) {
    let outdatedProcessData = null;
    let outdatedRoleData = null;
  
    let startserverChangeInfoCodes = {
        "failed": ["red","Git pull skipped because CI tests failed"],
        "pending": ["yellow","Git pull skipped because CI tests are currently running"],
        "pulled_tested": ["green", "Git pulled and all remote ci succeeded"],
        "pulled": ["green", "Git pulled"],
        "uncommitted": ["red","Git pull skipped because there are uncommitted changes"],
        "missing": ["red","Git pull skipped because deployment status is unknown"],
    };
    
    let active_local_cmd_type_map = {
        "daemon_restart": "A local daemon restart is running",
        "software_check": "A local software check is running",
        "update_check": "A local update check is running",
        "service_restart": "A local service restart is running",
        "system_update": "A local system update is running",
        "deployment_update": "A local smartserver update is running"
    }
    
    let active_service_cmd_type_map = {
        "daemon_restart": "A {1}daemon restart{2} is running since {3} {4}",
        "software_check": "A {1}software check{2} is running since {3} {4}",
        "update_check": "A {1}update check{2} is running since {3} {4}",
        "service_restart": "A {1}service restart{2} is running since {3} {4}",
        "system_update": "A {1}system update{2} is running since {3} {4}",
        "deployment_update": "A {1}smartserver update{2} is running since {3} {4}"
    }
    
    let last_cmd_type_map = {
        "daemon_restart": "Last {1}daemon restart{2} {3} after {4} {5}",
        "software_check": "Last {1}software check{2} {3} after {4} {5}",
        "update_check": "Last {1}update check{2} {3} after {4} {5}",
        "service_restart": "Last {1}service restart{2} {3} after {4} {5}",
        "system_update": "Last {1}system update{2} {3} after {4} {5}",
        "deployment_update": "Last {1}smartserver update{2} {3} after {4} {5}"
    };

    let cmd_type_map = {
        "daemon_restart": "daemon restart",
        "software_check": "software check",
        "update_check": "update check",
        "service_restart": "service restart",
        "system_update": "system update",
        "deployment_update": "smartserver update"
    };

    ret.getLastFullRefresh = function(last_data_modified)
    {
        let last_update = 0;
        if( last_update == 0 || last_data_modified["system_reboot"] < last_update ) last_update = last_data_modified["system_reboot"];
        if( last_update == 0 || last_data_modified["system_updates"] < last_update ) last_update = last_data_modified["system_updates"];
        if( last_update == 0 || last_data_modified["smartserver_changes"] < last_update ) last_update = last_data_modified["smartserver_changes"];
      
        let date = null;
        let dateFormatted = null;
        let dateType = null;
        let msg = "";
        
        if( last_update > 0 )
        {
            date = new Date(last_update * 1000);
            [ dateFormatted, dateType ] = mx.UpdateServiceHelper.formatDate(date);
            if( dateType == "other" )
            {
                msg = mx.I18N.get("Last update search on {}").fill( dateFormatted );
            }
            else
            {
                msg = mx.I18N.get("Last update search was {}").fill( dateFormatted );
            }
        }
        else
        { 
            msg = mx.I18N.get("Please press 'Refresh' to check for updates for the first time");         
        }
        
        return [ date, msg ];
    }
    
    ret.getSystemOutdatedDetails = function(last_data_modified, changed_data)
    {
        let processHeaderMsg = "";
        let processDetailsMsg = "";

        outdatedProcessData = changed_data.hasOwnProperty("outdated_processes") ? changed_data["outdated_processes"] : outdatedProcessData;
        
        let processCount = outdatedProcessData.length;

        if( processCount > 0 )
        {
            let services = [];
            for( process in outdatedProcessData )
            {
                if( process["service"] ) services.append(process["service"]);
            }
            
            let plural = processCount > 1;
          
            let i18n_main_msg = plural ? "{} outdated processes" : "{} outdated process";
            let i18n_info_msg = plural ? "through a system update which affects these processes" : "through a system update which affects these process";
            
            processHeaderMsg = "<div class=\"info\">" + mx.I18N.get(i18n_main_msg).fill(processCount) + "<div class=\"sub\">" + mx.I18N.get(i18n_info_msg) + "</div></div><div class=\"buttons\">";
            if( services.length > 0) processHeaderMsg += "<div class=\"form button exclusive yellow\" onclick=\"mx.UNCore.actionRestartServices(this)\" data-service=\"" + services.join(",") + "\">" + mx.I18N.get("Restart all") + "</div>";
            processHeaderMsg += "<div class=\"form button toggle\" id=\"systemProcesses\" onclick=\"mx.UNCore.toggle(this,'systemStateDetails')\"></div></div>";

            processDetailsMsg = "<div class=\"row\">";
            processDetailsMsg += "<div>" + mx.I18N.get("PID") + "</div>";
            processDetailsMsg += "<div>" + mx.I18N.get("PPID") + "</div>";
            processDetailsMsg += "<div>" + mx.I18N.get("UID") + "</div>";
            processDetailsMsg += "<div>" + mx.I18N.get("User") + "</div>";
            processDetailsMsg += "<div class=\"grow\">" + mx.I18N.get("Command") + "</div>";
            processDetailsMsg += "<div class=\"grow\">" + mx.I18N.get("Service") + "</div>";
            processDetailsMsg += "<div></div>";
            processDetailsMsg += "</div>";
            for( index in outdatedProcessData )
            {
                let process = outdatedProcessData[index];

                processDetailsMsg += "<div class=\"row\">";
                processDetailsMsg += "<div>" + process["pid"] + "</div>";
                processDetailsMsg += "<div>" + process["ppid"] + "</div>";
                processDetailsMsg += "<div>" + process["uid"] + "</div>";
                processDetailsMsg += "<div>" + process["user"] + "</div>";
                processDetailsMsg += "<div>" + process["command"] + "</div>";
                processDetailsMsg += "<div>" + process["service"] + "</div>";
                processDetailsMsg += "<div>" + ( process["service"] ? "<div class=\"form button exclusive yellow\" onclick=\"mx.UNCore.actionRestartServices(this)\" data-service=\"" + process["service"] + "\">" + mx.I18N.get("Restart") + "</div>" : "" ) + "</div>";
                processDetailsMsg += "</div>";
            }
          
        }

        let roleDetailsMsg = "";
        let roleHeaderMsg = "";
      
        outdatedRoleData = changed_data.hasOwnProperty("outdated_roles") ? changed_data["outdated_roles"] : outdatedRoleData;
        
        let roleCount = outdatedRoleData.length;

        if( roleCount > 0 )
        {
            let plural = roleCount > 1;
          
            let i18n_main_msg = plural ? "{} overwritten smartserver roles" : "{} overwritten smartserver role";
            let i18n_info_msg = plural ? "through a system update which affects these roles" : "through a system update which affects these role";
            
            roleHeaderMsg = "<div class=\"info\">" + mx.I18N.get(i18n_main_msg).fill(roleCount) + "<div class=\"sub\">" + mx.I18N.get(i18n_info_msg) + "</div></div><div class=\"buttons\">";
            roleHeaderMsg += "<div class=\"form button exclusive yellow\" onclick=\"mx.UNCore.actionDeployUpdates(this)\" data-tag=\"" + outdatedRoleData.join(",") + "\">" + mx.I18N.get("Install all") + "</div>";
            roleHeaderMsg += "<div class=\"form button toggle\" id=\"smartserverRoles\" onclick=\"mx.UNCore.toggle(this,'roleStateDetails')\"></div></div>";

            roleDetailsMsg = "<div class=\"row\">";
                roleDetailsMsg += "<div class=\"grow\">" + mx.I18N.get("Role") + "</div>";
                roleDetailsMsg += "<div></div>";
                roleDetailsMsg += "</div>";
            for( index in outdatedRoleData )
            {
                let role = outdatedRoleData[index];
                
                roleDetailsMsg += "<div class=\"row\">";
                roleDetailsMsg += "<div>" + role + "</div>";
                roleDetailsMsg += "<div>" + ( role ? "<div class=\"form button exclusive yellow\" onclick=\"mx.UNCore.actionDeployUpdates(this)\" data-tag=\"" + role + "\">" + mx.I18N.get("Install") + "</div>" : "" ) + "</div>";
                roleDetailsMsg += "</div>";
            }
          
        }

        return [processDetailsMsg, processHeaderMsg, roleDetailsMsg, roleHeaderMsg, roleCount ];
    }

    ret.getSystemStateDetails = function(last_data_modified, changed_data)
    {
        let msg = "";

        if( changed_data["is_reboot_needed"]["all"] )
        { 
            msg = "<div class=\"info red\">" + mx.I18N.get("Reboot needed");
            
            let reasons = {};
            if( changed_data["is_reboot_needed"]["os"] || changed_data["is_reboot_needed"]["installed"] ) reasons["1"] = mx.I18N.get("installed system updates");
            if( changed_data["is_reboot_needed"]["outdated"] ) reasons["2"] = mx.I18N.get("outdated processes");
                             
            let reasonKeys = Object.keys(reasons);
            let isMultiReason = reasonKeys.length > 1;
            
            let infoMsg = isMultiReason ? "Is needed because of {1} and {2}" : "Is needed because of {}";

            msg += "<div class=\"sub\">" + mx.I18N.get(infoMsg).fill( isMultiReason ? reasons : reasons[reasonKeys[0]] ) + "</div>";
            
            msg += "</div><div class=\"buttons\"><div class=\"form button exclusive red\" onclick=\"mx.UNCore.actionRebootSystem(this)\">" + mx.I18N.get("Reboot system") + "</div></div>";
        }
        
        return msg;
    }
    
    ret.getSystemUpdateDetails = function(last_data_modified, changed_data, last_full_update)
    {
        let detailsMsg = "";
        let headerMsg = "";
        let updateCount = changed_data["system_updates"].length;
        
        //let date = last_data_modified["system_updates"] ? new Date(last_data_modified["system_updates"] * 1000) : null;
        //if( last_full_update.getTime() == date.getTime() ) date = null;
        //const [ dateFormatted, dateType ] = mx.UpdateServiceHelper.formatDate(date);
        
        if( updateCount > 0 )
        {
            let plural = updateCount > 1;
          
            let i18n_main_msg = plural ? "There are {} system updates available" : "There is {} system update available";
            
            mx.I18N.get(i18n_main_msg).fill(updateCount)

            headerMsg = "<div class=\"info\">" + mx.I18N.get(i18n_main_msg).fill(updateCount) + "</div><div class=\"buttons\"><div class=\"form button exclusive green\" onclick=\"mx.UNCore.actionInstallUpdates(this)\">" + mx.I18N.get("Install") + "</div><div class=\"form button toggle\" onclick=\"mx.UNCore.toggle(this,'systemUpdateDetails')\"></div></div>";

            detailsMsg = "<div class=\"row\">";
            
            let update = changed_data["system_updates"][0];
            let has_current = update["current"] != null;
            
            detailsMsg += "<div>" + mx.I18N.get("Name") + "</div>";
            if( has_current ) detailsMsg += "<div>" + mx.I18N.get("Current") + "</div>";
            detailsMsg += "<div class=\"grow\">" + mx.I18N.get("Update") + "</div>";
            detailsMsg += "<div>" + mx.I18N.get("Arch") + "</div>";
            detailsMsg += "</div>";
            
            for( index in changed_data["system_updates"] )
            {
                let update = changed_data["system_updates"][index];

                detailsMsg += "<div class=\"row\">";
                detailsMsg += "<div>" + update["name"] + "</div>";
                if( has_current ) detailsMsg += "<div>" + update["current"] + "</div>";
                detailsMsg += "<div>" + update["update"] + "</div>";
                detailsMsg += "<div>" + update["arch"] + "</div>";
                detailsMsg += "</div>";
            }
        }
        else
        {
            headerMsg = "<div class=\"info\">" + mx.I18N.get("There are no system updates available") + "</div>";
        }
      
        return [ updateCount, detailsMsg, headerMsg ];
    }
    
    ret.getSmartserverChangeDetails = function(last_data_modified, changed_data, last_full_update)
    {
        let detailsMsg = "";
        let headerMsg = "";
        let updateCount = changed_data["smartserver_changes"].length;
        
        //let date = last_data_modified["smartserver_changes"] ? new Date(last_data_modified["smartserver_changes"] * 1000) : null;
        //if( last_full_update.getTime() == date.getTime() ) date = null;
        //const [ dateFormatted, dateType ] = mx.UpdateServiceHelper.formatDate(date);
      
        if( updateCount > 0 )
        {
            let plural = updateCount > 1;
          
            let i18n_main_msg = plural ? "There are {} smartserver updates available" : "There is {} smartserver update available";
            
            headerMsg = "<div class=\"info\">" + mx.I18N.get(i18n_main_msg).fill(updateCount) + "</div><div class=\"buttons\"><div class=\"form button exclusive green\" onclick=\"mx.UNCore.actionDeployUpdates(this)\">" + mx.I18N.get("Install") + "</div><div class=\"form button toggle\" onclick=\"mx.UNCore.toggle(this,'smartserverChangeDetails')\"></div></div>";

            detailsMsg = "<div class=\"row\">";
            detailsMsg += "<div>" + mx.I18N.get("Flag") + "</div>";
            detailsMsg += "<div class=\"grow\">" + mx.I18N.get("File") + "</div>";
            detailsMsg += "</div>";
            for( index in changed_data["smartserver_changes"] )
            {
                let change = changed_data["smartserver_changes"][index];

                detailsMsg += "<div class=\"row\">";
                detailsMsg += "<div>" + change["flag"] + "</div>";
                detailsMsg += "<div>" + change["path"] + "</div>";
                detailsMsg += "</div>";
            }
        }
        else
        {
            headerMsg = "<div class=\"info\">" + mx.I18N.get("There are no smartserver updates available") + "</div>";
        }
      
        return [ updateCount, detailsMsg, headerMsg ];
    }
    
    ret.getSmartserverChangeState = function(last_data_modified, changed_data)
    {
        let code = changed_data["smartserver_code"];
        let msg = "";
        
        if( code )
        {
            let date = new Date(changed_data["smartserver_pull"] * 1000);
            const [ lastPullFormatted, type ] = mx.UpdateServiceHelper.formatDate(date);
          
            [colorClass,updateMsg] = startserverChangeInfoCodes[code];
            
            msg = "<div class=\"info " + colorClass + "\">" + mx.I18N.get(updateMsg);
            if( code != "missing" ) msg += "<div class=\"sub\">" + mx.I18N.get("Last git pull was on {}").fill( lastPullFormatted ) + "</div>";
            msg += "</div>";
        }

        return msg;
    }
    
    ret.getActiveServiceJobName = function(cmd_type)
    {
        return mx.I18N.get(active_service_cmd_type_map[cmd_type]);
    }
    
    ret.getActiveLocalJobName = function(cmd_type)
    {
        return mx.I18N.get(active_local_cmd_type_map[cmd_type]);
    }

    ret.getJobDetails = function(last_data_modified, changed_data)
    {
        let detailsMsg = "";
        let headerMsg = "";
        
        let count = changed_data["jobs"].length
      
        if( count > 0 )
        {
            let last_job = changed_data["jobs"][0];
            
            if( last_job["state"] == "running" )
            {
                changed_data["jobs"].shift();
                last_job = changed_data["jobs"][0];
            }
            
            let action_msg_1 = "<div class=\"detailView\" onclick=\"mx.UNCore.openDetails(this,'" + last_job["start"] + "','" + last_job["type"] + "','" + last_job["user"] + "')\">"
            let action_msg_2 = "</div>";
            
            let state_msg = mx.I18N.get(last_job["state"] == "success" ? "was successful" : last_job["state"]);
            let color = last_job["state"] == "failed" || last_job["state"] == "crashed" ? " red" : "";
            
            let last_job_sentence = mx.I18N.get(last_cmd_type_map[last_job["type"]]);
            let duration = Math.round( last_job["duration"] );
            let msg = last_job_sentence.fill({"1": action_msg_1, "2": action_msg_2, "3": state_msg, "4": duration, "5": mx.I18N.get( duration == 1 ? "second" : "seconds" ) });
          
            headerMsg = "<div class=\"info" + color + "\">" + msg;
            headerMsg += "</div><div class=\"buttons\"><div class=\"form button toggle\" onclick=\"mx.UNCore.toggle(this,'lastRunningJobsDetails')\"></div></div>";

            detailsMsg = "<div class=\"row\">";
            detailsMsg += "<div></div>";
            detailsMsg += "<div class=\"grow\">" + mx.I18N.get("Job") + "</div>";
            detailsMsg += "<div>" + mx.I18N.get("User") + "</div>";
            detailsMsg += "<div>" + mx.I18N.get("State") + "</div>";
            detailsMsg += "<div>" + mx.I18N.get("Duration") + "</div>";
            detailsMsg += "<div>" + mx.I18N.get("Date") + "</div>";
            detailsMsg += "</div>";
            for( index in changed_data["jobs"])
            {
                let job = changed_data["jobs"][index];
                let date = new Date(job["timestamp"] * 1000);

                detailsMsg += "<div class=\"row\" onclick=\"mx.UNCore.openDetails(this,'" + job["start"] + "','" + job["type"] + "','" + last_job["user"] + "')\">";
                detailsMsg += "<div class=\"state " + job["state"] + "\"></div>";
                detailsMsg += "<div>" + mx.I18N.get(cmd_type_map[job["type"]]) + "</div>";
                detailsMsg += "<div>" + job["user"] + "</div>";
                detailsMsg += "<div>" + mx.Logfile.formatState(job["state"]) + "</div>";
                detailsMsg += "<div>" + mx.Logfile.formatDuration( job["duration"].split(".")[0] ) + "</div>";
                detailsMsg += "<div class=\"indexLogDate\">" + date.toLocaleString() + "</div>";
                detailsMsg += "</div>";
            }
        }
        else
        {
            $last_running_jobs_msg = "<div class=\"info green\">" + mx.I18N.get("No job history available") + "</div>";
            $last_running_jobs_details_msg = "";
        }
        
        return [detailsMsg, headerMsg];
    }
    
    ret.getWorkflow = function(systemUpdatesCount, smartserverChangeCount, lastUpdateDate)
    {
        msg = "";
        timeout = -1;
        
        if( systemUpdatesCount > 0 || smartserverChangeCount > 0 )
        {
            let duration = (new Date()).getTime() - lastUpdateDate.getTime();
            
            let isTimeout = duration > 300000;
            if( !isTimeout ) timeout = 300000 - duration;
            
            let isMultiReason = systemUpdatesCount > 0 && smartserverChangeCount > 0;
            
            let key = "";
            if( isMultiReason )
            {
                key = "There are {1} {2} and {3} {4} update(s) available";
            }
            else if( systemUpdatesCount > 1 || smartserverChangeCount > 1 ) 
            {
                key = "There are {1} {2} updates available";
            }
            else
            {
                key = "There is {1} {2} update available";
            }
            
            let reasons = {};
            let index = 0;
            if( systemUpdatesCount ) 
            {
                reasons[index+1] = systemUpdatesCount;
                reasons[index+2] = mx.I18N.get("system");
                index = 2;
            }
            if( smartserverChangeCount ){
                reasons[index+1] = smartserverChangeCount;
                reasons[index+2] = mx.I18N.get("smartserver");
            }
            
            msg = "<div class=\"info\">" + mx.I18N.get(key).fill( reasons );
            if( isTimeout ) msg += "<div class=\"sub\">" + mx.I18N.get("Disabled because it is only possible max. 5 minutes after a system status refresh") + "</div>";
            msg += "</div><div class=\"buttons\"><div class=\"form button exclusive";
            if( isTimeout ) msg += " disabled blocked";
            
            msg += " green\" onclick=\"mx.UNCore.actionUpdateWorkflow(this)\">" + mx.I18N.get("Install all") + "</div></div>";
        }
        
        return [ msg, timeout ];
    }
    
    return ret;
})( mx.UpdateServiceTemplates || {} ); 
