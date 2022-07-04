<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Service;
use League\CommonMark\GithubFlavoredMarkdownConverter;

#[Service]
final class GitHubFlavoredMarkdownFormatter implements Formatter {

    private readonly GithubFlavoredMarkdownConverter $converter;

    public function __construct() {
        $this->converter = new GithubFlavoredMarkdownConverter();
    }

    public function getFormatType() : string {
        return 'md';
    }

    public function format(string $contents) : string {
        return $this->converter->convert($contents);
    }
}