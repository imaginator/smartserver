# ImagiLAN

(forked from https://github.com/HolgerHees/smartserver)

Infrastructure and Server Automation

## Build Infrastructure

`ansible-playbook  -i config/bunker/inventory.yml   infrastructure.yml  --diff` 

## Deploy services

`ansible-playbook  -i config/bunker/inventory.yml   services.yml  --diff`
