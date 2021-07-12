<?php
/**
 * Autoloader Klasse zum automatischen Einbinden der benötigten Klassendateien
 * @author Lars Münchhagen <lars.muenchhagen@outlook.de>
 * @version 0.0.1
 *
 */


namespace system;


/*--------------------TODO AND FIX----------*/

require ROOT.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'Settings.php';

class Autoloader
{

    /*--------------------PUBLIC----------------*/
    public function __construct()
    {
        spl_autoload_register(array($this, 'load_class'));
    }

    public static function register()
    {
        new Autoloader();
    }

    public function load_class($class_name){
        spl_autoload_register(function ($class_name) {
            $fileName = $class_name.'.php';

            if (file_exists($fileName)) {
                require_once $fileName;
            }
        });
    }


}