<?php

namespace Edistribucion\Models;

class CUPS
{

    public string $cups;
    public string $cupsId;
    public string $id;
    public bool $active;
    public float $power;
    public string $rate;

    public function __construct(
        string $cups,
        string $cupsId,
        string $id,
        bool $active,
        float $power,
        string $rate
    ) {
        $this->setCups($cups);
        $this->setCupsId($cupsId);
        $this->setId($id);
        $this->setActive($active);
        $this->setPower($power);
        $this->setRate($rate);
    }

    public function __toString(): string
    {
        return $this->getId();
    }

    public function __toArray(): array
    {
        return [
            'CUPS' => $this->cups,
            'CUPS_Id' => $this->cupsId,
            'Id' => $this->id,
            'Active' => $this->active,
            'Power' => $this->power,
            'Rate' => $this->rate,
        ];
    }

    public function getCups(): string
    {
        return $this->cups;
    }

    public function setCups(string $cups): CUPS
    {
        $this->cups = $cups;
        return $this;
    }

    public function getCupsId(): string
    {
        return $this->cupsId;
    }

    public function setCupsId(string $cupsId): CUPS
    {
        $this->cupsId = $cupsId;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): CUPS
    {
        $this->id = $id;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): CUPS
    {
        $this->active = $active;
        return $this;
    }

    public function getPower(): float
    {
        return $this->power;
    }

    public function setPower(float $power): CUPS
    {
        $this->power = $power;
        return $this;
    }

    public function getRate(): string
    {
        return $this->rate;
    }

    public function setRate(string $rate): CUPS
    {
        $this->rate = $rate;
        return $this;
    }


}