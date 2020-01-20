<?php

namespace BufferSDK\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class GuzzleClient implements ClientInterface {

    const CLIENT_DEFAULT_FORMAT = 'json';

    /** @var string */
    protected $baseURL = 'https://api.bufferapp.com/1/';

    /** @var Client */
    private $httpClient;

    /**
     * @var mixed|ResponseInterface
     */
    private $httpRequest;

    /**
     * @var string
     */
    private $httpContent;

    /**
     * @var string
     */
    private $outputFormat;

    /**
     * Client constructor.
     */
    public function __construct(AuthorizationTokenInterface $auth) {
        if ($this->httpClient === null):
            $this->httpClient = new Client([
                'base_uri' => $this->baseURL,
                'headers' => ['Authorization' => 'Bearer '.$auth->getAccessToken()]
            ]);
        endif;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return array
     * @throws GuzzleException
     */
    public function createHttpRequest (string $method, string $uri, array $options = []): array {
        $this->httpRequest = $this->httpClient->request($method, $uri, $options);
        $this->setContent($options);
        $this->setOutputFormat();

        return $this->getResponse();
    }

    /**
     * @param array $options
     * @return self
     */
    private function setContent (array $options): self {
        $requestBody = $this->httpRequest->getBody();
        if (isset($options['stream']) && $options['stream'] === true):
            $content = '';
            while (!$requestBody->eof()):
                $content .= $requestBody->read(1024);
            endwhile;
            $requestBody->close();
        else:
            $content = $requestBody->getContents();
        endif;

        $this->httpContent = $content;

        return $this;
    }

    /**
     * @return self
     */
    private function setOutputFormat (): self {
        $contentTypes = $this->httpRequest->getHeader('Content-Type');

        if (!empty($contentTypes)):
            $contentType = '';
            foreach ($contentTypes as $type):
                $arr = explode(';', $type);
                $contentType = $arr[0];
                break;
            endforeach;

            switch ($contentType):
                case 'application/json':
                case 'application/javascript':
                default:
                    $this->outputFormat = self::CLIENT_DEFAULT_FORMAT;
                    break;
            endswitch;
        else:
            $this->outputFormat = self::CLIENT_DEFAULT_FORMAT;
        endif;

        return $this;
    }

    /**
     * @return array
     */
    public function getResponse (): array {
        switch ($this->outputFormat):
            case 'json':
            case 'array':
                $response = json_decode($this->httpContent, true);
                break;
            default:
                $response = [];
                break;
        endswitch;

        return $response;
    }

}