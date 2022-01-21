<?php

namespace App\Service\Route\Actions;

use App\Classes\StaticStorage\UpdatedFrom;
use App\Dto\Route\MQRouteDto;
use App\Entity\Agreement\Organization;
use App\Entity\Container\Container;
use App\Entity\Contractor;
use App\Entity\Driver\ContractorDriver;
use App\Entity\Driver\Driver;
use App\Entity\Route\CargoPipeline;
use App\Entity\Route\CargoPipelineEvent;
use App\Entity\Route\CargoPipelinePlacesOfEvent;
use App\Entity\Route\Route;
use App\Entity\Route\RouteContainer;
use App\Entity\Route\RouteMovementPoint;
use App\Entity\Route\RouteWay;
use App\Entity\Route\Transportation;
use App\Entity\Route\TransportationType;
use App\Entity\Vehicle\ContractorVehicle;
use App\Entity\Vehicle\Vehicle;
use App\Exceptions\NotRelativeMQException;
use App\Exceptions\WrongObjectException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;

class ActionsMQRouteService extends ActionsRouteService
{
    public function createRoute(MQRouteDto $routeDto): ?Route
    {
        $route = new Route();
        $routeDto->createFieldSet($route);
        if (UpdatedFrom::UPDATED_FROM_IBMMQ !== $routeDto->getUpdatedFrom()) {
            throw new WrongObjectException('Невозможно вызвать метод без очереди.');
        }
        $this->setVehicleOrder($route, $routeDto);
        $this->setRouteCustomer($route);
        if (!$route->getOrderDate()) {
            $route->setOrderDate($route->getVehicleOrder()->getCreatedOn());
        }
        $this->setRouteWay($routeDto->getRouteWay(), $route);
        $wayPoints = $route->getRouteWay()->getRouteWayPoints();
        $this->setArriveTimeZone($route, $wayPoints);
        // Установка путевых точек из маршрута
        if (0 === $route->getMovementPoints()->count()) {
            $this->setParentMovementPoints($route, $wayPoints);
        }
        if ($routeDto->getMovementPoints()->count() > 0) {
            $this->setMovementPoints($route, $routeDto->getMovementPoints());
        }
        $this->setTransportationType($route, $routeDto->getTransportationType());
        $this->setTransportation($route, $routeDto->getTransportation());

        // Установка пользовательских полей
        $this->setContractor($route, $routeDto->getContractor());
        if ($route->getContractor()) {
            $this->setMQDriverOne($route, $routeDto->getDriverOne());
            if ($routeDto->getDriverTwo()) {
                $this->setMQDriverTwo($route, $routeDto->getDriverTwo());
            }
            $this->setMQCar($route, $routeDto->getTransport());
            if ($routeDto->getTrailer()) {
                $this->setMQTrailer($route, $routeDto->getTrailer());
            }
            $this->setMQRouteContainers($route, $routeDto->getRouteContainers());
        }
        $this->updateOrganization($route, $routeDto);
        $route->setIsCancel($routeDto->getDeleted());
        $route->setCreatedOn($routeDto->nowOrInit());
        $route->setUpdatedOn($routeDto->nowOrInit());
        $this->updateVehicleOrder($route);
        $route->setInitialSumm($routeDto->getFreightSumm());
        // Установка событий грузопровода
        $cargoPipelines = $routeDto->getCargoPipelines();
        if ($cargoPipelines && 0 != $cargoPipelines->count() && true == $route->getCargoFlow()) {
            $this->setCargoPipelines($route, $cargoPipelines);
        }

        return $route;
    }

