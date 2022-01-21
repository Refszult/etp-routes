<?php

namespace App\Entity\Route;

use App\Entity\Contractor;
use App\Entity\CustomerContractor;
use App\Entity\Driver\Driver;
use App\Entity\Vehicle\Vehicle;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Timezones;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\CargoPipelineRepository")
 */
class CargoPipeline
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(
     *     targetEntity=Driver::class,
     *     inversedBy="cargoPipelines",
     *     fetch="EAGER",
     *     cascade={"persist"}
     * )
     * @Groups({"Default"})
     * @Assert\NotBlank(
     *     message="Необходимо указать водителя.",
     *     groups={"ETP", "Create"},
     * )
     */
    private $driver;

    /**
     * @ORM\ManyToOne(
     *     targetEntity=Vehicle::class,
     *     inversedBy="cargoPipelines",
     *     fetch="EAGER",
     *     cascade={"persist"}
     * )
     * @Groups({"Default"})
     * @Assert\NotBlank(
     *     message="Необходимо указать ТС.",
     *     groups={"ETP"},
     * )
     */
    private $vehicle;

    /**
     * @ORM\ManyToOne(
     *     targetEntity=CargoPipelineEvent::class,
     *     inversedBy="cargoPipelines",
     *     fetch="EAGER",
     *     cascade={"persist"}
     * )
     * @Groups({"Default"})
     * @Assert\NotBlank(
     *     message="Необходимо указать событие грузопровода.",
     *     groups={"ETP"},
     * )
     */
    private $cargoPipelineEvent;

    /**
     * @ORM\ManyToOne(
     *     targetEntity=CargoPipelinePlacesOfEvent::class,
     *     inversedBy="cargoPipelines",
     *     fetch="EAGER",
     *     cascade={"persist"}
     * )
     * @Groups({"Default"})
     * @Assert\NotBlank(
     *     message="Необходимо указать место события.",
     *     groups={"ETP"},
     * )
     */
    private $cargoPipelinePlaceOfEvent;

    /**
     * @ORM\ManyToOne(
     *     targetEntity=Route::class,
     *     inversedBy="cargoPipelines",
     *     fetch="EAGER",
     *     cascade={"persist"}
     * )
     * @JMS\Exclude()
     */
    private $route;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"Default"})
     * @Assert\NotBlank(
     *     message="Необходимо указать время события грузопровода.",
     * )
     */
    private $cargoPipelineEventDate;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     */
    protected $cargoPipelineEventDateTz = '+00:00';

    /**
     * @ORM\ManyToOne(targetEntity=Contractor::class)
     * @Groups({"Default"})
     */
    private $partner;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     * @Assert\NotBlank(
     *     message="Необходимо указать порядковый номер события грузопровода.",
     * )
     */
    private $rowNumber = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriver(): ?Driver
    {
        return $this->driver;
    }

    public function setDriver(Driver $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getCargoPipelineEvent(): ?CargoPipelineEvent
    {
        return $this->cargoPipelineEvent;
    }

    public function setCargoPipelineEvent(CargoPipelineEvent $cargoPipelineEvent): self
    {
        $this->cargoPipelineEvent = $cargoPipelineEvent;

        return $this;
    }

    public function getCargoPipelinePlaceOfEvent(): ?CargoPipelinePlacesOfEvent
    {
        return $this->cargoPipelinePlaceOfEvent;
    }

    public function setCargoPipelinePlaceOfEvent(CargoPipelinePlacesOfEvent $cargoPipelinePlaceOfEvent): self
    {
        $this->cargoPipelinePlaceOfEvent = $cargoPipelinePlaceOfEvent;

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

    public function getCargoPipelineEventDate(): ?\DateTimeInterface
    {
        if ($this->cargoPipelineEventDate) {
            $this->cargoPipelineEventDate->setTimezone(new \DateTimeZone($this->cargoPipelineEventDateTz));
        }

        return $this->cargoPipelineEventDate;
    }

    public function setCargoPipelineEventDate(\DateTimeInterface $cargoPipelineEventDate): self
    {
        $this->cargoPipelineEventDate = $cargoPipelineEventDate;
        if ($this->cargoPipelineEventDate) {
            $this->setCargoPipelineEventDateTz($cargoPipelineEventDate->getTimezone());
        }

        return $this;
    }

    public function getCargoPipelineEventDateTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->cargoPipelineEventDateTz);
    }

    public function setCargoPipelineEventDateTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->cargoPipelineEventDateTz = $timeZone->getName();
        } else {
            $this->cargoPipelineEventDateTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getPartner(): ?Contractor
    {
        return $this->partner;
    }

    public function setPartner(?Contractor $partner): self
    {
        $this->partner = $partner;

        return $this;
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
}
