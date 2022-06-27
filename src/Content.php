<?php declare(strict_types=1);


namespace Cspray\Jasg;

use DateTimeImmutable;

final class Content {

    public function __construct(
        private readonly string $name,
        private readonly DateTimeImmutable $postDate,
        private readonly FrontMatter $frontMatter,
        private readonly Template $template
    ) {}

    public function getName() : string {
        return $this->name;
    }

    public function getDate() : DateTimeImmutable {
        return $this->postDate;
    }

    public function getFrontMatter() : FrontMatter {
        return $this->frontMatter;
    }

    public function getTemplate() : Template {
        return $this->template;
    }

}