    public function updateRoute(Route $route, MQRouteDto $routeDto)
    {
        if (UpdatedFrom::UPDATED_FROM_IBMMQ !== $routeDto->getUpdatedFrom()) {
            throw new WrongObjectException('Невозможно вызвать метод без очереди.');
        }
        $routeDto->updateFieldSet($route);
        $this->setRouteWay($routeDto->getRouteWay(), $route);
        $wayPoints = $route->getRouteWay()->getRouteWayPoints();
        $this->setArriveTimeZone($route, $wayPoints);
        // Установка путевых точек из маршрута
        if (0 === $route->getMovementPoints()->count()) {
            $this->setParentMovementPoints($route, $wayPoints);
        }
        if ($routeDto->getMovementPoints()->count() > 0) {
            $this->setMovementPoints($route, $routeDto->getMovementPoints());
        }
        $this->setTransportationType($route, $routeDto->getTransportationType());
        $this->setTransportation($route, $routeDto->getTransportation());

        // Установка пользовательских полей
        $this->setContractor($route, $routeDto->getContractor());
        if ($route->getContractor()) {
            $this->setMQDriverOne($route, $routeDto->getDriverOne());
            $this->setMQDriverTwo($route, $routeDto->getDriverTwo());
            $this->setMQCar($route, $routeDto->getTransport());
            $this->setMQTrailer($route, $routeDto->getTrailer());
            $this->setMQRouteContainers($route, $routeDto->getRouteContainers());
        }
        $this->updateOrganization($route, $routeDto);
        $route->setIsCancel($routeDto->getDeleted());
        $route->setUpdatedOn($routeDto->nowOrInit());
        $this->updateVehicleOrder($route);
        // Установка событий грузопровода
        $cargoPipelines = $routeDto->getCargoPipelines();
        if (0 != $cargoPipelines->count() && true == $route->getCargoFlow()/* && 0 == $route->getCargoPipelines()->count()*/) {
            $this->setCargoPipelines($route, $cargoPipelines);
        }
        if ($route->getAuction() || $route->getTender()) {
            $this->checkRouteVolume($route, $routeDto, $routeDto->getUpdatedFrom());
        }

        return $route;
    }

    protected function updateOrganization(Route $route, MQRouteDto $routeDto)
    {
        if ($routeDto->getOrganization()) {
            $organization = $this->entityManager->getRepository(Organization::class)
                ->findOneBy(['guid' => $routeDto->getOrganization()->getGuid()]);
        } else {
            /** @var Organization $organization */
            $organization = $this->entityManager->getRepository(Organization::class)
                ->find(Organization::DRIVER_DEFAULT_ORGANIZATION);
        }
        if ($organization) {
            $route->setOrganization($organization);
        }
    }

    /**
     * Устанавливает Заказ ТС для рейса.
     *
     * @throws Exception
     */
    protected function setVehicleOrder(Route $route, MQRouteDto $routeDto)
    {
        if ($routeDto->getVehicleOrder()) {
            if ($vehicleOrder = $this->finderHelper->findVehicleOrder($routeDto->getVehicleOrder())) {
                $route->setVehicleOrder($vehicleOrder);
            } else {
                if ($this->finderHelper->isNotNulGuid($routeDto->getVehicleOrder()->getGuid())) {
                    $this->createVehicleOrder($route, $routeDto->getVehicleOrder()->getGuid());
                } else {
                    throw new WrongObjectException('Неверные данные в GUID ЗТС.');
                }
            }
        } else {
            throw new WrongObjectException('Не передан GUID ЗТС.');
        }
    }

    /**
     * Установка маршрута рейса.
     *
     * @throws NotRelativeMQException
     */
    protected function setRouteWay(?RouteWay $routeWay, Route $route)
    {
        if ($routeWay = $this->finderHelper->findRouteWay($routeWay)) {
            $route->setRouteWay($routeWay);
        } else {
            throw new NotRelativeMQException('Не удалось добавить маршрут к рейсу. Маршрут не найден.');
        }
    }

    /**
     * Установка точек движения из переданных данных.
     *
     * @throws NotRelativeMQException
     */
    protected function setMovementPoints(Route $route, ?Collection $incomMovementPoints)
    {
        if ($incomMovementPoints) {
            // Обновление и удаление текущих точек.
            foreach ($route->getMovementPoints() as $oldMovementPoint) {
                $number = $oldMovementPoint->getRowNumber();
                /** @var ArrayCollection $movementPoints */
                $movementPoints = $incomMovementPoints->filter(
                    function ($entry) use ($number) {
                        /* @var RouteMovementPoint $entry */
                        return $entry->getRowNumber() === $number;
                    }
                );
                if (count($movementPoints) > 0) {
                    /** @var RouteMovementPoint $movementPoint */
                    $movementPoint = $movementPoints->first();
                    $this->updateMovementPoint($movementPoint, $oldMovementPoint);
                    $incomMovementPoints->removeElement($movementPoint);
                } else {
                    $this->entityManager->remove($oldMovementPoint);
                    $route->removeMovementPoint($oldMovementPoint);
                }
            }

            // Добавление новых точек
            if (count($incomMovementPoints) > 0) {
                foreach ($incomMovementPoints as $movementPoint) {
                    $this->createMovementPoint($movementPoint, $route);
                }
            }
        }
    }

