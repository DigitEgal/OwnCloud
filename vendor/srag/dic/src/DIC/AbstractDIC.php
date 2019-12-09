<?php

namespace srag\DIC\OwnCloud\DIC;

use ILIAS\DI\Container;
use srag\DIC\OwnCloud\Database\DatabaseDetector;
use srag\DIC\OwnCloud\Database\DatabaseInterface;

/**
 * Class AbstractDIC
 *
 * @package srag\DIC\OwnCloud\DIC
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
abstract class AbstractDIC implements DICInterface
{

    /**
     * @var Container
     */
    protected $dic;


    /**
     * @inheritDoc
     */
    public function __construct(Container &$dic)
    {
        $this->dic = &$dic;
    }


    /**
     * @inheritdoc
     */
    public function database() : DatabaseInterface
    {
        return DatabaseDetector::getInstance($this->databaseCore());
    }
}
