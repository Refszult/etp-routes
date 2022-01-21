<?php

namespace App\Entity\Route;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\OwnerRouteWayRepository")
 */
class OwnerRouteWay
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\RouteOwner")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $routeOwner;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Department")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $routeWayStart;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Department")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $routeWayEnd;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRouteOwner(): ?RouteOwner
    {
        return $this->routeOwner;
    }

    public function setRouteOwner(?RouteOwner $routeOwner): self
    {
        $this->routeOwner = $routeOwner;

        return $this;
    }

    public function getRouteWayStart(): ?Department
    {
        return $this->routeWayStart;
    }

    public function setRouteWayStart(?Department $routeWayStart): self
    {
        $this->routeWayStart = $routeWayStart;

        return $this;
    }

    public function getRouteWayEnd(): ?Department
    {
        return $this->routeWayEnd;
    }

    public function setRouteWayEnd(?Department $routeWayEnd): self
    {
        $this->routeWayEnd = $routeWayEnd;

        return $this;
    }
}
