var playlist_location = '';

$(window).bind('resize', function() {
	playlistLocate();
});

function recalculatePlaybackTime() {
	
	for(var item in playlist_items)
		time += parseInt(playlist_items[item].durt);

	$(".pl-t-text").html("Playlist (" + playlist_items.length + " items)");

	$(".pl-footer").html('<div class="track-total">\
	<div class="track-title"><b>Total playback time:</b></div>\
	<div class="track-time">' + secondsToTime(time) + '</div>\
	</div>');

}

function load_playlist() {

	var title; // track's title
	var time = 0; // playlist duration time
	var position; // track in playlist position
	
	var pl = $(".pl-contents-inner").html("");
	
	for(var item in playlist_items) {
	
		position = pos_format(parseInt(item)+1, playlist_items.length);
		
		if(playlist_items[item].arti != '' && playlist_items[item].titl != '')
			title = '<b>' + position + '. ' + playlist_items[item].arti + '</b> - ' + playlist_items[item].titl;
		else
			title = '<b>' + position + '.</b> ' + playlist_items[item].file;
			
		time += parseInt(playlist_items[item].durt);
		
		pl.append('<div class="track-item" id="ti-' + item + '" tipurl="/fileinfo.php?id=' + playlist_items[item].id + '" onclick="play_track(' + item + ');">\
		<div class="track-title" id="ttl-' + item + '">'+title+'</div>\
		<div class="track-time" id="ttm-' + item + '">' + secondsToTime(playlist_items[item].durt) + '</div>\
		</div>');

	}

	$(".pl-t-text").html("Playlist (" + playlist_items.length + " items)");

	$(".pl-footer").html('<div class="track-total">\
	<div class="track-title"><b>Total playback time:</b></div>\
	<div class="track-time">' + secondsToTime(time) + '</div>\
	</div>');
	
	if(playback_status != PS_STOP) $("#ti-" + current_track).toggleClass( "track-current", true );
	
	playlist_location = document.location.href;
	
}

function show_playlist() {
	$(".playlist-wrap").show(0, function(){
		playlistLocate();
		$(".pl-contents").stop().animate({ height : "358px" }, 150, function(){
			if($("#ti-" + current_track).size()) {
				scroll_to_pos(current_track); 
			}
		});
	});
}

function playlistLocate() {
	var keyLeft = $(".global_plist").offset().left - $(".body-warp").offset().left - ($(".playlist-wrap").width()/2) + ($(".pl-corner").width()/2) + 2;
	$(".playlist-wrap").css({left:keyLeft+'px'});
}

function hide_playlist() {
	$(".pl-contents").stop().animate({ height : "0px" }, 150, function(){ 
		$(".playlist-wrap").fadeOut(150);
	});
}

function playlist_sw(mode) {

	if(playlist_items.length == 0) mode = false;
	if(playlist_appear == mode) return false;
	
	playlist_appear = mode;

	if(playlist_appear) {
		show_playlist();
		$(".global_plist").stop().animate({opacity:1}, 500);
	} else {
		hide_playlist();
		$(".global_plist").stop().animate({opacity:0.3}, 500);
	}
	
	return true;

}

function scroll_to_pos(pos) {
	var target = $(".pl-contents");
	var el = $("#ti-"+pos);
	var dd = 100 / target.height() * el.position().top;
	if (dd > 95 || dd <= 5) {
		var dY = (el.position().top + target.scrollTop()) - target.position().top - 160; 
		$(".pl-contents").stop().animate({ scrollTop : dY }, 250);
	}
}

function return_playlist() {
	if(playlist_location != '') {
		nav(playlist_location);
	}
}