    /**
     * Установка событий грузопровода.
     *
     * @throws NotRelativeMQException
     */
    protected function setCargoPipelines(Route $route, ?Collection $incomCargoPipelines)
    {
        if ($incomCargoPipelines) {
            $route->getCargoPipelines()->clear();
            /** @var CargoPipeline $cargoPipeline */
            $rowNumber = 1;
            foreach ($incomCargoPipelines as $cargoPipeline) {
                $cargoPipeline->setRowNumber($rowNumber);
                $place = $cargoPipeline->getCargoPipelinePlaceOfEvent();
                if ($place) {
                    if (!$place->getGuid()) {
                        throw new NotRelativeMQException('Не найдено место проведения действия.');
                    } else {
                        /** @var CargoPipelinePlacesOfEvent|null $existPlace */
                        $existPlace = $this->entityManager->getRepository(CargoPipelinePlacesOfEvent::class)
                            ->findOneBy(['guid' => $place->getGuid()]);
                        if (!$existPlace) {
                            throw new NotRelativeMQException('Не найдено место проведения действия.');
                        } else {
                            $cargoPipeline->setCargoPipelinePlaceOfEvent($existPlace);
                        }
                    }
                } else {
                    throw new WrongObjectException('Не передано место проведения действия.');
                }
                $event = $cargoPipeline->getCargoPipelineEvent();
                if ($event) {
                    if (!$event->getGuid()) {
                        throw new NotRelativeMQException('Не найдено событие для действия.');
                    } else {
                        /** @var CargoPipelineEvent|null $existEvent */
                        $existEvent = $this->entityManager->getRepository(CargoPipelineEvent::class)
                            ->findOneBy(['guid' => $event->getGuid()]);
                        if (!$existEvent) {
                            throw new NotRelativeMQException('Не найдено событие для действия.');
                        } else {
                            $cargoPipeline->setCargoPipelineEvent($existEvent);
                        }
                    }
                } else {
                    throw new WrongObjectException('Не передано событие для действия.');
                }
                $route->addCargoPipeline($cargoPipeline);
                ++$rowNumber;
//                $cargoPipeline->setRoute($route);
                if (!$cargoPipeline->getDriver()) {
                    throw new NotRelativeMQException('Попытка записи несуществующего водителя.');
                } else {
                    if (!$cargoPipeline->getDriver()->getGuid()) {
                        throw new NotRelativeMQException('Попытка записи несуществующего водителя.');
                    }
                }
                /** @var Driver $driver */
                $driver = $this->entityManager->getRepository(Driver::class)
                    ->findOneBy(['guid' => $cargoPipeline->getDriver()->getGuid()]);
                if (!$driver) {
                    throw new NotRelativeMQException('Попытка записи несуществующего водителя.');
                }
                $cargoPipeline->setDriver($driver);
                $vehicle = $this->findCargoPipelineVehicle($route, $cargoPipeline->getVehicle());
                if (!$vehicle) {
                    throw new NotRelativeMQException('Попытка записи не существуещего ТС.');
                }
                $cargoPipeline->setVehicle($vehicle);
//                if (!$cargoPipelineEvent = $this->finderHelper->findCargoPipelineEvent($cargoPipeline->getCargoPipelineEvent())) {
//                    throw new NotRelativeMQException('Попытка записи пустого события в грузопровод.');
//                }
//                $cargoPipeline->setCargoPipelineEvent($cargoPipelineEvent);
//                if (!$cargoPipelinePlaceOfEvent = $this->finderHelper->findCargoPipelinePlaceOfEvent($cargoPipeline->getCargoPipelinePlaceOfEvent())) {
//                    throw new NotRelativeMQException('Попытка записи пустого события в грузопровод.');
//                }
//                $cargoPipeline->setCargoPipelinePlaceOfEvent($cargoPipelinePlaceOfEvent);
//                $this->entityManager->persist($cargoPipeline);
            }
        }
    }

