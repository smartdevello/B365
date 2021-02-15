<?php
function GetOddsSummary($event_id, $site){

    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}odds_summary` WHERE event_id = $event_id AND site = '$site'"));

    if (count($results) == 0) {
        $endpoint = "event/odds/summary?token=" .b365_token."&event_id=".$event_id;
        $results = apiRequest($endpoint);
        $results = $results->results->{$site};
        saveOddsSummary($results, $event_id, $site);
    } else {
        $results = $results[0];
        $results->odds_update = json_decode($results->odds_update);
        $results->odds = json_decode($results->odds);
    }
    return $results;
}

function GetEventHistory($event_id, $history_type){

    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}event_histories` WHERE event_id = $event_id AND history_type = '$history_type'"));

    if (count($results) == 0) {
        $endpoint = "event/history?token=".b365_token."&event_id=$event_id&qty=20";
        $results = apiRequest($endpoint, 1);
        $results = $results->results->{$history_type};
        saveEventHistory($results, $event_id, $history_type);
    } else {
        $results = $results[0];
        $results = json_decode($results->histories);
    }
    return $results;
}
function GetLeagueTable($league_id, $standing_type){

    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}league_standing` WHERE league_id = $league_id AND standing_type = '$standing_type'"));
    if (count($results) == 0) {

        $endpoint = "league/table?token=".b365_token."&league_id=$league_id";
        $results = apiRequest($endpoint, 1);

        $results = $results->results->{$standing_type}->tournaments[0];
        saveLeagueTable($results, $league_id, $standing_type);
    } else {
        $results = $results[0];
        $results->rows = json_decode($results->rows);
    }
    return $results;
}


function saveLeagueTable($results, $league_id, $standing_type)
{
    global $wpdb;
    $data = Array (
        'league_id' => $league_id,
        'standing_type' => $standing_type,
        'league_name' => $results->name,
        'rows' => json_encode($results->rows),
    );

    $res = $wpdb->insert ("{$wpdb->prefix}league_standing", $data);
    if ($res == false) {
        $wpdb->print_error();
        $wpdb->show_errors();
    }
}


function saveEventHistory($results, $event_id, $history_type)
{
    global $wpdb;
    $data = Array (
        'event_id' => $event_id,
        'history_type' => $history_type,
        'histories' => json_encode($results),
    );

    $res = $wpdb->insert ("{$wpdb->prefix}event_histories", $data);
    if ($res == false) {
        $wpdb->print_error();
        $wpdb->show_errors();
    }

}


function saveOddsSummary($results, $event_id, $site)
{
    global $wpdb;
    $data = Array (
        'event_id' => $event_id,
        'site' => $site,
        'matching_dir' => $results->matching_dir,
        'odds_update' => json_encode($results->odds_update),
        'odds' => json_encode($results->odds),
    );

    $res = $wpdb->insert ("{$wpdb->prefix}odds_summary", $data);
    if ($res == false) {
        $wpdb->print_error();
        $wpdb->show_errors();
    }
}
function all_frontend_tips($atts = array()){

    // set up default parameters
    extract(shortcode_atts(array(
        'upcoming' => false,
        'league_id' => -1,
        ), $atts));


    global $wpdb;
    $tips = $wpdb->get_results($wpdb->prepare("SELECT id, league_id, time, league_name  FROM `{$wpdb->prefix}tips`"));
    $current_time = time();

    usort($tips, function($a, $b){
            if ($a->time == $b->time) return 0;
            return ($a->time < $b->time)? -1:1;
    });

    if ($upcoming !== false) {
        foreach($tips as $row){
            if ($current_time < $row->time) {
                if ($league_id !== -1){
                    if ($league_id == $row->league_id)  do_shortcode("[frontend_tip_id id=$row->id]");
                } else do_shortcode("[frontend_tip_id id=$row->id]");

            }
        }
    } else {
        ?>
        <div class="alltips">
            <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#upcoming_tips">Upcoming</a></li>
                    <li><a data-toggle="tab" href="#ended_tips">Ended</a></li>
            </ul>
            <div class="tab-content">
                <div id="upcoming_tips" class="tab-pane fade in active" >
                <?php
                    foreach($tips as $row){
                        if ($current_time < $row->time) {
                            if ($league_id !== -1) {
                                if ($league_id == $row->league_id)  do_shortcode("[frontend_tip_id id=$row->id]");
                            } else do_shortcode("[frontend_tip_id id=$row->id]");
                        }
                    }
                ?>
                </div>
                <div id="ended_tips" class="tab-pane fade">
                <?php
                    usort($tips, function($a, $b){
                        if ($a->time == $b->time) return 0;
                        return ($a->time < $b->time)? 1:-11;
                    });
                    foreach($tips as $row){
                        if ($current_time > $row->time) {
                            if ($league_id !== -1) {
                                if ($league_id == $row->league_id) do_shortcode("[frontend_tip_id id=$row->id]");
                            } else do_shortcode("[frontend_tip_id id=$row->id]");
                        }
                    }
                ?>
                </div>
            <div>
        </div>
        <?php
    }

}
add_shortcode('all_frontend_tips', 'all_frontend_tips');
function frontend_tip_id($atts = array()){
    // set up default parameters
    extract(shortcode_atts(array(
        'id' => '2'
        ), $atts));

    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}tips` WHERE id = $id"));
    $tip = $results[0];
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}upcoming_events WHERE event_id={$tip->event_id}"));
    $event = $results[0];


    $event->league = json_decode($event->league);
    $event->home = json_decode($event->home);
    $event->away = json_decode($event->away);
    $event->extra = json_decode($event->extra);


    $league_id = $event->league->id;
    $league_flag = "https://assets.betsapi.com/v2/images/flags/" . $event->league->cc . ".svg";
    $home_flag = trim("https://assets.b365api.com/images/team/m/" . $event->home->image_id. ".png");
    $away_flag = trim("https://assets.b365api.com/images/team/m/" . $event->away->image_id. ".png");
    ?>
    <div class="tips">
        <div class="row league_time">
            <div class="col-sm-8 col-xs-8 league_img_name">
                    <img class="league_img" src="<?php echo $league_flag; ?>" alt="">
                    <h4  class="league_name"><?php echo $event->league->name ?></h4>
            </div>
            <div class="col-sm-4 col-xs-4 tip_match_time">
                <h4> <?php echo date("d/m/Y H:i", $event->time + 3600); ?></h4>
            </div>
        </div>
        <div class="row tip_match_teams">
            <h1 > <?php  echo $tip->match_teams; ?></h1>
        </div>

        <div class="row tip_team_flags">
            <div class="col-sm-4 col-xs-4 tip_home_img">
                <img  src="<?php echo $home_flag; ?>" alt="">
            </div>
            <div class="col-sm-4 col-xs-4  tip_score">
                <?php
                if (!empty($tip->score)) {
                    $s = explode('-', $tip->score);
                    $home_score = trim($s[0]);
                    $away_score = trim($s[1]);
                    echo "<h1>$home_score  -  $away_score</h1>";
                } else echo "<h3>vs</h3>";
                ?>
            </div>
            <div class="col-sm-4 col-xs-4  tip_away_img">
                <img  src="<?php echo $away_flag; ?>" alt="">
            </div>
        </div>

        <div class="row bet_bookmaker">
            <div class="col-sm-3 col-xs-3"></div>
            <div class="col-sm-6 col-xs-6 tip_odds_val">
                <?php if(!empty($tip->outcome) && !empty($tip->odds)) {
                    ?>
                    <h3><?php echo $tip->outcome; ?></h3>
                    <h3>@</h3>
                    <h3><?php echo $tip->odds; ?></h3>
                    <?php
                }?>

            </div>
            <div class="col-sm-3 col-xs-3"></div>
        </div>
        <?php if (!empty($tip->odds)) {?>
        <div class="row bet_bookmaker">
            <div class="col-sm-3 col-xs-3"></div>
            <div class="col-sm-6 col-xs-6 tip_odds_val">
                <?php
                    if ($tip->bookmaker == "Bet365") {
                        ?>
                        <a href="https://footballpredictions365.com/go/bet365" target="_blank">
                            <img src="https://footballpredictions365.com/wp-content/uploads/2021/01/Bet365-logo.png" alt="Bet365">
                        </a>
                        <?php
                    } else if ($tip->bookmaker == "Betfair") {
                        ?>
                        <a href="https://footballpredictions365.com/go/betfair" target="_blank">
                            <img src="https://footballpredictions365.com/wp-content/uploads/2021/01/Betfair-logo.png" alt="Betfair">
                        </a>
                        <?php
                    }
                ?>
            </div>
            <div class="col-sm-3 col-xs-3"></div>
        </div>
        <?php }

        $link = site_url()."/football-prediction/".$event->home->name."-".$event->away->name."/".date("d-m", $event->time)."/".date("Y", $event->time)."/".$tip->id;
        ?>
        <div class="row view_status">
            <span class="col-sm-2 col-xs-2"></span>
            <a class="col-sm-8 col-xs-8" href = "<?php echo $link; ?> ">VIEW STATS</a>
            <span class="col-sm-2 col-xs-2"></span>
        </div>
        




    </div>
    <?php
}
add_shortcode('frontend_tip_id', 'frontend_tip_id');
function matchtip_function($atts = array())
{
    // set up default parameters
    extract(shortcode_atts(array(
        'id' => '2'
        ), $atts));

    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$wpdb->prefix}tips` WHERE id = $id"));
    $tip = $results[0];
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}upcoming_events WHERE event_id={$tip->event_id}"));
    $event = $results[0];

    $event->league = json_decode($event->league);
    $event->home = json_decode($event->home);
    $event->away = json_decode($event->away);
    $event->extra = json_decode($event->extra);

    $league_id = $event->league->id;
    $league_flag = "https://assets.betsapi.com/v2/images/flags/" . $event->league->cc . ".svg";
    $home_flag = trim("https://assets.b365api.com/images/team/m/" . $event->home->image_id. ".png");
    $away_flag = trim("https://assets.b365api.com/images/team/m/" . $event->away->image_id. ".png");

    $bet365_odds = GetOddsSummary($tip->event_id, "Bet365");
    $betfair_odds = GetOddsSummary($tip->event_id, "BetFair");

    $h2h_histories = GetEventHistory($tip->event_id, "h2h");
    $home_histories = GetEventHistory($tip->event_id, "home");
    $away_histories = GetEventHistory($tip->event_id, "away");

    $overall_leaguestanding = GetLeagueTable($league_id, "overall");
    $home_leaguestanding = GetLeagueTable($league_id, "home");
    $away_leaguestanding = GetLeagueTable($league_id, "away");

    ?>
    <div class="alltips  tips">
        <div class="row league_time">
            <div class="col-sm-8 col-xs-8 league_img_name">
                    <img class="league_img" src="<?php echo $league_flag; ?>" alt="">
                    <h4 class="league_name" style=""><?php echo $event->league->name ?></h4>
            </div>
            <div class="col-sm-4 col-xs-4 tip_match_time">
                <h4> <?php echo date("d/m/Y H:i", $event->time + 3600); ?></h4>
            </div>
        </div>
        <div class="row tip_match_teams">
            <h1 > <?php  echo $tip->match_teams; ?></h1>
        </div>

        <div class="row">
            <div class="col-sm-4 col-xs-4 tip_home_img">
                <img  src="<?php echo $home_flag; ?>" alt="">
            </div>
            <div class="col-sm-4 col-xs-4 tip_score">
                <?php
                if (!empty($tip->score)) {
                    $s = explode('-', $tip->score);
                    $home_score = trim($s[0]);
                    $away_score = trim($s[1]);
                    echo "<h1>$home_score&emsp; - &emsp; $away_score</h1>";
                } else echo "<h3>vs</h3>";
                ?>

            </div>
            <div class="col-sm-4 col-xs-4 tip_away_img">
                <img  src="<?php echo $away_flag; ?>" alt="">
            </div>
        </div>
        <div class="row bet_bookmaker">
            <div class="col-sm-3 col-xs-3"></div>
            <div class="col-sm-6 col-xs-6 tip_odds_val">
                <?php if(!empty($tip->outcome) && !empty($tip->odds)) {
                    ?>
                    <h3><?php echo $tip->outcome; ?></h3>
                    <h3>@</h3>
                    <h3><?php echo $tip->odds; ?></h3>
                    <?php
                }?>
            </div>
            <div class="col-sm-3 col-xs-3"></div>
        </div>
        <div class="row">
            <div class="col-sm-3 col-xs-3"></div>
            <div class="col-sm-6 col-xs-6 tip_odds_val">
                <?php
                    if ($tip->bookmaker == "Bet365") {
                        ?>
                        <a href="https://footballpredictions365.com/go/bet365" target="_blank">
                            <img src="https://footballpredictions365.com/wp-content/uploads/2021/01/Bet365-logo.png" alt="Bet365">
                        </a>
                        <?php
                    } else if ($tip->bookmaker == "Betfair") {
                        ?>
                        <a href="https://footballpredictions365.com/go/betfair" target="_blank">
                            <img src="https://footballpredictions365.com/wp-content/uploads/2021/01/Betfair-logo.png" alt="Betfair">
                        </a>
                        <?php
                    }
                ?>
            </div>
            <div class="col-sm-3 col-xs-3"></div>
        </div>
        <div class="row tip_tabs">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#odds">ODDS</a></li>
                <li><a data-toggle="tab" href="#h2h">H2H</a></li>
                <li><a data-toggle="tab" href="#leaguestanding">TABLE</a></li>
            </ul>
            <div class="tab-content">
                <div id="odds" class="tab-pane fade in active" >
                    <table class="table">
                        <tbody>
                            <tr class="odds_header">
                                <th></th>
                                <th>1</th>
                                <th>X</th>
                                <th>2</th>
                            </tr>
                            <?php if (!empty($bet365_odds->odds->end->{"1_1"})) { ?>
                            <tr class="odds_row">
                                <td class="odds_row_img">
                                    <a href="https://footballpredictions365.com/go/bet365" target="_blank">
                                        <img src="https://footballpredictions365.com/wp-content/uploads/2021/01/Bet365-logo.png" alt="Bet365">
                                    </a>
                                </td>
                                <td class="odds_row_win">
                                  <a href="https://footballpredictions365.com/go/bet365" target="_blank">
                                    <?php 
                                        echo number_format($bet365_odds->odds->end->{"1_1"}->home_od, 2);
                                     ?>
                                </td>
                                <td class="odds_row_draw">
                                  <a href="https://footballpredictions365.com/go/bet365" target="_blank">
                                    <?php echo number_format($bet365_odds->odds->end->{"1_1"}->draw_od,2); ?>
                                </td>
                                <td class="odds_row_loose">
                                  <a href="https://footballpredictions365.com/go/bet365" target="_blank">
                                    <?php echo number_format($bet365_odds->odds->end->{"1_1"}->away_od,2); ?>
                                </td>
                            </tr>
                            <?php } ?>
                            <?php if (!empty($betfair_odds->odds->end->{"1_1"})) { ?>
                            <tr class="odds_row">
                                <td class="odds_row_img">
                                    <a href="https://footballpredictions365.com/go/betfair" target="_blank">
                                        <img src="https://footballpredictions365.com/wp-content/uploads/2021/01/Betfair-logo.png" alt="Betfair">
                                    </a>
                                </td>
                                <td class="odds_row_win">
                                  <a href="https://footballpredictions365.com/go/betfair" target="_blank">
                                    <?php echo number_format($betfair_odds->odds->end->{"1_1"}->home_od,2); ?>
                                </td>
                                <td class="odds_row_draw">
                                  <a href="https://footballpredictions365.com/go/betfair" target="_blank">
                                    <?php echo number_format($betfair_odds->odds->end->{"1_1"}->draw_od,2); ?>
                                </td>
                                <td class="odds_row_loose">
                                  <a href="https://footballpredictions365.com/go/betfair" target="_blank">
                                    <?php echo number_format($betfair_odds->odds->end->{"1_1"}->away_od,2); ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div id="h2h" class="tab-pane fade">
                    <div class="col-sm-12 col-xs-12 home_away_history">
                        <div class="col-sm-6 col-xs-12">
                            <div class="team_name_flag">
                                <img src="<?php echo $home_flag;?>">
                                <h4><?php echo $event->home->name; ?></h4>
                            </div>
                            
                            <ul class="nav nav-tabs">
                                <li class="active"><a data-toggle="tab" href="#hometeam_all">All</a></li>
                                <li><a data-toggle="tab" href="#hometeam_home">Home</a></li>
                                <li><a data-toggle="tab" href="#hometeam_away">Away</a></li>
                            </ul>
                            <div class="tab-content">
                                <div id="hometeam_all" class="tab-pane fade in active" >
                                    <table class="table h2h_table">
                                        <tbody>
                                            <tr style="display: none;"></tr>
                                            <tr class="h2h_data">
                                                <td>
                                                    <table class="table">
                                                        <tbody>
                                                            <?php
                                                            if (is_array($home_histories) || is_object($home_histories)) {
                                                                $team_cnt = 0;
                                                                foreach($home_histories as $history){
                                                                    $s = explode('-', $history->ss);
                                                                    $team_cnt++;
                                                                    ?>
                                                                        <tr <?php if ($team_cnt > 10) echo "class=\"default_hidden\"";?>>
                                                                            <td><?php echo date('d/m', $history->time); ?></td>
                                                                            <td>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] > $s[1]) echo "<strong>" . $history->home->name ."</strong>";
                                                                                        else echo $history->home->name;
                                                                                    ?>
                                                                                </span>
                                                                                <span> vs </span>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] < $s[1]) echo "<strong>". $history->away->name. "</strong>";
                                                                                        else echo $history->away->name;

                                                                                    ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $history->ss;?>
                                                                            </td>
                                                                            <td>
                                                                                <?php
                                                                                    if ($s[0] == $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/draw.svg";
                                                                                    else if ($s[0] > $s[1]) {
                                                                                        if ($event->home->name == $history->home->name) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/win.svg";
                                                                                        else $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/loss.svg";
                                                                                    }
                                                                                    else {
                                                                                        if ($event->home->name == $history->home->name) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/loss.svg";
                                                                                        else $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/win.svg";

                                                                                    }
                                                                                 ?>
                                                                                 <img src="<?php echo $gameresult_img; ?>">
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                }
                                                                if ($team_cnt > 10) {
                                                                    ?>
                                                                    <tr>
                                                                        <td> </td>
                                                                        <td><button class = "showmore_button">Show More</button></td>
                                                                        <td> </td>
                                                                        <td> </td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="hometeam_home" class="tab-pane fade">
                                    <table class="table h2h_table">
                                        <tbody>
                                            <tr style="display: none;"></tr>
                                            <tr class="h2h_data">
                                                <td>
                                                    <table class="table">
                                                        <tbody>
                                                            <?php
                                                            if (is_array($home_histories ) || is_object($home_histories)) {
                                                                $team_cnt = 0;
                                                                foreach($home_histories as $history){
                                                                    if ($history->home->name != $event->home->name) continue;
                                                                    $s = explode('-', $history->ss);
                                                                    $team_cnt ++;
                                                                    ?>
                                                                        <tr <?php if($team_cnt > 10) echo "class=\"default_hidden\""; ?>>
                                                                            <td><?php echo date('d/m', $history->time); ?></td>
                                                                            <td>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] > $s[1]) echo "<strong>" . $history->home->name ."</strong>";
                                                                                        else echo $history->home->name;
                                                                                    ?>
                                                                                </span>
                                                                                <span> vs </span>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] < $s[1]) echo "<strong>" . $history->away->name ."</strong>";
                                                                                        else echo $history->away->name;
                                                                                    ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $history->ss;?>
                                                                            </td>
                                                                            <td>
                                                                                <?php
                                                                                    if ($s[0] == $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/draw.svg";
                                                                                    else if ($s[0] > $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/win.svg";
                                                                                    else $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/loss.svg";
                                                                                 ?>
                                                                                 <img src="<?php echo $gameresult_img; ?>">
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                }

                                                                if ($team_cnt > 10) {
                                                                    ?>
                                                                    <tr>
                                                                        <td> </td>
                                                                        <td><button class = "showmore_button">Show More</button></td>
                                                                        <td> </td>
                                                                        <td> </td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="hometeam_away" class="tab-pane fade">
                                    <table class="table h2h_table">
                                        <tbody>
                                            <tr style="display: none;"></tr>
                                            <tr class="h2h_data">
                                                <td>
                                                    <table class="table">
                                                        <tbody>
                                                            <?php
                                                            if (is_array($home_histories ) || is_object($home_histories)) {
                                                                $team_cnt = 0;
                                                                foreach($home_histories as $history){
                                                                    if ($history->away->name != $event->home->name) continue;
                                                                    $s = explode('-', $history->ss);
                                                                    $team_cnt++;

                                                                    ?>
                                                                        <tr <?php if($team_cnt>10) echo "class=\"default_hidden\"";?>>
                                                                            <td><?php echo date('d/m', $history->time); ?></td>
                                                                            <td>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] > $s[1]) echo "<strong>" . $history->home->name ."</strong>";
                                                                                        else echo $history->home->name;
                                                                                    ?>
                                                                                </span>
                                                                                <span> vs </span>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] < $s[1]) echo "<strong>". $history->away->name. "</strong>";
                                                                                        else echo $history->away->name;

                                                                                    ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $history->ss;?>
                                                                            </td>
                                                                            <td>
                                                                                <?php
                                                                                    if ($s[0] == $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/draw.svg";
                                                                                    else if ($s[0] > $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/loss.svg";
                                                                                    else $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/win.svg";
                                                                                 ?>
                                                                                 <img src="<?php echo $gameresult_img; ?>">
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                }
                                                                if ($team_cnt > 10) {
                                                                    ?>
                                                                    <tr>
                                                                        <td> </td>
                                                                        <td><button class = "showmore_button">Show More</button></td>
                                                                        <td> </td>
                                                                        <td> </td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xs-12">
                            <div class="team_name_flag">
                                <img src="<?php echo $away_flag;?>">
                                <h4><?php echo $event->away->name; ?></h4>
                            </div>
                            <ul class="nav nav-tabs">
                                <li class="active"><a data-toggle="tab" href="#awayteam_all">All</a></li>
                                <li><a data-toggle="tab" href="#awayteam_home">Home</a></li>
                                <li><a data-toggle="tab" href="#awayteam_away">Away</a></li>
                            </ul>
                            <div class="tab-content">
                                <div id="awayteam_all" class="tab-pane fade in active" >
                                    <table class="table h2h_table">
                                        <tbody>
                                            <tr style="display: none;"></tr>
                                            <tr class="h2h_data">
                                                <td>
                                                    <table class="table">
                                                        <tbody>
                                                            <?php
                                                            if (is_array($away_histories ) || is_object($away_histories)) {
                                                                $team_cnt = 0;
                                                                foreach($away_histories as $history){
                                                                    $s = explode('-', $history->ss);
                                                                    $team_cnt ++;

                                                                    ?>
                                                                        <tr <?php if($team_cnt>10) echo "class=\"default_hidden\"";?>>
                                                                            <td><?php echo date('d/m', $history->time); ?></td>
                                                                            <td>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] > $s[1]) echo "<strong>" . $history->home->name ."</strong>";
                                                                                        else echo $history->home->name;
                                                                                    ?>
                                                                                </span>
                                                                                <span> vs </span>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] < $s[1]) echo "<strong>". $history->away->name. "</strong>";
                                                                                        else echo $history->away->name;

                                                                                    ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $history->ss;?>
                                                                            </td>
                                                                            <td>
                                                                            <?php
                                                                                    if ($s[0] == $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/draw.svg";
                                                                                    else if ($s[0] > $s[1]) {
                                                                                        if ($event->away->name == $history->home->name) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/win.svg";
                                                                                        else $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/loss.svg";
                                                                                    }
                                                                                    else {
                                                                                        if ($event->away->name == $history->home->name) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/loss.svg";
                                                                                        else $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/win.svg";

                                                                                    }
                                                                                 ?>
                                                                                 <img src="<?php echo $gameresult_img; ?>">
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                }
                                                                if ($team_cnt > 10) {
                                                                    ?>
                                                                    <tr>
                                                                        <td> </td>
                                                                        <td><button class = "showmore_button">Show More</button></td>
                                                                        <td> </td>
                                                                        <td> </td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="awayteam_home" class="tab-pane fade">
                                    <table class="table h2h_table">
                                        <tbody>
                                            <tr style="display: none;"></tr>
                                            <tr class="h2h_data">
                                                <td>
                                                    <table class="table">
                                                        <tbody>
                                                            <?php
                                                            if (is_array($away_histories ) || is_object($away_histories)) {
                                                                $team_cnt = 0;
                                                                foreach($away_histories as $history){
                                                                    if ($history->home->name != $event->away->name) continue;
                                                                    $s = explode('-', $history->ss);
                                                                    $team_cnt++;

                                                                    ?>
                                                                        <tr <?php if($team_cnt>10) echo "class=\"default_hidden\"";?>>
                                                                            <td><?php echo date('d/m', $history->time); ?></td>
                                                                            <td>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] > $s[1]) echo "<strong>" . $history->home->name ."</strong>";
                                                                                        else echo $history->home->name;
                                                                                    ?>
                                                                                </span>
                                                                                <span> vs </span>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] < $s[1]) echo "<strong>". $history->away->name. "</strong>";
                                                                                        else echo $history->away->name;

                                                                                    ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $history->ss;?>
                                                                            </td>
                                                                            <td>
                                                                                <?php
                                                                                    if ($s[0] == $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/draw.svg";
                                                                                    else if ($s[0] > $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/win.svg";
                                                                                    else $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/loss.svg";
                                                                                 ?>
                                                                                 <img src="<?php echo $gameresult_img; ?>">
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                }
                                                                if ($team_cnt > 10) {
                                                                    ?>
                                                                    <tr>
                                                                        <td> </td>
                                                                        <td><button class = "showmore_button">Show More</button></td>
                                                                        <td> </td>
                                                                        <td> </td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div id="awayteam_away" class="tab-pane fade">
                                    <table class="table h2h_table">
                                        <tbody>
                                            <tr style="display: none;"></tr>
                                            <tr class="h2h_data">
                                                <td>
                                                    <table class="table">
                                                        <tbody>
                                                            <?php
                                                            if (is_array($away_histories ) || is_object($away_histories)) {
                                                                $team_cnt = 0;
                                                                foreach($away_histories as $history){
                                                                    if ($history->away->name != $event->away->name) continue;
                                                                    $s = explode('-', $history->ss);
                                                                    $team_cnt++;

                                                                    ?>
                                                                        <tr <?php if($team_cnt>10) echo "class=\"default_hidden\"";?>>
                                                                            <td><?php echo date('d/m', $history->time); ?></td>
                                                                            <td>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] > $s[1]) echo "<strong>" . $history->home->name ."</strong>";
                                                                                        else echo $history->home->name;
                                                                                    ?>
                                                                                </span>
                                                                                <span> vs </span>
                                                                                <span>
                                                                                    <?php
                                                                                        if ($s[0] < $s[1]) echo "<strong>". $history->away->name. "</strong>";
                                                                                        else echo $history->away->name;

                                                                                    ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php echo $history->ss;?>
                                                                            </td>
                                                                            <td>
                                                                                <?php
                                                                                    if ($s[0] == $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/draw.svg";
                                                                                    else if ($s[0] > $s[1]) $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/loss.svg";
                                                                                    else $gameresult_img  = plugin_dir_url( __FILE__ )."assets/images/win.svg";
                                                                                 ?>
                                                                                 <img src="<?php echo $gameresult_img; ?>">
                                                                            </td>
                                                                        </tr>
                                                                    <?php
                                                                }
                                                                if ($team_cnt > 10) {
                                                                    ?>
                                                                    <tr>
                                                                        <td> </td>
                                                                        <td><button class = "showmore_button">Show More</button></td>
                                                                        <td> </td>
                                                                        <td> </td>
                                                                    </tr>
                                                                    <?php
                                                                }
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-xs-12">
                        <table class="table h2h_table" id="twoteams_history">
                            <tbody>
                                <tr>
                                    <td>H2H</td>
                                </tr>
                                <tr class="h2h_data">
                                    <td>
                                        <table class="table">
                                            <tbody>
                                                <?php
                                                if (is_array($h2h_histories ) || is_object($h2h_histories)) {
                                                    $team_cnt = 0;
                                                    foreach($h2h_histories as $history){
                                                        $s = explode('-', $history->ss);
                                                        $team_cnt++;

                                                        ?>
                                                            <tr <?php if($team_cnt>5) echo "class=\"default_hidden\"";?>>
                                                                <td><?php echo date('d/m', $history->time); ?></td>
                                                                <td>
                                                                    <span>
                                                                        <?php
                                                                            if ($s[0] > $s[1]) echo "<strong>" . $history->home->name ."</strong>";
                                                                            else echo $history->home->name;
                                                                         ?>
                                                                    </span>
                                                                    <span> vs </span>
                                                                    <span>
                                                                        <?php
                                                                            if ($s[0] < $s[1]) echo "<strong>". $history->away->name. "</strong>";
                                                                            else echo $history->away->name;

                                                                        ?>
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <?php echo $history->ss;?>
                                                                </td>
                                                            </tr>
                                                        <?php
                                                    }
                                                    if ($team_cnt > 10) {
                                                        ?>
                                                        <tr>
                                                            <td> </td>
                                                            <td><button class = "showmore_button">Show More</button></td>
                                                            <td> </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="leaguestanding" class="tab-pane fade">
                    <?php if (is_array($overall_leaguestanding->rows) || is_object($overall_leaguestanding->rows)) {?>
                    <div class="col-sm-12 col-xs-12">
                        <ul class="nav nav-tabs">
                            <li class="active"><a data-toggle="tab" href="#league_standing_overall_tab">Overall</a></li>
                            <li><a data-toggle="tab" href="#league_standing_home_tab">Home</a></li>
                            <li><a data-toggle="tab" href="#league_standing_away_tab">Away</a></li>
                        </ul>
                        <div class="tab-content">
                            <div id="league_standing_overall_tab" class="tab-pane fade in active">
                                <div class="team_name_flag">
                                    <img src="<?php echo $league_flag;?>">
                                    <h4><?php echo $event->league->name; ?></h4>
                                </div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Team</th>
                                            <th>W</th>
                                            <th>D</th>
                                            <th>L</th>
                                            <th>G</th>
                                            <th>P</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (is_array($overall_leaguestanding->rows) || is_object($overall_leaguestanding->rows)) {
                                            foreach($overall_leaguestanding->rows as $team){
                                                $current_team_flag = false;
                                                if ($team->team->name == $event->home->name || $team->team->name == $event->away->name) $current_team_flag = true;
                                                ?>
                                                <tr class = "<?php if ($current_team_flag) echo "current_team_highlight";?>">
                                                    <td scope="row"><?php echo $team->pos; ?></td>
                                                    <td><?php echo $team->team->name; ?></td>
                                                    <td><?php echo $team->win; ?></td>
                                                    <td><?php echo $team->draw; ?></td>
                                                    <td><?php echo $team->loss; ?></td>
                                                    <td>
                                                        <span><?php echo $team->goalsfor; ?></span>
                                                        <span>-</span>
                                                        <span><?php echo $team->goalsagainst; ?></span>
                                                    </td>
                                                    <td><?php echo $team->points; ?></td>
                                                </tr>
                                                <?php
                                            }
                                        }

                                        ?>

                                    </tbody>
                                </table>
                            </div>
                            <div id="league_standing_home_tab" class="tab-pane fade">
                                <div class="team_name_flag">
                                    <img src="<?php echo $league_flag;?>">
                                    <h4><?php echo $event->league->name; ?></h4>
                                </div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Team</th>
                                            <th>W</th>
                                            <th>D</th>
                                            <th>L</th>
                                            <th>G</th>
                                            <th>P</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (is_array($home_leaguestanding->rows ) || is_object($home_leaguestanding->rows )) {
                                            foreach($home_leaguestanding->rows as $team){
                                                $current_team_flag = false;
                                                if ($team->team->name == $event->home->name) $current_team_flag = true;
                                                ?>
                                                <tr class = "<?php if ($current_team_flag) echo "current_team_highlight";?>">
                                                    <td scope="row"><?php echo $team->pos; ?></td>
                                                    <td><?php echo $team->team->name; ?></td>
                                                    <td><?php echo $team->win; ?></td>
                                                    <td><?php echo $team->draw; ?></td>
                                                    <td><?php echo $team->loss; ?></td>
                                                    <td>
                                                        <span><?php echo $team->goalsfor; ?></span>
                                                        <span>-</span>
                                                        <span><?php echo $team->goalsagainst; ?></span>
                                                    </td>
                                                    <td><?php echo $team->points; ?></td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                        ?>

                                    </tbody>
                                </table>
                            </div>

                            <div id="league_standing_away_tab" class="tab-pane fade">
                                <div class="team_name_flag">
                                    <img src="<?php echo $league_flag;?>">
                                    <h4><?php echo $event->league->name; ?></h4>
                                </div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Team</th>
                                            <th>W</th>
                                            <th>D</th>
                                            <th>L</th>
                                            <th>G</th>
                                            <th>P</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (is_array($away_leaguestanding->rows ) || is_object($away_leaguestanding->rows )) {
                                            foreach($away_leaguestanding->rows as $team){
                                                $current_team_flag = false;
                                                if ($team->team->name == $event->away->name) $current_team_flag = true;
                                                ?>
                                                <tr class = "<?php if ($current_team_flag) echo "current_team_highlight";?>">
                                                    <td scope="row"><?php echo $team->pos; ?></td>
                                                    <td><?php echo $team->team->name; ?></td>
                                                    <td><?php echo $team->win; ?></td>
                                                    <td><?php echo $team->draw; ?></td>
                                                    <td><?php echo $team->loss; ?></td>
                                                    <td>
                                                        <span><?php echo $team->goalsfor; ?></span>
                                                        <span>-</span>
                                                        <span><?php echo $team->goalsagainst; ?></span>
                                                    </td>
                                                    <td><?php echo $team->points; ?></td>
                                                </tr>
                                                <?php
                                            }
                                        }
                                        ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                    <?php }?>
                </div>
            </div>
        </div>
    </div>
    <?php

}

add_shortcode('tip', 'matchtip_function');
