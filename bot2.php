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
use danog\MadelineProto\EventHandler\CallbackQuery;
use danog\MadelineProto\MTProto;

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
            if (stripos($messageText, 'Привет') !== false || stripos($messageText, 'Здравствуй') !== false) {
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
    public function startCommand(Message $message): void {
        $this->sendMenu($message->senderId);
        $this->getCustomLogger()->info("Меню отправлено пользователю: {$message->senderId}");
    }

    // Обработчик команды /help
    #[FilterCommand('help')]
    public function helpCommand(Message $message): void
    {
        try {
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => 'Посетить GitHub репозиторий', 'url' => 'https://github.com/OldBorrow1488/tg-bot'],
                    ],
                ]
            ];
            $this->messages->sendMessage(
                peer:  $message->senderId,
                message: 'Привет! Вот инструкция по использованию бота.',
                reply_markup: $keyboard,);
            $this->getCustomLogger()->info("Сообщение инструкция отправлено пользователю: {$message->senderId}");
        } catch (\Exception $e) {
            $this->getCustomLogger()->error("Ошибка при отправке стартового сообщения: " . $e->getMessage());
        }
    }

    // Обработчик команды, отправляющий обычную клавиатуру
    #[FilterCommand('keyboard')]
    public function sendReplyKeyboard(Message $message): void
    {
        // Создание и отправка обычной клавиатуры
        $replyKeyboard = [
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
            'keyboard' => [
                [['text' => 'Привет']],
                [['text' => 'Здравствуй']],
                [['text' => '/start']],
            ],
        ];
        $this->messages->sendMessage(
            peer: $message->senderId,
            message: 'Пожалуйста, выберите один из вариантов:',
            reply_markup: $replyKeyboard,
        );
    }

    public function onAny($update): void
    {
        // Проверка на колбэк-запрос
        if (isset($update['_']) && $update['_'] === 'updateBotCallbackQuery') {

            $this->onCallbackQuery($update);
        }
    }



    // Обработчик колбэк-квэри (нажатий на инлайн-кнопки)
    public function onCallbackQuery($update) {

        $this->getCustomLogger()->info("Получен колбэк-запрос: " . json_encode($update));

        $callbackData = $update['data'];
        $chatID = $update['peer'];
        $messageID = $update['msg_id'];
        $this->getCustomLogger()->info("Получены данные: " . $callbackData);
        switch ($callbackData) {
            case 'about':
                $this->messages->editMessage(
                    no_webpage: true,
                    peer: $chatID,
                    id: $messageID,
                    message: 'Информация о боте: [Описание бота]',
                );
                break;
            case 'help':
                $this->messages->editMessage(
                    no_webpage: true,
                    peer: $chatID,
                    id: $messageID,
                    message:  'Как использовать бота: [Инструкция]',
                );
                break;
            case 'links':
                $this->messages->editMessage(
                    no_webpage: true,
                    peer: $chatID,
                    id: $messageID,
                    message: 'Полезные ссылки: [Ссылки]',
                );
                break;
            default:
                $this->messages->sendMessage(
                    no_webpage: true,
                    peer: $chatID,
                    message: 'Неизвестная команда.',
                );
                break;
        }

        // Отправка подтверждения обработки колбэк-запроса
        try {
            $this->messages->getBotCallbackAnswer(
                game: false,
                peer: $chatID,
                msg_id: $messageID,
                data: $callbackData,
            );
        } catch (\Exception $e) {
            $this->getCustomLogger()->error("Ошибка при отправке ответа на колбэк-запрос: " . $e->getMessage());
        }
    }

    // Метод для отправки меню с инлайн-кнопками
    public function sendMenu($chatID) {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'О боте', 'callback_data' => 'about']],
                [['text' => 'Помощь', 'callback_data' => 'help']],
                [['text' => 'Ссылки', 'callback_data' => 'links']],
            ],
        ];

        $this->messages->sendMessage(
            peer: $chatID,
            message:  'Выберите опцию из меню:',
            reply_markup: $keyboard,
        );
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
