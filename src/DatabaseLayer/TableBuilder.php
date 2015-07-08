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
        $this->getInterpreter()->buildTable($this->context);
    }

    public function destroy()
    {
        $this->getInterpreter()->destroyTable($this->context);
    }
}
