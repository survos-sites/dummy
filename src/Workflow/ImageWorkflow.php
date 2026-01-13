<?php

namespace App\Workflow;

use App\Controller\AppController;
use App\Entity\Image;
use Psr\Log\LoggerInterface;
use Survos\StateBundle\Attribute\Workflow;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;
use App\Workflow\IImageWorkflow as WF;
class ImageWorkflow
{

	public function __construct(
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator,
    )
	{
	}

	#[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_DISPATCH)]
	public function onTransitionDispatch(TransitionEvent $event): void
	{
        // this is now in Media, media:sync

	}

}
