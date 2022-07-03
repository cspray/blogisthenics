<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Cspray\Blogisthenics\Test\Support\VirtualContent;
use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use org\bovigo\vfs\vfsStreamFile as VirtualFile;

abstract class AbstractTestSite implements TestSite {

    final public function populateVirtualFileSystem(VirtualDirectory $dir) : void {
        $this->doPopulateVirtualFileSystem($dir);
    }

    public function getDataProviders() : array {
        return [];
    }

    public function getTemplateHelperProviders() : array {
        return [];
    }

    abstract protected function doPopulateVirtualFileSystem(VirtualDirectory $dir) : void;

    protected function content(string $fileName, array $frontMatter, string $contents, \DateTime $mtime = null) : VirtualFile {
        $file = (new VirtualContent())
            ->withFileName($fileName)
            ->withFrontMatter($frontMatter)
            ->withContent($contents)
            ->build();
        if (isset($mtime)) {
            $file->lastModified($mtime->getTimestamp());
        }
        return $file;
    }

    protected function dir(string $dirName, array $nodes) : VirtualDirectory {
        $dir = new VirtualDirectory($dirName);
        foreach ($nodes as $virtualFileOrDirectory) {
            $dir->addChild($virtualFileOrDirectory);
        }

        return $dir;
    }

    protected function file(string $fileName, string $contents = '', \DateTime $mtime = null) : VirtualFile {
        $file = new VirtualFile($fileName);
        $file->withContent($contents);
        if (isset($mtime)) {
            $file->lastModified($mtime->getTimestamp());
        }

        return $file;
    }


}