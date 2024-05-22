<?php

namespace Cspray\Blogisthenics\Bootstrap;

use Cspray\AnnotatedContainer\ContainerFactory\ParameterStore;
use Cspray\Typiphy\Type;
use Cspray\Typiphy\TypeIntersect;
use Cspray\Typiphy\TypeUnion;

/**
 * Provides access to a limited set of injectable parameters exposing metadata about blogisthenics.
 *
 * The following array shape defines the keys and types exposed by this ParameterStore. To make used of it use the
 * following Attribute:
 *
 * #[Inject('...', from: 'blogisthenics')]
 *
 * @psalm-type MetaDataPayload = array{
 *     rootDir: string
 * }
 */
final class BlogisthenicsMetaDataParameterStore implements ParameterStore {

    private array $data = [];

    public function __construct(string $rootDirectory) {
        $this->data['rootDir'] = $rootDirectory;
    }

    public function getName() : string {
        return 'blogisthenics';
    }

    public function fetch(TypeUnion|Type|TypeIntersect $type, string $key) : mixed {
        return $this->data[$key] ?? null;
    }
}