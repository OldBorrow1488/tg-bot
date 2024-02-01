<?php

require_once 'madeline81.phar';

use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Plugin\RestartPlugin;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\SimpleEventHandler;


// Путь к файлу сессии
$sessionPath = 'session.madeline';

$MadelineProto = new API('session.madeline');
$MadelineProto->start();

class MyEventHandler extends \danog\MadelineProto\EventHandler
{
    public function onStart(array $update): \Generator
    {
        $chatID = $this->getInfo()['bot_id'];
        $messageID = $update['message']['id'];

        yield $this->messages->sendMessage([
            'peer' => $chatID,
            'message' => 'Бот готов к работе!',
            'reply_to_msg_id' => $messageID,
        ]);
    }
}

// Ваш ID в Telegram
$yourUserId = 6384889815;

// Текст тестового сообщения
$testMessage = 'Привет, это тестовое сообщение от твоего бота!';

try {
    // Отправляем тестовое сообщение
    $MadelineProto->messages->sendMessage(['peer' => $yourUserId, 'message' => $testMessage]);

    // Отправляем сообщение о готовности к работе при команде /start
    $MadelineProto->messages->sendMessage(['peer' => $yourUserId, 'message' => 'Бот готов к работе!']);
} catch (\danog\MadelineProto\Exception $e) {
    // Обработка ошибок, если они возникнут
    echo 'Ошибка: ' . $e->getMessage();
}
