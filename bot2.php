<?php declare(strict_types=1);
require_once 'vendor/autoload.php';

use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\SimpleEventHandler;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\Filter\FilterCommand;
use danog\MadelineProto\EventHandler\SimpleFilter\FromAdmin;


$sessionPath = 'session.madeline';

class BasicEventHandler extends SimpleEventHandler
{
    public const ADMIN = "6384889815";

    public function getReportPeers()
    {
        return [self::ADMIN];
    }

    public static function getPlugins(): array
    {
        return [];
    }

    #[Handler]
    public function handleMessage(Incoming&Message $message): void
    {
        // Обработка входящих сообщений
        $messageText = $message->message;

        if (stripos($messageText, 'привет') !== false || stripos($messageText, 'здравствуй') !== false) {
            // Реакция на приветствие
            $this->messages->sendMessage(peer: $message->senderId, message: 'Здравствуйте, уважаемый пользователь!');
        }
    }


    #[FilterCommand('start')]
    public function startCommand(Message $message): void
    {
        $this->messages->sendMessage(peer: $message->senderId, message: 'Привет! Бот активен.');
    }

    #[FilterCommand('help')]
    public function helpCommand(Message $message): void
    {
        $this->messages->sendMessage(peer: $message->senderId, message: 'Привет! Вот руководство:');
    }
    public function onStart(): void
    {
        // Отправка сообщения при запуске бота
        $this->messages->sendMessage(peer: self::ADMIN, message: 'Бот успешно запущен.');
    }



}


BasicEventHandler::startAndLoop('bot.madeline');
