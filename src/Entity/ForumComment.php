<?php

namespace App\Entity;

use App\Repository\ForumCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ForumCommentRepository::class)]
class ForumComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["oneForumPost_read", "forumComment_read"])]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'forumComments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["oneForumPost_read", "forumComment_read"])]
    private ?User $idUser = null;

    #[ORM\Column]
    #[Groups(["oneForumPost_read", "forumComment_read"])]
    private ?bool $spoilers = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(["oneForumPost_read", "forumComment_read"])]
    private ?string $uuid = null;

    #[ORM\Column]
    private ?bool $isDeleted = null;

    #[ORM\Column]
    #[Groups(["oneForumPost_read", "forumComment_read"])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(["oneForumPost_read", "forumComment_read"])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'forumComments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["forumComment_read"])]
    private ?ForumPost $idPost = null;

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

    public function getIdPost(): ?ForumPost
    {
        return $this->idPost;
    }

    public function setIdPost(?ForumPost $idPost): self
    {
        $this->idPost = $idPost;

        return $this;
    }
}
