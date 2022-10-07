<?php
namespace Evo8fx\M2ddlogger\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use \Monolog\Logger;

/**
 * Class Handler
 * @package Evo8fx\M2ddlogger\Model\Logger
 */
class Handler extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/datadog.restapi.json';
}
