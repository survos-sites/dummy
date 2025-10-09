<?php

namespace App\Workflow;

use App\Controller\AppController;
use App\Entity\Image;
use Psr\Log\LoggerInterface;
use Survos\SaisBundle\Model\ProcessPayload;
use Survos\SaisBundle\Service\SaisClientService;
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
        private SaisClientService $saisClientService,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator,
    )
	{
	}

	#[AsTransitionListener(WF::WORKFLOW_NAME, WF::TRANSITION_DISPATCH)]
	public function onTransitionDispatch(TransitionEvent $event): void
	{

		/** @var Image image */
		$image = $event->getSubject();
        $payload = new ProcessPayload(
            AppController::SAIS_CLIENT_CODE,
            [$image->originalUrl],
            mediaCallbackUrl: $this->urlGenerator->generate('app_media_webhook', ['code' => $image->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
            thumbCallbackUrl: $this->urlGenerator->generate('app_thumb_webhook', ['code' => $image->getCode()], UrlGeneratorInterface::ABSOLUTE_URL),
        );
        $response = $this->saisClientService->dispatchProcess($payload);

        // this won't be necessary after the webhook is working, but we _could_ update the data now since we know it's ready.
        $resized = $response[0]['resized']??[];
        if (count($resized)) {
            $image->resized = $resized;
        }
	}

}
