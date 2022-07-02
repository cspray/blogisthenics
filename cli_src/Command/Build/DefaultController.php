<?php

namespace Cspray\Blogisthenics\Cli\Command\Build;

use Minicli\Command\CommandController;

class DefaultController extends CommandController {

    public function handle() : void {
        $this->getApp()->blogisthenics->buildSite();
    }

}