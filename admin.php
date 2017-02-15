<?php

require_once('header.php');
$messages = $db->fetchRows('select id, username, title, text, status from messages order by id desc');

$fields = array('id', 'username', 'title', 'text');

echo "<table border=1><tr>";
foreach ($fields as $field)
    echo "<td>".$field;
foreach ($messages as $msg) {
    $msg['text'] = mb_substr(strip_tags($msg['text']), 0, 20)."...";
    echo "\n<tr class=status".$msg['status'].">";
    foreach ($fields as $field) {
        if ($field=='id')
            $val = "<a href=edit.php?msg_id=".$msg['id'].">".$msg['id']."</a>";
        else
            $val = $msg[$field];
        echo "<td>$val";
    }
}
?>
</table>

<link rel=stylesheet href=css/main.css>

