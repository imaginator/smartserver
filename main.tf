terraform {
  required_providers {
    lxd = {
      source  = "terraform-lxd/lxd"
      version = "1.5.0"
    }
  }
}

provider "lxd" {

  generate_client_certificates = true
  accept_remote_certificate    = true

  lxd_remote {
    name     = "bunker"
    scheme   = "https"
    address  = "10.7.11.10"
    port     = "8444"
    password = "oddtaa45- name: Set dmz as default policy"
    default  = true
  }
}

# Storage
resource "lxd_storage_pool" "imagiStore" {
  name   = "imagiStore"
  driver = "btrfs"
  config = {
    source = "/var/snap/lxd/common/lxd/storage-pools/imagiStore"
  }
}

resource "lxd_profile" "imagiProfile" {
  name = "imagiProfile"

  config = {
    "limits.cpu"          = "1"
    "security.nesting"    = "true"
    "security.privileged" = "true"
    "user.user-data" : "#cloud-config\nusers:\n  - name: root\n    ssh-authorized_keys:\n      - ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQDVhl1T7oXdVMrrKi4uJ6n0Mb2z2pIhbszr4J7kpbcl8ociYIASAEg0WxqFrfrty8SThdgVy6oOIjJsKzinMDFqWtCDxKtF1rQ3lC1gYUcyaa9hfwNbZw/2zuI9tPH+80jn1kikjDmliIASDQQR049ZUw1s/KHHAQHDJRHewVJqo1jSEOuYoJ8LbIQ0LcNdJNGYjsikqL5KgdVrtiJtFDkEn+ugHMbsHaozlTIFqwfKzuKjwQo2sJWt8wFvUSpM0lZUCzMQTn5PnaXfl7OgJiJ6deZL0uRRSG9MNnvNalX5BviYn42axE0Czx1qNsk2BB672kPZ+tu0ahVnO8bp50FE9/HMgCU0KUor1kR7VfQV8jvHobNaNwBQtFpxosDXBJBCjWYYXgr0L1oEYXrEuiQyP2hZo5emsy0wpb7g8TKXAk6m1i1Z1WvojNfzk81dCx5yB2aV5VFMS0/xLZbWBmAqVSbKZIcEhKBvoC9bIEEGxAD3WPkJ2mttLanemS0UNSk= my_pubkey@os\n"
  }

  device {
    name = "root"
    type = "disk"
    properties = {
      pool = "imagiStore"
      path = "/"
    }
  }

  device {
    name = "shared"
    type = "disk"
    properties = {
      source = "/tmp"
      path   = "/tmp"
    }
  }

  device {
    name = "eth0"
    type = "nic"
    properties = {
      nictype = "macvlan"
      parent  = "trusted"
    }
  }
}


resource "lxd_container" "server1" {
  name      = "server1"
  image     = "ubuntu:22.04"
  ephemeral = false
  profiles  = ["imagiProfile"]
}

resource "lxd_container" "server2" {
  name      = "server2"
  image     = "ubuntu:22.04"
  ephemeral = false
  profiles  = ["imagiProfile"]
}


#IP of aws instance copied to a file ip.txt in local system
resource "local_file" "ip" {
  content  = lxd_container.server1.ipv4_address
  filename = "ip.txt"
}



