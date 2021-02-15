jQuery(document).ready(function ($) {
    var datepicker = $('#datepicker1').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true,
        weekStart:1,
    });
    $('#datepicker1').change(function () {
        d = $(this).datepicker("getDate");
        d = new Date(d);
        day = d.getFullYear() + leftPad(d.getMonth() + 1, 2) + leftPad(d.getDate(), 2);
        //$('#upcoming_events_date').text(day.substr(6,2) + "/" + day.substr(4,2) + "/" + day.substr(0,4));

        $.post(event_Ajax.ajaxurl + "?action=getEvents",
        {
            day: day,
        },
        function(data, status){
            $('#upcoming_soccergames_table').html(data);
            $('#totalmathces').text("Showing " +  $('.game_time_teams').length + " matches");            
        });


    });

    var $loading = $('#loadingDiv').hide();
    $(document)
      .ajaxStart(function () {
        $loading.show();
        $('#upcoming_soccergames_table').hide();

      })
      .ajaxStop(function () {
        $loading.hide();
        $('#upcoming_soccergames_table').show();
    });

    //$(document).ready(function($){

        day = $('#datepicker1 input').val();

        d = new Date();
        d.setDate(d.getDate());
        day = d.getFullYear() + leftPad(d.getMonth() + 1, 2) + leftPad(d.getDate(), 2);
        //$('#upcoming_events_date').text(leftPad(d.getDate(), 2) + "/" + leftPad(d.getMonth() + 1, 2) + "/" + d.getFullYear());
        $('#datepicker1 input').val(leftPad(d.getDate(), 2) + "/" + leftPad(d.getMonth() + 1, 2) + "/" + d.getFullYear());
        $.post(event_Ajax.ajaxurl + "?action=getEvents",
        {
            day: day,
        },
        function(data, status){
            $('#upcoming_soccergames_table').html(data);
            $('#totalmathces').text("" +  $('.game_time_teams').length + " kampe");
        });

        $('#all_tips').DataTable({
            "order": [],
        });
        //$('#upcoming_soccergames_table').DataTable();

    //});

    $('body').on('click', '.game_time_teams', function(e){

        var tr = $(this);

        $('.modal_field_date').text($('#upcoming_events_date').text() + " " + tr.find('div.col-sm-1').text());
        $('.modal_field_date').attr("time", tr.attr("time"));
        $('.modal_field_match').text(tr.find('.game_teams').text());

        $('.modal_field_outcome').val("");
        $('.modal_field_odds').val("");
        $('.modal_bookmaker').val("");

        $('#matchModal').attr("event_id", tr.attr("event_id"));
        $('#matchModal').attr("league_id", tr.attr("league_id"));
        $('#matchModal').attr("league_name", tr.attr("league_name"));
        $('#createTips i').hide();
        $('#matchModal').modal('show');
    });



    $('body').on('click', '#matchModal #createTips', function(e){
        data = {
            event_id: $.trim($('#matchModal').attr("event_id")),
            league_id: $.trim($('#matchModal').attr("league_id")),
            league_name: $.trim($('#matchModal').attr("league_name")),
            time: $.trim($('.modal_field_date').attr("time")),
            match_teams: $.trim($('.modal_field_match').text()),
            outcome: $.trim($('.modal_field_outcome').val()),
            odds: $.trim($('.modal_field_odds').val()),
            bookmaker: $.trim($('.modal_bookmaker').val()),
            description: $.trim($('.modal_field_description').val())
        };

        $('#createTips i').show();

        $.post(event_Ajax.ajaxurl + "?action=createTips",
        {
            data: data,
        },
        function(data, status){
            console.log(data);

            if (data == 1) {
                $('#matchModal .tip_success_message').fadeIn(100);
                $('#matchModal .tip_success_message').fadeOut(1000);
            } else {
                $('#matchModal .tip_fail_message').fadeIn(100);
                $('#matchModal .tip_fail_message').fadeOut(1000);
            }
            setTimeout(() => {
                $('#createTips i').hide();
                $('#matchModal').modal('hide');
            }, 1000);


        });

    });

    $('body').on('click', '#all_tips .updatetip', function(){
        btn = $(this);
        tr = $(this).parent().parent();
        tip_id = tr.attr("tip_id");
        data = {
            id: tip_id,
            outcome: $.trim(tr.find('.field_outcome').val()),
            score: $.trim(tr.find('.field_score').val()),
            odds: $.trim(tr.find('.field_odds').val()),
            bookmaker: $.trim(tr.find('.bookmaker').val()),
            description: $.trim(tr.find('.field_description').val()),
        };
        $.post(event_Ajax.ajaxurl + "?action=updateTip",
        {
            data: data,
        },
        function(data, status){
            if (status == "success") {
                $('.tip_success_message').fadeIn(1000);
                $('.tip_success_message').fadeOut(2000);
            } else {
                $('.tip_fail_message').fadeIn(1000);
                $('.tip_fail_message').fadeOut(2000);

            }
        });
    });
    $('body').on('click', '#all_tips .deletetip', function(){
        btn = $(this);
        var tr = $(this).parent().parent();
        tip_id = tr.attr("tip_id");

        data = {
            id: tip_id,
        };

        $.post(event_Ajax.ajaxurl + "?action=deleteTip",
        {
            data: data,
        },
        function(data, status){
            if (status == "success") {
                $('.tip_success_message').fadeIn(1000);
                $('.tip_success_message').fadeOut(2000);
                tr.hide();
            } else {
                $('.tip_fail_message').fadeIn(1000);
                $('.tip_fail_message').fadeOut(2000);

            }
        });
    });

    function leftPad(number, targetLength) {
        var output = number + '';
        while (output.length < targetLength) {
            output = '0' + output;
        }
        return output;
    }
    $('body').on('click', '#hometeam_all button.showmore_button', function(){
        btn = $(this);
        rootdiv = $('#hometeam_all');
        $(this).text(function(i, text){
                return text === "Show More" ? "Show Less" : "Show More";
        });
        rootdiv.find('tr.default_hidden').toggle();
    });
    $('body').on('click', '#hometeam_home button.showmore_button', function(){
        btn = $(this);
        rootdiv = $('#hometeam_home');
        $(this).text(function(i, text){
                return text === "Show More" ? "Show Less" : "Show More";
        });
        rootdiv.find('tr.default_hidden').toggle();
    });
    $('body').on('click', '#hometeam_away button.showmore_button', function(){
        btn = $(this);
        rootdiv = $('#hometeam_away');
        $(this).text(function(i, text){
                return text === "Show More" ? "Show Less" : "Show More";
        });
        rootdiv.find('tr.default_hidden').toggle();
    });
    $('body').on('click', '#awayteam_all button.showmore_button', function(){
        btn = $(this);
        rootdiv = $('#awayteam_all');
        $(this).text(function(i, text){
                return text === "Show More" ? "Show Less" : "Show More";
        });
        rootdiv.find('tr.default_hidden').toggle();
    });
    $('body').on('click', '#awayteam_home button.showmore_button', function(){
        btn = $(this);
        rootdiv = $('#awayteam_home');
        $(this).text(function(i, text){
                return text === "Show More" ? "Show Less" : "Show More";
        });
        rootdiv.find('tr.default_hidden').toggle();
    });
    $('body').on('click', '#awayteam_away button.showmore_button', function(){
        btn = $(this);
        rootdiv = $('#awayteam_away');
        $(this).text(function(i, text){
                return text === "Show More" ? "Show Less" : "Show More";
        });
        rootdiv.find('tr.default_hidden').toggle();
    });
    $('body').on('click', '#twoteams_history button.showmore_button', function(){
        btn = $(this);
        rootdiv = $('#twoteams_history');
        $(this).text(function(i, text){
                return text === "Show More" ? "Show Less" : "Show More";
        });
        rootdiv.find('tr.default_hidden').toggle();
    });


    $('body').on('click', 'img.favorite_league', function(){
        league_id = $(this).parent().parent().attr('league_id');
        src = $(this).attr('src');
        var data;
        if (src.includes('star-gray.png')) {
            src = src.replace('star-gray.png', 'star-gold.png');

            data = {
                league_id: league_id,
                favorite: 1,
            };
        } else {
            src = src.replace('star-gold.png', 'star-gray.png', );
            data = {
                league_id: league_id,
                favorite: 0,
            };

        }
        $(this).attr('src', src);

        $.post(event_Ajax.ajaxurl + "?action=mark_favorite_league",
        {
            data: data,
        },
        function(data, status){
        });
    });
});
