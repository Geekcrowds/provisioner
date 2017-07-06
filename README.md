# Background

The provisioner is an application to generate provisioning information for hardware VoIP phones. It is written in php and uses [twig](https://twig.sensiolabs.org/) templates. It has been tested with the following phones: 
* Cisco SPA3x and SPA5x
* Yealink T2x and T4x
* Polycom SoundPoint and VVX

# Installation for CentOS 7 with httpd

Prepare you fresh CentOS 7 server.
```
yum -y install httpd mod_ssl git epel-release
```

## Clone the repo
```
cd /var/www/html
git clone https://github.com/OpenTelecom/provisioner.git
```

## Configure httpd
### Create the httpd conf file
```
vim /etc/httpd/conf/kazooprovision.conf
<VirtualHost *:80>
        ServerName provisioner.yourdomain.foundation
        ServerAdmin webmaster@yourdomain.foundation
        DocumentRoot /var/www/html/provisioner/
        Timeout 600
        DirectoryIndex index.php index.html
        <Directory />
                Options FollowSymLinks
                AllowOverride All
        </Directory>
</VirtualHost>
```
Change the ```ServerName``` ```ServerAdmin``` ```DocumentRoot``` as appropriate.

### Load the configuration

```systemctl reload httpd```

## Configure the provisioner
### Create and update the config.json file
```
cp /var/www/html/provisioner/config_sample.json /var/www/html/provisioner/config.json
```

Update ```config.json``` with the appropriate settings:

Set the value for ```"adapter"``` to "2600hz".

Set the value for ```"db_prefix"```. Choose a value that all provisioner Couch databases will be prefixed with e.g. zz_provisioner.

Replace ```my.domain.com``` with the domain name of the provisioning server.

Replace ```my.bigcouch-server.com``` with the domain name of the Couch server where the provisioner databases will be stored.

Replace ```Master provider``` with the name of the provider. This is an arbitrary value and can be set to anything.

Replace ```MyIP``` with the IP address of the provisioning server.

Replace ```MyDomain``` with the domain name of the provisioning server.

###Create the necessary provisioner Couch databases 

```php setup_db.php```

This will create a number of databases with the prefix as set in the ```config.json``` file.

###Create a document in the provisioner providers Couch database
```
{
   "_id": "PROVIDED-BY-COUCHDB",
   "name": "CloudPBX",
   "authorized_ip": [
       "::0",
       "127.0.0.1",
       "crossbar-public-ip",
       "crossbar-public-ip",
       "crossbar-public-ip",
       "crossbar-public-ip",
       "crossbar-public-ip",
       "crossbar-public-ip"
   ],
   "domain": "provisioning-server-domain",
   "default_account_id": null,
   "pvt_access_type": "admin",
   "pvt_type": "provider",
   "settings": {
       "outbound_proxy": {
           "enable": "1",
           "primary": {
               "host": "kamailio.domain"
           }
       }
   }
}
```
Replace ```Provider Name``` with the name of this provider. This is an arbitrary value and can be set to anything.

Replace ```crossbar-public-ip``` with the IP of the crossbar server that will be communicating with the provisioner.

Replace ```kamailio.domain``` with the domain name or IP of the Kamailio server that devices will authenticate with.

## Configure Kazoo

Configure the appropriate settings in the ```crossbar.devices``` document available in your Kazoo HAProxy server at http://127.0.0.1:15984/_utils/document.html?system_config/crossbar.devices
```{
   "_id": "crossbar.devices",
   "default": {
       "provisioning_type": "super_awesome_provisioner",
       "provisioning_url": "http://provisioner.yourdomain.foundation/api/accounts",
       "allow_aggregates": "true"
   }
}
```
## Create Kazoo device

### Background
The following keys should be populated in the device document in order for the provisioning data to be generated:

```sip``` contains the username and password for the first account on the phone

```mac_address``` the MAC address for the phone

```provision.endpoint_brand``` the phone brand e.g. yealink, cisco, polycom

```provision.endpoint_family``` the phone family e.g. t2x, spa5xx

```provision.endpoint_model``` the phone model e.g. t26, spa303

```provision.settings.accounts``` contains the username, password, domain and proxy details for the second and subsequent accounts on the phone

```provision.settings.lines``` contains the mapping from the line keys on the phone to various functions. The function is determined by the type setting.

```provision.settings.combo_keys``` contains the mapping from the combo keys on the phone to various functions. The function is determined by the type setting.

```provision.settings.sidecar``` contains configuration for settings that are common across all sidecars. The function is determined by the type setting.

```provision.settings.sidecar_01``` contains the mapping from the keys on the first sidecar to various functions. The function is determined by the type setting.

```type``` One of the following values:

* 13: speed dial
* 15: account
* 16: BLF

### Example Yealink device document
```
{
  "data": {
    "sip": {
      "password": "passw0rd",
      "username": "user_abcd",
      "expire_seconds": 300,
      "invite_format": "username",
      "method": "password"
    },
    "device_type": "sip_device",
    "enabled": true,
    "mac_address": "00:15:15:15:15:15",
    "name": "test t26",
    "owner_id": "cd7ca46d83a38b7f02a8e1b73f8a463f",
    "provision": {
      "endpoint_brand": "yealink",
      "endpoint_family": "t2x",
      "endpoint_model": "t26",
      "settings": {
        "accounts": {
          "2": {
            "basic": {
              "enable": true,
              "display_name": "test 2 t26"
            },
            "sip": {
              "username": "user_4abcj",
              "password": "1234",
              "realm_01": "1000009.yourdomain.foundation",
              "outbound_proxy_01": "sip.yourdomain.foundation",
              "transport": "1"
            }
          }
        },
        "lines": {
          "1": {
            "type": "15",
            "key": {
              "line": "1",
              "value": "1593",
              "label": "1593"
            }
          },
          "2": {
            "type": "15",
            "key": {
              "line": "2",
              "value": "1594",
              "label": "1594"
            }
          },
          "3": {
            "type": "16",
            "key": {
              "line": "2",
              "value": "2009",
              "label": "2009"
            }
          }
        },
        "combo_keys": {
          "1": {
            "type": "16",
            "key": {
              "line": "1",
              "value": "1596",
              "label": "1596"
            }
          },
          "2": {
            "type": "16",
            "key": {
              "line": "1",
              "value": "1599",
              "label": "1599"
            }
          },
          "3": {
            "type": "13",
            "key": {
              "line": "1",
              "value": "5551231234",
              "label": "5551231234"
            }
          }
        },
        "sidecar_01": {
          "1": {
            "type": "16",
            "key": {
              "line": "1",
              "value": "1593",
              "label": "1593"
            }
          },
          "2": {
            "type": "16",
            "key": {
              "line": "2",
              "value": "1594",
              "label": "1594"
            }
          },
          "3": {
            "type": "16",
            "key": {
              "line": "2",
              "value": "1595",
              "label": "1595"
            }
          }
        }
      }
    }
   }
}
```
### Example Yealink device explained

* Two accounts are configured one with username user_abcd and the other with username user_4abcj
* Three lines are configured. Line 1 is linked to account 1, line 2 is linked to account 2, line 3 is set to monitor BLF on extension 2009 on account 2
* Three combo_keys are configured. Key 1 is set to monitor BLF on extension 1596 on account 1, key 2 is set to monitor BLF on extension 1599 on account 1, key 3 is set to speed dial 5551231234 using account 1.
* Three buttons on sidecar are configured. Key 1 is set to monitor BLF on extension 1593 on account 2, key 2 is set to monitor BLF on extension 1594 on account 2, key 3 is set to monitor BLF on extension 1595 on account 2.

## Known issues
