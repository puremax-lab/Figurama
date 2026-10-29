<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/** A rating left by a customer who bought the piece. One review per customer and product. */
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_review_author_product', columns: ['product_id', 'author_id'])]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $author;

    #[ORM\Column(type: 'smallint')]
    #[Assert\NotNull(message: 'Choisissez une note.')]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Note de 1 à 5.')]
    private ?int $rating = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Écrivez quelques mots.')]
    #[Assert\Length(min: 10, max: 1000, minMessage: '10 caractères minimum.', maxMessage: '1000 caractères maximum.')]
    private ?string $comment = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Product $product, User $author)
    {
        $this->product = $product;
        $this->author = $author;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment === null ? null : trim($comment);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
