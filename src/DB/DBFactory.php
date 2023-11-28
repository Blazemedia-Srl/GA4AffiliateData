<?php

namespace Blazemedia\Shortlinks\DB;

use Blazemedia\Shortlinks\DB\MongoDB;

class DBFactory {

    static function getDB( $engine, $connectionString ) {

        switch( $engine ) {

            case 'mongodb' : return new MongoDB( $connectionString );
        }

    }

}
