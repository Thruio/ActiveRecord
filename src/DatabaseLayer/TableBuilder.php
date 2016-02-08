<?php
namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\ActiveRecord;

class TableBuilder extends VirtualQuery
{
    private $context;

    public function __construct(ActiveRecord $context)
    {
        $this->context = $context;
    }

    public function build()
    {
        return $this->getInterpreter()->buildTable($this->context);
    }

    public function destroy()
    {
        return $this->getInterpreter()->destroyTable($this->context);
    }

    public function getGeneratedSchema()
    {
        return $this->getInterpreter()->getGeneratedSchema($this->context);
    }

    public function getActualSchema()
    {
        return $this->getInterpreter()->getActualSchema($this->context);
    }
}
