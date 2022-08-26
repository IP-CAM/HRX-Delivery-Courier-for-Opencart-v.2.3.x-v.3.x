<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitc699ad291530560ea0fe3bb3a031d87b
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitc699ad291530560ea0fe3bb3a031d87b', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitc699ad291530560ea0fe3bb3a031d87b', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitc699ad291530560ea0fe3bb3a031d87b::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
