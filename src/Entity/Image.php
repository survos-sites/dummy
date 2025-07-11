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
use Survos\MeiliBundle\Api\Filter\FacetsFieldSearchFilter;
use Survos\SaisBundle\Service\SaisClientService;
use Survos\WorkflowBundle\Traits\MarkingInterface;
use Survos\WorkflowBundle\Traits\MarkingTrait;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['image.read','marking']],
)]
#[ApiFilter(FacetsFieldSearchFilter::class,
    properties: ['marking', 'productSku'],
    arguments: [ "searchParameterName" => "facet_filter"]
)]
#[ORM\Entity(repositoryClass: ImageRepository::class)]
#[Groups(['image.read'])]
class Image implements MarkingInterface
{
    use MarkingTrait;
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
            $this->code = SaisClientService::calculateCode($originalUrl,AppController::SAIS_CLIENT_CODE);
        }
        $this->marking = IImageWorkflow::PLACE_NEW;
    }

    public string $productSku {
        get => $this->product->sku;
    }

    #[ORM\Column(nullable: true)]
    public ?array $resized = null;

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
}
