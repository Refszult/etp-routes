<?php

namespace App\Entity\Route;

use App\Entity\Contractor;
use App\Entity\Rating\AppealEventRouteEvent;
use App\Repository\Route\RouteEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass=RouteEventRepository::class)
 */
class RouteEvent
{
    const EVENT_STEAL = 1;

    const EVENT_ACCIDENT = 0;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Route::class)
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $route;

    /**
     * @ORM\ManyToOne(targetEntity=Contractor::class)
     * @Groups({"Default"})
     */
    private $contractor;

    /**
     * @ORM\Column(type="smallint", options={"default" : 1, "unsigned" : true})
     * @Groups({"Default"})
     */
    private $type = 0;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"Default"})
     */
    private $eventDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Rating\AppealEventRouteEvent", mappedBy="event")
     * @JMS\Exclude()
     */
    private $appealEvents;

    public function __construct()
    {
        $this->appealEvents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoute(): ?Route
    {
        return $this->route;
    }

    public function setRoute(?Route $route): self
    {
        $this->route = $route;

        return $this;
    }

    public function getContractor(): ?Contractor
    {
        return $this->contractor;
    }

    public function setContractor(?Contractor $contractor): self
    {
        $this->contractor = $contractor;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    /**
     * @return Collection|AppealEventRouteEvent[]
     */
    public function getAppealEvents(): Collection
    {
        return $this->appealEvents;
    }

    public function addAppealEvent(AppealEventRouteEvent $appealEvent): self
    {
        if (!$this->appealEvents->contains($appealEvent)) {
            $this->appealEvents[] = $appealEvent;
            $appealEvent->setEvent($this);
        }

        return $this;
    }

    public function removeAppealEvent(AppealEventRouteEvent $appealEvent): self
    {
        if ($this->appealEvents->removeElement($appealEvent)) {
            // set the owning side to null (unless already changed)
            if ($appealEvent->getEvent() === $this) {
                $appealEvent->setEvent(null);
            }
        }

        return $this;
    }
}
