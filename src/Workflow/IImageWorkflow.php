<?php

namespace App\Workflow;

use Survos\WorkflowBundle\Attribute\Place;
use Survos\WorkflowBundle\Attribute\Transition;

interface IImageWorkflow
{
	public const WORKFLOW_NAME = 'ImageWorkflow';

	#[Place(initial: true)]
	public const PLACE_NEW = 'new';

	#[Place]
	public const PLACE_DISPATCHED = 'dispatched';

	#[Place]
	public const PLACE_READY = 'ready';

	#[Place]
	public const PLACE_FAILED = 'failed';

	#[Transition(from: [self::PLACE_NEW], to: self::PLACE_DISPATCHED)]
	public const TRANSITION_DISPATCH = 'dispatch';

	#[Transition(from: [self::PLACE_DISPATCHED], to: self::PLACE_READY,
        info: "After thumb webhook has been received",
        guard: "subject.hasThumbnails")]
	public const TRANSITION_COMPLETE = 'complete';

	#[Transition(from: [self::PLACE_READY], to: self::PLACE_FAILED)]
	public const TRANSITION_FAIL = 'fail';
}
