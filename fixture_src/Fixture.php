<?php

namespace Cspray\JasgFixture;

interface Fixture {

    public function getPath() : string;

    public function getContentPath(string $file) : string;

    public function getContents(string $file) : string;

}