<?php

namespace Edistribucion;

abstract class EdisActionGeneric
{

    private int $id;
    private string $descriptor;
    private string $callingDescriptor;
    private array $params;
    private array $extras;
    private string $command;

    public function __construct(int $id, $descriptor, $callingDescriptor, $params, $extras = [])
    {
        $this->setId($id);
        $this->setDescriptor($descriptor);
        $this->setCallingDescriptor($callingDescriptor);
        $this->params = $params;
        $this->extras = $extras;
    }

    public function __toString(): string
    {
        $data = [
            "id" => $this->getId(),
            "descriptor" => $this->getDescriptor(),
            "callingDescriptor" => $this->getCallingDescriptor(),
            "params" => $this->params
        ];

        foreach ($this->extras as $extra => $value) {
            $data[$extra] = $value;
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    public function add_field($key, $value): void
    {
        $this->extras[$key] = $value;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id . ";a";
    }

    public function setCommand(string $value): void
    {
        $this->command = implode(".", explode("/ACTION$", $value));
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setDescriptor($value): void
    {
        $this->descriptor = "apex://" . $value;
        $this->setCommand($value);
    }

    public function getDescriptor(): string
    {
        return $this->descriptor;
    }

    public function setCallingDescriptor($value): void
    {
        $this->callingDescriptor = "markup://c:" . $value;
    }

    public function getCallingDescriptor(): string
    {
        return $this->callingDescriptor;
    }
}
