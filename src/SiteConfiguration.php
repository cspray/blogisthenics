<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics;

final class SiteConfiguration {

    private $data;

    public function __construct(array $config) {
        $this->data = $config;
    }

    public function getLayoutDirectory() : string {
        return $this->data['layout_directory'];
    }

    public function getOutputDirectory() : string {
        return $this->data['output_directory'];
    }

    public function getDefaultLayoutName() : string {
        return $this->data['default_layout'];
    }

}