    /**
     * Установка первого водителя.
     *
     * @throws NotRelativeMQException
     */
    protected function setMQDriverOne(Route $route, ?Driver $driver)
    {
        if ($driver) {
            if ($driver = $this->findDriver($driver, $route)) {
                $route->setDriverOne($driver);
            } else {
                throw new NotRelativeMQException('Данного водителя не существует или не разрешено добавлять к рейсу.');
            }
        } else {
            $route->setDriverOne(null);
            $route->setDriverTwo(null);
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
            $contractorDriver = $this->entityManager->getRepository(ContractorDriver::class)
                ->findOneBy(['driver' => $outDriver->getId(), 'contractor' => $route->getContractor()->getId()]);
            if (!$contractorDriver) {
                $contractorDriver = new ContractorDriver();
                $contractorDriver->setDriver($outDriver);
                $contractorDriver->setContractor($route->getContractor());
                $contractorDriver->setCreatedOn(new \DateTime());
                $contractorDriver->setUpdatedOn(new \DateTime());
                $contractorDriver->setComment('Связка добавлена автоматически при импорте рейса из IBMMQ.');
                $this->entityManager->persist($contractorDriver);
            }
            // На данный момент проверка активной доверенности заблокирована при создании рейса из IBMMQ.
//            if (!$contractorDriver->getActiveAttorney()) {
//                throw new NotRelativeMQException('Данный водитель не имеет активной доверенности у подрядчика.');
//            }
        }

        return $outDriver;
    }

    /**
     * Установка второго водителя.
     *
     * @throws NotRelativeMQException
     * @throws Exception
     */
    protected function setMQDriverTwo(Route $route, ?Driver $driver)
    {
        $canAddDriver = false;
        if ($route->getBoostFlag()) {
            if ($driver) {
                if ($driver = $this->findDriver($driver, $route)) {
                    $canAddDriver = true;
                    $route->setDriverTwo($driver);
                } else {
                    throw new NotRelativeMQException('Данного водителя не существует или не разрешено добавлять к рейсу.');
                }
            }
        }

        if (!$canAddDriver) {
            $route->setDriverTwo(null);
        }
    }

    /**
     * Установка транспорта для рейса.
     *
     * @throws Exception
     */
    protected function setMQCar(Route $route, ?Vehicle $transport)
    {
        if ($transport) {
            if ($transport = $this->finderHelper->findTransport($transport)) {
                if (!$transport->getIsCancel()) {
                    if ($transport->getModel()->getType()->getIsTruck()) {
                        $this->clearTransport($route, $transport);
                        $route->setTransport($transport);
                        $this->createContractorVehicle($transport, $route);
                    } else {
                        throw new WrongObjectException('Переданное ТС не является автомобилем.');
                    }
                } else {
                    throw new WrongObjectException('Переданное ТС было отменено.');
                }
            } else {
                throw new WrongObjectException('Переданное ТС не найдено.');
            }
        } else {
            $this->clearTransport($route, null);
        }
    }

    /**
     * Поиск ТС для грузопровода.
     *
     * @throws Exception
     */
    protected function findCargoPipelineVehicle(Route $route, ?Vehicle $vehicle)
    {
        if ($vehicle) {
            if ($vehicle = $this->finderHelper->findTransport($vehicle)) {
                if (!$vehicle->getIsCancel()) {
                    if (!$vehicle->getModel()->getType()->getIsTruck()) {
//                        $this->createContractorVehicle($vehicle, $route);
//                    } else {
                        throw new WrongObjectException('Переданное ТС не является автомобилем.');
                    }
                } else {
                    throw new WrongObjectException('Переданное ТС было отменено.');
                }
            }
        }

        return $vehicle;
    }

