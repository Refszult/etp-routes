<?php

namespace App\Service\Route;

use App\Classes\RequestResponse\ServiceResponse;
use App\Dto\Route\CommandRouteDto;
use App\Entity\Tender\RouteTemplate;
use App\Service\Base\BaseDtoService;
use App\Service\Route\Actions\ActionsCommandRouteService;

/**
 * Class CommandRouteService
 * Сервис CRUD Рейсов через DTO для работы консольных команд.
 */
class CommandRouteService extends BaseDtoService
{
    private ActionsCommandRouteService $routeService;
    private RouteStateService $routeStateService;
    private RouteIBMMQService $routeIBMMQService;

    /**
     * {@inheritdoc}
     */
    public function onConstruct()
    {
        $this->routeService = $this->container->get(ActionsCommandRouteService::class);
        $this->routeStateService = $this->container->get(RouteStateService::class);
        $this->routeIBMMQService = $this->container->get(RouteIBMMQService::class);
    }

    /**
     * Создание нового рейса.
     *
     * @param CommandRouteDto $routeDto
     * @param RouteTemplate $routeTemplate
     * @return ServiceResponse
     *
     */
    public function createRoute(CommandRouteDto $routeDto, RouteTemplate $routeTemplate): ServiceResponse
    {
        $route = null;
        $this->response->addErrors($this->validator->validate($routeDto));
        if (!$this->response->hasErrors()) {
            $route = $this->routeService->createRoute($routeDto, $routeTemplate);
            $this->response->addErrors($this->validator->validate($route));
            if (!$this->response->hasErrors()) {
                $this->entityManager->persist($route);
                $this->routeStateService->createUpdateRouteStateFromCUR($route);
                $this->entityManager->flush();
                $this->routeIBMMQService->sendRouteToIBMMQ($route);
            }
        }

        return $this->prepareResponse($this->response, $route);
    }
}
