<?php
require_once('header.php');

echo "<h1>Гостевая книга России</h1>";

if (!isset($_SESSION['order']))
    $_SESSION['order'] = 'asc';
if (isset($_GET['order']) && in_array($_GET['order'], array('asc','desc')))
    $_SESSION['order'] = $_GET['order'];

echo "Упорядочить записи: ";
if ($_SESSION['order'] == 'asc')
    echo "<b>Сначала старые</b> <a href=?order=desc>Сначала новые</a>";
else
    echo "<a href=?order=asc>Сначала старые</a> <b>Сначала новые</b>";

// New message
echo "
<div id=send_error></div>
<table>";
$fields = array('username' => "Имя", 'email' => "Email", 'title' => "Заголовок", 'date' => "Дата", 'text' => "Текст сообщения");
foreach ($fields as $name=>$caption)    {
    echo "<tr><td>$caption</td><td>";
    if ($name=='text')
        echo "<textarea id=text></textarea>";
    elseif ($name=='date')
        echo "<input id=date value='".date("d.m.Y")."'>";
    else
        echo "<input id=$name>";
}
echo "<tr><td>Решите простой пример: <span id=captcha_text>".gen_captcha()."</span></td><td><input id=captcha></td>";

echo "</table><button onclick='send_message()'>Отправить сообщение</button>";


$messages = $db->fetchRows('select * from messages where status=1 order by id '.$_SESSION['order']);

foreach ($messages as $msg) {
    show_message($msg);
}


?>
<div id=splash>Новое сообщение добавлено и появится после проверки модератором!</div>

<link rel=stylesheet href=css/main.css>
<script src=js/jquery-1.12.4.js></script>
<script>
    function send_message() {
        $.ajax({
            url: 'ajax.php',
            method: 'POST',
            data: {
                action: 'new_message',
                username: $('#username').val(),
                email: $('#email').val(),
                title: $('#title').val(),
                date: $('#date').val(),
                text: $('#text').val(),
                captcha: $('#captcha').val()
            },
            success: function(a,b,c)    {
                eval('res='+a)
                if (!res.success)   {
                    $('#send_error').html(res.error)
                }
                else    {
//                    show info message
                    $('#splash').show(500, function(){setTimeout('$("#splash").hide(500)', 2000)})
//                    set new captcha
                    $('#captcha_text').html(res.new_captcha)
//                    clear fields
                    $('input').val('')
                    $('textarea').val('')
                }

            }
        })
    }
</script>