    /**
     * @throws Exception
     */
    protected function createContractorVehicle(Vehicle $vehicle, Route $route)
    {
        $contractor = $route->getContractor();
        $existCV = $this->entityManager->getRepository(ContractorVehicle::class)
            ->findOneBy(['contractor' => $contractor, 'vehicle' => $vehicle]);
        if (!$existCV) {
            $contractorVehicle = new ContractorVehicle();
            if (null != $vehicle->getContractorOwner() && $vehicle->getContractorOwner()->getId() === $contractor->getId()) {
                $contractorVehicle->setIsOwn(true);
            } else {
                $contractorVehicle->setIsOwn(false);
            }
            $contractorVehicle->setContractor($route->getContractor());
            $contractorVehicle->setVehicle($vehicle);
            $contractorVehicle->setComment('Связка добавлена автоматически при импорте рейса из IBMMQ.');
            $contractorVehicle->setCreatedOn(new \DateTime());
            $contractorVehicle->setUpdatedOn(new \DateTime());
            $this->entityManager->persist($contractorVehicle);
        }
    }

    /**
     * Установка трейлера для рейса.
     *
     * @throws Exception
     */
    protected function setMQTrailer(Route $route, ?Vehicle $trailer)
    {
        if (!$route->getCargoFlow()) {
            if ($trailer) {
                if ($route->getTransport()) {
                    if ($trailer = $this->finderHelper->findTransport($trailer)) {
                        if (!$trailer->getModel()->getType()->getIsTruck()) {
                            $this->clearTrailer($route, $trailer);
                            $route->setTrailer($trailer);
                            $this->createContractorVehicle($trailer, $route);
                        } else {
                            throw new WrongObjectException('Выбранное ТС не является прицепом.');
                        }
                    } else {
                        throw new NotRelativeMQException('Не найден переданный прицеп');
                    }
                    if (!$trailer) {
                        if ('Тягач' === $route->getTransport()->getModel()->getType()->getName()) {
                            throw new WrongObjectException('Передан тягач. Необходимо передать прицеп для него.');
                        }
                    }
                } else {
                    throw new WrongObjectException('Чтобы добавить прицеп необходимо передать автомобиль.');
                }
            } else {
                $this->clearTrailer($route, $trailer);
            }
        } else {
            if ($trailer) {
                if ($trailer = $this->finderHelper->findTransport($trailer)) {
                    if (!$trailer->getModel()->getType()->getIsTruck()) {
                        $this->clearTrailer($route, $trailer);
                        $route->setTrailer($trailer);
                        $this->createContractorVehicle($trailer, $route);
                    } else {
                        throw new WrongObjectException('Выбранное ТС не является прицепом.');
                    }
                } else {
                    throw new NotRelativeMQException('Не найден переданный прицеп');
                }
            } else {
                throw new WrongObjectException('Невозможно организовать грузопровод без прицепа.');
            }
        }
    }

