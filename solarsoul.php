<?php
require 'config.php';
require 'core/auth.inc.php';

$result = mysql_query("select * from `search_files` where `filepath` LIKE '/mnt/disk1/Music/Special/Solarsoul/Mixes \& Lives%' order by `filepath`, `filename`");

header("Content-type: text/xml");

echo "<data>\n";
while($row = mysql_fetch_assoc($result)) {
	$href = 'http://' . $cfg['site_name'] . '/download/' . $row['index'] . '/' . $row['filename'];
	echo "\t<song>\n\t\t<title><![CDATA[${row['audio_title']}]]></title>\n\t\t<link><![CDATA[${href}]]></link>\n\t</song>\n";
}
echo "</data>\n";

dbClose();

?>