server_host = "{{server_domain}}"

status_file = "{{global_tmp}}ci_service.status"

lib_dir = "{{global_lib}}ci/"
log_dir = "{{global_log}}ci/"
build_dir = "{{global_build}}"
repository_dir = "{{global_build}}ci_job"

repository_url = "{{vault_deployment_config_git}}"
access_token = "{{vault_deployment_token_git if vault_deployment_token_git != 'None' else ''}}"

daemon_ip = "{{update_daemon_ip}}"