<?php

/**
 * @see       https://github.com/laminas/laminas-view for the canonical source repository
 * @copyright https://github.com/laminas/laminas-view/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-view/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\View\Helper;

use Laminas\View\Exception;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplateMapResolver;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_View
 * @group      Laminas_View_Helper
 */
class RenderChildModelTest extends TestCase
{
    protected function setUp()
    {
        $this->resolver = new TemplateMapResolver([
            'layout'  => __DIR__ . '/../_templates/nested-view-model-layout.phtml',
            'child1'  => __DIR__ . '/../_templates/nested-view-model-content.phtml',
            'child2'  => __DIR__ . '/../_templates/nested-view-model-child2.phtml',
            'complex' => __DIR__ . '/../_templates/nested-view-model-complexlayout.phtml',
        ]);
        $this->renderer = $renderer = new PhpRenderer();
        $renderer->setCanRenderTrees(true);
        $renderer->setResolver($this->resolver);

        $this->viewModelHelper = $renderer->plugin('view_model');
        $this->helper          = $renderer->plugin('render_child_model');

        $this->parent = new ViewModel();
        $this->parent->setTemplate('layout');
        $this->viewModelHelper->setRoot($this->parent);
        $this->viewModelHelper->setCurrent($this->parent);
    }

    public function testRendersEmptyStringWhenUnableToResolveChildModel()
    {
        $result = $this->helper->render('child1');
        $this->assertSame('', $result);
    }

    public function setupFirstChild()
    {
        $child1 = new ViewModel();
        $child1->setTemplate('child1');
        $child1->setCaptureTo('child1');
        $this->parent->addChild($child1);
        return $child1;
    }

    public function testRendersChildTemplateWhenAbleToResolveChildModelByCaptureToValue()
    {
        $this->setupFirstChild();
        $result = $this->helper->render('child1');
        $this->assertContains('Content for layout', $result, $result);
    }

    public function setupSecondChild()
    {
        $child2 = new ViewModel();
        $child2->setTemplate('child2');
        $child2->setCaptureTo('child2');
        $this->parent->addChild($child2);
        return $child2;
    }


    public function testRendersSiblingChildrenWhenCalledInSequence()
    {
        $this->setupFirstChild();
        $this->setupSecondChild();
        $result = $this->helper->render('child1');
        $this->assertContains('Content for layout', $result, $result);
        $result = $this->helper->render('child2');
        $this->assertContains('Second child', $result, $result);
    }

    public function testRendersNestedChildren()
    {
        $child1 = $this->setupFirstChild();
        $child1->setTemplate('layout');
        $child2 = new ViewModel();
        $child2->setTemplate('child1');
        $child2->setCaptureTo('content');
        $child1->addChild($child2);

        $result = $this->helper->render('child1');
        $this->assertContains('Layout start', $result, $result);
        $this->assertContains('Content for layout', $result, $result);
        $this->assertContains('Layout end', $result, $result);
    }

    public function testRendersSequentialChildrenWithNestedChildren()
    {
        $this->parent->setTemplate('complex');
        $child1 = $this->setupFirstChild();
        $child1->setTemplate('layout');
        $child1->setCaptureTo('content');

        $child2 = $this->setupSecondChild();
        $child2->setCaptureTo('sidebar');

        $nested = new ViewModel();
        $nested->setTemplate('child1');
        $nested->setCaptureTo('content');
        $child1->addChild($nested);

        $result = $this->renderer->render($this->parent);
        $this->assertRegExp(
            '/Content:\s+Layout start\s+Content for layout\s+Layout end\s+Sidebar:\s+Second child/s',
            $result,
            $result
        );
    }

    public function testAttemptingToRenderWithNoCurrentModelRaisesException()
    {
        $renderer = new PhpRenderer();
        $renderer->setResolver($this->resolver);
        $this->expectException(Exception\RuntimeException::class);
        $this->expectExceptionMessage('no view model');
        $renderer->render('layout');
    }
}
