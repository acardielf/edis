<?php

namespace Edistribucion;

class EdistribucionMessageAction
{

    private int $id;
    private $descriptor;
    private $callingDescriptor;
    private $params;
    private array $extras;
    private $command;

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
        if (!empty($this->extras)) {
            //$this->update($this->extras);
        }
        return json_encode($data,JSON_UNESCAPED_SLASHES);
    }

    public function add_field($key, $value)
    {
        $this->extras[$key] = $value;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id . ";a";
    }

    public function setCommand()
    {
        $this->command = implode(explode("/ACTION$",$this->descriptor));
    }

    public function getCommand()
    {
        return $this->command;
    }

    public function setDescriptor($value)
    {
        $this->descriptor = "apex://" . $value;
        $this->setCommand();
    }

    public function getDescriptor()
    {
        return $this->descriptor;
    }

    public function setCallingDescriptor($value)
    {
        $this->callingDescriptor = "markup://c:" . $value;
    }

    public function getCallingDescriptor()
    {
        return $this->callingDescriptor;
    }
}
