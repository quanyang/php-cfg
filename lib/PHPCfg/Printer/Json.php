<?php

/*
 * This file is part of PHP-CFG, a Control flow graph implementation for PHP
 *
 * @copyright 2015 Anthony Ferrara. All rights reserved
 * @license MIT See LICENSE at the root of the project for more info
 */

namespace PHPCfg\Printer;

use PHPCfg\Printer;

class Json extends Printer {
    protected function renderOp(\PHPCfg\Op $op) {
        $result = $op->getType();
        if ($op instanceof Op\CallableOp) {
            $result .= '<' . $op->name->value . '>';
            foreach ($op->getParams() as $key => $param) {
                $result .= $this->indent("\nParam[$key]: " . $this->renderOperand($param->result));
            }
        }
        if ($op instanceof Op\Expr\Assertion) {
            $result .= "<" . $this->renderAssertion($op->assertion) . ">";
        }
        foreach ($op->getVariableNames() as $varName) {
            $vars = $op->$varName;
            if (!is_array($vars)) {
                $vars = [$vars];
            }
            foreach ($vars as $var) {
                if (!$var) {
                    continue;
                }
                $result .= "\n    $varName: ";
                $result .= $this->indent($this->renderOperand($var));
            }
        }
        $childBlocks = [];
        foreach ($op->getSubBlocks() as $blockName) {
            $sub = $op->$blockName;
            if (is_null($sub)) {
                continue;
            }
            if (!is_array($sub)) {
                $sub = [$sub];
            }
            foreach ($sub as $subBlock) {
                if (!$subBlock) {
                    continue;
                }
                $this->enqueueBlock($subBlock);
                $childBlocks[] = [
                "block" => $subBlock,
                "name"  => $blockName,
                ];
            }
        }
        return [
        "op"          => $op,
        "label"       => $result,
        "attributes"  => $op->getAttributes(),
        "childBlocks" => $childBlocks,
        ];
    }

    function splitOperand(array $operand) {
        $expr = $operand[0];
        $output = array();
        $output['operand'] = $expr;
        foreach ($operand as $val) {
            $temp = array_map(trim,split(":",$val));
            if (count($temp)>1) {
                $output[$temp[0]] = $temp[1];
            }
        }
        return $output;
    }

    public function printCFG(array $blocks) {
        $rendered = $this->render($blocks);
        $blocks = array();
        foreach ($rendered['blocks'] as $block) {
            $ops = $rendered['blocks'][$block];
            $block_ = array();
            $block_['parentBlock'] = array();
            foreach ($block->parents as $prev) {
                if ($rendered['blockIds']->contains($prev)) {
                    array_push($block_['parentBlock'],$rendered['blockIds'][$prev]);
                }
            }
            $block_['blockId'] = $rendered['blockIds'][$block];
            $operand_= array();
            foreach ($ops as $op) { 
                $operand = $this->splitOperand(split("\n",$op['label']));
                if ($attributes = $op['attributes']){
                    $operand['startLine'] = $attributes['startLine'];
                    $operand['endLine'] = $attributes['endLine'];
                    $operand['file'] = $attributes['filename'];
                }
                foreach ($op['childBlocks'] as $child) {
                    $operand[$child['name']] = "Block#" . $rendered['blockIds'][$child['block']];
                }
                array_push($operand_,$operand);
            }
            $block_['blocks'] = $operand_;
            array_push($blocks,$block_);
        }
        return $blocks;
    }

    function printVars(array $blocks) {

    }
}