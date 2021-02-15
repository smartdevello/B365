<?php
/**
 * Plugin Name: Bet365
 */
define('b365_base_url', 'https://api.b365api.com/v2/');
define ('b365_token', '2649-nJ47oQZfxk4ZTR');

require_once(__DIR__."/B365_adminAjax.php");
require_once(__DIR__."/custom-shortcodes.php");
// require_once(__DIR__."/football-prediction.php");

//Enable Widget
add_filter( 'widget_text', 'shortcode_unautop' );
add_filter( 'widget_text', 'do_shortcode' );

add_action( 'init',  function() {
    add_rewrite_rule( 'football-prediction/([^/]*)/([^/]*)/([^/]*)/([^/]*)', 'index.php?homeaway=$matches[1]&ddmm=$matches[2]&yyyy=$matches[3]&tip_id=$matches[4]', 'top' );
});

register_sidebar( array(
    'name' => 'Tip Page SideBar',
    'id' => 'tip_widget',
    'description'  => __( 'Content of your Tip detail page goes here' ),
    'before_widget' => '<div class="tipwidget">',
    'after_widget' => '</div>',
    'before_title' => '<h3 class="widget-title">',
    'after_title' => '</h3>'
));

add_filter( 'query_vars', function( $query_vars ) {
    $query_vars[] = 'homeaway';
    $query_vars[] = 'ddmm';
    $query_vars[] = 'yyyy';
    $query_vars[] = 'tip_id';
    return $query_vars;
});

add_action( 'template_include', function( $template ) {
    if ( get_query_var( 'homeaway' ) == false || get_query_var( 'homeaway' ) == '' ) {
        return $template;
    }
    if ( get_query_var( 'ddmm' ) == false || get_query_var( 'ddmm' ) == '' ) {
        return $template;
    }
    if ( get_query_var( 'yyyy' ) == false || get_query_var( 'yyyy' ) == '' ) {
        return $template;
    }
    if ( get_query_var( 'tip_id' ) == false || get_query_var( 'tip_id' ) == '' ) {
        return $template;
    }
    //return plugin_dir_url( __FILE__ ) . 'football-prediction.php';
    return get_stylesheet_directory() . '/tiptemplate.php';
});


