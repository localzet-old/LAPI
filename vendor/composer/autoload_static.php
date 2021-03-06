<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit833b62153b989ff452ead6964fa859cb
{
    public static $prefixesPsr0 = array (
        'H' => 
        array (
            'HTTP_Request2' => 
            array (
                0 => __DIR__ . '/..' . '/pear/http_request2',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Net_URL2' => __DIR__ . '/..' . '/pear/net_url2/Net/URL2.php',
        'PEAR_Exception' => __DIR__ . '/..' . '/pear/pear_exception/PEAR/Exception.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit833b62153b989ff452ead6964fa859cb::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit833b62153b989ff452ead6964fa859cb::$classMap;

        }, null, ClassLoader::class);
    }
}
