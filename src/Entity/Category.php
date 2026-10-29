<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private string $name;

    #[ORM\Column(length: 60, unique: true)]
    private string $slug;

    /** Three letters used in product references, for example FAN. */
    #[ORM\Column(length: 3)]
    private string $code;

    public function __construct(string $name, string $slug, string $code)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->code = $code;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
