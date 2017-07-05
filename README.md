# Background

The provisioner is an application to generate provisioning information for hardware VoIP phones. It is written in php and uses [twig](https://twig.sensiolabs.org/) templates. It has been tested with the following phones: 
* Cisco SPA3x and SPA5x
* Yealink T2x and T4x
* Polycom SoundPoint and VVX

# Installation for CentOS 7 with httpd

## Clone the repo
```
cd /var/www/html
git clone git@github.com:OpenTelecom/provisioner.git
```

## Configure httpd
### Create the httpd conf file
```
vim /etc/httpd/conf.d/kazooprovision.conf
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
cp /var/www/html/provisioner/config.json.sample /var/www/html/provisioner/config.json
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
"name": "Provider Name",
   "authorized_ip": [
       "::0",
       "127.0.0.1",
       "couch.ip"
   "domain": "provisioner.yourdomain.foundation",
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
```
Replace ```Provider Name``` with the name of this provider. This is an arbitrary value and can be set to anything.

Replace ```couch.ip``` with the IP of the crossbar server that will be communicating with the provisioner.

Replace ```kamailio.domain``` with the domain name or IP of the Kamailio server that devices will authenticate with.

## Configure Kazoo

Configure the appropriate settings in the ```crossbar.devices``` document
```{
   "_id": "crossbar.devices",
   "default": {
       "provisioning_type": "super_awesome_provisioner",
       "provisioning_url": "http://provisioner.yourdomain.foundation/api/accounts",
       "allow_aggregates": "true"
   }
}
```
