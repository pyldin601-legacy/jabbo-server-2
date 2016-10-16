function pfParse() {
	$(".popupfolder").filter(".new").each(function(){
		var obj = $(this);
		obj.toggleClass("new", false);
		var id = obj.attr('dir');
		obj.append("<img src='/images2/dnbutton.png'>");
		obj.attr('onclick', 'return false');
		obj.click(function(){
			showPopupFolderById(id, obj);
			return false;
		});
	});
}

function showPopupFolderById(id, obj) {
	$("body").append('<div class="pf-window"></div>');
	var pf = $(".pf-window");
	obj.toggleClass("current", true);

	pf.css({ 
		left : obj.offset().left + 'px', 
		top : obj.offset().top + obj.height() + 'px',
		'min-width' : obj.outerWidth() - 2 + 'px',
		display : 'block' 
	});
}

function hidePopupFolder() {
	$(".pf-window").remove();
	$("a").filter(".current").toggleClass("current", false);
}

$(document).ready(function(){
/*	pfParse();
	$("body").click(function(){
		hidePopupFolder();
	}); */
});

