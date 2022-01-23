send_software_version_notification = {{'True' if send_update_notifier_email else 'False'}}
send_system_update_notification = {{'True' if send_update_notifier_email else 'False'}}

server_host = "{{server_domain}}"

daemon_ip = "{{update_daemon_ip}}"

target_dir = "{{global_lib}}update_daemon/"
components_config_dir = "{{global_etc}}update_daemon/software/"

software_version_state_file = "{}software_versions.state".format(target_dir)
system_update_state_file = "{}system_updates.state".format(target_dir)
deployment_state_file = "{}deployment.state".format(target_dir)

git_directory = "{{projects_path}}{{project_name}}"

global_config = { 
  "github_access_token": "{{vault_deployment_token_git}}"
}

os_type = "{{os_type}}"

job_state_file = "{{global_tmp}}update_daemon.state"
job_log_folder = "{{global_log}}update_daemon/"