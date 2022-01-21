<?php

namespace App\Controller\Api\Route;

use App\Controller\Api\BaseApiController;
use App\Dto\Route\ApiCustomerRouteDto;
use App\Service\Auction\AuctionDeserializer;
use App\Service\Auction\AuctionService;
use App\Service\RequestResponse\RequestService;
use App\Service\Route\CustomerApiRouteService;
use App\Service\Route\RouteDeserializer;
use App\Entity\Route\Route as RouteEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use \Symfony\Component\HttpFoundation\JsonResponse;
use Swagger\Annotations as SWG;
use FOS\RestBundle\Controller\Annotations\View as ViewAnnotation;
use App\Annotations\SerializerGroups;

class CustomerRouteController extends BaseApiController
{
    /**
     * Создание рейса заказчиком.
     *
     * @Route(
     *     "/customers/{customer_id}/routes",
     *     name="customer_route_create",
     *     methods={"POST"},
     *     requirements={"customer_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="customer_id",
     *     in="path",
     *     type="integer",
     *     description="ID заказчика, от лица которого создается рейс",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Объект рейса",
     *     required=true,
     *     format="application/json",
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=ApiCustomerRouteDto::class, groups={"Default", "Route_info", "Doc", "RouteWay_base"})
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Создает новый рейс от лица заказчика.",
     *     @Model(type=RouteEntity::class, groups={"Default", "Route_info", "Route_customer", "RouteWay_base"})
     * )
     * @SWG\Tag(name="Рейсы заказчика")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "Route_customer", "RouteWay_base"})
     * @SerializerGroups({"Default", "Route_info", "Route_customer", "RouteWay_base"})
     *
     * @param int                          $customer_id
     * @param Request                      $request
     * @param RouteDeserializer            $deserializer
     * @param CustomerApiRouteService      $routeService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function createRoute(
        int $customer_id,
        Request $request,
        RouteDeserializer $deserializer,
        CustomerApiRouteService $routeService
    ) {
        $params = $request->getContent();
        $routeDto = $deserializer->deserializeToCustomerDto($params);
        $response = $this->prepareResponse(
            $routeService->createCustomerRoute($customer_id, $routeDto),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Создание рейса заказчиком и перевод его на аукцион.
     *
     * @Route(
     *     "/customers/{customer_id}/routes-auction/",
     *     name="customer_route_auction_create",
     *     methods={"POST"},
     *     requirements={"customer_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="customer_id",
     *     in="path",
     *     type="integer",
     *     description="ID заказчика, от лица которого создается рейс",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Объект рейса",
     *     required=true,
     *     format="application/json",
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=ApiCustomerRouteDto::class, groups={"Default", "Route_info", "Doc", "RouteWay_base"})
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Создает новый рейс от лица заказчика.",
     *     @Model(type=RouteEntity::class, groups={"Default", "Route_info", "Route_customer", "RouteWay_base"})
     * )
     * @SWG\Tag(name="Рейсы заказчика")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "Route_customer", "RouteWay_base", "Auction_info", "Customer", "Customer_details"})
     * @SerializerGroups({"Default", "Route_info", "Route_customer", "RouteWay_base", "Auction_info", "Customer", "Customer_details"})
     *
     * @param int $customer_id
     * @param Request $request
     * @param RouteDeserializer $deserializer
     * @param AuctionDeserializer $auctionDeserializer
     * @param CustomerApiRouteService $routeService
     * @param AuctionService $auctionService
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function createRouteAuction(
        int $customer_id,
        Request $request,
        RouteDeserializer $deserializer,
        AuctionDeserializer $auctionDeserializer,
        CustomerApiRouteService $routeService,
        AuctionService $auctionService
    ) {
        $params = $request->getContent();
        $routeDto = $deserializer->deserializeToCustomerDto($params);
        $auctionRoutesJson = $routeService->createCustomerRouteAuction($customer_id, $routeDto);
        $auctionModel = $auctionDeserializer->deserializeToModel($auctionRoutesJson);
        $response = $auctionService->createAuction($customer_id, $auctionModel);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Обновление рейса заказчика.
     *
     * @Route(
     *     "/customers/{customer_id}/routes/{route_id}",
     *     name="customer_route_update",
     *     methods={"PUT"},
     *     requirements={"customer_id": "\d+", "route_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="customer_id",
     *     in="path",
     *     type="integer",
     *     description="ID заказчика, от лица которого создается рейс",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="route_id",
     *     in="path",
     *     type="integer",
     *     description="ID рейса",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Объект рейса",
     *     required=true,
     *     format="application/json",
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=ApiCustomerRouteDto::class, groups={"Default", "Route_info", "Doc", "RouteWay_base"})
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Обновляет новый рейс от лица заказчика.",
     *     @Model(type=RouteEntity::class, groups={"Default", "Route_info", "Route_customer", "RouteWay_base"})
     * )
     * @SWG\Tag(name="Рейсы заказчика")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "Route_customer", "RouteWay_base"})
     * @SerializerGroups({"Default", "Route_info", "Route_customer", "RouteWay_base"})
     *
     * @param int                          $customer_id
     * @param int                          $route_id
     * @param Request                      $request
     * @param RouteDeserializer            $deserializer
     * @param CustomerApiRouteService      $routeService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function updateRoute(
        int $customer_id,
        int $route_id,
        Request $request,
        RouteDeserializer $deserializer,
        CustomerApiRouteService $routeService
    ) {
        $params = $request->getContent();
        $routeDto = $deserializer->deserializeToCustomerDto($params);
        $response = $this->prepareResponse(
            $routeService->updateCustomerRoute($customer_id, $route_id, $routeDto),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка рейсов заказчика c пагинацией.
     *
     * @Route(
     *     "customers/{customer_id}/routes",
     *     name="get_customer_routes",
     *     methods={"GET"},
     *     requirements={"customer_id":"\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Номер страницы с результатами",
     *     default=1
     * )
     *
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Число записей на странице",
     *     default=10
     * )
     *
     * @SWG\Parameter(
     *     name="customer_id",
     *     in="path",
     *     type="integer",
     *     description="ID заказчика",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="name",
     *     in="query",
     *     type="string",
     *     description="Строка поиска по коду рейса",
     * )
     *
     * @SWG\Parameter(
     *     name="transportation",
     *     in="query",
     *     type="integer",
     *     description="Тип рейса"
     * )
     *
     * @SWG\Parameter(
     *     name="route_way",
     *     in="query",
     *     type="integer",
     *     description="ID маршрута",
     * )
     *
     * @SWG\Parameter(
     *     name="first_point",
     *     in="query",
     *     type="integer",
     *     description="Начальная точка маршрута",
     * )
     *
     * @SWG\Parameter(
     *     name="last_point",
     *     in="query",
     *     type="integer",
     *     description="Конечная точка маршрута",
     * )
     *
     * @SWG\Parameter(
     *     name="date_first_loading",
     *     in="query",
     *     type="string",
     *     description="Дата первой погрзуки рейса (YYYY-MM-DD)",
     * )
     *
     * @SWG\Parameter(
     *     name="date_from",
     *     in="query",
     *     type="string",
     *     description="Минимальная дата рейса (YYYY-MM-DD)",
     * )
     *
     * @SWG\Parameter(
     *     name="date_from",
     *     in="query",
     *     type="string",
     *     description="Максимальная дата рейса (YYYY-MM-DD)",
     * )
     *
     * @SWG\Parameter(
     *     name="contractor",
     *     in="query",
     *     type="integer",
     *     description="ID подрядчика",
     * )
     *
     * @SWG\Parameter(
     *     name="driver",
     *     in="query",
     *     type="integer",
     *     description="ID водителя",
     * )
     *
     * @SWG\Parameter(
     *     name="vehicle",
     *     in="query",
     *     type="integer",
     *     description="ID транспортного средства",
     * )
     *
     * @SWG\Parameter(
     *     name="manager",
     *     in="query",
     *     type="string",
     *     description="Username ответственного за рейс пользователя",
     * )
     *
     * @SWG\Parameter(
     *     name="is_draft",
     *     in="query",
     *     type="boolean",
     *     description="Статус формирования рейса",
     * )
     *
     * @SWG\Parameter(
     *     name="is_cancel",
     *     in="query",
     *     type="boolean",
     *     description="Статус пометки на удаление",
     * )
     *
     * @SWG\Parameter(
     *     name="is_dirty",
     *     in="query",
     *     type="boolean",
     *     description="Статус рейса с ошибками",
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список рейсов для подрядчика.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=RouteEntity::class, groups={"Default", "Doc", "Route_info", "Route_customer"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Рейсы заказчика")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "Route_customer"})
     * @SerializerGroups({"Default", "Route_info", "Route_customer"})
     *
     * @param int                       $customer_id
     * @param Request                   $request
     * @param CustomerApiRouteService   $routeService
     * @param RequestService            $requestService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getRouteWaysList(
        int $customer_id,
        Request $request,
        CustomerApiRouteService $routeService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse(
            $routeService->getCustomerRouteList($customer_id, $params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение деталей рейса для заказчика.
     *
     * @Route(
     *     "/customers/{customer_id}/routes/{route_id}",
     *     name="get_customers_route_details",
     *     methods={"GET"},
     *     requirements={"customer_id": "\d+", "route_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="customer_id",
     *     in="path",
     *     type="integer",
     *     description="ID заказчика, чей рейс выводится",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="route_id",
     *     in="path",
     *     type="integer",
     *     description="ID рейса",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Возвращает данные рейса.",
     *     @Model(type=RouteEntity::class, groups={"Default", "Route_info", "Route_customer", "Doc", "RouteWay_base", "VehicleModel_info", "RouteAuction_info"})
     * )
     * @SWG\Tag(name="Рейсы заказчика")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "Route_customer", "RouteWay_base", "VehicleModel_info", "RouteAuction_info"})
     * @SerializerGroups({"Default", "Route_info", "Route_customer", "RouteWay_base", "VehicleModel_info", "RouteAuction_info"})
     *
     * @param int                     $customer_id
     * @param int                     $route_id
     * @param CustomerApiRouteService $routeService
     * @param Request                 $request
     *
     * @return JsonResponse
     */
    public function getRouteInfo(
        int $customer_id,
        int $route_id,
        CustomerApiRouteService $routeService,
        Request $request
    ) {
        $response = $this->prepareResponse(
            $routeService->getCustomerRoute($customer_id, $route_id),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка рейсов для аукционов.
     *
     * @Route(
     *     "customers/{customer_id}/routes_for_auctions",
     *     name="get_routes_list_for_auction",
     *     methods={"GET"},
     *     requirements={"customer_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="customer_id",
     *     in="path",
     *     type="integer",
     *     description="ID заказчика",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="auction_id",
     *     in="path",
     *     type="integer",
     *     description="ID аукциона, для которого факт привязки игнорируется",
     *     required=false
     * )
     *
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Номер страницы с результатами",
     *     default=1
     * )
     *
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Число записей на странице",
     *     default=10
     * )
     *
     * @SWG\Parameter(
     *     name="name",
     *     in="query",
     *     type="string",
     *     description="Название рейса"
     * )
     *
     * @SWG\Parameter(
     *     name="ids",
     *     items=@SWG\Schema(
     *         type="integer"
     *     ),
     *     in="query",
     *     type="array",
     *     description="Массив id рейсов"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список рейсов для подрядчика.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=RouteEntity::class, groups={"Default", "Doc"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Аукционы")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Doc"})
     * @SerializerGroups({"Default", "Doc"})
     *
     * @return JsonResponse
     */
    public function getRoutesForAuctionList(
        int $customer_id,
        Request $request,
        CustomerApiRouteService $routeService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse(
            $routeService->getRoutesForAuctionList($customer_id, $params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }
}
