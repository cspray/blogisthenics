<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Cli;

use Amp\Process\Process;
use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Cli\Command;
use Cspray\AnnotatedContainer\Cli\Input;
use Cspray\AnnotatedContainer\Cli\TerminalOutput;
use Cspray\Blogisthenics\SiteConfiguration;
use Revolt\EventLoop;
use function Amp\async;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\pipe;

#[Service(profiles: ['cli'])]
class ServeCommand implements Command {

    public function __construct(
        private readonly SiteConfiguration $siteConfiguration
    ) {}

    public function getName() : string {
        return 'serve';
    }

    public function getHelp() : string {
        return <<<HELP
blogisthenics serve
HELP;

    }

    public function handle(Input $input, TerminalOutput $output) : int {
        $command = ['php', '-S', '1337'];
        $process = Process::start(
            command: $command,
            workingDirectory: $this->siteConfiguration->getRootDirectory()
        );

        async(fn() => pipe($process->getStdout(), getStdout()));
        async(fn() => pipe($process->getStderr(), getStderr()));

        EventLoop::onSignal(SIGINT, fn() => $process->kill());

        return $process->join();
    }
}