add_action('admin_menu', 'B365_settings');
function B365_settings() {

    add_menu_page('B365 Admin', 'B365 Admin', 'administrator', 'B365_admin', 'B365_Dashboard');
    // add_menu_page( string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', string $icon_url = '', int $position = null );
    add_submenu_page('B365_admin', 'B365 Tips', 'All Tips', 'administrator', 'B365_Tips', 'B365_Dashboard_Tips');
    // add_submenu_page('B365_setting_slug', 'B365 Admin Settings', 'Gift Cards Sold', 'administrator', 'B365_Admin_gift_card_sold', 'Gift_Card_Sold');

}
add_action('wp_footer', 'B365_scripts');
add_action('admin_footer', 'B365_scripts');
function B365_front_scripts(){


    // Include Custom JS
    wp_register_script('B365_custom_js', plugins_url('js/B365_custom.js' , __FILE__ ));
    wp_localize_script('B365_custom_js', 'event_Ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
    wp_enqueue_script('B365_custom_js');

      // Include B365_custom.css
      wp_register_style('B365_custom.css', plugins_url('css/B365_custom.css' , __FILE__ ));
      wp_enqueue_style('B365_custom.css');
}
//add_action('admin_enqueue_scripts', 'B365_scripts');
function B365_scripts()
{

//   wp_register_script('jQuery', "https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js");
//   wp_enqueue_script('jQuery');


  // Include BootStrap JS
  wp_register_script('B365_bootstrap_min_js', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js');
  wp_enqueue_script('B365_bootstrap_min_js');

  // Include jQuery-dataTable_min_js
  wp_register_script('dataTable_min_js', 'https://cdn.datatables.net/1.10.12/js/jquery.dataTables.min.js');
  wp_enqueue_script('dataTable_min_js');


  // Include dataTables_bootstrap_min_js
  wp_register_script('dataTables_bootstrap_min_js', 'https://cdn.datatables.net/1.10.12/js/dataTables.bootstrap.min.js');
  wp_enqueue_script('dataTables_bootstrap_min_js');


  // Include dataTables_bootstrap_datepicker_js
  wp_register_script('dataTables_bootstrap_datepicker_js', "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/js/bootstrap-datepicker.min.js");
  wp_enqueue_script('dataTables_bootstrap_datepicker_js');


  // Include Custom JS
  wp_register_script('B365_custom_js', plugins_url('js/B365_custom.js' , __FILE__ ));
  wp_localize_script('B365_custom_js', 'event_Ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
  wp_enqueue_script('B365_custom_js');

  // Include BootStrap CSS
  wp_register_style('B365_bootstrap_min_css', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css');
  wp_enqueue_style('B365_bootstrap_min_css');

  //Include Bootstrap-datepicker css
  wp_register_style('B365_bootstrap_datepicker_css', "https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.7.1/css/bootstrap-datepicker3.standalone.min.css");
  wp_enqueue_style('B365_bootstrap_datepicker_css');


  // Include dataTables.bootstrap.min.css
  wp_register_style('dataTables_bootstrap_min_css', 'https://cdn.datatables.net/1.10.12/css/dataTables.bootstrap.min.css');
  wp_enqueue_style('dataTables_bootstrap_min_css');




  // Include font-awesome.min.css
  wp_register_style('font-awesome.min.css', "//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css");
  wp_enqueue_style('font-awesome.min.css');


    // Include B365_custom.css
    wp_register_style('B365_custom.css', plugins_url('css/B365_custom.css' , __FILE__ ));
    wp_enqueue_style('B365_custom.css');

}


register_activation_hook( __FILE__, 'B365_plugin_create_db' );
function B365_plugin_create_db() {
    // Create DB Here
    global $wpdb;
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'event_histories';
    $sql = "CREATE TABLE `$table_name` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_id` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `history_type` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `histories` text CHARACTER SET utf8mb4 DEFAULT NULL,
        PRIMARY KEY (id)
      ) $charset_collate;";
    dbDelta( $sql );

    $table_name = $wpdb->prefix . 'league_standing';
    $sql = "CREATE TABLE `$table_name` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `league_id` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `standing_type` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `league_name` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
        `rows` text CHARACTER SET utf8mb4 DEFAULT NULL,
        PRIMARY KEY (id)
      ) $charset_collate;";
    dbDelta($sql);

    $table_name = $wpdb->prefix . 'odds_summary';
    $sql = "CREATE TABLE `$table_name` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_id` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `site` varchar(50) CHARACTER SET utf8mb4 DEFAULT NULL,
        `matching_dir` int(11) DEFAULT NULL,
        `odds_update` text CHARACTER SET utf8mb4 DEFAULT NULL,
        `odds` text CHARACTER SET utf8mb4 DEFAULT NULL,
        PRIMARY KEY (id)
      ) $charset_collate;";
    dbDelta($sql);

    $table_name = $wpdb->prefix . 'tips';
    $sql = "CREATE TABLE `$table_name` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `event_id` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `league_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `league_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `time` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `match_teams` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
        `score` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `outcome` varchar(100) CHARACTER SET utf8mb4 DEFAULT NULL,
        `odds` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `bookmaker` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `description` text CHARACTER SET utf8mb4 DEFAULT NULL,
        PRIMARY KEY (id)
      ) $charset_collate;";
      dbDelta($sql);

      $table_name = $wpdb->prefix . 'upcoming_events';
      $sql = "CREATE TABLE `$table_name` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `day` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
        `event_id` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
        `league_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        `favorite` tinyint(1) NOT NULL DEFAULT 0,
        `sport_id` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
        `time` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
        `time_status` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
        `league` text CHARACTER SET utf8mb4 DEFAULT NULL,
        `home` text CHARACTER SET utf8mb4 DEFAULT NULL,
        `away` text CHARACTER SET utf8mb4 DEFAULT NULL,
        `ss` varchar(30) CHARACTER SET utf8mb4 DEFAULT NULL,
        `bet365_id` varchar(20) CHARACTER SET utf8mb4 DEFAULT NULL,
        `extra` text CHARACTER SET utf8mb4 DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (id)
      ) $charset_collate;";

      dbDelta($sql);

    copy( plugin_dir_url( __FILE__ ) . 'tiptemplate.php', get_stylesheet_directory() . '/tiptemplate.php');


}
function delete_plugin_database_tables(){
    global $wpdb;
    $tableArray = [
      $wpdb->prefix . "event_histories",
      $wpdb->prefix . "league_standing",
      $wpdb->prefix . "odds_summary",
      $wpdb->prefix . "tips",
      $wpdb->prefix . "upcoming_events",
   ];

  foreach ($tableArray as $tablename) {
     $wpdb->query("DROP TABLE IF EXISTS $tablename");
  }
}

register_uninstall_hook(__FILE__, 'delete_plugin_database_tables');

function B365_Dashboard_Tips()
{
    $tips = GetAllTips();

    ?>
        <div class="alert alert-success tip_success_message" style="display:none; width:15%; margin:auto;">
            <strong>Success</strong>
            </div>
        <div class="alert alert-danger tip_fail_message" style="display:none; width:15%; margin:auto;">
            <strong>Failed</strong>
        </div>

        <h1 style="text-align: center;">All Tips</h1>
        <div class="table-responsive">
               <table id="all_tips" class="table table-bordered hover">
                    <thead>
                         <tr>
                              <td>ID</td>
                              <td>League ID</td>
                              <td>Time</td>
                              <td>Match</td>
                              <td>Score</td>
                              <td>Spil</td>
                              <td>Odds</td>
                              <td>Bookmaker</td>
                              <!-- <td>Description</td> -->
                              <td>Action</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        usort($tips, function($a, $b){
                            if ($a->time > $b->time) return -1;
                            else return 1;
                        });

                        foreach($tips as $tip){
                            ?>
                            <tr tip_id ="<?php echo $tip->id; ?>">
                                <td><?php echo $tip->id; ?></td>
                                <td><?php echo $tip->league_id; ?></td>
                                <td><?php echo date('d/m - H:i', $tip->time + 3600);?></td>
                                <td><?php echo $tip->match_teams;?></td>
                                <td><input class="field_score" type="text" value="<?php echo $tip->score;?>"></td>
                                <td><input class="field_outcome" type="text" value="<?php echo $tip->outcome;?>"></td>
                                <td><input class="field_odds" type="text" value="<?php echo $tip->odds;?>"></td>
                                <td>
                                    <select class="bookmaker">
                                        <option value="" disabled selected hidden>Choose Bookmaker</option>
                                        <option value="Bet365"<?php if($tip->bookmaker=="Bet365") echo "selected"; ?>>Bet365</option>
                                        <option value="Betfair"<?php if($tip->bookmaker=="Betfair") echo "selected"; ?>>Betfair</option>
                                    </select>
                                </td>
                                <!-- <td>
                                    <textarea class="field_description" rows="3" cols="30"><?php echo trim($tip->description); ?></textarea>
                                </td> -->
                                <td>
                                    <button class="btn btn-primary updatetip">Update</button>
                                    <button class="btn btn-danger deletetip">Delete</button>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
               </table>
          </div>

          <div class="all_available_shortcodes">
                    <h2>All available Shotcodes</h2>
                    <h3>[tip id=2]</h3>
                    <h3>[frontend_tip_id id=3]</h3>
                    <h3>[all_frontend_tips]</h3>
                    <h3>[all_frontend_tips league_id=94]</h3>
                    <h3>[all_frontend_tips upcoming=true]</h3>
                    <h3>[all_frontend_tips league_id=94 upcoming=true]</h3>
          </div>
    <?php

}
function GetAllTips()
{
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tips"));
    return $results;

}
function GetEventOddsSummary($event_id)
{
    $url = "event/odds/summary?token=".b365_token."&event_id=$event_id";
    $response =apiRequest($url);
}
function GetUpcomingevents($day)
{

    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}upcoming_events WHERE day = $day"));

    $events = array();
    if (count($results) == 0)
    {
        $favorites = [];
        $upcoming_url = "events/upcoming?skip_esports=esoccer&sport_id=1&token=".b365_token."&day=$day&page=1";
        $response =apiRequest($upcoming_url);
        foreach($response->results as $event){
            $event->event_id = $event->id;

            $event->league_id = $event->league->id;
            if (!isset($favorites[$event->league_id])) {
                $res = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}upcoming_events WHERE league_id=$event->league_id LIMIT 1");
    
                if (count($res) == 0) $favorites[$event->league_id] = 0;
                else $favorites[$event->league_id] = $res[0]->favorite;
            }
            $event->favorite = $favorites[$event->league_id];

            $events[] = $event;
        }


        $pages = ceil($response->pager->total/$response->pager->per_page);

        for ($i = 2; $i<=$pages; $i++){
            $upcoming_url = "events/upcoming?skip_esports=esoccer&sport_id=1&token=".b365_token."&day=$day&page=".$i;
            $response =apiRequest($upcoming_url);
            foreach($response->results as $event){
                $event->event_id = $event->id;
                
                $event->league_id = $event->league->id;
                if (!isset($favorites[$event->league_id])) {
                    $res = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}upcoming_events WHERE league_id=$event->league_id LIMIT 1");
        
                    if (count($res) == 0) $favorites[$event->league_id] = 0;
                    else $favorites[$event->league_id] = $res[0]->favorite;
                }
                $event->favorite = $favorites[$event->league_id];

                $events[] = $event;
            }

        }




        SaveUpcomingEvents($events, $day);
    } else
    {
        foreach($results as $item)
        {
            $event = $item;
            $event->league = json_decode($event->league);
            $event->home = json_decode($event->home);
            $event->away = json_decode($event->away);
            $event->extra = json_decode($event->extra);
            $events[] = $event;
        }
    }
    usort($events, function ($a, $b){
        if ($a->favorite == $b->favorite) {
            if ($a->league->name == $b->league->name) {
                if ((int)$a->time == $b->time) return 0;
                return ((int)$a->time < (int)$b->time)?-1:1;
            }
            return ($a->league->name < $b->league->name)? -1: 1;
        } else {
            if ($a->favorite == 1) return -1;
            else return 1;
        }

    });
    return $events;


}

function SaveUpcomingEvents($events, $day)
{

    global $wpdb;
    foreach($events as $event)
    {

        $data = Array (
            'day' => $day,
            'event_id' => $event->event_id,
            'league_id' => $event->league_id,
            'favorite' => $event->favorite,
            'sport_id' => $event->sport_id,
            'time' => $event->time,
            'time_status' => $event->time_status,
            'league' => json_encode($event->league),
            'home' => json_encode($event->home),
            'away' => json_encode($event->away),
            'ss' => $event->ss,
            'bet365_id' => $event->bet365_id,
            'extra' => json_encode($event->extra),
            'created_at' =>  time(),
            'updated_at' =>  time(),
        );

        $res = $wpdb->insert ("{$wpdb->prefix}upcoming_events", $data);
        if ($res == false) {
            $wpdb->print_error();
            $wpdb->show_errors();
        }

    }

}
function AdminEventTemplate($events)
{

    ?>

    <thead>
    <!-- <tr>
        <th></th>
        <th></th>
    </tr> -->
    </thead>
    <tbody>
    <?php

    $displayed_league = array();
    foreach($events as $event){
        global $wpdb;
        if (time() > $event->time) continue;
        $favorite_starimg = "";
        $league_id = $event->league->id;

        if (array_search($event->league->id, $displayed_league) === false)
        {
            $league_flag = "https://assets.betsapi.com/v2/images/flags/".$event->league->cc.".svg";

            if ($event->favorite) {
                $favorite_starimg = plugin_dir_url( __FILE__ )."assets/images/star-gold.png";
            } else $favorite_starimg = plugin_dir_url( __FILE__ )."assets/images/star-gray.png";

            ?>
            <tr class="league_tr <?php if ($event->favorite) echo "star_league_tr";?>" league_id="<?php echo $event->league->id;?>">
                <td>
                    <img class="league_img" src="<?php echo $league_flag; ?>" alt="">
                    <h3 style="display:inline; padding:5px;font-size:16px;"><?php echo $event->league->name ?></h3>
                    <img class="favorite_league" src="<?php echo $favorite_starimg; ?>">
                </td>
            </tr>
            <?php
             $displayed_league[] = $event->league->id;
        }
        $game_time = date('H:i', $event->time + 3600);
        $game_teams = $event->home->name." - ".$event->away->name;

        ?>
            <tr class="game_time_teams" event_id="<?php echo $event->event_id;?>" time="<?php echo $event->time;?>" league_id="<?php echo $event->league->id;?>" league_name = "<?php echo $event->league->name; ?>">
                <td>
                    <div class="row">
                        <div class="col-sm-1 col-xs-2"><span><?php echo $game_time;  ?></span></div>
                        <div class="col-sm-11 col-xs-10 game_teams"><?php echo $game_teams;  ?></div>
                    </div>

                </td>
            </tr>
        <?php

    }
     ?>
    </tbody>

<?php
}
function my_print_error(){

    global $wpdb;
    $wpdb->print_error();

}
function B365_Dashboard(){
    $day = date('Ymd', strtotime(' +1 day'));
    //echo do_shortcode('[dotirating rating=3]');
    //echo do_shortcode('[tip id=2]');
    //echo do_shortcode('[frontend_tip_id id=3]');
    //echo do_shortcode('[all_frontend_tips]');
    //echo do_shortcode('[all_frontend_tips league_id=94]');
    //echo do_shortcode('[all_frontend_tips upcoming=true]');
    //echo get_stylesheet_directory();
    ?>

    <div id="loadingDiv">
            <i class="fa fa-refresh fa-spin"></i>
    </div>
    <div class="container">

        <h1 class="gametable_head">Kampprogram</h1>
        <!-- <p class="gametable_description">Shows upcoming soccer games  of  </p>  -->
        <div class="row">
            <div class="col-sm-1 col-xs-0" id="upcoming_events_date"></div>
            <div class="col-sm-8 col-xs-7" id="totalmathces"></div>
            <div class="input-group date col-sm-3 col-xs-5" data-provide="datepicker" id="datepicker1">
                    <input type="text" class="form-control">
                    <div class="input-group-addon">
                        <span class="glyphicon glyphicon-th"></span>
                    </div>
            </div>
        </div>
        <div class="table-responsive">
            <table id="upcoming_soccergames_table" class="table"  >
                <?php
                    //AdminEventTemplate($events);
                    ?>
            </table>
      </div>

      <div class="modal fade" id="matchModal" role="dialog">
          <div class="modal-dialog modal-lg">

            <!-- Modal content-->
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Opret spilforslag</h4>
              </div>
              <div class="modal-body">

                <div class="alert alert-success tip_success_message" style="display:none; width:15%; margin:auto;">
                        <strong>Success</strong>
                </div>
                <div class="alert alert-danger tip_fail_message" style="display:none; width:15%; margin:auto;">
                    <strong>Failed</strong>
                </div>

                 <div class="row modal_match_row">
                     <span class="col-sm-2 col-xs-2"></span>
                     <span class="col-sm-2 col-xs-3"><b>Dato</b></span>
                     <span class="col-sm-4 col-xs-4 modal_field_date"></span>
                 </div>
                 <div class="row modal_match_row">
                     <span class="col-sm-2 col-xs-2"></span>
                     <span class="col-sm-2 col-xs-3"><b>Kamp</b></span>
                     <span class="col-sm-4 col-xs-4 modal_field_match"></span>
                 </div>
                 <div class="row modal_match_row">
                    <span class="col-sm-2 col-xs-2"></span>
                     <span class="col-sm-2 col-xs-3"><b>Spil</b></span>
                     <input class="col-sm-4 col-xs-4 modal_field_outcome" type="text" >
                 </div>
                 <div class="row modal_match_row">
                    <span class="col-sm-2 col-xs-2"></span>
                     <span class="col-sm-2 col-xs-3"><b>Odds</b></span>
                     <input class="col-sm-4 col-xs-4 modal_field_odds" type="text">
                 </div>

                 <div class="row modal_match_row">
                    <span class="col-sm-2 col-xs-2"></span>
                    <span class="col-sm-2 col-xs-3"><b>Bookmaker</b></span>
                    <select class="col-sm-6 col-xs-7 modal_bookmaker">
                        <option value="" disabled selected hidden>Vælg bookmaker</option><option value="Bet365">Bet365</option><option value="Betfair">Betfair</option>
                    </select>
                 </div>
                 <!--<div class="row modal_match_row">
                     <span class="col-sm-2 col-xs-2"></span>
                     <textarea class="col-sm-8 col-xs-8 modal_field_description" rows="5" cols="70" placeholder="Description"></textarea>
                 </div> !-->
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="createTips"> <i class="fa fa-spinner fa-spin"></i>Opret spilforslag</button>
              </div>
            </div>

          </div>
        </div>
    </div>

    <?php
}

?>
