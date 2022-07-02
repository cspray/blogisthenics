<?php

namespace Cspray\Blogisthenics\Cli;

use Minicli\App;

final class CliApp extends App {

    public function __construct() {
        parent::__construct([
            'app_path' => [
                __DIR__ . '/Command',
                '@minicli/command-help'
            ]
        ], $this->getBlogisthenicsSignature());
    }

    private function getBlogisthenicsSignature() : string {
        return <<<TEXT
    ____  __            _      __  __               _          
   / __ )/ /___  ____ _(_)____/ /_/ /_  ___  ____  (_)_________
  / __  / / __ \/ __ `/ / ___/ __/ __ \/ _ \/ __ \/ / ___/ ___/
 / /_/ / / /_/ / /_/ / (__  ) /_/ / / /  __/ / / / / /__(__  ) 
/_____/_/\____/\__, /_/____/\__/_/ /_/\___/_/ /_/_/\___/____/  
              /____/ 
              
              
List Commands: 

./blogisthenics help

Command Usage:

./blogisthenics <command> help
TEXT;
    }

}