<?php

namespace App\Entity\Route;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteTariffRepository")
 *  * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="tariff_pkey", columns={
 *     "contractor_guid",
 *     "cargo_flow",
 *     "routeway_guid",
 *     "price_kind_guid",
 *     "tariff_type_guid",
 *     "date_start",
 *     "route_way_type",
 *     "boost_flag"
 * })
 * })
 */
class RouteTariff
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    private $contractorGuid;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @Groups({"Default"})
     */
    private $cargoFlow;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    private $routewayGuid;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    protected $tariffTypeGuid;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    protected $priceKindGuid;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"Default"})
     */
    protected $tariffTypeName;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    private $routeWayType;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Groups({"Default"})
     */
    private $fraht;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Groups({"Default"})
     */
    private $frahtIncity;

    /**
     * @ORM\Column(type="integer", nullable=false)
     * @Groups({"Default"})
     */
    private $frahtOutcity;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"Default"})
     */
    private $dateStart;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"Default"})
     */
    private $dateEnd;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @Groups({"Default"})
     */
    private $boostFlag = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContractorGuid(): ?UuidInterface
    {
        if (is_string($this->contractorGuid)) {
            return Uuid::fromString($this->contractorGuid);
        }

        return $this->contractorGuid;
    }

    public function setContractorGuid(string $contractorGuid): self
    {
        $this->contractorGuid = $contractorGuid;

        return $this;
    }

    public function getCargoFlow(): ?bool
    {
        return $this->cargoFlow;
    }

    public function setCargoFlow(bool $cargoFlow): self
    {
        $this->cargoFlow = $cargoFlow;

        return $this;
    }

    public function getRoutewayGuid(): ?UuidInterface
    {
        if (is_string($this->routewayGuid)) {
            return Uuid::fromString($this->routewayGuid);
        }

        return $this->routewayGuid;
    }

    public function setRoutewayGuid(string $routewayGuid): self
    {
        $this->routewayGuid = $routewayGuid;

        return $this;
    }

    public function getTariffTypeGuid(): ?UuidInterface
    {
        if (is_string($this->tariffTypeGuid)) {
            return Uuid::fromString($this->tariffTypeGuid);
        }

        return $this->tariffTypeGuid;
    }

    public function setTariffTypeGuid(string $tariffTypeGuid): self
    {
        $this->tariffTypeGuid = $tariffTypeGuid;

        return $this;
    }

    public function getPriceKindGuid(): ?UuidInterface
    {
        if (is_string($this->priceKindGuid)) {
            return Uuid::fromString($this->priceKindGuid);
        }

        return $this->priceKindGuid;
    }

    public function setPriceKindGuid(string $priceKindGuid): self
    {
        $this->priceKindGuid = $priceKindGuid;

        return $this;
    }

    public function getTariffTypeName(): ?string
    {
        return $this->tariffTypeName;
    }

    public function setTariffTypeName(?string $tariffTypeName): self
    {
        $this->tariffTypeName = $tariffTypeName;

        return $this;
    }

    public function getRouteWayType(): ?UuidInterface
    {
        if (is_string($this->routeWayType)) {
            return Uuid::fromString($this->routeWayType);
        }

        return $this->routeWayType;
    }

    public function setRouteWayType(string $routeWayType): self
    {
        $this->routeWayType = $routeWayType;

        return $this;
    }

    public function getFraht(): ?int
    {
        return $this->fraht;
    }

    public function setFraht(?int $fraht): self
    {
        $this->fraht = $fraht;

        return $this;
    }

    public function getFrahtIncity(): ?int
    {
        return $this->frahtIncity;
    }

    public function setFrahtIncity(?int $frahtIncity): self
    {
        $this->frahtIncity = $frahtIncity;

        return $this;
    }

    public function getFrahtOutcity(): ?int
    {
        return $this->frahtIncity;
    }

    public function setFrahtOutcity(?int $frahtOutcity): self
    {
        $this->frahtOutcity = $frahtOutcity;

        return $this;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getBoostFlag(): ?bool
    {
        return $this->boostFlag;
    }

    public function setBoostFlag(bool $boostFlag): self
    {
        $this->boostFlag = $boostFlag;

        return $this;
    }
}
