<?php

namespace App\Service\Route\Actions;

use App\Dto\Route\ApiContractorRouteDto;
use App\Entity\Route\Route;

class ActionsContractorApiRouteService extends ActionsRouteService
{
    /**
     * Обновление рейса со стороны подрядчика.
     *
     * @param Route $route
     * @param ApiContractorRouteDto $routeDto
     * @return Route|null
     *
     */
    public function updateRoute(Route $route, ApiContractorRouteDto $routeDto): ?Route
    {
        $routeDto->updateFieldSet($route);
        $this->setDriverOne($route, $routeDto);
        $this->setDriverTwo($route, $routeDto);
        $this->setCar($route, $routeDto);
        $this->setTrailer($route, $routeDto);
        $this->setRouteContainers($route, $routeDto);
        $route->setUpdatedOn($routeDto->nowOrInit());
        $this->updateVehicleOrder($route);
        $this->setCargoPipeline($route, $routeDto);
        if ($route->getAuction() || $route->getTender()) {
            $this->checkRouteVolume($route, $routeDto);
        }

        return $route;
    }
}
