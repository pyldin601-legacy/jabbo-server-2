/************************************************
|                                               |
| Jabbo Audio Player (Logic Section) Module     |
|                                               |
************************************************/

var player_enabled = false;
var playback_status = 0;

const PS_STOP = 0;
const PS_PAUSE = 1;
const PS_PLAY = 2;

var page_items = [];
var playlist_items = [];
var images_index = [];

var current_track = 0;

var percent_loaded = 0;
var track_position = 0;
var track_length = 0;

var release_player = false;
var repeat_mode = false

var playlist_appear = false;

var volume_max = 1;
var current_volume = 1;
var volume_id = false;
var volume_interval = 50;

function init_global_player_2() {

	// Initialize player control hotkeys //
	$(document).keypress(function(event){
		//console.log(event.keyCode);
		if(event.ctrlKey == true) {
			if(event.keyCode  == '38') { play_prev(); 		return false; }
			if(event.keyCode  == '40') { play_next(); 		return false; }
			if(event.keyCode  == '39') { seek_forward(); 	return false; }
			if(event.keyCode  == '37') { seek_backward(); 	return false; }
			if(event.charCode == '32') { play_pause();		return false; }
		}
	});

	// Set player flag ON //
	player_enabled = true;
	
	// Paint player control frame //
	$(".fl_player").html(
	"<div class=\"global_close href\" tooltip=\"Stop playback\"></div>"+
	"<div class=\"global_play href\" tooltip=\"Play/Pause <b>(ctrl+space)</b>\"></div>"+
	"<div class=\"global_prev href\" tooltip=\"Previous track\"></div>"+
	"<div class=\"global_next href\" tooltip=\"Next track\"></div>"+
	"<div class=\"global_rept href\" tooltip=\"Repeat on/off<br>(alt+click shuffles current playlist)\"></div>"+
	"<div class=\"global_plist href\" tooltip=\"Show/hide playlist\"></div>"+
	"<div class=\"global_time global_time_a\" tooltip=\"Time played\">00:00</div>"+
	"<div class=\"global_time global_time_b\" tooltip=\"Time left\">00:00</div>"+
	"<div class=\"global_tag\">Jabbo Audio Player</div>"+
	"<div class=\"global_seeker\">"+
		"<div class=\"loadBG\">"+
			"<div class=\"loadFG\"></div>"+
		"</div>"+
		"<div class=\"global_slider\"></div>"+
	"</div>"
	);

	// Initialize player control slider //
	$(".global_slider").slider({ 
		stop: function(event, el) { 
			release_player = false; 
			if( playback_status != PS_STOP ) { seekTrack(event, el); }
			if( playback_status == PS_PLAY ) { $(".ui-slider-handle").stop().fadeTo(500, 1); }
		},
		start: function(event, el) { 
			release_player = true; 
			$(".ui-slider-handle").stop().fadeTo(500, 0.3); 
		},
		slide: function(event, el) {
			if( playback_status != PS_STOP ) { 
				seekMove(el.value); 
			}
		},
		max: 100
	});


	$(".global_slider").mouseover(function(){
		//$(".floatime").css({display:'block'});
	}).mouseout(function(){
		//$(".floatime").css({display:'none'});
	}).mousemove(function(e){
		//var v = 100 / $(".global_slider").width() * (e.pageX - $(".global_slider").offset().left);
		//floa_update( e, v );
	});

	// Initialize player control keys //
	$(".global_rept").click(function(e){ repeat_switch(e); });
	$(".global_play").click(function(){ play_pause(); });
	$(".global_next").click(function(){ play_next(); });
	$(".global_prev").click(function(){ play_prev(); });
	$(".global_close").click(function(){ stop_now(); });
	$(".global_plist").click(function(){ playlist_sw(!playlist_appear); });

	// Inder playlist for player
	index_playlist_2();

	// Show player control frame //
	$(".fl_player").css("display", "block");
	$(".fl_player").stop().animate({ opacity: 1 }, 500);
	
	// Check GET parameters
	var startFrom = getUrlVars()['play'];
	if(startFrom !== undefined) {
		play_init(parseInt(startFrom) - 1);
	}
	
}

