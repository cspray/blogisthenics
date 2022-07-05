<?php

namespace Cspray\Blogisthenics;

use Cspray\AnnotatedContainer\Attribute\Inject;
use Cspray\AnnotatedContainer\Attribute\Service;
use Cspray\AnnotatedContainer\Attribute\ServiceDelegate;
use Cspray\Blogisthenics\Exception\SiteValidationException;

#[Service]
class SiteConfigurationFactory {

    public function __construct(
        #[Inject('rootDir', from: BlogisthenicsParameterStore::STORE_NAME)]
        private readonly string $rootDirectory
    ) {}

    #[ServiceDelegate(SiteConfiguration::class)]
    public function createSiteConfiguration() : SiteConfiguration {
        return $this->getSiteConfiguration();
    }

    private function getSiteConfiguration() : SiteConfiguration {
        $filePath = $this->rootDirectory . '/.blogisthenics/config.json';
        if (is_file($filePath)) {
            $config = json_decode(file_get_contents($filePath), true);
        } else {
            $config = self::getDefaults();
        }

        $this->guardInvalidSiteConfigurationPreGeneration($config);

        return new SiteConfiguration(
            $this->rootDirectory,
            layoutDirectory: $config['layout_directory'],
            contentDirectory: $config['content_directory'],
            dataDirectory: $config['data_directory'] ?? null,
            outputDirectory: $config['output_directory'],
            defaultLayout: $config['default_layout'],
            includeDraftContent: $config['include_draft_content'] ?? false
        );
    }

    private function guardInvalidSiteConfigurationPreGeneration(array $rawConfiguration) : void {
        $this->validateDirectory($rawConfiguration, 'layout_directory', true);
        $this->validateDirectory($rawConfiguration, 'content_directory', true);
        $this->validateDirectory($rawConfiguration, 'output_directory', false);
        if (array_key_exists('data_directory', $rawConfiguration)) {
            $this->validateDirectory(
                $rawConfiguration,
                'data_directory',
                true,
                ' If your site does not require static data do not include this configuration value.'
            );
        }
    }

    private function validateDirectory(array $rawConfiguration, string $configKey, bool $mustExist, string $messageSuffix = null) : void {
        $configuredDir = $rawConfiguration[$configKey] ?? null;
        if (empty($configuredDir)) {
            $msg = sprintf('The "%s" specified in your .blogisthenics/config.json configuration contains a blank value.', $configKey);
            if (isset($messageSuffix)) {
                $msg .= $messageSuffix;
            }
            throw new SiteValidationException($msg);
        }

        if ($mustExist) {
            $layoutDir = $this->rootDirectory . '/' . $configuredDir;
            if (!is_dir($layoutDir)) {
                $msg = $this->getMissingDirectoryMessage($configKey, $configuredDir);
                if (isset($messageSuffix)) {
                    $msg .= $messageSuffix;
                }
                throw new SiteValidationException($msg);
            }
        }
    }

    private function getMissingDirectoryMessage(string $config, string $dir) : string {
        return sprintf(
            'The "%s" in your .blogisthenics/config.json configuration, "%s", does not exist.'  ,
            $config,
            $dir
        );
    }

    private static function getDefaults() : array {
        return [
            'layout_directory' => 'layouts',
            'content_directory' => 'content',
            'output_directory' => '_site',
            'default_layout' => 'main',
            'include_draft_content' => false
        ];
    }


}