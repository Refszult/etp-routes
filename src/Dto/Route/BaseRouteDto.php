<?php

namespace App\Dto\Route;

use App\Classes\StaticStorage\UpdatedFrom;
use App\Dto\DtoClass;
use App\Entity\Agreement\Organization;
use App\Entity\Contractor;
use App\Entity\Customer;
use App\Entity\Driver\Driver;
use App\Entity\Route\CargoPipeline;
use App\Entity\Route\RouteContainer;
use App\Entity\Route\RouteOwner;
use App\Entity\Route\RouteWay;
use App\Entity\Vehicle\Vehicle;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

abstract class BaseRouteDto extends DtoClass
{
    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var int
     * @JMS\Type("integer")
     * @JMS\Groups({"MQ"})
     */
    protected $updatedFrom = UpdatedFrom::UPDATED_FROM_ETP;

    /**
     * @var Customer
     * @JMS\Type("App\Entity\Customer")
     * @JMS\Groups({"Route_info"})
     */
    protected $customer;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $orderDate;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $routeDate;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $boostFlag = false;

    /**
     * @Assert\NotBlank(
     *     message="Необходимо указать маршрут",
     *     groups={"Create"}
     * )
     *
     * @var RouteWay
     * @JMS\Type("App\Entity\Route\RouteWay")
     * @JMS\Groups({"Route_info", "Route_des"})
     */
    protected $routeWay;

    /**
     * @var Vehicle
     * @JMS\Type("App\Entity\Vehicle\Vehicle")
     */
    protected $transport;

    /**
     * @var Vehicle
     * @JMS\Type("App\Entity\Vehicle\Vehicle")
     */
    protected $trailer;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $planDateOfFirstPointArrive;

    /**
     * @var RouteOwner
     * @JMS\Type("App\Entity\Route\RouteOwner")
     */
    protected $routeOwner;

    /**
     * @var Contractor
     * @JMS\Type("App\Entity\Contractor")
     */
    protected $contractor;

    /**
     *
     * @var Driver
     * @JMS\Type("App\Entity\Driver\Driver")
     */
    protected $driverOne;

    /**
     * @var Driver
     * @JMS\Type("App\Entity\Driver\Driver")
     */
    protected $driverTwo;

    /**
     * @var Collection|RouteContainer[]
     * @JMS\Type("ArrayCollection<App\Entity\Route\RouteContainer>")
     * @JMS\Groups({"Route_info", "Route_des"})
     */
    protected $routeContainers;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $haulerBlocked = false;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $cargoFlow = false;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $routeOwnerId;

    /**
     * @var Collection|CargoPipeline[]|null
     * @JMS\Type("ArrayCollection<App\Entity\Route\CargoPipeline>")
     * @Assert\Valid
     */
    protected $cargoPipelines;

    /**
     * @var Organization
     * @JMS\Type("App\Entity\Agreement\Organization")
     */
    protected $organization;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $isDraft = true;

    public function __construct()
    {
        $this->cargoPipelines = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpdatedFrom(): int
    {
        return $this->updatedFrom;
    }

    public function setUpdatedFrom(int $updatedFrom): void
    {
        $this->updatedFrom = $updatedFrom;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function setCustomer(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function getOrderDate(): ?\DateTime
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTime $orderDate): void
    {
        $this->orderDate = $orderDate;
    }

    public function getRouteDate(): ?\DateTime
    {
        return $this->routeDate;
    }

    public function setRouteDate(\DateTime $routeDate): void
    {
        $this->routeDate = $routeDate;
    }

    public function getBoostFlag(): bool
    {
        return $this->boostFlag;
    }

    public function setBoostFlag(bool $boostFlag): void
    {
        $this->boostFlag = $boostFlag;
    }

    public function getRouteWay(): RouteWay
    {
        return $this->routeWay;
    }

    public function setRouteWay(RouteWay $routeWay): void
    {
        $this->routeWay = $routeWay;
    }

    /**
     * @return Vehicle
     */
    public function getTransport(): ?Vehicle
    {
        return $this->transport;
    }

    public function setTransport(Vehicle $transport): void
    {
        $this->transport = $transport;
    }

    /**
     * @return Vehicle
     */
    public function getTrailer(): ?Vehicle
    {
        return $this->trailer;
    }

    public function setTrailer(Vehicle $trailer): void
    {
        $this->trailer = $trailer;
    }

    public function getPlanDateOfFirstPointArrive(): ?\DateTime
    {
        return $this->planDateOfFirstPointArrive;
    }

    public function setPlanDateOfFirstPointArrive(\DateTime $planDateOfFirstPointArrive): void
    {
        $this->planDateOfFirstPointArrive = $planDateOfFirstPointArrive;
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

    /**
     * @return Contractor
     */
    public function getContractor(): ?Contractor
    {
        return $this->contractor;
    }

    public function setContractor(Contractor $contractor): void
    {
        $this->contractor = $contractor;
    }

    /**
     * @return Driver
     */
    public function getDriverOne(): ?Driver
    {
        return $this->driverOne;
    }

    public function setDriverOne(Driver $driverOne): void
    {
        $this->driverOne = $driverOne;
    }

    /**
     * @return Driver
     */
    public function getDriverTwo(): ?Driver
    {
        return $this->driverTwo;
    }

    public function setDriverTwo(Driver $driverTwo): void
    {
        $this->driverTwo = $driverTwo;
    }

    /**
     * @return RouteContainer[]|Collection
     */
    public function getRouteContainers()
    {
        return $this->routeContainers;
    }

    /**
     * @param RouteContainer[]|Collection $routeContainers
     */
    public function setRouteContainers($routeContainers): void
    {
        $this->routeContainers = $routeContainers;
    }

    public function getHaulerBlocked(): bool
    {
        return $this->haulerBlocked;
    }

    public function setHaulerBlocked(bool $haulerBlocked): void
    {
        $this->haulerBlocked = $haulerBlocked;
    }

    public function getCargoFlow(): bool
    {
        return $this->cargoFlow;
    }

    public function setCargoFlow(bool $cargoFlow): void
    {
        $this->cargoFlow = $cargoFlow;
    }

    public function getRouteOwnerId(): string
    {
        return $this->routeOwnerId;
    }

    public function setRouteOwnerId(string $routeOwnerId): void
    {
        $this->routeOwnerId = $routeOwnerId;
    }

    public function getCargoPipelines(): ?Collection
    {
        return $this->cargoPipelines;
    }

    public function addCargoPipelines(CargoPipeline $cargoPipeline): self
    {
        if (!$this->cargoPipelines->contains($cargoPipeline)) {
            $this->cargoPipelines[] = $cargoPipeline;
        }

        return $this;
    }

    public function removeCargoPipelines(CargoPipeline $cargoPipeline): self
    {
        if ($this->cargoPipelines->contains($cargoPipeline)) {
            $this->cargoPipelines->removeElement($cargoPipeline);
        }

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getIsDraft(): bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): void
    {
        $this->isDraft = $isDraft;
    }
}
