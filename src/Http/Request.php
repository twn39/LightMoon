<?php

namespace LightMoon\Http;

use GuzzleHttp\Psr7\UploadedFile;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * HTTP request
 *
 * Extends the Request definition to add methods for accessing incoming data,
 * specifically server parameters, cookies, matched path parameters, query
 * string arguments, body parameters, and upload file information.
 *
 * "Attributes" are discovered via decomposing the request (and usually
 * specifically the URI path), and typically will be injected by the application.
 *
 * Requests are considered immutable; all methods that might change state are
 * implemented such that they retain the internal state of the current
 * message and return a new instance that contains the changed state.
 */
class Request extends \GuzzleHttp\Psr7\Request
{
    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $cookieParams = [];

    /**
     * @var null|array|object
     */
    private $parsedBody;

    /**
     * @var array
     */
    private $queryParams = [];

    /**
     * @var array
     */
    private $uploadedFiles = [];

    /**
     * @param string                               $method       HTTP method
     * @param string|UriInterface                  $uri          URI
     * @param array                                $headers      Request headers
     * @param string|null|resource|StreamInterface $body         Request body
     * @param string                               $version      Protocol version
     */
    public function __construct(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $version = '1.1'
    ) {
        parent::__construct($method, $uri, $headers, $body, $version);
    }

    /**
     * Return an UploadedFile instance array.
     *
     * @param array $files A array which respect $_FILES structure
     * @throws InvalidArgumentException for unrecognized values
     * @return array
     */
    public static function normalizeFiles(array $files)
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value);
                continue;
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     * @return array|UploadedFileInterface
     */
    private static function createUploadedFileFromSpec(array $value)
    {
        if (is_array($value['tmp_name'])) {
            return self::normalizeNestedFileSpec($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @param array $files
     * @return UploadedFileInterface[]
     */
    private static function normalizeNestedFileSpec(array $files = [])
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }

    public static function fromSwoole($request)
    {
        $method = $request->server['request_method'];
        $headers = $request->header;
        $uri = self::getUriFromSwoole($request);
        if($request->rawContent()) {
            $body = $request->rawContent();
        } else {
            $body = '';
        }
        list($scheme, $protocol) = explode('/', $request->server['server_protocol']);

        $serverRequest = new static($method, $uri, $headers, $body, $protocol);

        $cookie = isset($request->cookie)
            ? $request->cookie
            : [];

        $get = isset($request->get)
            ? $request->get
            : [];

        $post = isset($request->post)
            ? $request->post
            : [];

        $files = isset($request->files)
            ? $request->files
            : [];

        return $serverRequest
            ->withCookieParams($cookie)
            ->withQueryParams($get)
            ->withParsedBody($post)
            ->withUploadedFiles(self::normalizeFiles($files));
    }

    /**
     * Get a Uri populated with values from $_SERVER.
     *
     * @param $request
     * @return UriInterface
     */
    public static function getUriFromSwoole($request)
    {

        $uri = new Uri('');
        list($scheme, $protocol) = explode('/', $request->server['server_protocol']);

        $uri = $uri->withScheme($scheme);

        $hasPort = false;
        if (isset($request->header['host'])) {
            $hostHeaderParts = explode(':', $request->header['host']);
            $uri = $uri->withHost($hostHeaderParts[0]);
            if (isset($hostHeaderParts[1])) {
                $hasPort = true;
                $uri = $uri->withPort($hostHeaderParts[1]);
            }
        }

        if (!$hasPort && isset($request->server['server_port'])) {
            $uri = $uri->withPort($request->server['server_port']);
        }

        $uri = $uri->withPath($request->server['request_uri']);
        $queryString = isset($request->server['query_string'])
            ? $request->server['query_string']
            : '';
        $uri = $uri->withQuery($queryString);

        return $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getParam($key, $default = null)
    {
        if (false === array_key_exists($key, $this->parsedBody)) {
            return $default;
        }
        return $this->parsedBody[$key];
    }

    /**
     * @return array|null|object
     */
    public function getParams()
    {
        return $this->parsedBody;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public function getQueryParam($key, $default = null)
    {
        if (false === array_key_exists($key, $this->queryParams)) {
            return $default;
        }
        return $this->queryParams[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($attribute, $default = null)
    {
        if (false === array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($attribute, $value)
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;

        return $new;
    }

    /**
     * @param array $attributes
     * @return Request
     */
    public function withAttributes(array $attributes)
    {
        $new  = clone $this;
        $new->attributes = $attributes;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($attribute)
    {
        if (false === array_key_exists($attribute, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);

        return $new;
    }
}
