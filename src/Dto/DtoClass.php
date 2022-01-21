<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;

abstract class DtoClass implements DtoInterface
{
    /**
     * @var array
     * @JMS\Exclude()
     */
    public $createFields = [];

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $updateFields = [];

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $fileFields = [];

    /**
     * @var ?\DateTime
     * @JMS\Type("DateTime")
     * @JMS\Groups({"MQ"})
     */
    protected $initDate = null;

    /**
     * Заполнение моделей из полей создания.
     *
     * @param object $object
     */
    public function createFieldSet($object)
    {
        foreach ($this->createFields as $field) {
            if (null !== $this->{$field}) {
                $method = 'set'.ucfirst($field);
                $object->{$method}($this->{$field});
            }
        }
    }

    public function createFileSet($object)
    {
    }

    /**
     * Заполнение моделей из полей обновления.
     *
     * @param object $object
     */
    public function updateFieldSet($object)
    {
        foreach ($this->updateFields as $field) {
            if (null !== $this->{$field}) {
                $method = 'set'.ucfirst($field);
                $object->{$method}($this->{$field});
            }
        }
    }

    public function updateFileSet($object)
    {
    }

    /**
     * Возвращает текущее время или время инициализации.
     *
     * @return \DateTime
     *
     * @throws \Exception
     */
    public function nowOrInit()
    {
        $date = new \DateTime();
        if ($this->initDate) {
            if ($this->initDate instanceof \DateTime) {
                $date = $this->initDate;
            }
        }

        return $date;
    }
}