    /**
     * Установка контейнеров рейса.
     */
    protected function setMQRouteContainers(Route $route, ?Collection $routeContainersFromDTO)
    {
        if ($routeContainersFromDTO) {
            $incomRouteContainers = $routeContainersFromDTO;
            if (!$this->checkContainersData($incomRouteContainers)) {
                throw new WrongObjectException('Указаны неверные данные в списке контейнеров.');
            }

            // Обновление и удаление текущих связок контейнеров
            foreach ($route->getRouteContainers() as $existRouteContainer) {
                $vehicleGuid = $existRouteContainer->getVehicle()->getGuid()->toString();
                $containerGuid = $existRouteContainer->getContainer()->getGuid()->toString();
                $routeContainers = $incomRouteContainers->filter(
                    function ($entry) use ($vehicleGuid, $containerGuid) {
                        /** @var RouteContainer $entry */
                        if ($entry->getVehicle()->getGuid()->toString() === $vehicleGuid
                            && $entry->getContainer()->getGuid()->toString() === $containerGuid
                        ) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                );
                if ($routeContainers->count() > 0) {
                    $routeContainer = $routeContainers->first();
                    $incomRouteContainers->removeElement($routeContainer);
                } else {
                    $this->entityManager->remove($existRouteContainer);
                }
            }

            // Добавление новых связок контейнеров
            if (count($routeContainersFromDTO) > 0) {
                foreach ($routeContainersFromDTO as $routeContainer) {
                    if ($routeContainer->getVehicle() && $routeContainer->getContainer()) {
                        $vehicle = $this->finderHelper->findTransport($routeContainer->getVehicle());
                        $container = $this->finderHelper->findContainer($routeContainer->getContainer());
                        if ($vehicle && $container) {
                            $this->addRouteContainer($route, $vehicle, $container);
                            $container->addContractor($route->getContractor());
                            $this->entityManager->persist($container);
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
     * Проверка передаваемых данных по контейнерам рейса.
     *
     * @return bool
     */
    protected function checkContainersData(Collection $routeContainers)
    {
        $output = false;
        foreach ($routeContainers as $routeContainer) {
            if ($routeContainer->getVehicle() && $routeContainer->getContainer()) {
                if (($routeContainer->getVehicle()->getId() && $routeContainer->getContainer()->getId())
                    || ($routeContainer->getVehicle()->getGuid() && $routeContainer->getContainer()->getGuid())) {
                    $output = true;
                }
            }
        }

        return $output;
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
     * Обновление точки движения для рейса на основе данных переданной точки.
     *
     * @throws NotRelativeMQException
     */
    protected function updateMovementPoint(RouteMovementPoint $movementPoint, RouteMovementPoint $oldMovementPoint)
    {
        $oldMovementPoint->setRowNumber($movementPoint->getRowNumber());
        if (!$department = $this->finderHelper->findDepartment($movementPoint->getDepartment())) {
            throw new NotRelativeMQException('Попытка записи пустого подразделения в точки рейса.');
        }
        $oldMovementPoint->setDepartment($department);
        $oldMovementPoint->setActive($movementPoint->getActive());
        $oldMovementPoint->setPointAddress($movementPoint->getPointAddress());
    }

    /**
     * Создание точки движения для рейса на основе данных переданной точки.
     *
     * @throws NotRelativeMQException
     */
    protected function createMovementPoint(RouteMovementPoint $movementPoint, Route $route)
    {
        $newMovementPoint = new RouteMovementPoint();
        $newMovementPoint->setRoute($route);
        $newMovementPoint->setActive($movementPoint->getActive());
        if (!$department = $this->finderHelper->findDepartment($movementPoint->getDepartment())) {
            throw new NotRelativeMQException('Попытка записи пустого подразделения в точки рейса.');
        }
        $newMovementPoint->setDepartment($department);
        $newMovementPoint->setRowNumber($movementPoint->getRowNumber());
        $newMovementPoint->setPointAddress($movementPoint->getPointAddress());
        $route->getMovementPoints()->add($newMovementPoint);
    }

    /**
     * Установка параметра вида перевозки.
     *
     * @throws NotRelativeMQException
     */
    protected function setTransportationType(Route $route, ?TransportationType $transportationType)
    {
        $transportationType = $this->finderHelper->findTransportationType($transportationType);
        $route->setTransportationType($transportationType);
    }

    /**
     * Поиск типа перевозки.
     *
     * @throws NotRelativeMQException
     */
    protected function setTransportation(Route $route, ?Transportation $transportation)
    {
        if ($transportation) {
            $transportation = $this->finderHelper->findTransportation($transportation);
            $route->setTransportation($transportation);
        } else {
            $defaultTransportation = $this->getDefaultTransportation();
            $route->setTransportation($defaultTransportation);
        }
    }

    /**
     * Установка перевозчика.
     *
     * @throws NotRelativeMQException
     */
    protected function setContractor(Route $route, ?Contractor $contractor)
    {
        if ($contractor) {
            if ($contractor = $this->finderHelper->findContractor($contractor)) {
                $route->setContractor($contractor);
            } else {
                throw new NotRelativeMQException('Переданного перевозчика не существует.');
            }
        } else {
            $route->setContractor(null);
            //TODO возможно стоит дропнуть и все что связано с контрактором
        }
    }
}