function index_playlist_2() {

	page_items = [];
	images_index = [];

	var item = 0;
	$(".play-file").each(function() {
		page_items.push({id : this.value, arti : $(this).attr("arti"), titl : $(this).attr("titl"), durt : $(this).attr("durt"), file : $(this).attr("file")});

		$("#icon-" + this.value).each(function(){
			$(this).toggleClass("icon-audio", false);
			$(this).toggleClass("icon-play", true);
			$(this).toggleClass('href', true);
			$(this).attr('onclick', 'return play_init(' + item + ', event)');
			$(this).attr('tooltip', 'Play/Pause <b>(click)</b><br>Play unique titles <b>(alt+click)</b>');
		});

		$("#link-" + this.value).each(function(){
			$(this)
				//.removeAttr('href')
				.parent().parent()
					.attr('onclick', 'return play_init(' + item + ', event);')
					.toggleClass('like-a', true);
		});

		item ++;
	});

	item = 0;
	$(".image-file").each(function() {
		images_index.push({id:this.value,file:$(this).attr("file")});
		$(this).attr("item",item);
		item ++;
	});
	
	jplayer_shot();
	
}

function find_in_playlist(artist, title) {
	for(pl_item in playlist_items) {
		if(playlist_items[pl_item].arti == artist && playlist_items[pl_item].titl == title) return pl_item;
	}
	return false;
}

function sync_page_playlist(unique, track) {

	var playlist_position = track;
	
	if(unique == false) {
		playlist_items = page_items;
	} else {
		playlist_items = [];
		for(item in page_items) {
			if(find_in_playlist(page_items[item].arti, page_items[item].titl) == false)
				playlist_items.push(page_items[item]);
			
		}
		playlist_position = find_in_playlist(page_items[track].arti, page_items[track].titl);
	}
		
	load_playlist();
	
	return playlist_position;

}

function play_init(index, e) {

	if(e !== undefined && e.altKey == true) {
		current_track = parseInt(sync_page_playlist(true, index));
		jplayer_start();
	} else if( (playlist_items[index] !== undefined) && (playlist_items[index].id == page_items[index].id) && (index == current_track) ) {
		play_pause();
	} else {
		if(page_items.length == 0) return false;
		current_track = parseInt(sync_page_playlist(false, index));
		jplayer_start();
	}

	return false;

}

function play_track(index) {

	if ( index == current_track && playback_status != PS_STOP ) {
		play_pause();
	} else {
		current_track = parseInt(index);
		jplayer_start();
	}

	return false;

}

function repeat_switch(e) {

	if ( e !== undefined && playlist_items.length > 0 )
		if ( e.type == 'click' && e.altKey == true ) {
			shuffle(playlist_items);
			load_playlist();
			current_track = 0;
			jplayer_start();
			return false;
		}

	jplayer_repeat( ! repeat_mode );

}

function play_pause(e) {

	if( playback_status == PS_STOP ) {
		jplayer_start();
		return false;
	}

	if( playback_status == PS_PLAY ) {
		jplayer_pause();
		return false;
	}
	
	if( playback_status == PS_PAUSE ) {
		jplayer_play();
		return false;
	}

}

function stop_now() {
	jplayer_stop();
}

function play_next() {
	if(playlist_items.length > current_track + 1) {
		current_track = current_track + 1;
		jplayer_start();
	} 
}

function play_prev() {
	if(playlist_items.length > 0 && current_track <= playlist_items.length && current_track > 0) {
		current_track = current_track - 1;
		jplayer_start();
	} 
}

function seekMove(value) {
	if(playlist_items[current_track] && percent_loaded > 0) {
		track_length = parseInt(playlist_items[current_track].durt);
		$(".global_time_a").html(secondsToTime(track_length / 100 * value));
		$(".global_time_b").html("-" + secondsToTime(track_length - (track_length / 100 * value)));
	}
}

function seekTrack(ev, ms) {
	if(percent_loaded > 0)
		$("#jquery_jplayer_1").jPlayer("playHead", 100 / percent_loaded * ms.value);

}

function seek_forward() {
	if(track_position + 5 < percent_loaded)
		$("#jquery_jplayer_1").jPlayer("playHead", track_position + 5);

}

function seek_backward() {
	if(track_position > 5)
		$("#jquery_jplayer_1").jPlayer("playHead", track_position - 5);

}

