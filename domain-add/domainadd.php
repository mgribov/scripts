#!/usr/bin/php
<?php

define(CONFIG_DNS, '/usr/local/named/etc/named.conf');
define(CONFIG_DNS_SLAVE, '/usr/local/named/etc/named.conf.slaves');
define(CONFIG_DNS_MASTER, '/usr/local/named/etc/zones/master/');

define(TPL_DNS_CONF, '/usr/local/named/etc/tpl/named.conf.tpl');
define(TPL_DNS_SLAVE, '/usr/local/named/etc/tpl/named.conf.slave.tpl');
define(TPL_DNS_ZONE, '/usr/local/named/etc/tpl/zone.tpl');

$hosts = array(
                'ns1' => array('ip'=>'', 'host'=>''),
                'ns2' => array('ip'=>'', 'host'=>''),
                'ns3' => array('ip'=>'', 'host'=>''),
                'mx1' => array('ip'=>'', 'host'=>''),
                'mx2' => array('ip'=>'', 'host'=>''),
                'mx3' => array('ip'=>'', 'host'=>''),
            );


$domain = strtolower($argv[1]);
$command = $argv[2];

if (!$domain) {
    return usage();
}

if ($command) {
    return save_config($domain, $command);
}

// catchall
return save_config($domain, 'dns');

function save_config($domain, $type) {

    $now = date('Ymd') . '01';
    $abort = false;

    echo 'adding ' . $type . ' for ' . $domain . "\r\n";

    switch ($type) {
        case "all":
        case "web":
            /*
            echo "\tmaking web directories for " . $domain . " in " . PREFIX_WEBHOSTING . "\r\n";
            mkdir(PREFIX_WEBHOSTING . $domain);
            mkdir(PREFIX_WEBHOSTING . $domain . '/public_html');
            mkdir(PREFIX_WEBHOSTING . $domain . '/logs');
            break;
            */

        case "dns":
            // add the zone file
            if ($abort || file_exists(CONFIG_DNS_MASTER . $domain)) {
                echo "this domain already as a " . $type . " entry\r\n";
                $abort = true;
            } else {
                echo "\tcreating master zone " . CONFIG_DNS_MASTER . $domain . "\r\n";
                $zone = preg_replace('/\<domain\>/', $domain, file_get_contents(TPL_DNS_ZONE));
                $zone = preg_replace('/\<timestamp\>/', $now, $zone);
                $file = fopen(CONFIG_DNS_MASTER . $domain, 'w');
                fwrite($file, $zone);
                fclose($file);
            }

            // add domain to main named.conf
            if ($abort || stristr($domain, file_get_contents(CONFIG_DNS))) {
                echo "\tthis domain already exists in main dns config\r\n";
                $abort = true;
            } else {
                echo "\twriting changes to " . CONFIG_DNS . "\r\n";
                $confFile = preg_replace('/\<domain\>/', $domain, file_get_contents(TPL_DNS_CONF));
                $file = fopen(CONFIG_DNS, 'a');
                fwrite($file, $confFile);
                fclose($file);
            }

            // create slave zone, same file gets overwritten with new data
            if ($abort || stristr($domain, file_get_contents(CONFIG_DNS_SLAVE))) {
                echo "\tthis domain already exists in slave dns config\r\n";
                $abort = true;
            } else {
                echo "\tcreating slave zone in " . CONFIG_DNS_SLAVE . "\r\n";
                $confFile = preg_replace('/\<domain\>/', $domain, file_get_contents(TPL_DNS_SLAVE));
                $file = fopen(CONFIG_DNS_SLAVE, 'a');
                fwrite($file, $confFile);
                fclose($file);
            }

            if (!$abort) {
                $mx_string = '\"' . $domain . '         smtp:[mail.domain.com]\"';
                echo "\tadding domain to secondary mx server: $mx_string\r\n";
                system('ssh bind@ns2.domain.com "echo ' . $mx_string . ' >> /usr/local/etc/postfix/transport"');
            }


            break;
        default:
            return usage();
    }
}

function usage() {
    echo 'usage: ' . $argv[0] . " <domain.com> [all|web|dns]\n\r";
    exit();
}

