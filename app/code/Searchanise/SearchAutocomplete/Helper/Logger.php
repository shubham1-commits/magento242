<?php

namespace Searchanise\SearchAutocomplete\Helper;

class Logger extends \Magento\Framework\App\Helper\AbstractHelper
{
    const TYPE_ERROR   = 'Error';
    const TYPE_INFO    = 'Info';
    const TYPE_WARNING = 'Warning';
    const TYPE_DEBUG   = 'Debug';

    private static $allowedTypes = [
        self::TYPE_ERROR,
        self::TYPE_INFO,
        self::TYPE_WARNING,
        self::TYPE_DEBUG,
    ];

    /**
     * @var \Searchanise\SearchAutocomplete\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\Response
     */
    private $response = null;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Searchanise\SearchAutocomplete\Helper\Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;

        parent::__construct($context);
    }

    /**
     * Log message
     */
    public function log()
    {
        $args = func_get_args();
        $message = [];
        $type = array_pop($args);

        // Check log type
        if (!in_array($type, self::$allowedTypes)) {
            if ($type !== null) {
                array_push($args, $type);
            }

            $type = self::TYPE_ERROR;
        }

        if ($type == self::TYPE_DEBUG && !$this->dataHelper->checkDebug(true)) {
            return false;
        }

        // Check log message
        if (!empty($args)) {
            foreach ($args as $k => $v) {
                if (!is_array($v) && preg_match('~[^\x20-\x7E\t\r\n]~', $v) > 0) {
                    $message[] = '=== BINARY DATA ===';
                } else {
                    $message[] = print_r($v, true);
                }
            }
        }
        $message = implode("\n", $message);

        switch ($type) {
            case self::TYPE_ERROR:
                $this->_logger->error('Searchanise #' . $message);
                break;
            case self::TYPE_WARNING:
                $this->_logger->warning('Searchanise #' . $message);
                break;
            case self::TYPE_DEBUG:
                $this->_logger->debug('Searchanise #' . $message);
                break;
            default:
                $this->_logger->info('Searchanise #' . $message);
        }

        if ($this->dataHelper->checkDebug(true)) {
            call_user_func_array([$this, 'printR'], $args);
        }

        return true;
    }

    public function setResponseContext(\Magento\Framework\HTTP\PhpEnvironment\Response $httpResponse = null)
    {
        $this->response = $httpResponse;
        return $this;
    }

    public function printR()
    {
        static $count = 0;

        $args = func_get_args();
        $content = '';
        $time = date('c');

        if (!empty($args)) {
            $content .= '<ol style="font-family: Courier; font-size: 12px; border: 1px solid #dedede; background-color: #efefef; float: left; padding-right: 20px;">';
            $content .= '<li><pre>===== ' . $time . '===== </pre></li>' . "\n";

            foreach ($args as $k => $v) {
                $v = htmlspecialchars(print_r($v, true));
                if ($v == '') {
                    $v = '    ';
                }

                $content .= '<li><pre>' . $v . "\n" . '</pre></li>';
            }

            $content .= '</ol><div style="clear:left;"></div>';
        }

        $count++;

        if (!empty($content) && !empty($this->response)) {
            $this->response->appendBody($content);
        }
    }
}
