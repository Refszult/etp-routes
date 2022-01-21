<?php

namespace App\Service\Route;

use App\Classes\Api\Pagination;
use App\Classes\RequestResponse\ServiceResponse;
use App\Dto\Route\ApiContractorRouteDto;
use App\Entity\Auction\AuctionRoute;
use App\Entity\Auction\AuctionRouteOptionalDimension;
use App\Entity\Route\Route;
use App\Entity\Tender\RouteTemplate;
use App\Entity\Tender\RouteTemplateOptionalDimension;
use App\Entity\Vehicle\DimensionCalculate;
use App\Exceptions\WrongObjectException;
use App\Service\Base\BaseDtoService;
use App\Service\Route\Actions\ActionsContractorApiRouteService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ContractorApiRouteService
 * Сервис CRUD Рейсов через DTO для подрядчика.
 */
class ContractorApiRouteService extends BaseDtoService
{
    private ActionsContractorApiRouteService $routeService;
    private RouteWarningService $routeWarningService;
    private RouteIBMMQService $routeIBMMQService;

    /**
     * {@inheritdoc}
     */
    public function onConstruct()
    {
        $this->routeService = $this->container->get(ActionsContractorApiRouteService::class);
        $this->routeWarningService = $this->container->get(RouteWarningService::class);
        $this->routeIBMMQService = $this->container->get(RouteIBMMQService::class);
    }

    /**
     * Получение списка рейсов для подрядчика.
     *
     * @throws \Exception
     */
    public function getContractorRouteList(
        int $contractorId,
        ParameterBag $params
    ): ServiceResponse {
        $this->denyAccessUnlessGranted('GET_CONTRACTOR_ROUTE_LIST', $contractorId);
        $queryBuilder = $this->entityManager
            ->getRepository(Route::class)
            ->findContractorsByParams($params, 0, $contractorId);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return $this->prepareResponse($this->response, $pagination);
    }

    /**
     * Обновление рейса подрядчика.
     *
     * @throws \Exception
     */
    public function updateContractorRoute(
        int $contractorId,
        int $routeId,
        ApiContractorRouteDto $routeDto
    ): ServiceResponse {
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->findOneBy(['id' => $routeId, 'contractor' => $contractorId, 'isCancel' => false]);
        $this->denyAccessUnlessGranted('ROUTE_CONTRACTOR_UPDATE', $route);
        if (!$route) {
            throw new NotFoundHttpException('Рейс не найден');
        }
        if (!$route->getIsDraft()) {
            throw new WrongObjectException(
                'Данный рейс не разрешено редактировать.'
            );
        }
        $this->response->addErrors($this->validator->validate($routeDto));
        if (!$this->response->hasErrors()) {
            $route = $this->routeService->updateRoute($route, $routeDto);
            $this->response->addErrors($this->validator->validate($route, null, ['ETP', 'Default']));
            if (!$this->response->hasErrors()) {
                $this->entityManager->flush();
                $this->routeIBMMQService->sendRouteToIBMMQ($route);
            }
        }

        return $this->prepareResponse($this->response, $route);

    }

    /**
     * Получение объекта рейса для подрядчика.
     */
    public function getContractorRoute(
        int $contractorId,
        int $routeId
    ): ServiceResponse {
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->findOneBy(['id' => $routeId, 'contractor' => $contractorId]);
        $this->denyAccessUnlessGranted('GET_CONTRACTOR_ROUTE', $route);
        if (!$route) {
            throw new NotFoundHttpException('Рейс не найден');
        }
        $this->routeWarningService->checkRouteWarnings($route);
        $route = $this->getDimensionCalculatePercentage($route);

        return $this->prepareResponse($this->response, $route);
    }

    /**
     * Добавление в объект с опциональными габаритами процента пересчета стоимости на основе основного габарита.
     *
     * @param Route $route
     * @return Route
     */
    public function getDimensionCalculatePercentage(Route $route)
    {
        if ($tender = $route->getTender()) {
            /** @var RouteTemplate $routeTemplate */
            foreach ($tender->getRouteTemplates() as $routeTemplate) {
                if ($routeTemplate->getOptionalDimensions()->count()) {
                    /** @var RouteTemplateOptionalDimension $optionalDimension */
                    foreach ($routeTemplate->getOptionalDimensions() as $optionalDimension) {
                        /** @var DimensionCalculate $dimensionCalculate */
                        $dimensionCalculate = $this->entityManager->getRepository(DimensionCalculate::class)->findOneBy([
                            'mainDimension' => $routeTemplate->getDimension(),
                            'optionalDimension' => $optionalDimension->getDimension()
                        ]);
                        if ($dimensionCalculate) {
                            $optionalDimension->getDimension()->setPercentage($dimensionCalculate->getPercentage());
                        }
                    }
                }
            }
        }
        if ($auction = $route->getAuction()) {
            /** @var AuctionRoute $auctionRoute */
            foreach ($auction->getAuctionRoutes() as $auctionRoute) {
                if ($auctionRoute->getOptionalDimensions()->count()) {
                    /** @var AuctionRouteOptionalDimension $optionalDimension */
                    foreach ($auctionRoute->getOptionalDimensions() as $optionalDimension) {
                        /** @var DimensionCalculate $dimensionCalculate */
                        $dimensionCalculate = $this->entityManager->getRepository(DimensionCalculate::class)->findOneBy([
                            'mainDimension' => $auctionRoute->getDimension(),
                            'optionalDimension' => $optionalDimension->getDimension()
                        ]);
                        if (null !== $dimensionCalculate) {
                            $optionalDimension->getDimension()->setPercentage($dimensionCalculate->getPercentage());
                        }
                    }
                }
            }
        }

        return $route;
    }
}
