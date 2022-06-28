<?php

namespace Cspray\JasgFixture;

abstract class AbstractFixture implements Fixture {

    public function getContentPath(string $file) : string {
        return sprintf('%s/%s', $this->getPath(), $file);
    }

    public function getContents(string $file) : string {
        return file_get_contents($this->getContentPath($file));
    }
}