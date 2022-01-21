<?php

namespace App\Service\Route;

use App\Classes\RequestResponse\IBMMQResponse;
use App\Classes\XmlTransformation\XmlTransformationFactory;
use App\Entity\Route\Route;
use App\Exceptions\WrongObjectException;
use App\Service\IBMMQ\IBMMQPusher;
use Psr\Container\ContainerInterface;

/**
 * Class RouteIBMMQService
 * Сервис отправки сообщения по рейсу в IBMMQ.
 */
class RouteIBMMQService
{
    const CUR = 'CreateUpdateRoute';
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;
    /**
     * @var IBMMQPusher
     */
    protected IBMMQPusher $mqPusher;

    public function __construct(ContainerInterface $container, IBMMQPusher $mqPusher)
    {
        $this->container = $container;
        $this->mqPusher = $mqPusher;
    }

    /**
     * Отправка данных по ТС на IBMMQ.
     * @param Route $route
     */
    public function sendRouteToIBMMQ(Route $route)
    {
        try {
            $response = new IBMMQResponse();
            if ($this->mqPusher->canSend()) {
                $transformer = $this->container
                    ->get(XmlTransformationFactory::class)
                    ->build(self::CUR, $response);
                $message = $transformer->createXmlFromObject($route);
                $this->mqPusher->push($message);
            }
        } catch (\Throwable $exception) {
            throw new WrongObjectException('Не удалось провести обновление рейса.', $exception);
        }
    }
}
