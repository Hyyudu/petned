<?php
/**
 * Created by PhpStorm.
 * User: Хыиуду
 * Date: 22.05.2016
 * Time: 11:26
 */

require_once('header.php');

function quit($error = '') {
    global $result;
    if ($error) {
        $result['error'] = $error;
        $result['success'] = false;
    }
    die(json_encode($result));
}

$result = array('success' => true);

$action = $_REQUEST['action'];
if ($action== 'new_message')    {
    if (!trim($_REQUEST['text']))
        $result['error'].="Текст сообщения не может быть пустым!<br>";
    if ($_REQUEST['captcha'] != eval('return '.$_SESSION['captcha'].';'))
        $result['error'] .= "Неверный ответ на пример!";
    if (!$result['error']) {
        $db->insert('messages', $_REQUEST);
        $result['new_captcha'] = gen_captcha();
    }
}
elseif ($action=='set_status')  {
    $db->query('update messages set status=:status where id=:id', $_REQUEST);
    header("Location: admin.php");
}
if ($result['error'])
    $result['success'] = false;
quit();