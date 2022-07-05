<?php

namespace Cspray\Blogisthenics\Test\Support;

use org\bovigo\vfs\vfsStreamDirectory as VirtualDirectory;
use org\bovigo\vfs\vfsStreamFile as VirtualFile;

trait HasVirtualFilesystemHelpers
{
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