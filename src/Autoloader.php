<?php namespace andy87\knock_knock;

/**
 * @name: KnockKnock
 * @author Andrey and_y87 Kidin
 * @description Авто загрузчик
 * @homepage: https://github.com/andy87/KnockKnock
 * @license CC BY-SA 4.0 http://creativecommons.org/licenses/by-sa/4.0/
 * @date 2024-05-27
 * @version 1.3.0
 */

/**
 * Implements a lightweight PSR-0 compliant autoloader for KnockKnock.
 */
class Autoloader
{
    private string $directory;
    private string $prefix;
    private int $prefixLength;


    /**
     * @param string $baseDirectory
     */
    public function __construct(string $baseDirectory = __DIR__)
    {
        $this->directory = $baseDirectory;

        $this->prefix = __NAMESPACE__ . '\\';

        $this->prefixLength = strlen($this->prefix);
    }

    /**
     * @param bool $prepend
     */
    public static function register(bool $prepend = false): void
    {
        spl_autoload_register([new self(), 'autoload'], true, $prepend);
    }

    /**
     * @param string $className
     */
    public function autoload(string $className): void
    {
        if (str_starts_with($className, $this->prefix)) {
            $parts = explode('\\', substr($className, $this->prefixLength));

            $filepath = $this->directory . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';

            if (is_file($filepath)) require $filepath;
        }
    }
}
