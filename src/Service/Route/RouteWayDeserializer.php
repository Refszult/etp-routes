<?php

namespace App\Service\Route;

use App\Model\Route\RouteWayModel;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;

class RouteWayDeserializer
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var DeserializationContext
     */
    protected $context;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->context = new DeserializationContext();
        $this->context->setGroups(['Default', 'RouteWay_des']);
        $this->context->enableMaxDepthChecks();
    }

    public function setGroups(array $groups)
    {
        $this->context = new DeserializationContext();
        $this->context->setGroups($groups);
        $this->context->enableMaxDepthChecks();
    }

    /**
     * Десериализация в json в модель.
     *
     * @param string $data - данные в формате JSON
     *
     * @return RouteWayModel|null
     */
    public function deserializeToModel(string $data): ?RouteWayModel
    {
        return $this->serializer->deserialize($data, RouteWayModel::class, 'json', $this->context);
    }
}
