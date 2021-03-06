<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Op\Terminal;

use PHPCfg\Op\Terminal;
use PHPCfg\Operand;

class Echo_ extends Terminal {
    public $expr;

    public function __construct(Operand $expr, array $attributes = []) {
        parent::__construct($attributes);
        $this->expr = $this->addReadRef($expr);
    }

    public function getSubBlocks() {
        return [];
    }

    public function getVariableNames() {
        return ['expr'];
    }
}