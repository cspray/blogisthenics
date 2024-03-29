<?php

namespace Cspray\Blogisthenics\Test\Unit;

use Cspray\Blogisthenics\Exception\InvalidStateException;
use Cspray\Blogisthenics\SiteGeneration\Content;
use Cspray\Blogisthenics\SiteGeneration\ContentCategory;
use Cspray\Blogisthenics\Template\FrontMatter;
use Cspray\Blogisthenics\Template\Template;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase {

    public function testNoFrontMatterAttributePresentDefaultsToPublished() : void {
        $content = new Content(
            'name',
            new \DateTimeImmutable(),
            new FrontMatter([]),
            $this->getMockBuilder(Template::class)->getMock(),
            ContentCategory::Asset,
            null,
            null
        );

        $this->assertTrue($content->isPublished());
        $this->assertFalse($content->isDraft());
    }

    public function testFrontMatterSetPublishedFalseIsDraft() : void {
        $content = new Content(
            'name',
            new \DateTimeImmutable(),
            new FrontMatter(['published' => false]),
            $this->getMockBuilder(Template::class)->getMock(),
            ContentCategory::Layout,
            null,
            null
        );

        $this->assertFalse($content->isPublished());
        $this->assertTrue($content->isDraft());
    }

    public function testGetRenderedContentsBeforeRenderThrowsException() : void {
        $content = new Content(
            'name',
            new \DateTimeImmutable(),
            new FrontMatter(['published' => false]),
            $this->getMockBuilder(Template::class)->getMock(),
            ContentCategory::Page,
            null,
            null
        );

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('Called ' . Content::class . '::getRenderedContents before the corresponding Template has been rendered.');

        $content->getRenderedContents();
    }

}