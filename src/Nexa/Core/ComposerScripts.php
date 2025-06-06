<?php

namespace Nexa\Core;

class ComposerScripts
{
    public static function postAutoloadDump($event = null)
    {
        // Post autoload dump logic
        // This can be used to perform tasks after composer autoload is generated
        
        if ($event && method_exists($event, 'getIO')) {
            $io = $event->getIO();
            $io->write('Nexa Framework: Autoload dump completed successfully.');
        } else {
            echo "Nexa Framework: Autoload dump completed successfully.\n";
        }
    }
}