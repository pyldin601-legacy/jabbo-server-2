const SCROBBLE_NONE = 0;
const SCROBBLE_BEGIN = 1;
const SCROBBLE_DONE = 2;

var scrobble_cent = .5;
var scrobble_enable = true;
var scrobble_status = SCROBBLE_NONE;
var scrobble_timeout = 0;

var scrobble_timer = 0;

function scr_begin() {
	if((scrobble_enable == true) && (playlist_items[current_track].durt > 60) && (playback_status != PS_STOP)) {
		clearInterval(scrobble_timer);
		scrobble_timeout = Math.round(playlist_items[current_track].durt * scrobble_cent);
		scrobble_status = SCROBBLE_BEGIN;
		$.post('/lastfm.php', { 
			nowplaying: 1, 
			artist: playlist_items[current_track].arti, 
			song: playlist_items[current_track].titl, 
			duration: playlist_items[current_track].durt 
		});
		scrobble_timer = setInterval("scr_timer()", 1000);
	}
}

function scr_stop() {
	clearInterval(scrobble_timer);
	scrobble_status = SCROBBLE_NONE;
}

function scr_apply() {
	if(scrobble_status == SCROBBLE_BEGIN) {
		$.post('/lastfm.php', { 
			submission: 1,
			artist: playlist_items[current_track].arti, 
			song: playlist_items[current_track].titl, 
			duration: playlist_items[current_track].durt,
			id: playlist_items[current_track].id
		}, function(data){
			if(data.indexOf('QUEUED') != -1) {
				var queue = data.match(/QUEUED\s\:\s(\d+)\n/);
				showWarning("Last.fm scrobbler: submission added to queue" + (queue ? " (" + queue[1] + ")" : ""));
			} else if(data.indexOf('OK') == -1) {
				showWarning("Last.fm scrobbler can't authenticate!<br>Try to reconnect your Last.fm profile.");
			}
		});
		scrobble_status = SCROBBLE_DONE;
	}
}

function scr_timer() {
	if (playback_status == PS_PLAY) {
		scrobble_timeout = scrobble_timeout - 1;
		if(scrobble_timeout < 1) {
			clearInterval(scrobble_timer);
			scr_apply();
		}
	}
}

function init_lastfm_icon() {
	var fm = $(".icon-lastfm");
	if(scrobble_enable) {
		fm.attr("src","/images/icons/lastfm.png");
	} else {
		fm.attr("src","/images/icons/lastfm_off.png");
		scr_stop();
	}
}

function lastfm_love_do() {
    $(".lovedtracks").each(function(){
        var item = $(this);
        var artist = item.attr("artist");
        var title = item.attr("title");
        var user = item.attr("user");
        var url = '/scrobbler/trackloved.php?artist=' + artist + '&title=' + title + '&user=' + user;
        $('<img/>')
            .attr({src:url,tooltip:"You love this track on last.fm"})
            .css({opacity:0})
            .load(function(){item.replaceWith(this);$(this).fadeTo(500,1)})
			.error(function(){item.remove()});
    });
}
