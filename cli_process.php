<?php

/**
 * Index file for the config generator
 *
 * @author Francis Genet
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 * @version 5.0
 */

// Just to be sure
set_time_limit(5);
date_default_timezone_set('Pacific/Auckland');

require_once 'bootstrap.php' ;
require_once LIB_BASE . 'KLogger.php';

// Load the settings
$objSettings = new helper_settings();
$settings = $objSettings->getSettings();

$log = KLogger::instance(LOGS_BASE, Klogger::DEBUG);
$log->logInfo('======================================================');
$log->logInfo('================== Starting process ==================');
$log->logInfo('======================================================');

// Databse Based
if (!isset($argv)) {
    $uri = $_SERVER['REQUEST_URI'];
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
    $http_host = strtolower($_SERVER['HTTP_HOST']);
    $Clientip=$_SERVER["REMOTE_ADDR"];
//echo $Clientip;
if(strpos($ua,"yealink"))$log->logDebug("Current phone yealink type");
if(strpos($uri,"xml"))$log->logDebug("Current file xml");
if(strpos($uri,"xml")&&strpos($ua,"polycom"))exit();
if(strpos($uri,"log")&&strpos($ua,"polycom"))exit();
if(strpos($uri,"web")&&strpos($ua,"polycom"))exit();
if(strpos($uri,"license")&&strpos($ua,"polycom"))exit();
//if(strpos($uri,"phone")&&strpos($ua,"polycom"))exit();
    //$ua = "Mozilla/4.0 (compatible; snom710-SIP 8.7.3.8 1.1.5-IFX-05.01.12)";
    //$uri = "/000413742517.xml";

    //$ua = strtolower("FileTransport PolycomSoundStationIP-SSIP_5000-UA/4.0.3.7562 (SN:001565000000) Type/Application");
    //$uri = "/002e3a6fe532d90943e6fcaf08e1a408/001565000000_reg.cfg";

    //$ua = "linksys/spa-942-6.1.5(a) (88019fa84784)";
    //$uri = "/001565000000.xml";

    //$ua = strtolower("yealink SIP-T22P 7.61.0.80 00:15:65:00:00:00");

    // Cisco
    //$ua = "Cisco/SPA504G-7.4.9c (001565000000)(CCQ162306EA)";
    //$uri = "/002e3a6fe532d90943e6fcaf08e1a408/spa001565000000.xml";

    //$ua = "Linksys/SPA-921-5.1.8 (4MJ00HC02158)";
    //$uri = "/002e3a6fe532d90943e6fcaf08e1a408/directory-001565000000.xml";

    $log->logDebug("Current UA: $ua");
    $log->logDebug("Current URI: $uri");
    $log->logDebug("Current HOST: $http_host");

    // Load the configuration adapter (converts format from FreePBX/Kazoo/etc. to a standard format)
    // This will return a class which will pre-process configurations
    $adapter_name = "adapter_" . $settings->adapter . "_adapter";
    $adapter = new $adapter_name();

    $config_manager = $adapter->get_config_manager($uri, $ua, $http_host, $Clientip, $settings);
    if ($config_manager) {
        $config_manager->set_request_type('http');
        
        $result = $config_manager->generate_config_file();
        if ($result)
            echo $result;
        else
            die();
    } else 
        die();

// CLI Based
} else {
    // Just making sure that everything is where it should be
    if(!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3])) {
        die("Usage: php process.php <brand> <model> <source_file_path>\n");
    }

    $brand = strtolower($argv[1]);
    $model = strtolower($argv[2]);
    $source_file_path = $argv[3];

    if(!file_exists($source_file_path)) {
        die("File " . $source_file_path . " does not exist!\n");
    }

    $arrConfig = json_decode(file_get_contents($source_file_path), true);
    if(helper_utils::json_errors()) {
        die("FATAL: " . helper_utils::json_errors() . "\n");
    }

    // This is adapter is generic and is basically building a simple config manager
    // with a minimum of information (brand/model/a file containing the settings)
    $adapter = new adapter_generic_adapter();
    $config_manager = $adapter->get_config_manager($brand, $model, $arrConfig);
    $config_manager->set_request_type('tftp');

    foreach (helper_utils::get_file_list($brand, $model) as $value) {
        $config_manager->set_config_file($value);

        // make a file with the returned value
        // This is not doing it for now, it will need to be implemented
        $result = $config_manager->generate_config_file();
        if ($result)
            echo '['.$value.']'.$result;
        else
            die();
    }
}