function playing_now(e) {
	percent_loaded = e.jPlayer.status.seekPercent;
	track_position = e.jPlayer.status.currentPercentRelative;
	track_length = e.jPlayer.status.duration;

	if(release_player == false) {
		$(".global_slider").slider("value", e.jPlayer.status.currentPercentAbsolute);
		$(".global_time_a").html(secondsToTime(e.jPlayer.status.currentTime));
		
		if(playlist_items[current_track].durt > e.jPlayer.status.currentTime)
			$(".global_time_b").html("-" + secondsToTime(playlist_items[current_track].durt - e.jPlayer.status.currentTime));
	}

}

function downloading_now(e) {

	if(playback_status == PS_STOP) return false;
	
	percent_loaded = e.jPlayer.status.seekPercent

	var prev_value = Math.floor(100 / 262 * $(".loadFG").width());
	
	if(prev_value < percent_loaded)
		$(".loadFG").stop().animate( { width : Math.floor(262 / 100 * percent_loaded) + 'px' }, 250 );
	else
		$(".loadFG").stop().css( { width : Math.floor(262 / 100 * percent_loaded) + 'px' } );

}

function play_completed() {
	if(repeat_mode == true) {
		$("#jquery_jplayer_1").jPlayer("play", 0);
	} else {
		if(playlist_items.length > current_track + 1) {
			play_next();
		} else {
			stop_now();
		}
	}
}


function watchVideo(url, cha) {
	try {
		window.open("/watch?v=" + url + "&t=" + cha,'Jabbo Video Player','width=576,height=432,resizable=0,scrollbars=0');
		return false;
	} catch (err) {
		return true;
	}
}

function openVideo(url, cha) {
	try {
		var flvPath = "http://jabbo.tedirens.com/stream/"+url+".flv?t="+cha;
		window.location.href="/watch?v="+url+"&t="+cha;
		return false;
	} catch (err) {
		return true;
	}
}



function scrobbler_on() {
	window.open("http://www.last.fm/api/auth?api_key=f7a8f639e4747490849e3bc33475b118", "Connect Last.fm profile", "width=1000,height=512");
	return false;
}

function scrobbler_off() {
	$.get("/lastfm.php?disable=1", function(data){
		if(data == 'OK')
			checkStatus()
	});
}

/************************************************
|                                               |
| Jabbo Audio Player (Graphical Section) Module |
|                                               |
************************************************/

function jplayer_stop() {
	console.log("Stop command");
	playback_status = PS_STOP;
	fadeout_audio_stop();
	scr_stop();
}

function jplayer_shot() {
	if( playback_status == PS_STOP ) {
		apply_icon("/images/favicon/jabbo-icon.png");

		$(".global_close").stop().animate({opacity:0.3}, 500);
		$(".global_play").toggleClass( "global_play_pause", false );
		$(".track-item").toggleClass( "track-current", false );
		$(".global_time").html("00:00");
		$(".loadFG").css( { width : "0px" } );
		$(".global_slider").slider("value", 0);
		$(".global_slider").slider( "disable" );
		$(".ui-slider-handle").stop().animate({opacity:0.3}, 500);
		
		document.title = docTitle;
		
		$(".global_tag").html("Jabbo Audio Player");
		$(".global_tag").removeAttr('tipurl');

		$(".icon-pause").toggleClass("icon-pause", false);

		$(".high").toggleClass("high", false);
		$(".loadFG").stop().css({ width : '0px' });
		
	} else if ( playback_status == PS_PLAY ) {
		apply_icon("/images/favicon/play.png");

		// stop button enable
		$(".global_close").stop().animate({opacity:1}, 500);
		$(".global_slider").slider( "enable" );
		// display tag info
		if((playlist_items[current_track].arti != '') && (playlist_items[current_track].titl != '')) {
			$(".global_tag").html("<b>" + playlist_items[current_track].arti + "</b> - " + playlist_items[current_track].titl);
			document.title = playlist_items[current_track].arti + " - " + playlist_items[current_track].titl;
		} else {
			$(".global_tag").html(playlist_items[current_track].file);
			document.title = docTitle;
		}
		$(".global_tag").attr('tipurl', '/fileinfo.php?id=' + playlist_items[current_track].id);

		// enable "pause" button
		$(".global_play").toggleClass( "global_play_pause", true );
		// enable slider
		$(".ui-slider-handle").stop().animate({opacity:1}, 500);
		// playlist
		$(".icon-pause").each(function() { $(this).toggleClass("icon-pause", false); } );

		$(".track-item").toggleClass( "track-current", false );
		
		if($("#ti-" + current_track).size()) {
			$("#ti-" + current_track).toggleClass( "track-current", true );
			scroll_to_pos(current_track);
		}
		
		$(".high").toggleClass("high", false);
		$("#icon-" + playlist_items[current_track].id).toggleClass("icon-pause", true);
		$("#file-" + playlist_items[current_track].id).parents().filter("tr").toggleClass("high", true);
	
	} else if ( playback_status == PS_PAUSE ) {
		apply_icon("/images/favicon/pause.png");

		// enable "play" button
		$(".global_play").toggleClass( "global_play_pause", false );
		$(".global_slider").slider( "enable" );
		// disable slider
		$(".ui-slider-handle").stop().animate({opacity:0.3}, 500);
		// and pause it
		
		fadeout_audio();
		
		// playlist
		$("#icon-" + playlist_items[current_track].id).toggleClass("icon-pause", false);
	}
	
	if( (current_track > 0) && (playlist_items.length > 0) ) {
		$(".global_prev").stop().animate({opacity:1}, 500 );
	} else {
		$(".global_prev").stop().animate({opacity:0.3}, 500 );
	}
	if( (current_track < playlist_items.length - 1) && (playlist_items.length > 0) ) {
		$(".global_next").stop().animate({opacity:1}, 500 );
	} else {
		$(".global_next").stop().animate({opacity:0.3}, 500 );
	}
}

