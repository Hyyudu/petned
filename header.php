<?php
/**
 * Created by PhpStorm.
 * User: Хыиуду
 * Date: 02.06.2016
 * Time: 23:30
 */

header('Content-type: text/html; charset=utf-8');
session_start();
require_once('db.php');
$db = new DB();

function gen_captcha()  {
    $symbols = array('+', '-', '*');
    $captcha = rand(1,9).$symbols[rand(0, count($symbols)-1)].rand(1,9).$symbols[rand(0, count($symbols)-1)].rand(1,9);
    $_SESSION['captcha'] = $captcha;
    return $captcha;
}

function show_message($msg) {
    if (!$msg['username'])
        $msg['username'] = 'Аноним';
    if ($msg['email'])
        $msg['email'] = '('.$msg['email'].')';
    echo "
    <div class=message id=msg".$msg['id'].">
        <span class=date>".$msg['date']." от Р.Х.</span>
        <div class=title>".$msg['title']."</div>
        <span class=username>".$msg['username']."</span> <span class=email>".$msg['email']."</span>
        <div class=msg_text>".$msg['text']."</div>
    </div>
    ";
}