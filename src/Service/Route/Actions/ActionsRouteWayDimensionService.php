<?php

namespace App\Service\Route\Actions;

use App\Dto\Dimension\ApiDimensionCalculateDto;
use App\Dto\Route\ApiRouteWayDimensionDto;
use App\Entity\Route\RouteWayDimension;
use App\Entity\Vehicle\Dimension;
use App\Entity\Vehicle\DimensionCalculate;
use App\Exceptions\WrongObjectException;
use App\Service\Base\BaseActionDtoService;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ActionsRouteWayDimensionService extends BaseActionDtoService
{
    public function createRouteWayDimension(ApiRouteWayDimensionDto $routeWayDimensionDto): ?RouteWayDimension
    {
        $routeWayDimension = new RouteWayDimension();
        $routeWayDimensionDto->createFieldSet($routeWayDimension);
        $this->setDimension($routeWayDimension, $routeWayDimensionDto);
        $this->setFirstPoint($routeWayDimension, $routeWayDimensionDto);
        $this->setLastPoint($routeWayDimension, $routeWayDimensionDto);
        $this->setOptionalDimensions($routeWayDimension, $routeWayDimensionDto);
        $routeWayDimension->setCreatedOn($routeWayDimensionDto->nowOrInit());
        $routeWayDimension->setUpdatedOn($routeWayDimensionDto->nowOrInit());

        return $routeWayDimension;
    }

    public function updateRouteWayDimension(RouteWayDimension $routeWayDimension, ApiRouteWayDimensionDto $routeWayDimensionDto): ?RouteWayDimension
    {
        $routeWayDimensionDto->updateFileSet($routeWayDimension);
        $this->setDimension($routeWayDimension, $routeWayDimensionDto);
        $this->setOptionalDimensions($routeWayDimension, $routeWayDimensionDto);
        $routeWayDimension->setUpdatedOn($routeWayDimensionDto->nowOrInit());

        return $routeWayDimension;
    }

    /**
     * Установка габарита для маршрута.
     */
    private function setDimension(RouteWayDimension $routeWayDimension, ApiRouteWayDimensionDto $routeWayDimensionDto)
    {
        $dimension = null;
        if ($routeWayDimensionDto->getDimension()) {
            if ($dimension = $this->finderHelper->findDimension($routeWayDimensionDto->getDimension())) {
                $routeWayDimension->setDimension($dimension);
            } else {
                throw new WrongObjectException('Не найден переданный габарит ТС.');
            }
        }
    }

    /**
     * Установка первой точки возможного маршрута.
     */
    private function setFirstPoint(RouteWayDimension $routeWayDimension, ApiRouteWayDimensionDto $routeWayDimensionDto)
    {
        $firstPoint = null;
        if ($routeWayDimensionDto->getFirstPoint()) {
            if ($firstPoint = $this->finderHelper->findDepartment($routeWayDimensionDto->getFirstPoint())) {
                $routeWayDimension->setFirstPoint($firstPoint);
            } else {
                throw new WrongObjectException('Не найдена первая точка.');
            }
        }
    }

    /**
     * Установка последней точки возможного маршрута.
     */
    private function setLastPoint(RouteWayDimension $routeWayDimension, ApiRouteWayDimensionDto $routeWayDimensionDto)
    {
        $lastPoint = null;
        if ($routeWayDimensionDto->getLastPoint()) {
            if ($lastPoint = $this->finderHelper->findDepartment($routeWayDimensionDto->getLastPoint())) {
                $routeWayDimension->setLastPoint($lastPoint);
            } else {
                throw new WrongObjectException('Не найдена последняя точка.');
            }
        }
    }

    /**
     * Установка опциональных габаритов.
     */
    protected function setOptionalDimensions(RouteWayDimension $routeWayDimension, ApiRouteWayDimensionDto $routeWayDimensionDto)
    {
        if ($routeWayDimensionDto->getOptionalDimensions()) {
            $incomOptionalDimensions = $routeWayDimensionDto->getOptionalDimensions();
            // Обновление и удаление текущих связок опциональных габаритов
            /** @var Dimension $existOptionalDimensions */
            foreach ($routeWayDimension->getOptionalDimensions() as $existOptionalDimensions) {
                $dimensionId = $existOptionalDimensions->getId();
                $routeWayOptionalDimensions = $incomOptionalDimensions->filter(
                    function ($entry) use ($dimensionId) {
                        /** @var Dimension $entry */
                        if ($entry->getId() === $dimensionId) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                );
                if (count($routeWayOptionalDimensions) > 0) {
                    $routeWayOptionalDimension = $routeWayOptionalDimensions->first();
                    $routeWayOptionalDimensions->removeElement($routeWayOptionalDimension);
                }
            }
            /** @var Dimension $existOptionalDimensions */
            foreach ($routeWayDimension->getOptionalDimensions() as $existOptionalDimensions) {
                $routeWayDimension->removeOptionalDimension($existOptionalDimensions);
            }
            /** @var Dimension $newOptionalDimensions */
            foreach ($routeWayDimensionDto->getOptionalDimensions() as $newOptionalDimensions) {
                $routeWayDimension->addOptionalDimension($newOptionalDimensions);
                $this->entityManager->persist($newOptionalDimensions);
            }
        }
    }
}
