<?php

namespace App\Service\Route\Actions;

use App\Dto\Route\BaseRouteDto;
use App\Entity\Agreement\Organization;
use App\Entity\Auction\AuctionRouteOptionalDimension;
use App\Entity\Container\Container;
use App\Entity\Customer;
use App\Entity\Driver\ContractorDriver;
use App\Entity\Driver\Driver;
use App\Entity\Route\CargoPipeline;
use App\Entity\Route\Route;
use App\Entity\Route\RouteContainer;
use App\Entity\Route\RouteCurrentState;
use App\Entity\Route\RouteMovementPoint;
use App\Entity\Route\RouteWay;
use App\Entity\Route\RouteWayPoint;
use App\Entity\Route\Transportation;
use App\Entity\Route\TransportationType;
use App\Entity\Route\VehicleOrder;
use App\Entity\Tender\RouteTemplateOptionalDimension;
use App\Entity\Vehicle\Dimension;
use App\Entity\Vehicle\DimensionCalculate;
use App\Entity\Vehicle\Vehicle;
use App\Exceptions\WrongObjectException;
use App\Service\Base\BaseActionDtoService;
use Doctrine\Common\Collections\Collection;
use Exception;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ActionsRouteService extends BaseActionDtoService
{
    /**
     * Создание нового ЗТС.
     *
     * @throws Exception
     */
    protected function createVehicleOrder(Route $route, ?UuidInterface $guid = null)
    {
        $vehicleOrder = new VehicleOrder();
        if ($guid) {
            $vehicleOrder->setGuid($guid->toString());
        } else {
            $vehicleOrder->setGuid(Uuid::uuid4()->toString());
        }
        $vehicleOrder->setRoute($route);
        $vehicleOrder->setCreatedOn(new \DateTime());
        $vehicleOrder->setUpdatedOn(new \DateTime());
        //$this->entityManager->persist($vehicleOrder);
    }

    /**
     * Добавление заказчика к рейсу.
     */
    protected function setRouteCustomer(Route $route)
    {
        $customerId = $this->container->getParameter('default_customer_id');
        $customer = $this->entityManager->find(Customer::class, $customerId);
        if (!$customer) {
            throw new WrongObjectException('Не установлен заказчик по умолчанию');
        }
        $route->setCustomer($customer);
    }

    /**
     * Добавление точек движения из маршрута.
     */
    protected function setParentMovementPoints(Route $route, Collection $routeWayPoints)
    {
        foreach ($routeWayPoints as $routeWayPoint) {
            $movementPoint = new RouteMovementPoint();
            $movementPoint->setRoute($route);
            $movementPoint->setActive(true);
            $movementPoint->setDepartment($routeWayPoint->getDepartment());
            $movementPoint->setRowNumber($routeWayPoint->getRowNumber());
            $this->entityManager->persist($movementPoint);
            $route->addMovementPoint($movementPoint);
        }
    }

    /**
     * Установка таймзоны пункта прибытия.
     */
    protected function setArriveTimeZone(Route $route, Collection $wayPoints)
    {
        /** @var RouteWayPoint[] $arrayWayPoints */
        $arrayWayPoints = $wayPoints->toArray();
        usort($arrayWayPoints, function ($a, $b) {
            if ($a->getRowNumber() == $b->getRowNumber()) {
                return 0;
            }

            return ($a->getRowNumber() < $b->getRowNumber()) ? -1 : 1;
        });
        $trueTzString = $arrayWayPoints[0]->getDepartment()->getTimeZone() ?: date_default_timezone_get();
        $trueTz = new \DateTimeZone($trueTzString);
        if ($route->getPlanDateOfFirstPointArrive()) {
            $nowTz = $route->getPlanDateOfFirstPointArriveTz();
            if ($trueTz->getName() !== $nowTz->getName()) {
                $planDateOfFirstPointArrive = $route->getPlanDateOfFirstPointArrive();
                $planDateOfFirstPointArrive->setTimezone($trueTz);
                $route->setPlanDateOfFirstPointArrive($planDateOfFirstPointArrive);
            }
        }
        if ($route->getPlanDateOfFirstPointLoading()) {
            $nowTz = $route->getPlanDateOfFirstPointLoadingTz();
            if ($trueTz->getName() !== $nowTz->getName()) {
                $planDateOfFirstPointLoading = $route->getPlanDateOfFirstPointLoading();
                $planDateOfFirstPointLoading->setTimezone($trueTz);
                $route->setPlanDateOfFirstPointLoading($planDateOfFirstPointLoading);
            }
        }
        if ($route->getFactDateOfFirstPointArrive()) {
            $nowTz = $route->getFactDateOfFirstPointArriveTz();
            if ($trueTz->getName() !== $nowTz->getName()) {
                $factDateOfFirstPointArrive = $route->getFactDateOfFirstPointArrive();
                $factDateOfFirstPointArrive->setTimezone($trueTz);
                $route->setFactDateOfFirstPointArrive($factDateOfFirstPointArrive);
            }
        }
    }

    /**
     * Получение типа перевозки по умолчанию.
     */
    protected function getDefaultTransportation(): Transportation
    {
        //TODO - механизм поиска типа перевозки не выглядит надежным
        /** @var Transportation $defaultTransportation */
        $defaultTransportation = $this->entityManager->getRepository(Transportation::class)
            ->findOneBy(['guid' => '4e84c200-31d7-11e8-80c9-00155d668927']);

        return $defaultTransportation;
    }

    protected function getDefaultTransportationType(): TransportationType
    {
        /** @var TransportationType $defaultTransportationType */
        $defaultTransportationType = $this->entityManager->getRepository(TransportationType::class)
            ->findOneBy(['guid' => '6193de5b-405a-426f-809a-4103f9e264db']);

        return $defaultTransportationType;
    }

    /**
     * Сброс данных по транспорту.
     */
    protected function clearTransport(Route $route, ?Vehicle $vehicle)
    {
        $clear = true;
        if ($route->getTransport()) {
            if ($vehicle) {
                if ($route->getTransport()->getId() === $vehicle->getId()) {
                    $clear = false;
                }
            }
            if ($clear) {
                $this->clearContainers($route, $route->getTransport());
                $route->setTransport(null);
            }
        }
    }

    /**
     * Сброс данных по прицепу.
     */
    protected function clearTrailer(Route $route, ?Vehicle $vehicle)
    {
        $clear = true;
        if ($route->getTrailer()) {
            if ($vehicle) {
                if ($route->getTrailer()->getId() === $vehicle->getId()) {
                    $clear = false;
                }
            }
            if ($clear) {
                $this->clearContainers($route, $route->getTrailer());
                $route->setTrailer(null);
            }
        }
    }

    /**
     * Очистка контейнеров при смене автомобиля.
     */
    protected function clearContainers(Route $route, Vehicle $vehicle)
    {
        $containers = $this->entityManager->getRepository(RouteContainer::class)
            ->findBy(['route' => $route->getId(), 'vehicle' => $vehicle]);
        if (count($containers) > 0) {
            foreach ($containers as $container) {
                $this->entityManager->remove($container);
                $route->removeRouteContainer($container);
            }
        }
    }

    /**
     * Обновление данных ЗТС
     */
    protected function updateVehicleOrder(Route $route)
    {
        if (!$route->getIsDraft()) {
            $vehicleOrder = $route->getVehicleOrder();
            $context = new SerializationContext();
            $context->setGroups(['Default']);
            $context->enableMaxDepthChecks();
            /** @var SerializerInterface $serializer */
            $serializer = $this->container->get('jms_serializer');
            $data = json_decode($serializer->serialize($route, 'json', $context), 1);
            $vehicleOrder->setImmutableRoute($data);
            $this->entityManager->persist($vehicleOrder);
        }
    }

    /**
     * Поиск водителя.
     *
     * @throws Exception
     */
    protected function findDriver(Driver $driver, Route $route): ?Driver
    {
        $outDriver = $this->finderHelper->findDriver($driver);
        if ($outDriver) {
            if (Driver::STATUS_ALLOWED !== $outDriver->getStatus()) {
                throw new WrongObjectException('Данный водитель не верифицирован.');
            }
            $contractorDriver = $this->entityManager->getRepository(ContractorDriver::class)
                ->findOneBy(['driver' => $outDriver->getId(), 'contractor' => $route->getContractor()->getId()]);
            if ($contractorDriver) {
                if (!$contractorDriver->getActiveAttorney()) {
                    throw new WrongObjectException('Данный водитель не имеет активной доверенности у подрядчика.');
                }
                if ($route->getCargoFlow()) {
                    if (!$outDriver->getDriverAccessCargoFlow()) {
                        throw new WrongObjectException('Данный водитель не имеет разрешения на работу с грузопроводом.');
                    }
                }
                if (TransportationType::TRANSPORTATION_TYPE_AUTO === $route->getTransportationType()->getGuid()->toString()) {
                    if (!$outDriver->getDriverAccessAuto()) {
                        throw new WrongObjectException('Данный водитель не имеет разрешения на работу с автоперевозками.');
                    }
                }
                if (TransportationType::TRANSPORTATION_TYPE_TRAIN === $route->getTransportationType()->getGuid()->toString()) {
                    if (!$outDriver->getDriverAccessContainer()) {
                        throw new WrongObjectException('Данный водитель не имеет разрешения на работу с ЖД перевозками.');
                    }
                }
            } else {
                throw new WrongObjectException('Данный водитель не связан с подрядчиком, назначенным на рейс.');
            }
        }

        return $outDriver;
    }

    /**
     * Добавление нового контейнера рейса.
     */
    protected function addRouteContainer(Route $route, Vehicle $vehicle, Container $container)
    {
        $canAdd = false;
        if ($route->getTransport()->getId() === $vehicle->getId()) {
            $vehicleModel = $route->getTransport()->getModel();
            if ($vehicleModel->getContainerTransportVehicle()) {
                $vehicleId = $route->getTransport()->getId();
                $vehicleContainers = $this->filterVehicleContainers($route, $vehicleId);
                if (count($vehicleContainers) > 0) {
                    throw new WrongObjectException('Для данного транспорта указано слишком много контейнеров.');
                } else {
                    $canAdd = true;
                }
            } else {
                throw new WrongObjectException('Данный транспорт не может перевозить контейнеры.');
            }
        } elseif ($route->getTrailer()) {
            if ($route->getTrailer()->getId() === $vehicle->getId()) {
                $vehicleModel = $route->getTrailer()->getModel();
                if ($vehicleModel->getContainerTransportVehicle()) {
                    $vehicleId = $route->getTrailer()->getId();
                    $vehicleContainers = $this->filterVehicleContainers($route, $vehicleId);
                    if (count($vehicleContainers) > 1) {
                        throw new WrongObjectException('Для данного трейлера указано слишком много контейнеров.');
                    } else {
                        $canAdd = true;
                    }
                } else {
                    throw new WrongObjectException('Данный трейлер не может перевозить контейнеры.');
                }
            }
        }

        if ($canAdd) {
            $routeContainer = new RouteContainer();
            $routeContainer->setRoute($route);
            $routeContainer->setVehicle($vehicle);
            $routeContainer->setContainer($container);
            $this->entityManager->persist($routeContainer);
            $route->addRouteContainer($routeContainer);
        }
    }

    /**
     * Фильтр связок контейнеров по ID ТС.
     *
     * @return Collection
     */
    protected function filterVehicleContainers(Route $route, int $vehicleId)
    {
        $vehicleContainers = $route->getRouteContainers()->filter(
            function ($entry) use ($vehicleId) {
                /** @var RouteContainer $entry */
                if ($entry->getVehicle()->getId() === $vehicleId) {
                    return true;
                } else {
                    return false;
                }
            }
        );

        return $vehicleContainers;
    }

    /**
     * Установка первого водителя.
     *
     * @throws Exception
     */
    protected function setDriverOne(Route $route, BaseRouteDto $routeDto)
    {
        if ($driverOne = $routeDto->getDriverOne()) {
            $this->setDriverOneValue($route, $driverOne);
        } else {
            $route->setDriverOne(null);
            $route->setDriverTwo(null);
        }
    }

    /**
     * Установка значения первого водителя.
     *
     * @throws Exception
     */
    protected function setDriverOneValue(Route $route, Driver $driver)
    {
        $driver = $this->findDriver($driver, $route);
        if (!$driver) {
            throw new WrongObjectException('Данного водителя не существует или не разрешено добавлять к рейсу.');
        }
        $route->setDriverOne($driver);
    }

    /**
     * Установка второго водителя.
     *
     * @throws Exception
     */
    protected function setDriverTwo(Route $route, BaseRouteDto $routeDto)
    {
        if ($driverTwo = $routeDto->getDriverTwo()) {
            $driver = $this->findDriver($driverTwo, $route);
            if (!$driver) {
                throw new WrongObjectException('Данного водителя не существует или не разрешено добавлять к рейсу.');
            }
            $route->setDriverTwo($driver);
        } else {
            $route->setDriverTwo(null);
        }
    }

    /**
     * Установка транспорта для рейса.
     */
    protected function setCar(Route $route, BaseRouteDto $routeDto)
    {
        if ($routeDto->getTransport()) {
            $transport = $this->finderHelper->findTransport($routeDto->getTransport());
            if (!$transport) {
                throw new WrongObjectException('Переданное ТС не найдено.');
            }
            $this->clearTransport($route, $transport);
            $route->setTransport($transport);
        } else {
            $this->clearTransport($route, null);
        }
    }

    /**
     * Установка трейлера для рейса.
     */
    protected function setTrailer(Route $route, BaseRouteDto $routeDto)
    {
        $trailer = null;
        if ($routeDto->getTrailer()) {
            if ($trailer = $this->finderHelper->findTransport($routeDto->getTrailer())) {
                $this->clearTrailer($route, $trailer);
                $route->setTrailer($trailer);
            } else {
                throw new WrongObjectException('Не найден переданный прицеп');
            }
        } else {
            $this->clearTrailer($route, $trailer);
        }
    }

    /**
     * Установка контейнеров рейса.
     */
    protected function setRouteContainers(Route $route, BaseRouteDto $routeDto)
    {
        if ($routeDto->getRouteContainers()) {
            $incomRouteContainers = $routeDto->getRouteContainers();
            if (!$this->checkContainersData($incomRouteContainers)) {
                throw new WrongObjectException('Указаны неверные данные в списке контейнеров.');
            }

            // Обновление и удаление текущих связок контейнеров
            foreach ($route->getRouteContainers() as $existRouteContainer) {
                $vehicleId = $existRouteContainer->getVehicle()->getId();
                $containerId = $existRouteContainer->getContainer()->getId();
                $routeContainers = $incomRouteContainers->filter(
                    function ($entry) use ($vehicleId, $containerId) {
                        /** @var RouteContainer $entry */
                        if ($entry->getVehicle()->getId() === $vehicleId
                            && $entry->getContainer()->getId() === $containerId
                        ) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                );
                if (count($routeContainers) > 0) {
                    $routeContainer = $routeContainers->first();
                    $incomRouteContainers->removeElement($routeContainer);
                } else {
                    $this->entityManager->remove($existRouteContainer);
                }
            }

            // Добавление новых связок контейнеров
            if (count($routeDto->getRouteContainers()) > 0) {
                foreach ($routeDto->getRouteContainers() as $routeContainer) {
                    if ($routeContainer->getVehicle() && $routeContainer->getContainer()) {
                        $vehicle = $this->finderHelper->findTransport($routeContainer->getVehicle(), false);
                        $container = $this->finderHelper->findContainer($routeContainer->getContainer(), false);
                        if ($vehicle && $container) {
                            $this->addRouteContainer($route, $vehicle, $container);
                        } else {
                            throw new WrongObjectException('Указан неверный контейнер или ТС.');
                        }
                    }
                }
            }
        } else {
            foreach ($route->getRouteContainers() as $existRouteContainer) {
                $route->removeRouteContainer($existRouteContainer);
                $this->entityManager->remove($existRouteContainer);
            }
        }
    }

    /**
     * Установка грузопровода рейса. Грузопроводы всегда обновляются.
     */
    protected function setCargoPipeline(Route $route, BaseRouteDto $routeDto)
    {
//        if (!$this->checkCargoPipelineData($routeDto->getCargoPipelines(), $route)) {
//            throw new WrongObjectException('Указаны неверные данные в списке грузопровода.');
//        }
        $route->getCargoPipelines()->clear();
        if ($routeDto->getCargoPipelines()) {
            $canAddFirstDriver = true;
            /** @var CargoPipeline $newPipeline */
            foreach ($routeDto->getCargoPipelines() as $newPipeline) {
                $route->addCargoPipeline($newPipeline);
                if ($canAddFirstDriver && !$route->getDriverOne()) {
                    if (!$newPipeline->getDriver()) {
                        continue;
                    }
                    if ($newPipeline->getDriver()->findContractorDriver($route->getContractor())) {
                        $this->setDriverOneValue($route, $newPipeline->getDriver());
                    }
                }
            }
        }
//            // Обновление и удаление текущих связок грузопровода
//            /** @var CargoPipeline $existCargoPipeline */
//            foreach ($route->getCargoPipelines() as $existCargoPipeline) {
//                $vehicleId = $existCargoPipeline->getVehicle()->getId();
//                $driverId = $existCargoPipeline->getDriver()->getId();
//                $routeCargoPipelines = $incomCargoPipeLines->filter(
//                    function ($entry) use ($vehicleId, $driverId) {
//                        /** @var CargoPipeline $entry */
//                        if ($entry->getVehicle()->getId() === $vehicleId
//                            && $entry->getDriver()->getId() === $driverId
//                        ) {
//                            return true;
//                        } else {
//                            return false;
//                        }
//                    }
//                );
//                if (count($routeCargoPipelines) > 0) {
//                    $routeCargoPipeline = $routeCargoPipelines->first();
//                    $routeCargoPipelines->removeElement($routeCargoPipeline);
//                } else {
//                    $this->entityManager->remove($existCargoPipeline);
//                }
//            }
//            /** @var CargoPipeline $existRouteContainer */
//            foreach ($route->getCargoPipelines() as $existRouteContainer) {
//                $route->removeCargoPipeline($existRouteContainer);
//                $this->entityManager->remove($existRouteContainer);
//            }
//            /** @var CargoPipeline $newCargoPipeline */
//            foreach ($routeDto->getCargoPipelines() as $newCargoPipeline) {
//                $route->addCargoPipeline($newCargoPipeline);
//                $this->entityManager->persist($newCargoPipeline);
//            }
//        }
    }

    /**
     * Проверка передаваемых данных по контейнерам рейса.
     *
     * @return bool
     */
    protected function checkContainersData(Collection $routeContainers)
    {
        $output = false;
        foreach ($routeContainers as $routeContainer) {
            if ($routeContainer->getVehicle() && $routeContainer->getContainer()) {
                if (($routeContainer->getVehicle()->getId() && $routeContainer->getContainer()->getId())) {
                    $output = true;
                    $vehicle = $this->finderHelper->findTransport($routeContainer->getVehicle(), false);
                    $container = $this->finderHelper->findContainer($routeContainer->getContainer(), false);
                    if (!$vehicle || !$container) {
                        return false;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Проверка передаваемых данных в грузопроводе рейса.
     *
     * @throws Exception
     */
    protected function checkCargoPipelineData(Collection $cargoPipelines, Route $route): bool
    {
        $output = false;
        /** @var CargoPipeline $cargoPipeline */
        foreach ($cargoPipelines as $cargoPipeline) {
            if ($cargoPipeline->getVehicle() && $cargoPipeline->getDriver()) {
                if (($cargoPipeline->getVehicle()->getId() && $cargoPipeline->getDriver()->getId())) {
                    $output = true;
                    $vehicle = $this->finderHelper->findTransport($cargoPipeline->getVehicle(), false);
                    $driver = $this->findDriver($cargoPipeline->getDriver(), $route);
                    if (!$vehicle || !$driver) {
                        return false;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * Проверка объема ТС в рейсе по отношению к доступным габаритам, в случае его пересчет стоимости.
     *
     * @param int $updatedFrom
     *
     * @return ?void
     */
    protected function checkRouteVolume(Route $route, BaseRouteDto $routeDto, $updatedFrom = 0)
    {
        if ($currentState = $route->getRouteCurrentState()) {
            if (RouteCurrentState::STATUS_AWAITING_ARRIVAL != $currentState->getStatus()) {
                return;
            }
        }

        $dimensionNameList = '';
        $validate = false;
        $routeVolume = 0;

        /** @var ?Vehicle $transport */
        if ($transport = $route->getTransport()) {
            $routeVolume += $transport->getModel()->getBodyVolume();
        }
        /** @var ?Vehicle $trailer */
        if ($trailer = $route->getTrailer()) {
            $routeVolume += $trailer->getModel()->getBodyVolume();
        }
        /** @var ?Collection $containers */
        if ($containers = $route->getRouteContainers()) {
            /** @var RouteContainer $routeContainer */
            foreach ($containers as $routeContainer) {
                $routeVolume += $routeContainer->getContainer()->getVolume();
            }
        }

        $mainDimension = ($auctionRoute = $route->getAuctionRoute())
            ? $route->getAuctionRoute()->getDimension()
            : $route->getRouteTemplate()->getDimension();
        $volumeFrom = $mainDimension->getVolumeFrom();
        $volumeUpTo = $mainDimension->getVolumeUpTo();

        if ($routeVolume >= $volumeFrom && ($routeVolume <= $volumeUpTo || 0 == $volumeUpTo)) {
            $route->setFreightSumm($route->getInitialSumm());
            return;
        }

        $optionalDimensions = ($auctionRoute)
            ? $auctionRoute->getOptionalDimensions()
            : $route->getRouteTemplate()->getOptionalDimensions();

        $dimensionNameList = $mainDimension->getName();
        foreach ($optionalDimensions as $dimension) {
            $validate = $this->checkCalculateDimension($route, $dimension, $routeVolume, $mainDimension);
            $dimensionNameList .= ', ' . $dimension->getDimension()->getName();
            if ($validate) break;
        }

        //Если габарит больше основого, но не подходит по дополнительным
        if (false === $validate && ((0 == $volumeUpTo && $routeVolume > $volumeFrom) || (0 != $volumeUpTo && $routeVolume > $volumeUpTo))) {
            $route->setFreightSumm($route->getInitialSumm());
            return;
        }

        if (0 == $updatedFrom) {
            if (false == $validate) {
                throw new WrongObjectException(
                    'Объем указанных ТС в рейсе не соответствует габаритам заявленным при продаже рейса. ' .
                    'Указанный объем ' . $routeVolume .
                    ' Допустимый объем ' . $dimensionNameList .
                    ' Необходимо выбрать ТС в пределах допустимого объема.'
                );
            }
        }
    }

    /**
     * Метод поиска соотвествия двух габаритов, проверка на вхождение по объему всех ТС и пересчет, при необходимости
     *
     * @param Route $route
     * @param $dimension
     * @param $routeVolume
     * @param Dimension $mainDimension
     */
    private function checkCalculateDimension(Route $route, $dimension, $routeVolume, Dimension $mainDimension)
    {
        $validate = false;
        if ($dimension->getActive()) {
            $volumeFrom = $dimension->getDimension()->getVolumeFrom();
            $volumeUpTo = $dimension->getDimension()->getVolumeUpTo();
            if ($routeVolume >= $volumeFrom && ($routeVolume <= $volumeUpTo || 0 == $volumeUpTo)) {
                $calculateDimension = $this->entityManager->getRepository(DimensionCalculate::class)->findOneBy([
                    'mainDimension' => $mainDimension,
                    'optionalDimension' => $dimension->getDimension(),
                ]);

                $calculateDimensionPercentage = $dimension->getDimension()->getPercentage();
                if (null !== $calculateDimension) {
                    $calculateDimensionPercentage = $calculateDimension->getPercentage();
                }
                $sum = $route->getInitialSumm() / 100 * $calculateDimensionPercentage;

                //Округляем сотни
                $sum = round($sum / 1000, 1) * 1000;
                $route->setFreightSumm($sum);
                $validate = true;
            }
        }

        return $validate;
    }

    /**
     * Создание направлни погрузки.
     */
    protected function generateDirectionsOfLoading(RouteWay $routeWay)
    {
        if ($wayDirections = $routeWay->getRouteWayDirections()) {
            $wayPoints = $routeWay->getRouteWayPoints();
            /** @var Uuid[] $pointsArray */
            $pointsArray = [];
            foreach ($wayPoints as $wayPoint) {
                $pointsArray[$wayPoint->getRowNumber()] = $wayPoint->getDepartment()->getGuid();
            }
            ksort($pointsArray, SORT_NUMERIC);
            //TODO - посмотреть как это себя ведет. МБ нужно будет создавать новый массив с числовыми ключами.
            if (count($pointsArray) > 0) {
                $directionsOfLoading = [
                    'stringKey' => 'directionOfLoading',
                    'items' => [],
                ];
                for ($i = 1; $i < count($pointsArray); ++$i) {
                    for ($j = $i; $j < count($pointsArray); ++$j) {
                        $source = $pointsArray[$i];
                        $target = $pointsArray[$j + 1];
                        $cargos = [];
                        foreach ($wayDirections['items'] as $direction) {
                            if ($direction['sourceDepartmentPartGuid'] === $source->toString()
                                && $direction['targetDepartmentPartGuid'] === $target->toString()
                                && '6193DE5B-405A-426F-809A-4103F9E264DB' === $direction['KindOfTransportationTypeGuid']) {
                                $cargos[] = [
                                    'targetDepartmentCargoGuid' => $direction['targetDepartmentCargoGuid'],
                                    'volumeCargoPackaging' => '0',
                                ];
                            }
                        }
                        if (count($cargos)) {
                            $directionsOfLoading['items'][] = [
                                'sourceDepartmentPartGuid' => $source->toString(),
                                'targetDepartmentPartGuid' => $target->toString(),
                                'typeTransport' => 'АвтоПеревозка',
                                'volumeCargoPackaging' => '0',
                                'targetDepartmentsCargo' => [
                                    'stringKey' => 'targetDepartmentCargo',
                                    'items' => $cargos,
                                ],
                            ];
                        }
                    }
                }
            } else {
                throw new WrongObjectException('Не задан массив точек движения маршрута.');
            }
        } else {
            throw new WrongObjectException('Ошибка в направлениях маршрута рейсов.');
        }

        return $directionsOfLoading;
    }

    /**
     * Остановка организации в рейс.
     * @param Route $route
     * @param BaseRouteDto $routeDto
     */
    protected function setOrganization(Route $route, BaseRouteDto $routeDto)
    {
        if ($routeDto->getOrganization()) {
            /** @var Organization $organization */
            $organization = $this->entityManager->getRepository(Organization::class)
                ->find($routeDto->getOrganization()->getId());
            if ($organization) {
                $route->setOrganization($organization);
            } else {
                throw new WrongObjectException('Установлена неверная организация.');
            }
        }
    }
}
