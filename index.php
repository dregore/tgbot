<?php
require __DIR__ . '/vendor/autoload.php';

use CentralBankRussian\ExchangeRate\CBRClient;
use CentralBankRussian\ExchangeRate\Exceptions\ExceptionIncorrectData;
use CentralBankRussian\ExchangeRate\Exceptions\ExceptionInvalidParameter;
use CentralBankRussian\ExchangeRate\ExchangeRate;

$token = "5191109278:AAGWEVkjVHB6K0laNlCccFYdcAOw6wssLh8";
$bot = new \TelegramBot\Api\Client($token);

$menu = [['/help', '/rate', '/subscribe', '/history']];
$keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(
    $menu, 
    false, // onetime keyboard
    true // resize keyboard vertically for optimal fit
);

// DBAL
$connectionParams = array(
    'dbname' => 'tgbot',
    'user' => 'tgbot',
    'password' => 'GEBPS$FE3pd,2t[D',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
);
$conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
$queryBuilder = $conn->createQueryBuilder();

// команда для start
$bot->command('start', function ($message) use ($bot, $keyboard) {
    $answer = 'Добро пожаловать!';
    $bot->sendMessage($message->getChat()->getId(), $answer, null, false, null, $keyboard);
});

// команда для помощи
$bot->command('help', function ($message) use ($bot) {
    $answer = 'Команды:
               /help - вывод справки,
               /rate - получение курса валюты,
               /subscribe - подписаться на получение курса валюты
               /history - история запросов курсов валюты';
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// команда для получения курса валюты
$bot->command('rate', function ($message) use ($bot, $queryBuilder) {
    $exchangeRate = new ExchangeRate(new CBRClient());
    try {
         $currencyRate = $exchangeRate
             ->setDate(new DateTime(date('Y-m-d')))
             ->getCurrencyExchangeRates()
             ->getCurrencyRateBySymbolCode('USD');
    
        $answer = 'На '.date('d-m-Y').' курс '.($currencyRate->getQuantity() ?? 1).' '.$currencyRate->getName().' равен '.$currencyRate->getExchangeRate().' рублей';
        // Записываем по этому юзеру историю в базу
        $queryBuilder->insert('history')
                     ->values(['user_id' => '?', 
                               'inserted_at' => '?', 
                               'currency_quantity' => '?', 
                               'currency_name' => '?', 
                               'currency_rate' => '?'
                      ])
                      ->setParameter(0, $message->getChat()->getId())
                      ->setParameter(1, date('Y-m-d H:i:s'))
                      ->setParameter(2, $currencyRate->getQuantity())
                      ->setParameter(3, $currencyRate->getName())
                      ->setParameter(4, $currencyRate->getExchangeRate())
        ;
        $queryBuilder->execute();
    }
    catch (ExceptionIncorrectData | ExceptionInvalidParameter $e) {
        $answer = $e->getMessage();
    }
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// команда для подписки на курсы
// записываем $message->getChat()->getId() в базу
// отправляем раз в сутки курс
$bot->command('subscribe', function ($message) use ($bot, $queryBuilder) {
    $queryBuilder->insert('subscriptions')
                 ->values(['user_id' => '?'])
                 ->setParameter(0, $message->getChat()->getId());
    $result = $queryBuilder->execute();

    if($result){
        $answer = 'Вы подписаны на получение курсов валют 1 раз в сутки';
    }
    else{
        $answer = 'Что-то пошло не так';
    }
    $bot->sendMessage($message->getChat()->getId(), $answer);
});

// команда для получения истории по курсам
// нажимая - выдаем даты-кнопки с запросами
// по кнопкам - выдаем курс
$bot->command('history', function ($message) use ($bot, $queryBuilder) {
    $answer = '';
    $queryBuilder->select('*')
                 ->from('history')
                 ->where('user_id = ?')
                 ->setParameter(0, $message->getChat()->getId());
    $a = $queryBuilder->fetchAllAssociative();
    foreach($a as $item){
        $date = new DateTime($item['inserted_at']);
        $user_date = $date->format('d-m-Y H:i:s');
        $answer .= 'На '.$user_date.' курс '.($item['currency_quantity'] ?? 1).' '.$item['currency_name'].' равен '.$item['currency_rate'].' рублей'."\r\n";
    }

    $bot->sendMessage($message->getChat()->getId(), $answer);
});

$bot->run();
