<?php

declare(strict_types=1);

namespace App\Tests;

use App\Entity\Image;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Workflow\Registry;

final class NativeWorkflowTest extends WebTestCase
{
    public function testImageLifecyclePreservesGuardAndMarking(): void
    {
        self::bootKernel();
        $image = new Image(originalUrl: 'https://example.com/image.jpg');
        $workflow = self::getContainer()->get(Registry::class)->get($image, 'ImageWorkflow');
        self::assertSame('new', $image->marking);
        $workflow->apply($image, 'dispatch');
        self::assertSame('dispatched', $image->marking);
        self::assertFalse($workflow->can($image, 'complete'));
        $image->resized = ['thumb' => 'https://example.com/thumb.jpg'];
        self::assertTrue($workflow->can($image, 'complete'));
        $workflow->apply($image, 'complete');
        self::assertSame('ready', $image->marking);
    }

    public function testExplorerWithMenuSlotsRenders(): void
    {
        $client = self::createClient();
        $client->request('GET', '/workflow/ImageWorkflow');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'ImageWorkflow');
    }
}
