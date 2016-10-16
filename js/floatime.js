function floa_update(e, v) {

	var el = $(".floatime");

	var sTop = $(".global_slider").offset().top - el.outerHeight() - 5;
	var sLeft = e.pageX - (el.outerWidth()/2) + 2 ;
	
	console.log(e);
	
	el.html(v);
	el.css({top:sTop,left:sLeft});
	
}

