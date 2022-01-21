<?php

namespace App\Entity\Route;

use App\Entity\Traits\GuidTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\WarehouseRepository")
 */
class Warehouse
{
    use GuidTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"Default"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"Default"})
     */
    private $name;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=12, options={"default" : 0})
     * @Groups({"Default"})
     */
    private $longitude = 0;

    /**
     * @ORM\Column(type="decimal", precision=15, scale=12, options={"default" : 0})
     * @Groups({"Default"})
     */
    private $latitude = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"Default"})
     */
    private $address;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Department", inversedBy="warehouses")
     * @Groups({"Default"})
     */
    private $department;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLongitude()
    {
        return $this->longitude;
    }

    public function setLongitude($longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude()
    {
        return $this->latitude;
    }

    public function setLatitude($latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

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
}
