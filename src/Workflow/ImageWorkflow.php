<?php

namespace App\Workflow;

use App\Controller\AppController;
use App\Entity\Image;
use Survos\SaisBundle\Model\ProcessPayload;
use Survos\SaisBundle\Service\SaisClientService;
use Survos\WorkflowBundle\Attribute\Workflow;
use Symfony\Component\Workflow\Attribute\AsGuardListener;
use Symfony\Component\Workflow\Attribute\AsTransitionListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

#[Workflow(supports: [Image::class], name: self::WORKFLOW_NAME)]
class ImageWorkflow implements IImageWorkflow
{
	public const WORKFLOW_NAME = 'ImageWorkflow';

	public function __construct(
        private SaisClientService $saisClientService,
    )
	{
	}

	#[AsTransitionListener(self::WORKFLOW_NAME, IImageWorkflow::TRANSITION_DISPATCH)]
	public function onTransition(TransitionEvent $event): void
	{
		/** @var Image image */
		$image = $event->getSubject();
            $response = $this->saisClientService->dispatchProcess(new ProcessPayload(
                AppController::SAIS_CLIENT_CODE,
                [$image->getOriginalUrl()],
        ));
            // hack alert: just fail if the resized isn't there, then re-run
        $resized = $response[0]['resized']??[];
        if (!count($resized)) {
            throw new \Exception('Image resized empty for , run again later ' . $image->getOriginalUrl());
        }
        $image->setResized($resized);
	}
}
