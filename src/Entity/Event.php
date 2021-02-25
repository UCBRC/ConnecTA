<?php

namespace App\Entity;

use App\Entity\User\User;
use App\Repository\EventRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Pure;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
{
    /**
     * @var UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid_binary_ordered_time", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator")
     */
    private UuidInterface $id;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private string $title;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private string $content;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private string $instruction;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private string $thumbnail;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private DateTime $time;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User\User", inversedBy="events")
     * @ORM\JoinTable(name="events_users")
     */
    private $users;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private bool $published = false;

    public function __construct()
    {
        $this->time = new DateTime();
        $this->users = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id ?? "new";
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title ?? "";
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content ?? "";
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     */
    public function setThumbnail(string $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return string
     */
    public function getInstruction(): string
    {
        return $this->instruction ?? "";
    }

    /**
     * @param string $instruction
     */
    public function setInstruction(string $instruction): void
    {
        $this->instruction = $instruction;
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->published;
    }

    /**
     * @return User[]
     */
    #[Pure] public function getUsers(): array
    {
        return $this->users->toArray();
    }

    /**
     * @param User $user
     */
    public function addUser(User $user): void {
        $this->users->add($user);
    }

    /**
     * @param User $user
     */
    public function removeUser(User $user): void {
        $this->users->removeElement($user);
    }

    /**
     * @param bool $published
     */
    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    public function jsonSerialize()
    {
        return [
            "id" => $this->getId(),
            "title" => $this->getTitle(),
            "thumbnail" => $this->getThumbnail()
        ];
    }
}
