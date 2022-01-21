<?php

namespace App\Entity\Route;

use App\Entity\Container\Container;
use App\Entity\Vehicle\Vehicle;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteContainerRepository")
 */
class RouteContainer
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Route", inversedBy="routeContainers")
     * @Groups({"Default"})
     */
    private $route;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicle\Vehicle")
     * @Groups({"Default"})
     */
    private $vehicle;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Container\Container")
     * @Groups({"Default"})
     */
    private $container;

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

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getContainer(): ?Container
    {
        return $this->container;
    }

    public function setContainer(?Container $container): self
    {
        $this->container = $container;

        return $this;
    }
}
