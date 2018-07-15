<?php declare(strict_types=1);

namespace Cspray\Jasg\Test\Support;

use Vfs\FileSystem as VfsFileSystem;
use Vfs\Node\Directory as VfsDirectory;
use Vfs\Node\File as VfsFile;

abstract class AbstractTestSite {

    final public function populateVirtualFileSystem(VfsFileSystem $vfs) {
        $this->doPopulateVirtualFileSystem($vfs);
    }

    abstract protected function doPopulateVirtualFileSystem(VfsFileSystem $vfs);

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

    protected function file(string $contents = '') : VfsFile {
        return new VfsFile($contents);
    }


}