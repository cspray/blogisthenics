<?php declare(strict_types=1);

namespace Cspray\Jasg;

use DateTimeImmutable;

interface Content {

    public function getDate() : DateTimeImmutable;

    public function getSourcePath() : string;

    public function getFrontMatter() : FrontMatter;

    public function getTemplate() : Template;

}