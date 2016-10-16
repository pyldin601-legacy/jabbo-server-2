
var newPosition = 0;

$(document).ready(function() {
	setInterval("putPos()", 10000);
});

function putPos() {
	newPosition = Math.floor(jwplayer('mediaspace').getPosition());
	if(jwplayer('mediaspace').getDuration() > 0) {
		var pc = Math.floor(100 / jwplayer('mediaspace').getDuration() * jwplayer('mediaspace').getPosition());
		//if(window.opener) window.opener.$('#watch-' + fileIndex).css('width', pc + '%'); 
	} 
	$.post("/videopos.php", {i:fileIndex,p:newPosition});
}


function goNext(nINDEX, nCRC) {
	newPosition = Math.floor(jwplayer('mediaspace').getDuration());
	$.post("/videopos.php", {i:fileIndex,p:newPosition}, function(data) {
		window.location.href = "http://jabbo.tedirens.com/watch?v=" + nINDEX + "&t=" + nCRC;
	});
}
