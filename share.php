<?php

	if(isset($_GET['url'], $_GET['text'])) {
		$url = ($_GET['url']);
		$text = ($_GET['text']);
	} else {
		$url = '';
		$text = '';
	}

?>

<HTML>
<HEAD>
	<TITLE>Mega Sharer</TITLE>
</HEAD>
<BODY>
<FORM TYPE='GET'>
u: <INPUT SIZE="40" TYPE="TEXT" NAME="url" VALUE="<?php echo $url; ?>"><BR>
t: <INPUT SIZE="40" TYPE="TEXT" NAME="text" VALUE="<?php echo $text; ?>"><BR>
<INPUT TYPE="SUBMIT" VALUE="Go">
</FORM>
<BR><BR>
<A HREF="http://twitter.com/intent/tweet?text=<?php echo urlencode($text . ' ' . $url); ?>&source=clicktotweet">Twitter</A><BR>
<A HREF="http://www.facebook.com/sharer.php?u=<?php echo urlencode($url); ?>&t=<?php echo urlencode($text); ?>">Facebook</A><BR>
<A HREF="http://vkontakte.ru/share.php?url=<?php echo $url; ?>">VK.com</A><BR>
</BODY>
</HTML>