<?php

namespace App\Entity\Route;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\VehicleOrderRepository")
 * @ORM\Table(name="vehicle_orders")
 * @Gedmo\SoftDeleteable(fieldName="deletedOn", timeAware=false, hardDelete=false)
 */
class VehicleOrder
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @var Uuid
     * @ORM\Column(name="guid", type="uuid", unique=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    private $guid;

    /**
     * @ORM\Column(type="json_array")
     * @JMS\Groups({"VehicleOrder_info"})
     * @Groups({"VehicleOrder_info"})
     */
    private $immutableRoute = [];

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    private $createdOnTz = '+00:00';

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $createdOn;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    private $updatedOnTz = '+00:00';

    /**
     * @ORM\Column(type="datetime")
     * @Assert\NotBlank
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $updatedOn;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    private $deletedOnTz = '+00:00';

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $deletedOn;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Route\Route", mappedBy="vehicleOrder", cascade={"persist", "remove"})
     * @Groups({"Default"})
     */
    private $route;

    public function __construct()
    {
        $this->immutableRoute = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImmutableRoute()
    {
        return $this->immutableRoute;
    }

    public function setImmutableRoute($immutableRoute): self
    {
        $this->immutableRoute = $immutableRoute;

        return $this;
    }

    public function getGuid(): ?UuidInterface
    {
        if (is_string($this->guid)) {
            return Uuid::fromString($this->guid);
        }

        return $this->guid;
    }

    public function setGuid(string $guid): self
    {
        $this->guid = Uuid::fromString($guid);

        return $this;
    }

    public function getCreatedOn(): ?\DateTime
    {
        if ($this->createdOn) {
            $this->createdOn->setTimezone(new \DateTimeZone($this->createdOnTz));
        }

        return $this->createdOn;
    }

    public function getClearCreatedOn(): ?\DateTime
    {
        return $this->createdOn;
    }

    public function setCreatedOn(\DateTime $createdOn): self
    {
        $this->createdOn = $createdOn;
        if ($this->createdOn) {
            $this->setCreatedOnTz($createdOn->getTimezone());
        }

        return $this;
    }

    public function getCreatedOnTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->createdOnTz);
    }

    public function setCreatedOnTz(\DateTimeZone $timeZone): self
    {
        $this->createdOnTz = $timeZone->getName();

        return $this;
    }

    public function getUpdatedOn(): ?\DateTime
    {
        if ($this->updatedOn) {
            $this->updatedOn->setTimezone(new \DateTimeZone($this->updatedOnTz));
        }

        return $this->updatedOn;
    }

    public function getClearUpdatedOn(): ?\DateTime
    {
        return $this->updatedOn;
    }

    public function setUpdatedOn(\DateTime $updatedOn): self
    {
        $this->updatedOn = $updatedOn;
        if ($this->updatedOn) {
            $this->setUpdatedOnTz($updatedOn->getTimezone());
        }

        return $this;
    }

    public function getUpdatedOnTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->updatedOnTz);
    }

    public function setUpdatedOnTz(\DateTimeZone $timeZone): self
    {
        $this->updatedOnTz = $timeZone->getName();

        return $this;
    }

    public function getDeletedOn(): ?\DateTime
    {
        if ($this->deletedOn) {
            $this->deletedOn->setTimezone(new \DateTimeZone($this->updatedOnTz));
        }

        return $this->deletedOn;
    }

    public function getClearDeletedOn(): ?\DateTime
    {
        return $this->deletedOn;
    }

    public function setDeletedOn(\DateTime $deletedOn): self
    {
        $this->deletedOn = $deletedOn;
        if ($this->deletedOn) {
            $this->setDeletedOnTz($deletedOn->getTimezone());
        }

        return $this;
    }

    public function getDeletedOnTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->deletedOnTz);
    }

    public function setDeletedOnTz(\DateTimeZone $timeZone): self
    {
        $this->deletedOnTz = $timeZone->getName();

        return $this;
    }

    public function getRoute(): ?Route
    {
        return $this->route;
    }

    public function setRoute(Route $route): self
    {
        $this->route = $route;

        // set the owning side of the relation if necessary
        if ($this !== $route->getVehicleOrder()) {
            $route->setVehicleOrder($this);
        }

        return $this;
    }
}
