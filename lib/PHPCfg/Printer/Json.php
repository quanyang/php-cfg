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
        if ($op instanceof \PHPCfg\Op\CallableOp) {
            if (isset($op->name->value)) {
                $result .= '<' . $op->name->value . '>';
            }
            foreach ($op->getParams() as $key => $param) {
                $result .= $this->indent("\nparams: " . $this->renderOperand($param->result));
            }
        }
        if ($op instanceof \PHPCfg\Op\Expr\Assertion) {
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
                $result .= str_replace(array("\n","\r",'"'),array('\\\\n','\\\\r','\\"'),$this->renderOperand($var));
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
            $temp = array_map(trim,explode(":",$val,2));
            if (count($temp)>1) {
                if (isset($output[$temp[0]])) {
                    $tmp = array($output[$temp[0]]);
                    array_push($tmp,$temp[1]);
                    $output[$temp[0]] = $tmp;
                } else {
                    $output[$temp[0]] = $temp[1];
                }
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
                } else {
                    $operand['file'] = "None";
                    $operand['startLine'] = "None";
                    $operand['endLine'] = "None";
                }
                foreach ($op['childBlocks'] as $child) {
                    if (array_key_exists($child['name'], $operand)) {
                        if (!is_array($operand[$child['name']])) {
                            $operand[$child['name']] = array($operand[$child['name']]);
                        }
                        array_push($operand[$child['name']],"Block#" . $rendered['blockIds'][$child['block']]);
                    } else {
                        $operand[$child['name']] = "Block#" . $rendered['blockIds'][$child['block']];
                    }
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