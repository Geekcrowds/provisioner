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
    $couch_client = new couchClient($server_url, $configs->db_prefix . "factory_defaults");

    if (!$couch_client->databaseExists())
        $couch_client->createDatabase();


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
        $couch_client->storeDoc($factory_view);
    } catch (Exception $e) {
        die("ERROR: " . $e->getMessage() . " (" . $e->getCode() . ")<br>");
    }

}

 ?>
