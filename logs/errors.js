var items, title;

$(document).ready(function(){
	title = document.title;
	var items = -1;
	getLOG(-1);
});

function getLOG(pos) {
	$.post("/logs/index.php", { f : pos }, function(data) {
		var data = $.parseJSON(data);
		if(data['status'] == 'OK') {
			var newpos = data['size'];
			$(".data-log").append(data['data']);
			$(window).scrollTop($(document).height());
			getLOG(newpos);
			items++;
			document.title = (items>0?"("+items+") ":"") + title;
		} else {
			getLOG(pos);
		}
	}).fail(function(){
		setTimeout(function(){ getLOG(pos); }, 5000);
	});
}