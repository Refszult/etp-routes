<?php

namespace App\Entity\Route;

use App\Entity\Traits\GuidTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\TransportationTypeRepository")
 */
class TransportationType
{
    const TRANSPORTATION_TYPE_AUTO = '6193de5b-405a-426f-809a-4103f9e264db';
    const TRANSPORTATION_TYPE_AVIA = '7e952741-5791-41e4-a853-6d3e0e40527b';
    const TRANSPORTATION_TYPE_TRAIN = '8229411b-2c24-4dd1-af28-d7cfdbe62ef0';
    const TRANSPORTATION_TYPE_NOTS = 'f033151b-cee7-4d60-8b05-10641318d18b';

    use GuidTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"Default"})
     */
    private $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
