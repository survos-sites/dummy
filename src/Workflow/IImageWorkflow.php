<?php

namespace App\Workflow;

use Survos\WorkflowBundle\Attribute\Place;
use Survos\WorkflowBundle\Attribute\Transition;

interface IImageWorkflow
{
	public const WORKFLOW_NAME = 'ImageWorkflow';

	#[Place(initial: true,
        info: "persisted to database",
        description: "Created during app:load")]
	public const PLACE_NEW = 'new';

	#[Place(info: 'dispatched to sais')]
	public const PLACE_DISPATCHED = 'dispatched';

	#[Place(info: 'has resized')]
	public const PLACE_READY = 'ready';

	#[Place]
	public const PLACE_FAILED = 'failed';

	#[Transition(from: [self::PLACE_NEW],
        to: self::PLACE_DISPATCHED,
        info: 'request resize',
        description: "Send a resize request to sais"
    )]

	public const TRANSITION_DISPATCH = 'dispatch';

	#[Transition(from: [self::PLACE_DISPATCHED], to: self::PLACE_READY,
        info: "if resized exist",
        description: "After thumb webhook has been received",
        guard: "subject.hasThumbnails")]
	public const TRANSITION_COMPLETE = 'complete';

	#[Transition(from: [self::PLACE_READY], to: self::PLACE_FAILED)]
	public const TRANSITION_FAIL = 'fail';
}
