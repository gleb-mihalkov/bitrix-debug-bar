<?php
namespace BitrixDebugBar
{
    use DebugBar\StandardDebugBar;
    use Webmozart\PathUtil\Path;
    use Bitrix\Main\Config\Configuration;
    use Bitrix\Main\EventManager;
    use Bitrix\Main\Page\Asset;

    /**
     * Обертка для управления отладчиком.
     * @link http://phpdebugbar.com/docs/ Документация самого отладчика
     */
    class Debug
    {
        /**
         * Экземпляр отладчика.
         * @internal
         * @var StandardDebugBar
         */
        protected static $debugger;

        /**
         * Инициализирует отладчик.
         * @return void
         */
        public static function init()
        {
            self::$debugger = new StandardDebugBar();

            $events = EventManager::getInstance();
            $events->addEventHandler('main', 'OnEpilog', __CLASS__.'::onEpilog');
        }

        /**
         * Получает экземпляр отладчика.
         * @return StandardDebugBar Экземпляр отладчика.
         */
        public static function getInstance()
        {
            return self::$debugger;
        }

        /**
         * Возвращает коллектор отладчика.
         * @param  string $collector Имя коллектора.
         * @return mixed             Коллектор.
         */
        public static function to($collector = 'messages')
        {
            return self::$debugger[$collector];
        }

        /**
         * Переадресовывает вызов методу коллектора отладчика.
         * @internal
         * @param  string       $name Имя метода.
         * @param  array<mixed> $args Аргументы.
         * @return mixed              Результат работы метода.
         */
        public static function __callStatic($name, $args)
        {
            $collector = self::to();
            return call_user_func_array([$collector, $name], $args);
        }



        /**
         * Показывает, следует ли отображать панель отладки.
         * @internal
         * @return boolean True или false.
         */
        protected static function isDisplay()
        {
            $isDisplay = self::isPublicPart() && self::isDebug();
            return $isDisplay;
        }

        /**
         * Показывает, выполняется ли данный код в публичном разделе сайта.
         * @internal
         * @return boolean True или false.
         */
        protected static function isPublicPart()
        {
            $script = $_SERVER['SCRIPT_FILENAME'];
            $script = '/'.Path::makeRelative($script, $_SERVER['DOCUMENT_ROOT']);

            $admin = '/bitrix';

            $isPublic = !Path::isBasePath($admin, $script);
            return $isPublic;
        }

        /**
         * Показывает, работает ли сайт в режиме отладки.
         * @internal
         * @return boolean True или false.
         */
        protected static function isDebug()
        {
            $config = Configuration::getInstance()->get('exception_handling');
            $isDebug = $config['debug'];
            return $isDebug;
        }

        /**
         * Проверяет, является ли текущий запрос асинхронным.
         * @internal
         * @return boolean True или false.
         */
        protected static function isAjax()
        {
            $header = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                ? $_SERVER['HTTP_X_REQUESTED_WITH']
                : '';

            $isAjax = strtolower($header) == 'xmlhttprequest';
            return $isAjax;
        }

        /**
         * Обрабатывает завершение генерации страницы.
         * @internal
         * @return void
         */
        public static function onEpilog()
        {
            if (!self::isDisplay()) return;
            self::render();
        }

        /**
         * Добавляет файлы ресурсов отладчика в общей список ресурсов Битрикс.
         * @internal
         * @param string        $method Имя метода, с помощью которого будут добавлены ресурсы.
         * @param array<string> $files  Список абсолютных имен файлов - ресурсов.
         */
        protected static function addFiles($method, $files)
        {
            $assets = Asset::getInstance();

            foreach ($files as $file)
            {
                $path = '/'.Path::makeRelative($file, $_SERVER['DOCUMENT_ROOT']);
                $assets->$method($path);
            }
        }

        /**
         * Добавляет inline javascript вставку к ресурсам Битрикс.
         * @internal
         * @param string $script Код.
         */
        protected static function addScript($script)
        {
            $assets = Asset::getInstance();
            $assets->addString($script);
        }

        /**
         * Оборачивает код inline javascript так, чтобы он выполнялся после загрузки
         * страницы в браузере.
         * @internal
         * @param  string $script Код javascript.
         * @return string         Модифицированный код.
         */
        protected static function setCallOnReady($script)
        {
            $script = preg_replace('/\<script[^>]+\>/', '', $script);
            $script = preg_replace('/\<\/script\>/', '', $script);

            $script = 'document.addEventListener("DOMContentLoaded",function(){'.$script.'});';
            $script = '<script type="text/javascript">'.$script.'</script>';

            return $script;
        }

        /**
         * Добавляет отображение панели отладчика.
         * @internal
         * @return void
         */
        protected static function render()
        {
            if (self::isAjax())
            {
                self::$debugger->sendDataInHeaders();
                return;
            }

            $render = self::$debugger->getJavascriptRenderer();

            $assets = $render->getAssets();
            self::addFiles('addCss', $assets[0]);
            self::addFiles('addJs', $assets[1]);

            $script = $render->render();
            $script = self::setCallOnReady($script);
            self::addScript($script);
        }
    }
}