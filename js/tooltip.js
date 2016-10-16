var tip_timer;
var tip_show = false;
var tip;
var tip_request = false;

var warning_id;
var tipCache = [];

$(document).mousemove(function(e){
	hide_tip();

	if($(".contents-mini").length) // don't show tooltip in minimal mode
		return false;

	var tgt = $(e.target);
	tip_timer = setTimeout(function(){
		if(tip = tgt.parents().andSelf().filter("[tipurl]").last().attr("tipurl"))
			if(tipCache[tip] === undefined)
				tip_request = $.get(tip, function (data) { tip_request = false; tipCache[tip] = data; show_tip(e, data); });
			else
				show_tip(e, tipCache[tip]);
		else if(tip = tgt.parents().andSelf().filter("[tooltip]").last().attr("tooltip"))
			show_tip(e, '<div>' + tip + '</div>');
	}, 500);
});

$(document).bind( "keypress click scroll blur mouseleave", function(e) { hide_tip() } );

function show_tip(e, text) 
{

	var tt = $(".tool-tip").html(text);
	
	if($(".fi-preview").size() == 0) 
	{
		init_tip_pos(e.pageX, e.pageY);
		tt.css({display:"block"}).stop().animate({opacity:"1"}, 250);
	} 
	else 
	{
		$(".fi-preview").load(function(){
			init_tip_pos(e.pageX, e.pageY);
			$(".tool-tip").css({display:"block"}).stop().animate({opacity:1}, 250);
		}).error(function(){
			$(".fi-preview").remove();
			init_tip_pos(e.pageX, e.pageY);
			tt.css({display:"block"}).stop().animate({opacity:"1"}, 250);
		});
	}
	
	tip_show = true;
	
}

function init_tip_pos(cX, cY) {

	var tt = $(".tool-tip");

	var yLeft = (document.body.scrollTop + document.body.clientHeight) - (cY + tt.outerHeight(true) + 30);
	var xLeft = (document.body.scrollLeft + document.body.clientWidth) - (cX + tt.outerWidth(true) + 30);

	if(yLeft < 0) tt.css({top : cY + yLeft + "px"}); else tt.css({top : cY + "px"});
	if(xLeft < 0) tt.css({left : cX + xLeft + "px"}); else tt.css({left : cX + "px"});

}

function hide_tip() {
	clearTimeout(tip_timer);
	if(tip_request) tip_request.abort();
	if($(".tool-tip").css("display") != "none") {
		$(".tool-tip").stop().css( { display : "none", opacity : "0" } );
		tip_show = false;
	}
}

function showWarning(text) {

	clearTimeout(warning_id);
	$(".warning-wrap").remove();

	$("body").append("<div class=\"warning-wrap fx-round-br\">" + text + "</div>");
	warning_id = setTimeout(function(){ hideWarning() }, 5000);
	
	return false;

}

function hideWarning() {
	$(".warning-wrap").fadeOut(500, function(){	$(this).remove(); });
}

