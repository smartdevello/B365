<?php

function apiRequest($endpoint, $version = 2){
    $curl = curl_init();
    if ($version == 1) $baseurl = "https://api.betsapi.com/v1/";
    else $baseurl = "https://api.betsapi.com/v2/";

    curl_setopt_array($curl, array(
        CURLOPT_URL => $baseurl.$endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',

    ));
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response);
}


add_action( 'wp_ajax_getEvents', 'getEvents' );
add_action( 'wp_ajax_nopriv_getEvents', 'getEvents' );
function getEvents() {
  $day = $_POST["day"];
  $events = GetUpcomingevents($day);
  AdminEventTemplate($events);
  echo $day;
  wp_die();
}


add_action( 'wp_ajax_createTips', 'createTips' );
add_action( 'wp_ajax_nopriv_createTips', 'createTips' );
function createTips(){
    $data = $_POST['data'];
    global $wpdb;
    $res = $wpdb->insert ("{$wpdb->prefix}tips", $data);
    echo $res;
    wp_die();
}


add_action( 'wp_ajax_updateTip', 'updateTip' );
add_action( 'wp_ajax_nopriv_updateTip', 'updateTip');
function updateTip(){
    $data = $_POST['data'];    
    global $wpdb;
    $res = $wpdb->update("{$wpdb->prefix}tips", $data, array('id' => $data["id"]));
    echo $res;
    wp_die();
}


add_action( 'wp_ajax_deleteTip', 'deleteTip' );
add_action( 'wp_ajax_nopriv_deleteTip', 'deleteTip');

function deleteTip(){
    $data = $_POST['data'];    
    global $wpdb;
    $res = $wpdb->delete("{$wpdb->prefix}tips", array('id' => $data["id"]));
    echo $res;
    wp_die();
}

add_action( 'wp_ajax_mark_favorite_league', 'mark_favorite_league' );
add_action( 'wp_ajax_nopriv_mark_favorite_league', 'mark_favorite_league');

function mark_favorite_league(){
    $data = $_POST['data'];    
    global $wpdb;
    
    $league_id = $data['league_id'];
    $favorite = $data['favorite'];    
    
    $res = $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}upcoming_events SET favorite = $favorite WHERE league_id = $league_id"));
    echo $res;
    wp_die();
}


?>