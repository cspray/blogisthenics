<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics;

use DateTimeImmutable;

final class Page {

    private $sourcePath;
    private $postDate;
    private $frontMatter;
    private $template;

    public function __construct(
        string $sourcePath,
        DateTimeImmutable $postDate,
        PageFrontMatter $frontMatter,
        Template $template
    ) {
        $this->sourcePath = $sourcePath;
        $this->postDate = $postDate;
        $this->frontMatter = $frontMatter;
        $this->template = $template;
    }

    public function getDate() : DateTimeImmutable {
        return $this->postDate;
    }

    public function getFrontMatter() : PageFrontMatter {
        return $this->frontMatter;
    }

    public function getTemplate() : Template {
        return $this->template;
    }

    public function getSourcePath() : string {
        return $this->sourcePath;
    }

}