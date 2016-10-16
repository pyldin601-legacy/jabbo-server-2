// Jabbo .js Interface //

// Page is initialized and javascript active
$(document).ready(function(){

	originalLocation = document.location.href;
    

	// Instant search
	if(!jQuery.browser.mobile)
		$(".subject").bind('textchange', function(){search_quick()});
		
	// Init endless results scrolling //
	$(document).scroll(function () {

		if(!jQuery.browser.mobile)
			$(".inheader").offset({top:window.pageYOffset});
		
		if(varOnce == 1) return false;
		
		if(document.body.scrollTop > 0)
			$(".up-back").css("display", "block").stop().animate({opacity:0.5}, 250);
		else
			$(".up-back").stop().animate({opacity:0}, 250, function(){$(this).css("display", "none")});
		
		//var shift = 50 + (40 / (document.body.scrollHeight - document.body.clientHeight) * document.body.scrollTop);
		//$(".bg-image").css("background-position", "center "+shift+"%");
		
		if($(".next_page").length == 1) {
			var SH = document.body.scrollHeight;
			var CH = document.body.clientHeight;
			if (document.body.scrollTop >= (SH - CH - 400)) 
			{
				varOnce = 1;
				var np = $(".next_page");
				var from = np.attr("from");
				var qq = np.attr("subj");
				var loc = np.attr("loc");
				var files = np.attr("files");
				add_results(qq, from, loc, files);
			}
		}

		if($(".nav_next_page").length == 1) {
			var SH = document.body.scrollHeight;
			var CH = document.body.clientHeight;
			if (document.body.scrollTop >= (SH - CH - 400)) 
			{
				varOnce = 1;
				var np = $(".nav_next_page");
				var page = np.attr("page");
				var prev = np.attr("prev");
				go_web(window.location.href, {page:page,prev:prev}, false, false);
			}
		}
	});

	// Initialize ajax links
	parse_aj();
	
	$(document).click(function(e){
		if(player_enabled && target_inside($(e.target), $(".fl_player").add(".playlist-wrap")) == false) { playlist_sw(false); }
		if(target_inside($(e.target), $(".cmenu")) == false) { hideMenu() }
	});

    $("#jquery_jplayer_1").jPlayer({
		ready: function(e) { init_global_player_2(); },
		ended: function(e) { play_completed(); },
		error: function(e) { showWarning(e.jPlayer.error.message); },
		timeupdate: function(e) { playing_now(e); },
		progress: function(e) { downloading_now(e); },
        swfPath: "/player",
        supplied: "mp3",
		solution: "flash, html",
		volume: 1
    });
	
	if(window.location.hash != "") {
		if(window.location.hash == '#login') 
			login();
		window.location.hash = "";
	}

	$(".icon-help").bind('click', function() { window.open('/html/searching.html', '_newtab') });
	$(".icon-engine").bind('click', function() { addEngine() });
	
	$(".query").bind('mouseenter', function() { $(".icon-help").add(".icon-engine").stop().animate({opacity:0.3}, 250) });
	$(".query").bind('mouseleave', function() { $(".icon-help").add(".icon-engine").stop().animate({opacity:0}, 250) });

	//$(".up-back").bind('mouseenter', function(){ $(".up-back").stop().animate({opacity:1},250) });
	//$(".up-back").bind('mouseleave', function(){ $(".up-back").stop().animate({opacity:0.5},250) });
	
	$(".up-back").bind('click', function(){ leftScrollUp() });
	
	setInterval(function(){ checkStatus() }, 15000);
	
	$(window).bind('resize', bodyResize);
	
	$(".icon-lastfm").click(function(){
		scrobble_enable = !scrobble_enable;
		$.get("settings.php?act=put&k=scrobble_enabled&v=" + (scrobble_enable ? 1 : 0));
		init_lastfm_icon();
	});

	showImage();
	bodyResize();
	searchColorize();
	
	changeTitle(document.title);
	
	init_lastfm_icon();
	
});

function leftScrollUp() {
	$(".up-back").css({
		display:"none", 
		opacity:0
	});
	$("body").scrollTop(0)
}

function showImage() {

	if(location.hostname != 'jb.tedirens.com')
	{
		var url = "/images/wallpapers/sky_bg.jpg";
		$('<img/>').attr('src', url).load(function() {
			$(".bg-image").css("display", "block").css('background', 'url('+url+') center 50% no-repeat').animate({ opacity : 1 }, 1000);
		});
	}

}

function contextMenu(e, i) {
	var r = $(".popupMenu");
	$.get('/menu.php?f='+i, function(data) {
		r.html(data);
		r.css("left", e.pageX + "px");
		r.css("top", e.pageY + "px");
		r.css("display", "block");
		r.focus();
	});
	return false;
}

function parse_page() {
	parse_aj();
	if(player_enabled) {
		index_playlist_2();
		jplayer_shot();
	}
}

function folderSize(el, id) {
	$(el).html("Calculating...")
		 .attr("onclick", "")
		 .toggleClass("like-a", false);
	
	$.post("/dirsize.php?id=" + id, function(data){
		$(el).html(data);
	});
}

function bodyResize(e)
{
	if(document.body.clientWidth < 630)
	{
		$(".player-frame").add(".index-stats-td").css("display", "none");
		$(".j_title").css("display", "none");
		$(".body-warp")
			.toggleClass("contents-mini", true) 
			.toggleClass("contents-snap", true)
			.toggleClass("contents", false);
		$(".query").css({width:($(".body-warp").width()-$(".ll-title").width()-22)+'px'});
	}
	else if(document.body.clientWidth < 705)
	{
		$(".player-frame").add(".index-stats-td").css("display", "none");
		$(".j_title").css("display", "");
		$(".body-warp")
			.toggleClass("contents-snap", true)
			.toggleClass("contents", false);
		$(".query").css({width:($(".body-warp").width()-$(".ll-title").width()-22)+'px'});
	}
	else if(document.body.clientWidth < 778) 
	{
		$(".player-frame").add(".index-stats-td").css("display", "");
		$(".j_title").css("display", "");

		$(".body-warp")
			.toggleClass("contents-snap", true)
			.toggleClass("contents", false);
		$(".query").css({width:'240px'});
	}
	else if(document.body.clientWidth < 950) 
	{
		$(".player-frame").add(".index-stats-td").css("display", "");
		$(".j_title").css("display", "");
		$(".body-warp")
			.toggleClass("contents-snap", true)
			.toggleClass("contents", false);
		$(".query").css({width:'240px'});
	}
	else 
	{
		$(".player-frame").add(".index-stats-td").css("display", "");
		$(".j_title").css("display", "");
		$(".body-warp")
			.toggleClass("contents-snap", false)
			.toggleClass("contents", true);
		$(".query").css({width:'240px'});
	}

	$(".subject").css({width:($(".query").width() - 20) + 'px'});
	$(".subject").css("background-position", ($(".query").width() - 44) + "px center");

}

function addEngine() {
	window.external.AddSearchProvider("http://jabbo.tedirens.com/searchEngine.xml");
	return false;
}
