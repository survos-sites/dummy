<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use App\Workflow\IImageWorkflow;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\WorkflowBundle\Traits\MarkingInterface;
use Survos\WorkflowBundle\Traits\MarkingTrait;

#[ORM\Entity(repositoryClass: ImageRepository::class)]
class Image implements MarkingInterface
{
    use MarkingTrait;
    public function __construct(
        #[ORM\Column(type: Types::TEXT)]
        private ?string $originalUrl = null,
        #[ORM\Id]
        #[ORM\Column(type: Types::TEXT)]
        private ?string $code = null,
    )
    {
        if (!$this->code) {
            $this->code = hash('xxh3', $originalUrl);
        }
        $this->marking = IImageWorkflow::PLACE_NEW;
    }

    #[ORM\Column(nullable: true)]
    private ?array $resized = null;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getOriginalUrl(): ?string
    {
        return $this->originalUrl;
    }

    public function getResized(): ?array
    {
        return $this->resized;
    }

    public function setResized(?array $resized): static
    {
        $this->resized = $resized;

        return $this;
    }
}
