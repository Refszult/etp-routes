<?php

namespace App\Dto\Route;

use App\Dto\DtoInterface;
use App\Entity\Route\RouteOwner;
use App\Entity\Route\Transportation;
use App\Entity\Route\TransportationType;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * @Assert\Callback(
 *     callback = {
 *          "App\Validator\Callbacks\Route\RouteDtoValidator",
 *          "validateCargoPipelines",
 *     }
 * )
 * @Assert\Callback(
 *     callback = {
 *          "App\Validator\Callbacks\Route\RouteDtoValidator",
 *          "validateRoute",
 *     }
 * )
 */
class ApiCustomerRouteDto extends BaseRouteDto implements DtoInterface
{
    /**
     * @Assert\NotBlank(
     *     message="Плановая дата прибытия в первую точку не может быть пустой."
     * )
     *
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $planDateOfFirstPointArrive;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $planDateOfFirstPointLoading;

    /**
     * @Assert\NotBlank(
     *     message="Необходимо указать группу ответственных"
     * )
     *
     * @var RouteOwner
     * @JMS\Type("App\Entity\Route\RouteOwner")
     */
    protected $naRouteOwner;

    /**
     * @var Transportation
     * @JMS\Type("App\Entity\Route\Transportation")
     */
    protected $transportation;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $cargoFlow = false;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $freightSumm;

    /**
     * @var TransportationType
     * @JMS\Type("App\Entity\Route\TransportationType")
     */
    protected $transportationType;

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $createFields = [
        'routeWay',
        'transportation',
        'transportationType',
        'organization',
        'planDateOfFirstPointArrive',
        'planDateOfFirstPointLoading',
        'boostFlag',
        'cargoFlow',
        'freightSumm',
        'contractor',
        'transport',
        'trailer',
        'driverOne',
        'driverTwo',
        'isDraft',
    ];

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $updateFields = [
        'updatedFrom',
        'boostFlag',
        'cargoFlow',
        'organization',
        'planDateOfFirstPointArrive',
        'planDateOfFirstPointLoading',
        'transport',
        'trailer',
        'driverOne',
        'driverTwo',
        'isDraft',
    ];

    /**
     * @return \DateTime|null
     */
    public function getPlanDateOfFirstPointLoading(): ?\DateTime
    {
        return $this->planDateOfFirstPointLoading;
    }

    /**
     * @param \DateTime $planDateOfFirstPointLoading
     */
    public function setPlanDateOfFirstPointLoading(\DateTime $planDateOfFirstPointLoading): void
    {
        $this->planDateOfFirstPointLoading = $planDateOfFirstPointLoading;
    }

    /**
     * @return RouteOwner
     */
    public function getNaRouteOwner(): RouteOwner
    {
        return $this->naRouteOwner;
    }

    /**
     * @param RouteOwner $naRouteOwner
     */
    public function setNaRouteOwner(RouteOwner $naRouteOwner): void
    {
        $this->naRouteOwner = $naRouteOwner;
    }

    /**
     * @return Transportation
     */
    public function getTransportation(): ?Transportation
    {
        return $this->transportation;
    }

    /**
     * @param Transportation $transportation
     */
    public function setTransportation(Transportation $transportation): void
    {
        $this->transportation = $transportation;
    }

    /**
     * @return bool
     */
    public function getCargoFlow(): bool
    {
        return $this->cargoFlow;
    }

    /**
     * @param bool $cargoFlow
     */
    public function setCargoFlow(bool $cargoFlow): void
    {
        $this->cargoFlow = $cargoFlow;
    }

    /**
     * @return string
     */
    public function getFreightSumm(): string
    {
        return $this->freightSumm;
    }

    /**
     * @param string $freightSumm
     */
    public function setFreightSumm(string $freightSumm): void
    {
        $this->freightSumm = $freightSumm;
    }

    /**
     * @return TransportationType
     */
    public function getTransportationType(): ?TransportationType
    {
        return $this->transportationType;
    }

    /**
     * @param TransportationType $transportationType
     */
    public function setTransportationType(TransportationType $transportationType): void
    {
        $this->transportationType = $transportationType;
    }
}
