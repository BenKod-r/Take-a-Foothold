<?php

namespace App\Entity;

use App\Repository\ImageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ImageRepository::class)
 */
class Image
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $path;

    /**
     * @ORM\ManyToMany(targetEntity=Player::class, mappedBy="image")
     */
    private $players;

    /**
     * @ORM\OneToMany(targetEntity=Player::class, mappedBy="poster", cascade={"persist", "remove"})
     */
    private $poster;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->poster = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return Collection|Player[]
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players[] = $player;
            $player->addImage($this);
        }

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        if ($this->players->contains($player)) {
            $this->players->removeElement($player);
            $player->removeImage($this);
        }

        return $this;
    }

    /**
     * @return Collection|Player[]
     */
    public function getPoster(): Collection
    {
        return $this->poster;
    }

    public function addPoster(Player $poster): self
    {
        if (!$this->poster->contains($poster)) {
            $this->poster[] = $poster;
            $poster->setPoster($this);
        }

        return $this;
    }

    public function removePoster(Player $poster): self
    {
        if ($this->poster->contains($poster)) {
            $this->poster->removeElement($poster);
            // set the owning side to null (unless already changed)
            if ($poster->getPoster() === $this) {
                $poster->setPoster(null);
            }
        }

        return $this;
    }
}