function fadeout_audio() { // PAUSE
	if(current_volume > 0) {
		current_volume -= 0.1;
		$("#jquery_jplayer_1").jPlayer("volume", current_volume);
		if(volume_id)
			clearTimeout(volume_id);

		volume_id = setTimeout("fadeout_audio()", volume_interval);
	} else {
		current_volume = 0;
		$("#jquery_jplayer_1").jPlayer("volume", current_volume);
		$("#jquery_jplayer_1").jPlayer("pause");
		volume_id = false;
	}
}

function fadein_audio() { // UNPAUSE
	$("#jquery_jplayer_1").jPlayer("play");
	if(current_volume < volume_max) {
		current_volume += 0.1;
		$("#jquery_jplayer_1").jPlayer("volume", current_volume);
		if(volume_id)
			clearTimeout(volume_id);
		volume_id = setTimeout("fadein_audio()", volume_interval);
	} else {
		current_volume = 1;
		$("#jquery_jplayer_1").jPlayer("volume", current_volume);
		volume_id = false;
	}
}

function fadeout_audio_stop() { // STOP
	if(current_volume > 0) {
		current_volume -= 0.1;
		$("#jquery_jplayer_1").jPlayer("volume", current_volume);
		if(volume_id)
			clearTimeout(volume_id);

		volume_id = setTimeout("fadeout_audio()", volume_interval);
	} else {
		current_volume = 0;
		$("#jquery_jplayer_1").jPlayer("volume", current_volume);
		$("#jquery_jplayer_1").jPlayer("clearMedia");
		volume_id = false;
		jplayer_shot();
		console.log("Stopped");
	}
}



function jplayer_start() {
	if(playlist_items[current_track] !== undefined) {
		$("#jquery_jplayer_1").jPlayer("setMedia", { mp3: "/prelisten/" + playlist_items[current_track].id + ".txt", } );
		fadein_audio();
		scr_begin();
		playback_status = PS_PLAY;
		jplayer_shot();
	} else if(page_items.length > 0) {
		play_init(0);
	}
}

function jplayer_play() {
	if(playlist_items[current_track] === undefined) return false;
	
	// and play it
	playback_status = PS_PLAY;
	jplayer_shot();
	fadein_audio();
}

function jplayer_pause() {
	if(playlist_items[current_track] === undefined) return false;
	playback_status = PS_PAUSE;
	jplayer_shot();
	
}

function jplayer_repeat(r) {
	repeat_mode = r;
	if(repeat_mode) {
		$(".global_rept").stop().animate({opacity:1}, 500);
	} else {
		$(".global_rept").stop().animate({opacity:0.3}, 500);
	}
}

