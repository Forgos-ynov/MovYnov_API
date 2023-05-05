<?php

namespace App\Entity;

use App\Repository\ForumPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumPostRepository::class)]
class ForumPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'forumPosts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ForumCategory $idForumCategory = null;

    #[ORM\Column]
    private ?int $idMedia = null;

    #[ORM\ManyToOne(inversedBy: 'forumPosts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $idUser = null;

    #[ORM\Column]
    private ?bool $spoilers = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $uuid = null;

    #[ORM\Column]
    private ?bool $isDeleted = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'idPost', targetEntity: ForumComment::class, orphanRemoval: true)]
    private Collection $forumComments;

    public function __construct()
    {
        $this->forumComments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getIdForumCategory(): ?ForumCategory
    {
        return $this->idForumCategory;
    }

    public function setIdForumCategory(?ForumCategory $idForumCategory): self
    {
        $this->idForumCategory = $idForumCategory;

        return $this;
    }

    public function getIdMedia(): ?int
    {
        return $this->idMedia;
    }

    public function setIdMedia(int $idMedia): self
    {
        $this->idMedia = $idMedia;

        return $this;
    }

    public function getIdUser(): ?User
    {
        return $this->idUser;
    }

    public function setIdUser(?User $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function isSpoilers(): ?bool
    {
        return $this->spoilers;
    }

    public function setSpoilers(bool $spoilers): self
    {
        $this->spoilers = $spoilers;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function isIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, ForumComment>
     */
    public function getForumComments(): Collection
    {
        return $this->forumComments;
    }

    public function addForumComment(ForumComment $forumComment): self
    {
        if (!$this->forumComments->contains($forumComment)) {
            $this->forumComments->add($forumComment);
            $forumComment->setIdPost($this);
        }

        return $this;
    }

    public function removeForumComment(ForumComment $forumComment): self
    {
        if ($this->forumComments->removeElement($forumComment)) {
            // set the owning side to null (unless already changed)
            if ($forumComment->getIdPost() === $this) {
                $forumComment->setIdPost(null);
            }
        }

        return $this;
    }
}
