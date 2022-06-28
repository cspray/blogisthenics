<?php declare(strict_types=1);

namespace Cspray\Blogisthenics;

final class SiteConfiguration {

    public function __construct(private readonly array $data) {}

    public function getLayoutDirectory() : string {
        return $this->data['layout_directory'];
    }

    public function getOutputDirectory() : string {
        return $this->data['output_directory'];
    }

    public function getDefaultLayoutName() : ?string {
        return $this->data['default_layout'];
    }

}