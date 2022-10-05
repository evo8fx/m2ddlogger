<?php
namespace Evo8fx\M2ddlogger\Plugin;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use \Magento\Framework\Webapi\Rest\Response;
use \Magento\Framework\Serialize\SerializerInterface;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Webapi\Controller\Rest;
use Evo8fx\M2ddlogger\Model\Logger\Logger;
use Evo8fx\M2ddlogger\Formatter\DatadogFormatter;

/**
 * Class RestApiLog
 * @package Evo8fx\M2ddlogger\Plugin
 */
class RestApiLog
{
    /**
     * Store Config Ids
     */
    const API_LOGGER_ENABLED = 'm2ddlogger/api_logger/enabled';
    const API_LOGGER_ALLOWED_LOG_HEADERS = 'm2ddlogger/api_logger/allowed_log_headers';
    const API_LOGGER_EVENT_AS = 'm2ddlogger/api_logger/log_api_event_as';
    const API_LOGGER_LOG_REQUEST = 'm2ddlogger/api_logger/api_event_log_request';
    const API_LOGGER_LOG_RESPONSE = 'm2ddlogger/api_logger/api_event_log_response';
    const API_LOGGER_FORMAT = 'm2ddlogger/api_logger/api_event_log_format';

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var TimezoneInterface
     */
    protected $_date;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var SerializerInterface
     */
    private $_serializer;

    /**
     * RestApiLog constructor.
     * @param Logger $logger
     * @param TimezoneInterface $date
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Logger $logger,
        TimezoneInterface $date,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer
    )
    {
        $this->_logger = $logger;
        $this->_date = $date;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_serializer = $serializer;
    }

    /**
     * @param Rest $subject
     * @param RequestInterface $request
     */
    public function beforeDispatch(Rest $subject, RequestInterface $request)
    {
        try {
            // If Enabled Api Rest Log
            if (!$this->_scopeConfig->getValue(self::API_LOGGER_ENABLED, ScopeInterface::SCOPE_STORE)) {
                return;
            }

            // Prepare Data For Log
            $requestedLogData = [
                'storeId' => $this->_storeManager->getStore()->getId(),
                'path' => $request->getPathInfo(),
                'httpMethod' => $request->getMethod(),
                'requestData' => $request->getContent(),
                'clientIp' => $request->getClientIp(),
                'date' => $this->_date->date()->format('Y-m-d H:i:s')
            ];

            // Log Headers
            if ($this->_scopeConfig->getValue(self::API_LOGGER_ALLOWED_LOG_HEADERS, ScopeInterface::SCOPE_STORE)) {
                $requestedLogData['header'] = $this->getHeadersData($request->getHeaders());
            }

            // plain text logs
            //$formattedLogData = 'Response = ' . $this->_serializer->serialize($requestedLogData);

            // convert log to JSON format
            $formattedLogData = $this->toJson('Request = ' . $this->_serializer->serialize($requestedLogData));

            // Logging Data
            $this->_logger->debug($formattedLogData);
        } catch (\Exception $exception) {
            $this->_logger->critical($exception->getMessage(), ['exception' => $exception]);
        }
    }

    /**
     * @param Response $response
     * @param $result
     * @return mixed
     */
    public function afterSendResponse(Response $response, $result)
    {
        try {
            // If Enabled Api Rest Log
            if (!$this->_scopeConfig->getValue(self::API_LOGGER_ENABLED, ScopeInterface::SCOPE_STORE)) {
                return;
            }

            // Prepare Data For Log
            $requestedLogData = [
                'responseStatus' => $response->getReasonPhrase(),
                'responseStatusCode' => $response->getStatusCode(),
                'responseBody' => $response->getBody()
            ];

            // Log Headers
            if ($this->_scopeConfig->getValue(self::API_LOGGER_ALLOWED_LOG_HEADERS, ScopeInterface::SCOPE_STORE)) {
                $requestedLogData['header'] = $this->getHeadersData($response->getHeaders());
            }


            // plain text logs
            //$formattedLogData = 'Response = ' . $this->_serializer->serialize($requestedLogData);

            // convert log to JSON format
            $formattedLogData = $this->toJson('Response = ' . $this->_serializer->serialize($requestedLogData));


            // Logging Data
            $this->_logger->info($json_logData);
        } catch (\Exception $exception) {
            $this->_logger->critical($exception->getMessage(), ['exception' => $exception]);
        }
        return $result;
    }

    /**
     * Method for getting all available data in header and convert them to array
     *
     * @param $headers
     * @return array
     */
    private function getHeadersData($headers): array
    {
        $headerLogData = [];
        foreach ($headers as $header) {
            $headerLogData[$header->getFieldName()] = $header->getFieldValue();
        }
        return $headerLogData;
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
