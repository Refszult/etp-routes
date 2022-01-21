<?php

namespace App\Entity\Route;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteWayTransportationRepository")
 */
class RouteWayTransportation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\RouteWay", inversedBy="routeWayTransportations")
     * @ORM\JoinColumn(nullable=false)
     * @JMS\Exclude()
     */
    private $routeWay;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Transportation", inversedBy="routeWayTransportations")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $transportation;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @Groups({"Default"})
     */
    private $isMain = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTransportation(): ?Transportation
    {
        return $this->transportation;
    }

    public function setTransportation(?Transportation $transportation): self
    {
        $this->transportation = $transportation;

        return $this;
    }

    public function getIsMain(): ?bool
    {
        return $this->isMain;
    }

    public function setIsMain(bool $isMain): self
    {
        $this->isMain = $isMain;

        return $this;
    }
}
