
function to_playlist(item, list) {
	$.get("/playlist.php?action=add&i=" + item + "&pl=" + list, function (data) {
	});
	return false;
}

function new_playlist(item) {
	var name = prompt("Enter name for new list:", "");
	if(name != null && name != "") {
		$.get("/playlist.php?action=new&i=" + item + "&plname=" + name, function (data) {
		});
	}
	return false;
}

