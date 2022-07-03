<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support;

use org\bovigo\vfs\vfsStreamFile as VirtualFile;

final class VirtualContent {

    private string $fileName;
    private array $frontMatter;
    private string $contents;

    public function withFileName(string $fileName) : VirtualContent {
        $this->fileName = $fileName;
        return $this;
    }

    public function withFrontMatter(array $frontMatter) : VirtualContent {
        $this->frontMatter = $frontMatter;
        return $this;
    }

    public function withContent(string $contents) : VirtualContent {
        $this->contents = $contents;
        return $this;
    }

    public function build() : VirtualFile {
        $stringFrontMatter = json_encode((object) $this->frontMatter);
        $finalContents = $stringFrontMatter . PHP_EOL . $this->contents;
        $file = new VirtualFile($this->fileName);
        $file->withContent($finalContents);
        return $file;
    }

}