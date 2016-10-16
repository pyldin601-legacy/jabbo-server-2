// Jabbo AJAX Interface //

var emptyMD5 = "d41d8cd98f00b204e9800998ecf8427e";
var ajaxObj = false; 
var statusObj = false;
var statusHash = emptyMD5;

var allowRefresh = document.location.href.match(/\/dir-\d+$/) ? true : false;

var myHistory = {};

window.onpopstate = function(ss) {
	if(ss.state) {
		if(myHistory[ss.state] !== undefined) {
			window.title = myHistory[ss.state][1];
			$(".results > tbody").html(myHistory[ss.state][2]);
			parse_page();
		} else {
			nav_h(ss.state);
		}
	}

};

function ffget(url) {
	window.open(url, '');
	return false;
}

function parse_aj() {
	$('.ajlink').each(function(){
		var url = $(this).attr('href');
		$(this).attr('onclick', 'return nav("' + url + '")')
	});

	$('.ajx').each(function(){
		var url = $(this).attr('href');
		$(this).parent()
			.attr('onclick', 'return nav("' + url + '")')
			.addClass('like-a');
	});

	$('.ajxff').each(function(){
		var url = $(this).attr('href');
		$(this).parent()
			.attr('onclick', 'return ffget("' + url + '")')
			.addClass('like-a');
	});

	$('.deny').each(function(){
		$(this).attr('onclick', 'return showWarning("Access denied!")')
	});

	// Init context menu for files //
	$(".cmenu").each(function(){
		var i = this.id;
		if(i) {
			i = i.split("-");
			$(this).unbind("contextmenu").bind("contextmenu", function(e){
				contextMenu(e, i[1]);
				return false;
			});
		}
	});

	// Init context menu for dirs //
	$(".dmenu").each(function(){
		var i = $(this).attr('dir');
		var s = $(this).attr('session');
		var b = $(this).attr('bm');
		var n = $(this).find("div").html();
		$(this).unbind("contextmenu").bind("contextmenu", function(e){
			contextDirMenu(e, i, s, n, b);
			return false;
		});
	});

    //lastfm_love_do();
}

function checkStatus() {

	if(statusObj) statusObj.abort();

	statusObj = $.post("/status.php?hash=" + statusHash, function (data) {

		statusObj = false;
		
		if(data == "") return false;
		var tmp = jQuery.parseJSON(data);

		if(tmp.index !== undefined)
			$(".index-stats").html(tmp.index);

		if(tmp.playcount !== undefined)
			$(".tb-plays").html(tmp.playcount);

		if(tmp.downcount !== undefined)
			$(".tb-down").html(tmp.downcount);

		if(tmp.hash !== undefined)
			statusHash = tmp.hash;

		if(tmp.watchcount !== undefined)
			$(".tb-watch").html(tmp.watchcount);

		if(tmp.lastfm !== undefined)
			$(".tb-lastfm").html(tmp.lastfm);

	});

}

function changeTitle(title) {
	document.title = title;
	docTitle = title;
}

function go_web(url, post, hist, repl) {

	if(ajaxObj) ajaxObj.abort();

	if(hist) {
		clearTimeout(timer);
		timer = setTimeout('show_loader()', 750);
	}

	ajaxObj = $.post(url, post, function(data){
	
		ajaxObj = false;
		
		clearTimeout(timer);
		hide_loader();

		var tmp = jQuery.parseJSON(data);
		
		
		/* if title changed */
		if(tmp.title !== undefined)
			changeTitle(tmp.title);

		/* search form contents changed */
		if(tmp.query !== undefined) {
			$("#query_query").attr("value", tmp.query);
			searchColorize();
		}

		/* search results append */
		if(tmp.append !== undefined) {
			$(".cut_here").remove();
			$(".results > tbody:last").append(tmp.append);
			varOnce = 0;
		}
			
		/* contents change */
		if(tmp.body !== undefined)
			$(".results > tbody").html(tmp.body);
			
		if((tmp.body !== undefined) || (tmp.append !== undefined))
			parse_page();

		myHistory[url] = [url, document.title, $(".results > tbody").html()];

		if(hist) { 
			$('body').stop().animate({scrollTop:0},'slow');
			window.history.pushState(url, document.title, url);
			originalLocation = document.location.href;
			tipCache = [];
		} else if(repl) {
			$('body').stop().animate({scrollTop:0},'slow');
			window.history.replaceState(url, document.title, url);
		}


	});
	
}

function logout() {
	$.get("/logout.php", function(data){
		window.location.reload();
	});
	return false;
}

function login2() {
	$.post('/login.php', $(".send").serialize(), function(data) {
		if(data != 'OK') {
			showWarning(data);
		} else {
			window.location.reload();
		}
	});
	return false;
}

function signin() {
	window.location.href = "/signup.php";
}

function hide_login() {
	$(".login-frame").css({display:"none"});
}

function nav(url) {
	go_web(url, [], true, false);
	return false;
}

function nav_h(url) {
	go_web(url, [], false, false);
	return false;
}

function refresh_page() {
	go_web(document.location.href, [], false, false);
}

function show_loader() {
	$(".ajax-busy").css({display:'block'}).stop().animate({opacity:1},250);
}

function hide_loader() {
	$(".ajax-busy").stop().animate({opacity:0},250,function(){
		$(this).css({display:'none'})
	});
}

$(document).ajaxSend(function() {
	$(".query").toggleClass("working", true);
});

$(document).ajaxComplete(function() {
	$(".query").toggleClass("working", false);
});
