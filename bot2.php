<?php declare(strict_types=1);
// Подключение авто загрузчика Composer для доступа к зависимостям
require_once 'vendor/autoload.php';

// Импортирование необходимых классов из библиотеки MadelineProto
use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\SimpleEventHandler;
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler\Filter\FilterCommand;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\EventHandler\Keyboard\InlineKeyboard;

// Путь к файлу сессии MadelineProto
$sessionPath = 'session.madeline';

// Определение класса обработчика событий для нашего Telegram бота
class BasicEventHandler extends SimpleEventHandler
{

    private ?LoggerInterface $customLogger = null;

    public function getCustomLogger(): LoggerInterface
    {
        if ($this->customLogger === null) {
            $this->customLogger = new Logger('botLogger');
            $this->customLogger->pushHandler(new StreamHandler(__DIR__ . '/bot.log', Logger::DEBUG));
        }

        return $this->customLogger;
    }




    // ID администратора для отправки отчетов и уведомлений
    public const ADMIN = "6384889815";

    // Возвращает список получателей для отправки отчетов
    public function getReportPeers()
    {
        return [self::ADMIN];
    }

    // Обработчик входящих сообщений
    #[Handler]
    public function handleMessage(Incoming&Message $message): void
    {
        try {
            // Получение текста сообщения
            $messageText = $message->message;
            // Проверка на наличие приветственных слов в сообщении
            if (stripos($messageText, 'привет') !== false || stripos($messageText, 'здравствуй') !== false) {
                // Отправка ответного приветствия
                $this->messages->sendMessage(peer: $message->senderId, message: 'Здравствуйте, уважаемый пользователь!');
                // Запись в лог информации об отправленном сообщении
                $this->getCustomLogger()->info("Приветствие отправлено пользователю: {$message->senderId}");
            }
        } catch (\Exception $e) {
            // Запись в лог информации об ошибке
            $this->getCustomLogger()->error("Ошибка при отправке сообщения: " . $e->getMessage());
        }
    }

    // Обработчик команды /start
    #[FilterCommand('start')]
    public function startCommand(Message $message): void
    {
        try {
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Посетить GitHub', 'url' => 'https://github.com'],
                    ]
                ]
            ];
            $this->messages->sendMessage(
                peer:  $message->senderId,
                message: 'Привет! Бот активен.',
                reply_markup: $keyboard,);
            $this->getCustomLogger()->info("Стартовое сообщение отправлено пользователю: {$message->senderId}");
        } catch (\Exception $e) {
            $this->getCustomLogger()->error("Ошибка при отправке стартового сообщения: " . $e->getMessage());
        }
    }



    // Метод, вызываемый при запуске бота
    public function onStart(): void
    {
        try {
            // Отправка уведомления администратору о запуске бота
            $this->messages->sendMessage(peer: self::ADMIN, message: 'Бот успешно запущен.');
            // Запись в лог информации о запуске бота
            $this->getCustomLogger()->info("Бот успешно запущен.");
        } catch (\Exception $e) {
            // Запись в лог информации об ошибке
            $this->getCustomLogger()->error("Ошибка при запуске бота: " . $e->getMessage());
        }
    }
}


// Запуск бота в бесконечном цикле обработки событий
try {
    BasicEventHandler::startAndLoop('bot.madeline');
} catch (\Exception $e) {
    // Обработка исключения при ошибке запуска бота и запись информации в консоль
    echo "Ошибка запуска бота: " . $e->getMessage();
}
