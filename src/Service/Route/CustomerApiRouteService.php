<?php

namespace App\Service\Route;

use App\Classes\Api\Pagination;
use App\Classes\RequestResponse\ServiceResponse;
use App\Dto\Route\ApiCustomerRouteDto;
use App\Entity\Auction\Auction;
use App\Entity\Auction\AuctionRoute;
use App\Entity\Auction\AuctionRouteOptionalDimension;
use App\Entity\Customer;
use App\Entity\Route\Route;
use App\Entity\Tender\RouteTemplate;
use App\Entity\Tender\RouteTemplateOptionalDimension;
use App\Entity\Tender\Tender;
use App\Entity\Vehicle\DimensionCalculate;
use App\Exceptions\WrongObjectException;
use App\Service\Base\BaseDtoService;
use App\Service\Route\Actions\ActionsCustomerApiRouteService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CustomerApiRouteService
 * Сервис CRUD Рейсов через DTO для заказчика.
 */
class CustomerApiRouteService extends BaseDtoService
{
    private ActionsCustomerApiRouteService $routeService;
    private RouteWarningService $routeWarningService;
    private RouteIBMMQService $routeIBMMQService;

    /**
     * {@inheritdoc}
     */
    public function onConstruct()
    {
        $this->routeService = $this->container->get(ActionsCustomerApiRouteService::class);
        $this->routeWarningService = $this->container->get(RouteWarningService::class);
        $this->routeIBMMQService = $this->container->get(RouteIBMMQService::class);
    }

    /**
     * Получение списка рейсов для аукциона.
     *
     * @return ServiceResponse
     */
    public function getRoutesForAuctionList(int $customerId, ParameterBag $params): ServiceResponse
    {
        $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_LIST', $customerId);
        $queryBuilder = $this->entityManager
            ->getRepository(Route::class)
            ->findForAuction($params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return $this->prepareResponse($this->response, $pagination);
    }

    /**
     * Получение объекта рейса для заказчика.
     */
    public function getCustomerRoute(int $customerId, int $routeId): ServiceResponse
    {
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->findOneBy(['id' => $routeId, 'customer' => $customerId]);
        if (!$route) {
            throw new NotFoundHttpException('Рейс не найден');
        } else {
            $this->routeWarningService->checkRouteWarnings($route);
            $this->denyAccessUnlessGranted('GET_CUSTOMER_ROUTE', $route);
            $route = $this->getDimensionCalculatePercentage($route);

            return $this->prepareResponse($this->response, $route);
        }
    }

    /**
     * Получение списка рейсов для заказчика.
     *
     * @throws \Exception
     */
    public function getCustomerRouteList(
        int $customerId,
        ParameterBag $params
    ): ServiceResponse
    {
        $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_LIST', $customerId);
        $queryBuilder = $this->entityManager
            ->getRepository(Route::class)
            ->findByParams($params, $customerId);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return $this->prepareResponse($this->response, $pagination);
    }

    /**
     * Создание рейса заказчиком.
     *
     * @throws \Exception
     */
    public function createCustomerRoute(
        int $customerId,
        ApiCustomerRouteDto $routeDto
    ): ServiceResponse
    {
        $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_CREATE');
        $this->response->addErrors($this->validator->validate($routeDto, null, ['Default', 'Create']));
        $route = null;
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            throw new NotFoundHttpException('Заказчик не найден');
        }
        if (!$this->response->hasErrors()) {
            $route = $this->routeService->createRoute($routeDto, $customer);
            $this->checkRouteWays($route);
            $this->response->addErrors($this->validator->validate($route, null, ['ETP', 'Default']));
            if (!$this->response->hasErrors()) {
                $this->entityManager->persist($route);
                $this->entityManager->flush();
                $this->routeIBMMQService->sendRouteToIBMMQ($route);
            }
        }

        return $this->prepareResponse($this->response, $route);
    }

