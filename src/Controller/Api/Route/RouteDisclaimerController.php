<?php

namespace App\Controller\Api\Route;

use App\Controller\Api\BaseApiController;
use App\Service\Route\RouteDisclaimerService;
use App\Entity\Route\Route as WayRoute;
use App\Entity\Route\RouteDisclaimer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Nelmio\ApiDocBundle\Annotation\Model;
use \Symfony\Component\HttpFoundation\JsonResponse;
use Swagger\Annotations as SWG;
use FOS\RestBundle\Controller\Annotations\View as ViewAnnotation;
use App\Annotations\SerializerGroups;

class RouteDisclaimerController extends BaseApiController
{
    /**
     * Создание заявки на отказ от рейса.
     *
     * @Route(
     *     "/contractors/{contractor_id}/routes/{route_id}/create_disclaimer",
     *     name="contractor_route_disclaimer_create",
     *     methods={"POST"},
     *     requirements={"contractor_id":"\d+", "route_id":"\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="contractor_id",
     *     in="path",
     *     type="integer",
     *     description="ID подрядчика",
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
     *     description="Объект заявки на отказ",
     *     required=true,
     *     format="application/json",
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=RouteDisclaimer::class, groups={"Default", "Doc"})
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Создает новую заявку на отказ от рейса.",
     *     @Model(type=WayRoute::class, groups={"Default", "Doc", "Route_info", "RouteWay_base"})
     * )
     * @SWG\Tag(name="Заявки на отказ от рейса")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "RouteWay_base"})
     * @SerializerGroups({"Default", "Route_info", "RouteWay_base"})
     *
     * @param int                    $contractor_id
     * @param int                    $route_id
     * @param Request                $request
     * @param RouteDisclaimerService $routeDisclaimerService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function createRouteDisclaimer(
        int $contractor_id,
        int $route_id,
        Request $request,
        RouteDisclaimerService $routeDisclaimerService
    ) {
        $params = $request->getContent();
        $response = $routeDisclaimerService->createRouteDisclaimer($contractor_id, $route_id, $params);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Подтверждение заявки на отказ от рейса.
     *
     * @Route(
     *     "/customers/{customer_id}/routes/{route_id}/accept_disclaimer",
     *     name="customer_route_disclaimer_accept",
     *     methods={"PATCH"},
     *     requirements={"customer_id":"\d+", "route_id":"\d+"}
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
     *     name="route_id",
     *     in="path",
     *     type="integer",
     *     description="ID рейса",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Принимает заявку на отказ от рейса.",
     *     @Model(type=WayRoute::class, groups={"Default", "Doc", "Route_info", "RouteWay_base"})
     * )
     * @SWG\Tag(name="Заявки на отказ от рейса")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "RouteWay_base"})
     * @SerializerGroups({"Default", "Route_info", "RouteWay_base"})
     *
     * @param int                    $customer_id
     * @param int                    $route_id
     * @param Request                $request
     * @param RouteDisclaimerService $routeDisclaimerService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function acceptRouteDisclaimer(
        int $customer_id,
        int $route_id,
        Request $request,
        RouteDisclaimerService $routeDisclaimerService
    ) {
        $params = $request->getContent();
        $response = $routeDisclaimerService->acceptRouteDisclaimer($customer_id, $route_id, $params);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Отмена заявки на отказ от рейса.
     *
     * @Route(
     *     "/customers/{customer_id}/routes/{route_id}/decline_disclaimer",
     *     name="customer_route_disclaimer_decline",
     *     methods={"PATCH"},
     *     requirements={"customer_id":"\d+", "route_id":"\d+"}
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
     *     name="route_id",
     *     in="path",
     *     type="integer",
     *     description="ID рейса",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отменяет заявку на отказ от рейса.",
     *     @Model(type=WayRoute::class, groups={"Default", "Doc", "Route_info", "RouteWay_base"})
     * )
     * @SWG\Tag(name="Заявки на отказ от рейса")
     *
     * @ViewAnnotation(serializerGroups={"Default", "Route_info", "RouteWay_base"})
     * @SerializerGroups({"Default", "Route_info", "RouteWay_base"})
     *
     * @param int                    $customer_id
     * @param int                    $route_id
     * @param Request                $request
     * @param RouteDisclaimerService $routeDisclaimerService
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function declineRouteDisclaimer(
        int $customer_id,
        int $route_id,
        Request $request,
        RouteDisclaimerService $routeDisclaimerService
    ) {
        $params = $request->getContent();
        $response = $routeDisclaimerService->declineRouteDisclaimer($customer_id, $route_id, $params);

        return $this->view($response->body, $response->code, $response->headers);
    }
}
