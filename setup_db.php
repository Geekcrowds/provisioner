#!/usr/bin/php  -q
<?php 

/**
 * This file will configure your database.
 * It MUST be run before using the provisioner
 *
 * @author Francis Genet
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

require_once 'bootstrap.php';

require_once LIB_BASE . '/php_on_couch/couch.php';
require_once LIB_BASE . '/php_on_couch/couchClient.php';
require_once LIB_BASE . '/php_on_couch/couchDocument.php';

define('CONFIG_FILE', PROVISIONER_BASE . 'config.json');

// Loading config file
$configs = json_decode(file_get_contents(CONFIG_FILE));

if (!$configs)
    die('Could not load the config file');

$server_url = $configs->database->url . ":" . $configs->database->port;

if (strtolower($configs->database->type) == "bigcouch") {
    if (strlen($configs->database->username) && strlen($configs->database->password)) {
        $server_url = str_replace('http://', '', $server_url);
        $credentials = $configs->database->username . ':' . $configs->database->password . '@';
        $server_url = 'http://' . $credentials . $server_url;
    }

    // Factory defaults
    // ================

    // Creating the database
//    $couch_client->useDatabase($configs->db_prefix . "factory_defaults");
    // Creating the database
    $couch_client_factory = new couchClient($server_url, $configs->db_prefix . "factory_defaults");
    if (!$couch_client_factory->databaseExists())$couch_client_factory->createDatabase();
    $couch_client = new couchClient($server_url, $configs->db_prefix . "mac_lookup");
    if (!$couch_client->databaseExists())$couch_client->createDatabase();
    $couch_client_providers = new couchClient($server_url, $configs->db_prefix . "providers");
    if (!$couch_client_providers->databaseExists())$couch_client_providers->createDatabase();
    $couch_client = new couchClient($server_url, $configs->db_prefix . "system_account");
    if (!$couch_client->databaseExists())$couch_client->createDatabase();

    // Creating the views
    $factory_view = new stdCLass();
    $factory_view->_id = "_design/" . $configs->db_prefix . "factory_defaults";
    $factory_view->language = "javascript";

    // reset
    $view = new stdCLass();
    // By brand
    $view->{"list_by_brand"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'brand') return; emit(doc.brand, {'id': doc._id, 'name': doc.brand, 'settings' : doc.settings}); }"
    );

    // By family
    $view->{"list_by_family"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'family') return; emit([doc.brand,doc.family], {'id': doc._id, 'name': doc.family, 'settings' : doc.settings}); }"
    );

    // By model
    $view->{"list_by_model"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'model') return; emit([doc.family,doc.model], {'id': doc._id, 'name': doc.model, 'settings' : doc.settings}); }"
    );

    // Get All
    $view->{"list_by_all"} = array(
        "map" => "function(doc) { emit([doc.brand,doc.family,doc.model], {'id': doc._id, 'settings': doc.settings}); }"
    );
    $factory_view->views = $view;

    try {
        $couch_client_factory->storeDoc($factory_view);
    } catch (Exception $e) {
        die("ERROR: " . $e->getMessage() . " (" . $e->getCode() . ")<br>");
    }

    $providers_view = new stdCLass();
    $providers_view->_id = "_design/" . $configs->db_prefix . "providers";
    $providers_view->language = "javascript";

    // reset
    $view = new stdCLass();
    // By domain
    $view->{"list_by_domain"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'provider') return; emit(doc.domain, {'id': doc._id, 'name': doc.name, 'domain' : doc.domain , 'default_account_id' : doc.default_account_id, 'settings': doc.settings}); }"
    );

    // By ip
    $view->{"list_by_ip"} = array(
        "map" => "function(doc) { if (doc.pvt_type != 'provider') return; for (i in doc.authorized_ip) {emit(doc.authorized_ip[i], {'access_type': doc.pvt_access_type})}; }"
    );

    $providers_view->views = $view;

    try {
        $couch_client_providers->storeDoc($providers_view);
    } catch (Exception $e) {
        die("ERROR: " . $e->getMessage() . " (" . $e->getCode() . ")<br>");
    }
}

 ?>
