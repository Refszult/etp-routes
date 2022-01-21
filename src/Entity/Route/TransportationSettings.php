<?php

namespace App\Entity\Route;

use App\Repository\Route\TransportationSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity(repositoryClass=TransportationSettingsRepository::class)
 */
class TransportationSettings
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"Default", "Create_update_transportation_settings"})
     */
    private $id;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true, options={"default": false})
     * @Groups({"Default", "Create_update_transportation_settings"})
     */
    private $firstBid = false;

    /**
     * @var int
     * @ORM\Column(type="integer")
     * @Groups({"Default", "Create_update_transportation_settings"})
     */
    private $timeToCancel;

    /**
     * @var Transportation
     * @ORM\OneToOne(targetEntity="App\Entity\Route\Transportation", inversedBy="TransportationSettings")
     * @Groups({"Create_update_transportation_settings"})
     * @JMS\Groups({"Create_update_transportation_settings"})
     */
    private $transportation;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return TransportationSettings
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function isFirstBid(): bool
    {
        return $this->firstBid;
    }

    public function setFirstBid(bool $firstBid): TransportationSettings
    {
        $this->firstBid = $firstBid;

        return $this;
    }

    public function getTimeToCancel(): int
    {
        return $this->timeToCancel;
    }

    public function setTimeToCancel(int $timeToCancel): TransportationSettings
    {
        $this->timeToCancel = $timeToCancel;

        return $this;
    }

    public function getTransportation()
    {
        return $this->transportation;
    }

    public function setTransportation(Transportation $transportation): TransportationSettings
    {
        $this->transportation = $transportation;

        return $this;
    }
}
