# Панель отладки для CMS Битрикс.

Эта библиотека добавляет в Битрикс панель отладки [PHP Debug Bar](http://phpdebugbar.com/docs/).

## Установка

Для подключения панели следует вставить в `init.php` следующий код:

```php
BitrixDebugBar\Debug::init();
```

Панель отладчика отображается только если сайт находится в режиме отладки. Чтобы включить его, нужно установить в файле `bitrix/.settings.php` (или `bitrix/.settings_extra.php`) параметр настройки `exception_handling => debug` в `true` -

```
...
'exception_handling' => [
    'value' => [
        'debug' => true
        ...
    ]
    ...
]
```

Соответственно, чтобы на production-сервере панель не отображалась, задайте `exception_handling.debug` равным `false`.

## Использование

### Простой пример

```
use BitrixDebugBar\Debug;

// Выводим сообщения.
Debug::info('Hello!');
Debug::warning('Warning!');
Debug::error('Error!');
Debug::debug([
    'foo' => 'bar'
]);

// Показываем информацию об исключении.
try {
    throw new Exception('Exception!');
}
catch (Exception $e) {
    Debug::to('exceptions')->addException($e);
}

// Получаем используемый экземпляр класса DebugBar\StandardDebugBar.
$debugger = Debug::getInstance();
```

### Объяснение

Класс `Debug` предоставляет статические методы для доступа к объекту типа [DebugBar\StandardDebugBar](http://phpdebugbar.com/docs/readme.html#quick-start). С помощью метода Debug::to($name) мы можем получить нужный [коллектор](http://phpdebugbar.com/docs/data-collectors.html#using-collectors) для сообщений отладки. Вызовы же методов `info`, `warning` и прочее переадресовываются коллектору с именем `'messages'`.