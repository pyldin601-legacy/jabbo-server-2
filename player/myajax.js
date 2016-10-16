var prevQuery = "";

function add_results(query, from, loc, files) {
	go_web('/search.php?add=1&q='+query+'&from='+from+'&loc='+loc+'&files='+files, {smart:'1'}, false, false);
}


function delete_file(index) {
	if(confirm("Are you sure want to delete this file?")) {
		$.get("/unlink.php?f="+index, function(data) {
			var result = data.split(':');
			console.log(result);
			if(result[2] == 'ok') {
				$("#file-" + result[1]).parents().filter("tr").remove();
			} else {
				alert("Can't delete this file!");
			}
		});
	}
	return false;
}

function rename_file(index) {

	var name = prompt("Enter new name for the file:", "");
	if(name!=null && name!="") {
		$.get("/rename.php?f="+index+"&n="+name, function(data) {
			var result = data.split(':');
			console.log(result);
			if(result[2] == 'ok') {
				go_web(window.location.href, false);
			} else {
				alert("Can't change name of this file");
			}
		});
	}
	return false;

}

function search_this() {
	var tt = $(".subject").attr("value");
	if(tt != '') {
		go_web('/search.php?q=' + encodeURIComponent(tt), {smart:'1'}, true, false);
	}
}

function searchColorize() {
	var tt = $(".subject").attr("value");
	if (tt.indexOf("@") == 0) {
		$(".subject").css("background-color", "#dfd");
	} else {
		$(".subject").css("background-color", "#fff");
	}
}

function search_quick(event) {

	clearTimeout(delay);
	searchColorize();
	var tt = $(".subject").attr("value");
	if (tt.indexOf("@") == 0) {
		return false;
	}

	if(tt == "" && originalLocation != "" && window.location.href != originalLocation) {
		go_web(originalLocation, {smart:'1'}, false, true);
	} else if(tt != "") {
		delay = setTimeout(function(){ go_web('/search.php?q=' + encodeURIComponent(tt), {smart:'1'}, false, true) }, 250);
	}

}


function contextDirMenu(e, i, s, n, b) {
	var r = $(".popupMenu");
	var url = "/dir-" + i + "-" + encodeURIComponent(n);
	var url2 = "/dir-exp-" + i + "?x=1";
	var url3 = "/zip-" + i + "-" + s + "/" + encodeURIComponent(n) + ".zip";
	var sr = '';
	

	// link
	sr = sr + '<a class="item" target="_blank" href="'+url+'">Open folder in new tab</a>';
	sr = sr + '<a class="item" href="'+url3+'">Download folder as .zip archive</a>';
	
	if(b == 1)
		sr = sr + '<a class="item like-a" onclick="bookmarkDir('+i+',0)">Remove from bookmarks</a>';
	else
		sr = sr + '<a class="item like-a" onclick="bookmarkDir('+i+',1)">Add to bookmarks</a>';

	r.html(sr);
	r.css("left", e.pageX + "px");
	r.css("top", e.pageY + "px");
	r.css("display", "block");
	r.focus();
	return false;
}

function bookmarkDir(d, s) {
	$.post("/bookmark.php", { id : d, s : s }, function(data){
		if(data != "OK") 
			showWarning("Bookmarks service: " + data);
		else
			refresh_page();
	});
	return false;
}

function hideMenu() {
	var r = $(".popupMenu");
	r.css("display", "none");
	r.html("");
}

