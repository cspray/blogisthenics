<?php

namespace Cspray\Blogisthenics\Test\Integration;

use Cspray\Blogisthenics\Cli\CliApp;
use PHPUnit\Framework\TestCase;

class CliAppTest extends TestCase {

    public function testCommandWithNoArgsOutputsHowToListCommands() : void {
        $cliApp = new CliApp();

        $this->expectOutputRegex('#List Commands:\s+\./blogisthenics help#');

        $cliApp->runCommand();
    }

    public function testCommandWithNoArgsOutputsHowToGetCommandHelp() : void {
        $cliApp = new CliApp();

        $this->expectOutputRegex('#Command Usage:\s+\./blogisthenics <command> help#');

        $cliApp->runCommand();
    }

    public function testHelpCommandListsBuildAsAvailableOption() : void {
        $cliApp = new CliApp();

        $this->expectOutputRegex('#build#');

        $cliApp->runCommand(['./blogisthenics', 'help']);
    }

}