<?php

namespace Blazemedia\Shortlinks\DB;

interface DB {

    function save( string $url ) : string;

    function load( string $id ) : string;

    function exists( string $url ) : string|bool;
}
