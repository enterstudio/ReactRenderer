<?php

namespace Limenius\ReactRenderer\Renderer;

use Psr\Log\LoggerInterface;

/**
 * Class ExternalServerReactRenderer
 */
class ExternalServerReactRenderer extends AbstractReactRenderer
{
    /**
     * @var string
     */
    protected $serverSocketPath;

    /**
     * @var bool
     */
    protected $failLoud;

    /**
     * ExternalServerReactRenderer constructor.
     *
     * @param string          $serverSocketPath
     * @param bool            $failLoud
     * @param LoggerInterface $logger
     */
    public function __construct($serverSocketPath, $failLoud = false, LoggerInterface $logger = null)
    {
        $this->serverSocketPath = $serverSocketPath;
        $this->failLoud = $failLoud;
        $this->logger = $logger;
    }

    /**
     * @param string $serverSocketPath
     */
    public function setServerSocketPath($serverSocketPath)
    {
        $this->serverSocketPath = $serverSocketPath;
    }

    /**
     * @param string $componentName
     * @param string $propsString
     * @param string $uuid
     * @param array  $registeredStores
     * @param bool   $trace
     *
     * @return string
     */
    public function render($componentName, $propsString, $uuid, $registeredStores = array(), $trace)
    {
        if (strpos($this->serverSocketPath, '://') === false) {
            $this->serverSocketPath = 'unix://'.$this->serverSocketPath;
        }

        $sock = stream_socket_client($this->serverSocketPath, $errno, $errstr);
        fwrite($sock, $this->wrap($componentName, $propsString, $uuid, $registeredStores, $trace));

        $contents = '';

        while (!feof($sock)) {
            $contents .= fread($sock, 8192);
        }
        fclose($sock);

        $result = json_decode($contents, true);
        if ($result['hasErrors']) {
            $this->logErrors($result['consoleReplayScript']);
            if ($this->failLoud) {
                $this->throwError($result['consoleReplayScript'], $componentName);
            }
        }

        return $result['html'].$result['consoleReplayScript'];
    }
}
