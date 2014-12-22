<?php

/**
 * Base interface for the Bonsai node and leaf structure.
 */

namespace Bonsai;

require_once __DIR__ . '/Bonsai.php';

/**
 * Base interface for a node and leaf content structure.
 */
interface Tree
{

    public function __construct($nodeID);

    public function getContent();

    public function getTreeArray($withContent);
}
