<?php

require_once('header.php');

$msg = $db->fetchRow('select * from messages where id=:msg_id', $_REQUEST);
show_message($msg);
?>
<form action=ajax.php method=POST>
    <input type=hidden name=action value=set_status>
    <input type=radio name=status value=0 <?=($msg['status']==0?'checked':'')?> id=status0><label for=status0>Новое</label>
    <input type=radio name=status value=1 <?=($msg['status']==1?'checked':'')?> id=status1><label for=status1>Утверждено</label>
    <input type=radio name=status value=2 <?=($msg['status']==2?'checked':'')?> id=status2><label for=status2>Отклонено</label>
    <input type=hidden name=id value=<?=$_REQUEST['msg_id']?>><br>
    <input type=submit>
</form>


<link rel=stylesheet href=css/main.css>