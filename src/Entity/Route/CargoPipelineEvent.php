<?php

namespace App\Entity\Route;

use App\Entity\Traits\GuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\CargoPipelineEventRepository")
 */
class CargoPipelineEvent
{
    use GuidTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"Default"})
     */
    private $name;

    /**
     * @ORM\OneToMany(
     *     targetEntity=CargoPipeline::class,
     *     mappedBy="cargoPipelineEvent",
     *     cascade={"remove"}
     * )
     * @JMS\Exclude()
     */
    private $cargoPipelines;

    public function __construct()
    {
        $this->cargoPipelines = new ArrayCollection();
    }

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

    /**
     * @return Collection|CargoPipeline[]
     */
    public function getCargoPipelines(): Collection
    {
        return $this->cargoPipelines;
    }

    public function addCargoPipeline(CargoPipeline $cargoPipeline): self
    {
        if (!$this->cargoPipelines->contains($cargoPipeline)) {
            $this->cargoPipelines[] = $cargoPipeline;
            $cargoPipeline->setCargoPipelineEvent($this);
        }

        return $this;
    }

    public function removeCargoPipeline(CargoPipeline $cargoPipeline): self
    {
        if ($this->cargoPipelines->contains($cargoPipeline)) {
            $this->cargoPipelines->removeElement($cargoPipeline);
            // set the owning side to null (unless already changed)
            if ($cargoPipeline->getCargoPipelineEvent() === $this) {
                $cargoPipeline->setCargoPipelineEvent(null);
            }
        }

        return $this;
    }
}
