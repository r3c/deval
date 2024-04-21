<?php

namespace Deval;

class Location
{
    public $column;
    public $context;
    public $line;

    public function __construct($context, $line, $column)
    {
        $this->column = $column;
        $this->context = $context;
        $this->line = $line;
    }
}
