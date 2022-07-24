<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\ParameterStore;
use Cspray\Typiphy\Type;
use Cspray\Typiphy\TypeIntersect;
use Cspray\Typiphy\TypeUnion;

final class BlogisthenicsParameterStore implements ParameterStore {

    public const STORE_NAME = 'blogisthenics';

    private array $data = [];

    public function __construct(string $rootDirectory) {
        $this->data['rootDir'] = $rootDirectory;
    }

    public function getName() : string {
        return self::STORE_NAME;
    }

    public function fetch(TypeUnion|Type|TypeIntersect $type, string $key) : mixed {
        return $this->data[$key] ?? null;
    }
}