<?php
/**
 * Emarketa.
 */

namespace Evo8fx\M2ddlogger\Model\Config\Source\Datadog;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Location
 *
 * @package Evo8fx\M2ddlogger\Model\Config\Source\Datadog
 */
class LogOutputFormat implements OptionSourceInterface
{
    const LOG_FORMAT_JSON = "JSON";
    const LOG_FORMAT_TEXT = "TEXT";

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::LOG_FORMAT_JSON, 'label' => 'JSON'],
            ['value' => self::LOG_FORMAT_TEXT, 'label' => 'Text'],
        ];
    }
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::LOCATION_EU => 'JSON',
            self::LOCATION_USA => 'Text'
        ];
    }

}
