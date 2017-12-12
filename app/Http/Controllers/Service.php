<?php

namespace App\Http\Controllers;

class Service extends Controller
{

    public function app( string $direction, string $action, string $args = null )
    {
        $app = "$direction:$action";
        return view( 'welcome', ['word' => null, 'word_relation' => null, 'app' => $app, 'args' => $args] );
    }
}