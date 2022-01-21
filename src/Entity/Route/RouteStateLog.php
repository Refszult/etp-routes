<?php

namespace App\Entity\Route;

use App\Entity\Traits\CreatedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Timezones;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteStateLogRepository")
 */
class RouteStateLog
{
    use CreatedTrait;

    const MESSAGE_CUR = 'CUR';
    const MESSAGE_RM = 'RM';
    const MESSAGE_RWO = 'RWO';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Route::class, inversedBy="routeStatesLog")
     * @Groups({"Default"})
     */
    private $route;

    /**
     * @ORM\ManyToMany(targetEntity=RouteMovementPoint::class, mappedBy="routeStatesLog", orphanRemoval=true)
     * @Groups({"Default"})
     */
    private $currentPoints;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $arrivalTime;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    protected $arrivalTimeTz = '+00:00';

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"Default"})
     */
    private $messageType;

    public function __construct()
    {
        $this->currentPoints = new ArrayCollection();
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

    /**
     * @return Collection|RouteMovementPoint[]
     */
    public function getCurrentPoints(): Collection
    {
        return $this->currentPoints;
    }

    public function addCurrentPoints(RouteMovementPoint $movementPoint): self
    {
        if (!$this->currentPoints->contains($movementPoint)) {
            $this->currentPoints[] = $movementPoint;
            $movementPoint->addRouteStatesLog($this);
        }

        return $this;
    }

    public function removeCurrentPoints(RouteMovementPoint $movementPoint): self
    {
        if ($this->currentPoints->contains($movementPoint)) {
            $this->currentPoints->removeElement($movementPoint);
            $movementPoint->removeRouteStatesLog($this);
        }

        return $this;
    }

    public function getArrivalTime(): ?\DateTime
    {
        return $this->arrivalTime;
    }

    public function setArrivalTime(\DateTime $arrivalTime): self
    {
        $this->arrivalTime = $arrivalTime;
        if ($this->arrivalTime) {
            $this->setArrivalTimeTz($arrivalTime->getTimezone());
        }

        return $this;
    }

    public function getArrivalTimeTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->arrivalTimeTz);
    }

    public function setArrivalTimeTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->arrivalTimeTz = $timeZone->getName();
        } else {
            $this->arrivalTimeTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function setMessageType(?string $messageType): self
    {
        $this->messageType = $messageType;

        return $this;
    }
}
