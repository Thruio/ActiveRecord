<?php
namespace Thru\ActiveRecord\DatabaseLayer;

use Thru\ActiveRecord\ActiveRecord;

class TableBuilder extends VirtualQuery
{
    private $_context;

    public function __construct(ActiveRecord $context)
    {
        $this->_context = $context;
    }

    public function build()
    {
        $this->getInterpreter()->buildTable($this->_context);
    }

    public function destroy()
    {
        $this->getInterpreter()->destroyTable($this->_context);
    }
}
