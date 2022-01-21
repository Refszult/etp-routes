<?php

namespace App\Entity\Route;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteWayPointRepository")
 */
class RouteWayPoint
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="smallint", options={"default" : 1})
     * @Groups({"Default"})
     */
    private $rowNumber = 1;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=2, options={"default" : 0})
     * @Groups({"Default"})
     */
    private $timeSummer = 0;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=2, options={"default" : 0})
     * @Groups({"Default"})
     */
    private $timeWinter = 0;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=2, options={"default" : 0})
     * @Groups({"Default"})
     */
    private $distance = 0;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=2, options={"default" : 0})
     * @Groups({"Default"})
     */
    private $distanceFromFirstPoint = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Department")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $department;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\RouteWay", inversedBy="wayPoints")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $routeWay;

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

    public function getTimeSummer()
    {
        return $this->timeSummer;
    }

    public function setTimeSummer($timeSummer): self
    {
        $this->timeSummer = $timeSummer;

        return $this;
    }

    public function getTimeWinter()
    {
        return $this->timeWinter;
    }

    public function setTimeWinter($timeWinter): self
    {
        $this->timeWinter = $timeWinter;

        return $this;
    }

    public function getDistance()
    {
        return $this->distance;
    }

    public function setDistance($distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getDistanceFromFirstPoint()
    {
        return $this->distanceFromFirstPoint;
    }

    public function setDistanceFromFirstPoint($distanceFromFirstPoint): self
    {
        $this->distanceFromFirstPoint = $distanceFromFirstPoint;

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

    public function getRouteWay(): ?RouteWay
    {
        return $this->routeWay;
    }

    public function setRouteWay(?RouteWay $routeWay): self
    {
        $this->routeWay = $routeWay;

        return $this;
    }
}
