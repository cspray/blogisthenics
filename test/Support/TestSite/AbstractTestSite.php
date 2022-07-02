<?php declare(strict_types=1);

namespace Cspray\Blogisthenics\Test\Support\TestSite;

use Cspray\Blogisthenics\Test\Support\VirtualFile;
use Vfs\FileSystem as VfsFileSystem;
use Vfs\Node\Directory as VfsDirectory;
use Vfs\Node\File as VfsFile;

abstract class AbstractTestSite implements TestSite {

    final public function populateVirtualFileSystem(VfsFileSystem $fileSystem) : void {
        $this->doPopulateVirtualFileSystem($fileSystem);
    }

    public function getDataProviders() : array {
        return [];
    }

    public function getTemplateHelperProviders() : array {
        return [];
    }

    abstract protected function doPopulateVirtualFileSystem(VfsFileSystem $vfs) : void;

    protected function content(array $frontMatter, string $contents, \DateTime $mtime = null) : VfsFile {
        $file = (new VirtualFile())->withFrontMatter($frontMatter)->withContent($contents)->build();
        if (isset($mtime)) {
            $file->setDateModified($mtime);
        }
        return $file;
    }

    protected function dir(array $nodes) {
        return new VfsDirectory($nodes);
    }

    protected function file(string $contents = '', \DateTime $mtime = null) : VfsFile {
        $file = new VfsFile($contents);
        if (isset($mtime)) {
            $file->setDateModified($mtime);
        }

        return $file;
    }


}