<?php declare(strict_types=1);

namespace Cspray\Jasg;

use Amp\Promise;

interface Template {

    public function getFormat() : string;

    public function render(Template\Context $context) : string;

}