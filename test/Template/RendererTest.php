<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Blogisthenics\Test\Template;

use Cspray\Blogisthenics\Template\ContextFactory;
use Cspray\Blogisthenics\Template\MethodDelegator;
use Cspray\Blogisthenics\Template\Renderer;
use Cspray\Blogisthenics\Test\AsyncTestCase;
use function Amp\File\filesystem;
use Zend\Escaper\Escaper;

class RendererTest extends AsyncTestCase {

    public function testRendersWithData() {
        $filePath = tempnam(sys_get_temp_dir(), 'blogisthenics');
        $fs = filesystem();
        $postContents = '<div><?= $this->foo ?></div>';

        yield $fs->put($filePath, $postContents);

        $subject = new Renderer(new ContextFactory(new Escaper(), new MethodDelegator()));
        $actual = $subject->render($filePath, ['foo' => 'foo & bar']);

        $this->assertSame('<div>foo &amp; bar</div>', $actual, 'Expected HTML to render with data from FrontMatter');
    }
}