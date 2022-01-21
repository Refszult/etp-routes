<?php

namespace App\Entity\Route;

use App\Entity\Rating\AppealEventRouteMovementPoint;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Intl\Timezones;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteMovementPointRepository")
 */
class RouteMovementPoint
{
    const LOADING_LATENESS = 0;
    const UNLOADING_LATENESS = 1;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="smallint")
     * @Groups({"Default"})
     */
    private $rowNumber;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"Default"})
     */
    private $active;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Department")
     * @ORM\JoinColumn(nullable=false)
     * @JMS\Groups({"RouteMovementPoint_info", "Route_des", "Event_for_appeal", "Appeal_info", "Route_info"})
     * @Groups({"RouteMovementPoint_info", "Route_des", "Event_for_appeal", "Appeal_info", "Route_info"})
     */
    private $department;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Route", inversedBy="movementPoints")
     * @ORM\JoinColumn(nullable=false)
     * @JMS\Groups({"RouteMovementPoint_info", "Event_for_appeal", "Appeal_info"})
     * @Groups({"RouteMovementPoint_info", "Event_for_appeal", "Appeal_info"})
     */
    private $route;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $planDateArrival;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    protected $planDateArrivalTz = '+00:00';

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $factDateArrival;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    protected $factDateArrivalTz = '+00:00';

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @Groups({"Default"})
     */
    private $lateness = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"Default"})
     */
    private $pointAddress;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @Groups({"Default"})
     */
    private $latenessFlag = false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"Default"})
     */
    private $interval;

    /**
     * @ORM\Column(type="smallint", options={"default" : 1, "unsigned" : true})
     * @Groups({"Default"})
     */
    private $type = 1;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Rating\AppealEventRouteMovementPoint", mappedBy="movementPoint")
     * @JMS\Exclude()
     */
    private $appealEvents;

    /**
     * @ORM\ManyToMany(targetEntity=RouteCurrentState::class, inversedBy="currentPoints")
     * @JMS\Exclude()
     */
    private $routeCurrentStates;

    /**
     * @ORM\ManyToMany(targetEntity=RouteStateLog::class, inversedBy="currentPoints", orphanRemoval=true)
     * @JMS\Exclude()
     */
    private $routeStatesLog;

    public function __construct()
    {
        $this->appealEvents = new ArrayCollection();
        $this->routeStatesLog = new ArrayCollection();
        $this->routeCurrentStates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRowNumber(): ?int
    {
        return $this->rowNumber;
    }

    public function setRowNumber(int $rowNumber): self
    {
        $this->rowNumber = $rowNumber;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

        return $this;
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

    public function getPlanDateArrival(): ?\DateTime
    {
        if ($this->planDateArrival) {
            $this->planDateArrival->setTimezone(new \DateTimeZone($this->planDateArrivalTz));
        }

        return $this->planDateArrival;
    }

    public function getClearPlanDateArrival(): ?\DateTime
    {
        return $this->planDateArrival;
    }

    public function setPlanDateArrival(\DateTime $planDateArrival): self
    {
        $this->planDateArrival = $planDateArrival;
        if ($this->planDateArrival) {
            $this->setPlanDateArrivalTz($planDateArrival->getTimezone());
        }

        return $this;
    }

    public function getPlanDateArrivalTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->planDateArrivalTz);
    }

    public function setPlanDateArrivalTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->planDateArrivalTz = $timeZone->getName();
        } else {
            $this->planDateArrivalTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getFactDateArrival(): ?\DateTime
    {
        if ($this->factDateArrival) {
            $this->factDateArrival->setTimezone(new \DateTimeZone($this->factDateArrivalTz));
        }

        return $this->factDateArrival;
    }

    public function getClearFactDateArrival(): ?\DateTime
    {
        return $this->factDateArrival;
    }

    public function setFactDateArrival(?\DateTime $factDateArrival): self
    {
        $this->factDateArrival = $factDateArrival;
        if ($this->factDateArrival) {
            $this->setFactDateArrivalTz($factDateArrival->getTimezone());
        }

        return $this;
    }

    public function getFactDateArrivalTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->factDateArrivalTz);
    }

    public function setFactDateArrivalTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->factDateArrivalTz = $timeZone->getName();
        } else {
            $this->factDateArrivalTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getLateness(): ?bool
    {
        return $this->lateness;
    }

    public function setLateness(bool $lateness): self
    {
        $this->lateness = $lateness;

        return $this;
    }

    public function getPointAddress(): ?string
    {
        return $this->pointAddress;
    }

    public function setPointAddress(?string $pointAddress): self
    {
        $this->pointAddress = $pointAddress;

        return $this;
    }

    public function getLatenessFlag(): ?bool
    {
        return $this->latenessFlag;
    }

    public function setLatenessFlag(bool $latenessFlag): self
    {
        $this->latenessFlag = $latenessFlag;

        return $this;
    }

    public function getInterval(): ?int
    {
        return $this->interval;
    }

    public function setInterval(?int $interval): self
    {
        $this->interval = $interval;

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

    /**
     * @return Collection|AppealEventRouteMovementPoint[]
     */
    public function getAppealEvents(): Collection
    {
        return $this->appealEvents;
    }

    public function addAppealEvent(AppealEventRouteMovementPoint $appealEvent): self
    {
        if (!$this->appealEvents->contains($appealEvent)) {
            $this->appealEvents[] = $appealEvent;
            $appealEvent->setMovementPoint($this);
        }

        return $this;
    }

    public function removeAppealEvent(AppealEventRouteMovementPoint $appealEvent): self
    {
        if ($this->appealEvents->removeElement($appealEvent)) {
            // set the owning side to null (unless already changed)
            if ($appealEvent->getMovementPoint() === $this) {
                $appealEvent->setMovementPoint(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|RouteStateLog[]
     */
    public function getRouteStatesLog(): Collection
    {
        return $this->routeStatesLog;
    }

    public function addRouteStatesLog(RouteStateLog $routeStateLog): self
    {
        if (!$this->routeStatesLog->contains($routeStateLog)) {
            $this->routeStatesLog[] = $routeStateLog;
        }

        return $this;
    }

    public function removeRouteStatesLog(RouteStateLog $routeStateLog): self
    {
        if ($this->routeStatesLog->contains($routeStateLog)) {
            $this->routeStatesLog->removeElement($routeStateLog);
        }

        return $this;
    }

    /**
     * @return Collection|RouteCurrentState[]
     */
    public function getRouteCurrentState(): Collection
    {
        return $this->routeCurrentStates;
    }

    public function addRouteCurrentState(RouteCurrentState $routeCurrentState): self
    {
        if (!$this->routeCurrentStates->contains($routeCurrentState)) {
            $this->routeCurrentStates[] = $routeCurrentState;

        }

        return $this;
    }

    public function removeRouteCurrentState(RouteCurrentState $routeCurrentState): self
    {
        if ($this->routeCurrentStates->contains($routeCurrentState)) {
            $this->routeCurrentStates->removeElement($routeCurrentState);
        }

        return $this;
    }
}
