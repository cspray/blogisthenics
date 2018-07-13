<?php declare(strict_types=1);

/**
 *
 */

namespace Cspray\Jasg\Test\Template;

use Cspray\Jasg\Template\ContextFactory;
use Cspray\Jasg\Template\MethodDelegator;
use Cspray\Jasg\Template\Renderer;
use Cspray\Jasg\Test\AsyncTestCase;
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