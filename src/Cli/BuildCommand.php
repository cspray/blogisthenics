<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Cli;

use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Cli\Command;
use Cspray\AnnotatedContainer\Cli\Input;
use Cspray\AnnotatedContainer\Cli\TerminalOutput;
use Cspray\Blogisthenics\Engine;

#[Service(profiles: ['cli'])]
final class BuildCommand implements Command {

    public function __construct(
        private readonly Engine $engine
    ) {}

    public function getName() : string {
        return 'build';
    }

    public function getHelp() : string {
        return <<<HELP
blogisthenics build
HELP;

    }

    public function handle(Input $input, TerminalOutput $output) : int {
        $this->engine->buildSite();
        return 0;
    }
}