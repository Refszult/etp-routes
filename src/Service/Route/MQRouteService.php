<?php

namespace App\Service\Route;

use App\Classes\Api\RestResponse;
use App\Classes\RequestResponse\ServiceResponse;
use App\Classes\StaticStorage\UpdatedFrom;
use App\Dto\Route\MQRouteDto;
use App\Entity\Route\Department;
use App\Entity\Route\Route;
use App\Exceptions\WrongObjectException;
use App\Service\Base\BaseDtoService;
use App\Service\Route\Actions\ActionsMQRouteService;

/**
 * Class MQRouteService
 * Действия по отправке/принятию рейсов через IBMMQ.
 */
class MQRouteService extends BaseDtoService
{
    private RouteStateService $routeStateService;
    private ActionsMQRouteService $routeService;
    private RouteIBMMQService $routeIBMMQService;

    /**
     * {@inheritdoc}
     */
    public function onConstruct()
    {
        $this->routeStateService = $this->container->get(RouteStateService::class);
        $this->routeService = $this->container->get(ActionsMQRouteService::class);
        $this->routeIBMMQService = $this->container->get(RouteIBMMQService::class);
    }

    /**
     * Создание нового рейса.
     *
     * @param MQRouteDto $routeDto
     *
     * @return ServiceResponse
     *
     * @throws \Exception
     */
    public function createRoute(MQRouteDto $routeDto): ServiceResponse
    {
        $route = null;
        $this->response->addErrors($this->validator->validate($routeDto));
        if (!$this->response->hasErrors()) {
            $route = $this->routeService->createRoute($routeDto);
            $this->response->addErrors($this->validator->validate($route));
            if (!$this->response->hasErrors()) {
                $this->entityManager->persist($route);
                $this->routeStateService->createUpdateRouteStateFromCUR($route);
                $this->entityManager->flush();
            }
        }

        return $this->prepareResponse($this->response, $route);
    }

    /**
     * Обновление рейса.
     *
     * @param Route $route
     * @param MQRouteDto $routeDto
     * @return ServiceResponse
     *
     */
    public function updateRoute(Route $route, MQRouteDto $routeDto)
    {
        $this->response->addErrors($this->validator->validate($routeDto));
        if (!$this->response->hasErrors()) {
            $route = $this->routeService->updateRoute($route, $routeDto);
            $this->response->addErrors($this->validator->validate($route));
            if (!$this->response->hasErrors()) {
                $this->entityManager->persist($route);
                $this->entityManager->flush();
                if ($route->getInitialSumm() != $route->getFreightSumm() &&
                    $route->getFreightSumm() != $routeDto->getFreightSumm()
                ) {
                    $this->routeIBMMQService->sendRouteToIBMMQ($route);
                }
            }
        }

        return $this->prepareResponse($this->response, $route);
    }

    /**
     * Удаление Рейса.
     *
     * @param Route $route
     * @param bool  $ignoreGrants
     * @param int   $updatedFrom
     *
     * @return RestResponse
     */
    public function deleteRoute(
        Route $route,
        bool $ignoreGrants = false,
        $updatedFrom = UpdatedFrom::UPDATED_FROM_IBMMQ
    ) {
        $this->setGrantsCheck($ignoreGrants);
        $this->denyAccessUnlessGranted('DELETE_ROUTE', $route);
        try {
            $route->setUpdatedFrom($updatedFrom);
            $this->entityManager->persist($route);
            $this->entityManager->flush();
            $this->entityManager->remove($route);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            throw new WrongObjectException('Не удалось удалить рейс', $exception);
        }

        return new RestResponse('Рейс успешно удален.', 200);
    }

    /**
     * Завершение рейса.
     *
     * @param Route $route
     * @param array $data
     *
     * @return Route
     *
     * @throws \Exception
     */
    public function checkToClose(Route $route, array $data)
    {
        $closed = false;
        if (array_key_exists('typeWarehouseOperationRoute', $data)) {
            if ('Завершение рейса' === $data['typeWarehouseOperationRoute']) {
                $route->setClosed(true);
                $closed = true;
            } elseif ('Окончание разгрузки' === $data['typeWarehouseOperationRoute']) {
                if (array_key_exists('departmentGuid', $data)) {
                    if ($this->checkDepartmentByGuid($route, $data['departmentGuid'])) {
                        $route->setClosed(true);
                        $closed = true;
                    }
                }
            }
        }
        if ($closed) {
            $route->setUpdatedOn(new \DateTime());
            $this->entityManager->persist($route);
            $this->entityManager->flush();
        }

        return $route;
    }

    /**
     * Проверка отделение на существование и принадлежность к рейсу.
     *
     * @param Route  $route
     * @param string $guid
     *
     * @return bool
     */
    protected function checkDepartmentByGuid(Route $route, string $guid)
    {
        $output = false;
        $department = $this->entityManager->getRepository(Department::class)
            ->findOneBy(['guid' => $guid]);
        if ($department) {
            if ($route->isLastActiveMovementPoint($department)) {
                $output = true;
            }
        }

        return $output;
    }
}
