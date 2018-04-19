<?php
$dir    = './';
$files1 = scandir($dir);

foreach($files1 as $f) {
    echo "$f \n\r <br />";
}
?>
