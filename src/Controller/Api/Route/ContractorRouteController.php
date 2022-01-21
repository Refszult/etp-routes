<?php

namespace App\Controller\Api\Route;

use App\Controller\Api\BaseApiController;
use App\Service\RequestResponse\RequestService;
use App\Service\Route\ContractorApiRouteService;
use App\Service\Route\RouteDeserializer;
use App\Service\Route\RouteService;
use App\Model\Route\ContractorRouteModel;
use App\Entity\Route\Route as RouteEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use \Symfony\Component\HttpFoundation\JsonResponse;
use Swagger\Annotations as SWG;
use FOS\RestBundle\Controller\Annotations\View as ViewAnnotation;
use App\Annotations\SerializerGroups;

class ContractorRouteController extends BaseApiController
{
    /**
     * Получение списка рейсов подрядчиков c пагинацией.
     *
     * @Route(
     *     "contractors/{contractor_id}/routes",
     *     name="get_contractor_routes",
     *     methods={"GET"},
     *     requirements={"contractor_id":"\d+"}
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
     *     description="ID подрядчика",
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
     *     name="date_from",
     *     in="query",
     *     type="string",
     *     description="Минимальная дата рейса (YYYY-MM-DD)",
     * )
     *
     * @SWG\Parameter(
     *     name="date_to",
     *     in="query",
     *     type="string",
     *     description="Максимальная дата рейса (YYYY-MM-DD)",
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
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список рейсов для подрядчика.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=RouteEntity::class, groups={"Default", "Doc", "Route_info"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Рейсы подрядчика")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info"})
     * @SerializerGroups({"Default", "Route_info"})
     *
     * @param int            $contractor_id
     * @param Request        $request
     * @param ContractorApiRouteService   $routeService
     * @param RequestService $requestService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function getRouteWaysList(
        int $contractor_id,
        Request $request,
        ContractorApiRouteService $routeService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse(
            $routeService->getContractorRouteList($contractor_id, $params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Обновление рейса подрядчика.
     *
     * @Route(
     *     "/contractors/{contractor_id}/routes/{route_id}",
     *     name="contractor_route_update",
     *     methods={"PUT"},
     *     requirements={"contractor_id": "\d+", "route_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="contractor_id",
     *     in="path",
     *     type="integer",
     *     description="ID подрядчика, от лица которого обновляется рейс",
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
     *         ref=@Model(type=ContractorRouteModel::class, groups={"Default", "Route_info", "Doc", "RouteWay_base"})
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Создает новый рейс от лица заказчика.",
     *     @Model(type=RouteEntity::class, groups={"Default", "Route_info", "RouteWay_base"})
     * )
     * @SWG\Tag(name="Рейсы подрядчика")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "RouteWay_base"})
     * @SerializerGroups({"Default", "Route_info", "RouteWay_base"})
     *
     * @param int               $contractor_id
     * @param int               $route_id
     * @param Request           $request
     * @param RouteDeserializer $deserializer
     * @param ContractorApiRouteService  $routeService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function updateRoute(
        int $contractor_id,
        int $route_id,
        Request $request,
        RouteDeserializer $deserializer,
        ContractorApiRouteService $routeService
    ) {
        $params = $request->getContent();
        $routeDto = $deserializer->deserializeToApiContractorDto($params);
        $response = $this->prepareResponse(
            $routeService->updateContractorRoute($contractor_id, $route_id, $routeDto),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение деталей рейса для подрядчика.
     *
     * @Route(
     *     "/contractors/{contractor_id}/routes/{route_id}",
     *     name="get_contractors_route",
     *     methods={"GET"},
     *     requirements={"contractor_id": "\d+", "route_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="contractor_id",
     *     in="path",
     *     type="integer",
     *     description="ID подрядчика, чей рейс выводится",
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
     *     @Model(type=Route::class, groups={"Default", "Route_info", "Doc", "RouteWay_base", "VehicleModel_info", "RouteAuction_info"})
     * )
     * @SWG\Tag(name="Рейсы подрядчика")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "RouteWay_base", "VehicleModel_info", "RouteAuction_info"})
     * @SerializerGroups({"Default", "Route_info", "RouteWay_base", "VehicleModel_info", "RouteAuction_info"})
     *
     * @param int                       $contractor_id
     * @param int                       $route_id
     * @param ContractorApiRouteService $routeService
     * @param Request                   $request
     *
     * @return JsonResponse
     */
    public function getRouteInfo(
        int $contractor_id,
        int $route_id,
        ContractorApiRouteService $routeService,
        Request $request
    ) {
        $response = $this->prepareResponse(
            $routeService->getContractorRoute($contractor_id, $route_id),
            $request
        );
        return $this->view($response->body, $response->code, $response->headers);
    }
}
