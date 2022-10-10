<?php
/**
 * Evo8fx.
 */

namespace Evo8fx\M2ddlogger\Formatter;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Utils;

/**
 * Class DatadogApiFormatter
 *
 * @package Evo8fx\M2ddlogger\Formatter
 */
class DatadogApiFormatter extends AbstractFormatter implements FormatterInterface
{
    //const CONFIG_LOG_FILE_PATH = 'm2ddlogger/event_logger/file/log_file_path';
    const CONFIG_DD_TAGS = 'm2ddlogger/event_logger/ddtags';
    /**
     * @var ScopeConfigInterface
     */
    private $config;
    /**
    * @var StoreManagerInterface
    */
    protected $storeManager;
    public $log_file_path = '/var/log/datadog.restapi.json';

    /**
      * DatadogFormatter constructor.
      * @param ScopeConfigInterface  $config
      * @param StoreManagerInterface $storeManager
      */
    public function __construct(
         ScopeConfigInterface $config,
         StoreManagerInterface $storeManager
    ) {
         $this->config = $config;
         $this->storeManager = $storeManager;
         //$this->log_file_path = $this->config->getValue(self::CONFIG_LOG_FILE_PATH);
    }

    /**
     * Method format
     *
     * @param array $record
     * @return array|mixed|string
     */
    public function format(array $record)
    {
        if (!isset($record['ddtype'])) {
            return $record;
        }

        $type = $record['ddtype'];
        $record['formatted'] = $this->getMessageString($record);
        $recordArray = $this->getRecordArray($record);

        switch ($type) {
            case 'file':
                $data = array_merge($record, $recordArray);
                return $this->toJson($data);
            case 'http_endpoint':
            default:
                return $recordArray;
        }
    }

    /**
     * Method getRecordArray
     *
     * @param array $record
     * @return array
     */
    public function getRecordArray(array $record)
    {
        $dd_tags = $this->buildDdTags();

        return [
          'hash_id' => hash('md5', $record['formatted']),
          'datetime' => date(DATE_ISO8601),
          'level' => $record['level_name'],
          'report_id' => $this->getReportId($record),
          'message' => $record['formatted'],
          'ddsource' => 'magento',
          'ddtags' => $this->formatDdTags($dd_tags),
          'ddhost_type' => $dd_tags['host_type'],
          'ddnode' => 'node',
          'hostname' => gethostname(),
          'host_type' => $dd_tags['host_type'],
          'service' => $dd_tags['service'],
          'env' => $dd_tags['env'],
          'log_file_path' => $this->log_file_path
        ];
    }

    /**
     * Method buildDdTags
     *
     * @return array
     */
    private function buildDdTags()
    {
        //strip unwanted characters from url
        $url = str_replace("/","",str_replace("https://","",strtolower($this->storeManager->getStore()->getUrl())));

        // parse url chunks
        $urlObj = explode(".", $url);

        // set local (US or CA) Tag
        $local = "US";
        if($urlObj[2]=="ca")
          $local = "CA";

        // set service and env Tags
        $service = strtoupper($urlObj[0]."-".$local);
        $env = "dev";
        $host_type = "dev-node";
        if($urlObj[0]=="www"){
          $env = "prod";
          $service = "PROD-".$local;
          $host_type = "web-node";
        }
        if($urlObj[0]=="prodadmin"){
          $env = "prod";
          $service = "PROD-".$local;
          $host_type = "admin-node";
        }

        // return array
        return [
          'site' => $url,
          'local' => $local,
          'service' => $service,
          'env' => $env,
          'host_type' => $host_type
        ];

    }

    /**
     * Method formatDdTags
     *
     * @param array $record
     * @return array
     */
    public function formatDdTags(array $dd_tags)
    {
        $return = [];
        foreach ($dd_tags as $key => $value) {
            if (!empty($value)) {
                $return[] = $key . ":" . $value;
            }
        }
        return implode(",", $return);
    }

    /**
     * Method getCompiledDdTags
     *
     * @return string
     */
    public function getCompiledDdTags()
    {
        $return = [];
        $tags = (array) $this->config->getValue(self::CONFIG_DD_TAGS);
        foreach ($tags as $key => $value) {
            if (!empty($value)) {
                $return[] = $key . ":" . $value;
            }
        }
        return implode(",", $return);
    }

    /**
     * Method toJson
     *
     * @param mixed $data
     * @return string
     */
    protected function toJson($data)
    {
        $formatter = new JsonFormatter();
        return $formatter->format($data);
    }
}
