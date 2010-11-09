<?php

/**
 * Register with graph.facebook.com to receive real-time notifications
 *
 */

define('URL_GRAPH', 'https://graph.facebook.com');

$opt = getopt('hi:s:f:c:t:o:a:');

if (count($argv) < 2 || array_key_exists('h', $opt)) {
    usage();
    exit;
}

// sanity checks
$options = check_options($opt);

$client_id = $options['i'];
$client_secret = $options['s'];
$callback = $options['c'];
$verify_token = $options['t'];
$object = $options['o'];
$action = $options['a'];

// @TODO field list is not complete yet, see: http://developers.facebook.com/docs/api/realtime
$fields = (false == $options['f']) ? 'feed' : $options['f'];


// get access token
$query_auth = URL_GRAPH . '/oauth/access_token?client_id=' . $client_id . '&client_secret=' . $client_secret . '&grant_type=client_credentials';
$data = call_curl($query_auth);
$auth_token = $data['html'];

// subscribe to stream with designated callback
$query_subscribe = URL_GRAPH . '/' . $client_id . '/subscriptions?' . $auth_token;
switch (strtolower($action)) {
    case 'get':
        $data = call_curl($query_subscribe);
        break;

    case 'delete':
        // for some reason this fails sometimes, use: curl -X DELETE "https://graph.facebook.com/<client_id>/subscriptions?access_token=<token>&object=user"
        $data = call_curl($query_subscribe . '&object=' . $object, 'DELETE');
        break;

    case 'post':
        $postfields = '&object=' . $object . '&fields=' . $fields . '&callback_url=' . $callback . '&verify_token=' . $verify_token;
        $data = call_curl($query_subscribe, 'POST', $postfields);
        break;
}

print($data['html'] . "\n");


function usage() {
    echo "Valid Options: \n";
    echo "\t-h - help\n";
    echo "\t-i - application id (required)\n";
    echo "\t-s - application secret (required)\n";
    echo "\t-c - call-back url (required) [ex: http://www.mysite.com/facebook_callback.php] \n";
    echo "\t-t - verify token (required) \n";
    echo "\t-f - comma-separated fields (posts, comments) (optional, default is posts) \n";
    echo "\t-o - comma-separated objects to act on (user, permission, page) (optional, default is user) \n";
    echo "\t-a - action (get, post, delete) (optional, default is get) \n";
}

function check_options(array $options) {
    if (!array_key_exists('i', $options) || !strlen($options['i'])) {
        usage();
        exit;
    }

    if (!array_key_exists('s', $options) || !strlen($options['s'])) {
        usage();
        exit;
    }


    // check actions: get, post, delete
    if (array_key_exists('a', $options)) {
        $check = explode(',', $options['a']);
        if (count($check) > 3 || count($check) < 1) {
            usage();
            exit;
        }

        $valid = array('get', 'post', 'delete');
        foreach ($check as $opt) {
            if (!in_array($opt, $valid)) {
                usage();
                exit;
            }
        }
    } else {
        $options['a'] = 'get';
    }

    // check objects: user, permission, page
    if (array_key_exists('o', $options)) {
        $check = explode(',', $options['o']);
        if (count($check) > 3 || count($check) < 1) {
            usage();
            exit;
        }

        $valid = array('user', 'permission', 'page');
        foreach ($check as $opt) {
            if (!in_array($opt, $valid)) {
                usage();
                exit;
            }
        }
    } else {
        $options['o'] = 'user';
    }

    if ($options['a'] == 'post') {
        if (!array_key_exists('c', $options) || !strlen($options['c'])) {
            usage();
            exit;
        }

        if (!array_key_exists('t', $options) || !strlen($options['t'])) {
            usage();
            exit;
        }
    }

    return $options;
}


function call_curl($url, $method = 'GET', $postfields = false) {
    $cookiefile = tempnam('/tmp', 'curl_cookie_');
    $curl = curl_init();

    $header[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
    $header[] = "Cache-Control: max-age=0";
    $header[] = "Connection: keep-alive";
    $header[] = "Keep-Alive: 300";
    $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
    $header[] = "Accept-Language: en-us,en;q=0.5";
    $header[] = "Pragma: "; // browsers keep this blank.

    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    if (strtoupper($method) == 'POST') {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
    }

    curl_setopt($curl,CURLOPT_COOKIEJAR, $cookiefile);
    curl_setopt($curl,CURLOPT_COOKIEFILE,$cookiefile);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.11) Gecko/2009060309 Ubuntu/8.04 (hardy) Firefox/3.0.11');
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_REFERER, 'http://www.google.com');
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 4);

    $html = curl_exec($curl); 
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl); 
    unlink($cookiefile);

    $data = array('html' => $html, 'http_code' => $http_code); 

    if ($data['http_code'] != '200') {
        die('Query: ' . $url . $postfields . "\n" . "Method: $method\n" . 'Error: ' . $data['http_code'] . "\n" . 'Return: ' . $data['html']);
    }
    
    return $data;
}
