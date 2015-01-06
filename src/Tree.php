<?php

/**
 * Base interface for the Bonsai node and leaf structure.
 */

namespace Bonsai;

/**
 * Base interface for a node and leaf content structure.
 */
interface Tree
{

    public function __construct($nodeID);

    public function getContent();

    public function getTreeArray($withContent);
}
