<?php declare(strict_types=1);

namespace Cspray\Jasg\Test\Support;

use Vfs\Node\File as VfsFile;

final class VirtualFile {

    private $frontMatter;
    private $contents;

    public function withFrontMatter(array $frontMatter) : VirtualFile {
        $this->frontMatter = $frontMatter;
        return $this;
    }

    public function withContent(string $contents) : VirtualFile {
        $this->contents = $contents;
        return $this;
    }

    public function build() : VfsFile {
        $stringFrontMatter = json_encode($this->frontMatter);
        // handle empty frontmatter should be interpreted as a JSON object and not an empty array
        if ($stringFrontMatter === '[]') {
            $stringFrontMatter = '{}';
        }
        $finalContents = $stringFrontMatter . PHP_EOL . $this->contents;
        return new VfsFile($finalContents);
    }

}