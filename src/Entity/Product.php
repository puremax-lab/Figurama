<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['name'], message: 'Un produit porte déjà ce nom.')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Choisissez une catégorie.')]
    private ?Category $category = null;

    #[ORM\Column(length: 80, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 80)]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 20, max: 2000)]
    private ?string $description = null;

    /** Price in cents, to avoid rounding errors with floats. */
    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 100, max: 100000, notInRangeMessage: 'Entre 1 € et 1 000 €.')]
    private ?int $priceCents = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    #[Assert\LessThanOrEqual(9999)]
    private int $stock = 0;

    /** Photo URL (Unsplash in the demo data). */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Url(requireTld: true)]
    private ?string $imageUrl = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 200)]
    private ?int $heightCm = null;

    /** Scale of the figurine, for example 1/12. */
    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Regex('/^1\/\d{1,3}$/', message: 'Échelle au format 1/12.')]
    private ?string $scale = null;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 60)]
    private ?string $material = null;

    #[ORM\Column]
    private bool $featured = false;

    /** Hidden products stay attached to past orders but are no longer sold. */
    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function computeSlug(): void
    {
        $this->slug = (new AsciiSlugger('fr'))->slug((string) $this->name)->lower()->toString();
    }

    public function isAvailable(): bool
    {
        return $this->active && $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= 3;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name === null ? null : trim($name);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriceCents(): ?int
    {
        return $this->priceCents;
    }

    public function setPriceCents(?int $priceCents): static
    {
        $this->priceCents = $priceCents;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function decreaseStock(int $quantity): void
    {
        if ($quantity > $this->stock) {
            throw new \DomainException(sprintf('Stock insuffisant pour « %s ».', $this->name));
        }
        $this->stock -= $quantity;
    }

    public function increaseStock(int $quantity): void
    {
        $this->stock += $quantity;
    }

    /** Technical reference shown on the product sheet, for example FAN-004. */
    public function getReference(): string
    {
        return sprintf('%s-%03d', $this->category?->getCode() ?? 'FIG', (int) $this->id);
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getHeightCm(): ?int
    {
        return $this->heightCm;
    }

    public function setHeightCm(?int $heightCm): static
    {
        $this->heightCm = $heightCm;

        return $this;
    }

    public function getScale(): ?string
    {
        return $this->scale;
    }

    public function setScale(?string $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    public function getMaterial(): ?string
    {
        return $this->material;
    }

    public function setMaterial(?string $material): static
    {
        $this->material = $material;

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
