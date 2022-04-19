networks = ["192.168.0.0/24"]
main_interface = "{{main_network_interface}}"
server_name = "{{server_name}}"
server_domain = "{{server_domain}}"
server_ip = "{{server_ip}}"

service_ip = "127.0.0.1"
service_port = "8507"

librenms_token = "{{vault_librenms_api_token if vault_librenms_api_token is defined else ''}}"
librenms_rest = "http://librenms:8000/api/v0/";
librenms_poller_interval = {{librenms_poller_interval * 60}}

openwrt_username = "{{vault_openwrt_api_username}}"
openwrt_password = "{{vault_openwrt_api_password}}"
openwrt_devices = [ "{{openwrt_devices | map(attribute='host') | list | join('","') }}" ]

influxdb_rest = "http://influxdb:8086"
influxdb_database = "system_info"
influxdb_token = "{{vault_influxdb_admin_token}}"

default_vlan = 1

remote_suspend_timeout = 300
remote_error_timeout = 900

cache_ip_dns_revalidation_interval = 900
cache_ip_mac_revalidation_interval = 900

arp_scan_interval = 60
arp_offline_device_timeout = 60
arp_clean_device_timeout = 60 * 60 * 24 * 7

openwrt_network_interval = 900
openwrt_client_interval = 60

librenms_device_interval = 900
librenms_vlan_interval = 900
librenms_fdb_interval = 300
librenms_port_interval = 60

port_scan_interval = 300
port_rescan_interval = 60*60*24

publisher_republish_interval = 900

user_devices = {
{% for username in userdata %}
{% if userdata[username].phone_device is defined %}
    {% if loop.index > 1 %},{% endif %}"{{userdata[username].phone_device['ip']}}": { "type": "{{userdata[username].phone_device['type'] | default('android')}}",  "timeout": {{userdata[username].phone_device['timeout'] | default(90)}} }
{% endif %}
{% endfor %}
}
