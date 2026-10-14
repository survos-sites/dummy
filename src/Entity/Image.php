<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Controller\AppController;
use App\DataFixtures\App;
use App\Repository\ImageRepository;
use App\Workflow\IImageWorkflow;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JoliCode\MediaBundle\DeleteBehavior\Attribute\MediaDeleteBehavior;
use JoliCode\MediaBundle\DeleteBehavior\Strategy;
use JoliCode\MediaBundle\Doctrine\Types as MediaTypes;
use JoliCode\MediaBundle\Model\Media;
use JoliCode\MediaBundle\Validator\Media as MediaConstraint;
use Survos\DataContracts\Util\MediaIdentity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['image.read','marking']],
)]
// old way
#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[Groups(['image.read'])]
class Image implements \Stringable
{
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    #[Groups(['transition', 'minimum', 'marking', 'searchable'])]
    public ?string $marking = null;

    public function getMarking(): ?string { return $this->marking; }

    public function setMarking(?string $marking, array $context = []): self
    {
        $this->marking = $marking;
        return $this;
    }

    public function __construct(
    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(referencedColumnName: 'sku', nullable: false)]
    private ?Product $product = null,

    #[ORM\Column(type: Types::TEXT)]
    public ?string $originalUrl = null,

    #[ORM\Id]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $code = null,
    )
    {
        if (!$this->code) {
            //$this->code = hash('xxh3', $originalUrl);
            $this->code = self::calculateCode($originalUrl);
        }
        $this->marking = IImageWorkflow::PLACE_NEW;
    }
    public static function calculateCode(string $url): string
    {
        return MediaIdentity::idFromOriginalUrl($url);
    }

    public string $id { get => $this->code; }

    public function getId(): string
    {
        return $this->code;
    }

    public string $productSku {
        get => $this->product->sku;
    }

    #[ORM\Column(nullable: true)]
    public ?array $resized = null;

    public bool $hasThumbnails {
        get => $this->resized && count($this->resized) > 0;
    }

    #[ORM\Column(nullable: true)]
    public ?int $originalSize = null;


    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function addThumbData($filter, ?string $url=null): static
    {
        $this->resized[$filter] = $url;
        return $this;
    }

    public function __toString()
    {
        return $this->originalUrl;
    }
}