    /**
     * Создание рейса заказчиком и перевод его на аукцион.
     *
     * @throws \Exception
     */
    public function createCustomerRouteAuction(
        int $customerId,
        ApiCustomerRouteDto $routeDto
    ): string
    {
        $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_CREATE');
        $this->denyAccessUnlessGranted('AUCTION_CREATE', $customerId);
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository(Customer::class)->find($customerId);
        if (!$customer) {
            throw new NotFoundHttpException('Заказчик не найден');
        }
        $this->response->addErrors($this->validator->validate($routeDto));
        $route = null;
        $auctionRoutesJson = null;
        if (!$this->response->hasErrors()) {
            if ($routeDto->getContractor()) {
                throw new WrongObjectException('Невозможно создать рейс и перевести его на аукцион т.к. заданы пользовательские поля.');
            }
            $route = $this->routeService->createRoute($routeDto, $customer);
            $this->checkRouteWays($route);
            $this->response->addErrors($this->validator->validate($route, null, ['ETP', 'Default']));
            if (!$this->response->hasErrors()) {
                $this->entityManager->persist($route);
                $this->entityManager->flush();
                $auctionRoutesJson = $this->routeService->generateAuctionRoutesJson($route);
            }
        }

        return $auctionRoutesJson;
    }

    /**
     * Обновление рейса заказчиком.
     *
     * @throws \Exception
     */
    public function updateCustomerRoute(
        int $customerId,
        int $routeId,
        ApiCustomerRouteDto $routeDto
    ): ServiceResponse
    {
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->findOneBy([
                'id' => $routeId,
                'customer' => $customerId,
                'isCancel' => false
            ]);
        if (!$route) {
            throw new NotFoundHttpException('Рейс не найден');
        }
        $this->denyAccessUnlessGranted('ROUTE_CUSTOMER_UPDATE', $route);
        if (!$route->getIsDraft()) {
            throw new WrongObjectException(
                'Данный рейс не разрешено редактировать.'
            );
        }
        if (!$this->canEditExtend($route)) {
            throw new WrongObjectException(
                'Данный рейс не разрешено редактировать, так как он принадлежит аукциону или тендеру'
            );
        }
        $this->response->addErrors($this->validator->validate($routeDto));
        if (!$this->response->hasErrors()) {
            $route = $this->routeService->updateRoute($route, $routeDto);
            $this->checkRouteWays($route);
            $this->response->addErrors($this->validator->validate($route, null, ['ETP', 'Default']));
            if (!$this->response->hasErrors()) {
                $this->entityManager->persist($route);
                $this->entityManager->flush();
                $this->routeIBMMQService->sendRouteToIBMMQ($route);
            }
        }

        return $this->prepareResponse($this->response, $route);
    }

    /**
     * Проверяет возможность редактирования рейса, зависящего от внешних объектов.
     * @param Route $route
     * @return bool
     */
    protected function canEditExtend(Route $route): bool
    {
        $output = true;
        if ($auction = $route->getAuction()) {
            if (Auction::STATUS_AWAIT === $auction->getStatus()
                || Auction::STATUS_ACTIVE === $auction->getStatus()
                || Auction::STATUS_NO_WINNER === $auction->getStatus()
                || Auction::STATUS_HAS_WINNER === $auction->getStatus()) {
                return $output = false;
            }
        }

        return $output;
    }

    /**
     * Проверка маршрута рейса на удаление.
     *
     * @param Route $route
     */
    protected function checkRouteWays(Route $route)
    {
        if ($routeWay = $route->getRouteWay()) {
            if (true === $routeWay->getIsCancel()) {
                throw new WrongObjectException('В используемом рейсе указан маршрут, помеченный на удаление.');
            }
        }
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
                        if ($dimensionCalculate) {
                            $optionalDimension->getDimension()->setPercentage($dimensionCalculate->getPercentage());
                        }
                    }
                }
            }
        }

        return $route;
    }
}
