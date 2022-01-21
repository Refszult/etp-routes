<?php

namespace App\Entity\Route;

use App\Entity\Traits\CreatedTrait;
use App\Entity\Traits\UpdatedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Timezones;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteCurrentStateRepository")
 */
class RouteCurrentState
{
    const STATUS_AWAITING_ARRIVAL = 0;
    const STATUS_AWAITING_LOADING = 1;
    const STATUS_IN_LOADING = 2;
    const STATUS_AWAITING_LOADING_OR_UNLOADING = 3;
    const STATUS_IN_LOADING_OR_UNLOADING = 4;
    const STATUS_AWAITING_UNLOADING = 5;
    const STATUS_ON_UNLOADING = 6;
    const STATUS_CLOSED = 7;

    use CreatedTrait;
    use UpdatedTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Route::class, mappedBy="routeCurrentState", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @JMS\Exclude()
     */
    private $route;

    /**
     * @ORM\ManyToMany(targetEntity=RouteMovementPoint::class, mappedBy="routeCurrentStates")
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
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $counter;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    protected $counterTz = '+00:00';

    /**
     * @ORM\Column(type="smallint", options={"default" : 0, "unsigned"=true})
     * @Groups({"Default"})
     */
    private $status = 0;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @Groups({"Default"})
     */
    protected $isRWOAction = false;

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

        // set (or unset) the owning side of the relation if necessary
        $newRouteCurrentStatus = null === $route ? null : $this;
        if ($route->getRouteCurrentState() !== $newRouteCurrentStatus) {
            $route->setRouteCurrentState($newRouteCurrentStatus);
        }

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
            $movementPoint->addRouteCurrentState($this);
        }

        return $this;
    }

    public function removeCurrentPoints(RouteMovementPoint $movementPoint): self
    {
        if ($this->currentPoints->contains($movementPoint)) {
            $this->currentPoints->removeElement($movementPoint);
            $movementPoint->removeRouteCurrentState($this);

        }

        return $this;
    }

    public function getArrivalTime(): ?\DateTime
    {
        if ($this->arrivalTime) {
            $this->arrivalTime->setTimezone(new \DateTimeZone($this->arrivalTimeTz));
        }

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

    public function getCounter(): ?\DateTime
    {
        if ($this->counter) {
            $this->counter->setTimezone(new \DateTimeZone($this->counterTz));
        }

        return $this->counter;
    }

    public function setCounter(\DateTime $counter): self
    {
        $this->counter = $counter;
        if ($this->counter) {
            $this->setCounterTz($counter->getTimezone());
        }

        return $this;
    }

    public function getCounterTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->counterTz);
    }

    public function setCounterTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->counterTz = $timeZone->getName();
        } else {
            $this->counterTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getIsRWOAction(): ?bool
    {
        return $this->isRWOAction;
    }

    public function setIsRWOAction(bool $isRWOAction): self
    {
        $this->isRWOAction = $isRWOAction;

        return $this;
    }
}
