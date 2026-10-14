<?php

declare(strict_types=1);

namespace App\Workflow;

use App\Entity\Image;
use Survos\WorkflowExtrasBundle\Attribute\Place;
use Survos\WorkflowExtrasBundle\Attribute\Transition;
use Survos\WorkflowExtrasBundle\Attribute\AsWorkflow;

#[AsWorkflow(supports: [Image::class], name: self::WORKFLOW_NAME)]
class IImageWorkflow
{
	public const WORKFLOW_NAME = 'ImageWorkflow';

	#[Place(initial: true,
        label: "persisted to database",
        description: "Created during app:load")]
	public const PLACE_NEW = 'new';

	#[Place(label: 'dispatched to sais')]
	public const PLACE_DISPATCHED = 'dispatched';

	#[Place(label: 'has resized')]
	public const PLACE_READY = 'ready';

	#[Place]
	public const PLACE_FAILED = 'failed';

	#[Transition(from: [self::PLACE_NEW],
        to: self::PLACE_DISPATCHED,
        label: 'request resize',
        description: "Send a resize request to sais"
    )]

	public const TRANSITION_DISPATCH = 'dispatch';

	#[Transition(from: [self::PLACE_DISPATCHED], to: self::PLACE_READY,
        label: "if resized exist",
        description: "After thumb webhook has been received",
        guard: "subject.hasThumbnails")]
	public const TRANSITION_COMPLETE = 'complete';

	#[Transition(from: [self::PLACE_READY], to: self::PLACE_FAILED)]
	public const TRANSITION_FAIL = 'fail';
}
