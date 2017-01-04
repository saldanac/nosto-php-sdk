<?php
/**
 * Copyright (c) 2017, Nosto Solutions Ltd
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its contributors
 * may be used to endorse or promote products derived from this software without
 * specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Nosto Solutions Ltd <contact@nosto.com>
 * @copyright 2017 Nosto Solutions Ltd
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 *
 */

/**
 * Helper class for doing http requests and returning unified response including header info.
 */
class NostoHttpRequest
{
    const AUTH_BASIC = 'basic';
    const AUTH_BEARER = 'bearer';

    const PATH_ACCOUNT_DELETED = '/hub/uninstall';
    const PATH_SSO_AUTH = '/hub/{platform}/load/{email}';
    const PATH_OAUTH_SYNC = '/oauth/exchange';

    /**
     * @var string base url for the nosto web hook requests.
     */
    public static $baseUrl = 'https://my.nosto.com';

    /**
     * @var string user-agent to use for all requests
     */
    public static $userAgent = '';

    /**
     * @var int timeout for waiting response from the api
     */
    public static $responseTimeout = 5;

    /**
     * @var int timeout for connecting to the api
     */
    public static $connectTimeout = 5;

    /**
     * @var string the request url.
     */
    private $url;

    /**
     * @var array list of headers to include in the requests.
     */
    private $headers = array();

    /**
     * @var string the request content (populated in post() and put() methods).
     */
    private $content = '';

    /**
     * @var array list of optional query params that are added to the request url.
     */
    private $queryParams = array();

    /**
     * @var array list of optional replace params that can be injected into the url if it contains placeholders.
     */
    private $replaceParams = array();

    /**
     * @var NostoHttpRequestAdapter the adapter to use for making the request.
     */
    private $adapter;

    /**
     * Constructor.
     * Creates the http request adapter which is chosen automatically by default based on environment.
     * Curl is preferred if available.
     *
     * @param NostoHttpRequestAdapter|null $adapter the http request adapter to use
     * @throws NostoException
     */
    public function __construct(NostoHttpRequestAdapter $adapter = null)
    {
        if ($adapter !== null) {
            $this->adapter = $adapter;
        } elseif (function_exists('curl_exec')) {
            $this->adapter = new NostoHttpRequestAdapterCurl(self::$userAgent);
        } else {
            $this->adapter = new NostoHttpRequestAdapterSocket(self::$userAgent);
        }
    }

    /**
     * Replaces or adds a query parameter to a url.
     *
     * @param string $param the query param name to replace.
     * @param mixed $value the query param value to replace.
     * @param string $url the url.
     * @return string the updated url.
     */
    public static function replaceQueryParamInUrl($param, $value, $url)
    {
        $parsedUrl = self::parseUrl($url);
        $queryString = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
        $queryString = self::replaceQueryParam($param, $value, $queryString);
        $parsedUrl['query'] = $queryString;
        return self::buildUrl($parsedUrl);
    }

    /**
     * Parses the given url and returns the parts as an array.
     *
     * @see http://php.net/manual/en/function.parse-url.php
     * @param string $url the url to parse.
     * @return array the parsed url as an array.
     */
    public static function parseUrl($url)
    {
        return parse_url($url);
    }

    /**
     * Replaces a parameter in a query string with given value.
     *
     * @param string $param the query param name to replace.
     * @param mixed $value the query param value to replace.
     * @param string $queryString the query string.
     * @return string the updated query string.
     */
    public static function replaceQueryParam($param, $value, $queryString)
    {
        $parsedQuery = self::parseQueryString($queryString);
        $parsedQuery[$param] = $value;
        return http_build_query($parsedQuery);
    }

    /**
     * Parses the given query string and returns the parts as an assoc array.
     *
     * @see http://php.net/manual/en/function.parse-str.php
     * @param string $queryString the query string to parse.
     * @return array the parsed string as assoc array.
     */
    public static function parseQueryString($queryString)
    {
        if (empty($queryString)) {
            return array();
        }
        parse_str($queryString, $parsedQueryString);
        return $parsedQueryString;
    }

    /**
     * Builds a url based on given parts.
     *
     * @see http://php.net/manual/en/function.parse-url.php
     * @param array $parts part(s) of an URL in form of a string or associative array like parseUrl() returns.
     * @return string
     */
    public static function buildUrl(array $parts)
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = isset($parts['host']) ? $parts['host'] : '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user = isset($parts['user']) ? $parts['user'] : '';
        $pass = isset($parts['pass']) ? ':' . $parts['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parts['path']) ? $parts['path'] : '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
    }

    /**
     * Replaces or adds a query parameters to a url.
     *
     * @param array $queryParams the query params to replace.
     * @param string $url the url.
     * @return string the updated url.
     */
    public static function replaceQueryParamsInUrl(array $queryParams, $url)
    {
        if (empty($queryParams)) {
            return $url;
        }
        $parsedUrl = self::parseUrl($url);
        $queryString = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';
        foreach ($queryParams as $param => $value) {
            $queryString = self::replaceQueryParam($param, $value, $queryString);
        }
        $parsedUrl['query'] = $queryString;
        return self::buildUrl($parsedUrl);
    }

    /**
     * Builds the custom-user agent by using the platform's name and version with the
     * plugin version
     *
     * @param string $platformName the name of the platform using the SDK
     * @param array $platformVersion the version of the platform using the SDK
     * @param array $pluginVersion the version of the plugin using the SDK
     */
    public static function buildUserAgent($platformName, $platformVersion, $pluginVersion)
    {
        self::$userAgent = sprintf(
            'Nosto %s / %s %s',
            $pluginVersion,
            $platformName,
            $platformVersion
        );
    }

    /**
     * Setter for the content type to add to the request header.
     *
     * @param string $contentType the content type.
     */
    public function setContentType($contentType)
    {
        $this->addHeader('Content-type', $contentType);
    }

    /**
     * Adds a new header to the request.
     *
     * @param string $key the header key, e.g. 'Content-type'.
     * @param string $value the header value, e.g. 'application/json'.
     */
    public function addHeader($key, $value)
    {
        $this->headers[] = $key . ': ' . $value;
    }

    /**
     * Returns the registered headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns the registered query params.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Setter for the request url query params.
     *
     * @param array $queryParams the query params.
     */
    public function setQueryParams($queryParams)
    {
        $this->queryParams = $queryParams;
    }

    /**
     * Setter for the request url replace params.
     *
     * @param array $replaceParams the replace params.
     */
    public function setReplaceParams($replaceParams)
    {
        $this->replaceParams = $replaceParams;
    }

    /**
     * Convenience method for setting the basic auth type.
     *
     * @param string $username the user name.
     * @param string $password the password.
     */
    public function setAuthBasic($username, $password)
    {
        $this->setAuth(self::AUTH_BASIC, array($username, $password));
    }

    /**
     * Setter for the request authentication header.
     *
     * @param string $type the auth type (use AUTH_ constants).
     * @param mixed $value the auth header value, format depending on the auth type.
     * @throws Exception if an incorrect auth type is given.
     */
    public function setAuth($type, $value)
    {
        switch ($type) {
            case self::AUTH_BASIC:
                // The use of base64 encoding for authorization headers follow the RFC 2617 standard for http
                // authentication (https://www.ietf.org/rfc/rfc2617.txt).
                $this->addHeader('Authorization', 'Basic ' . base64_encode(implode(':', $value)));
                break;

            case self::AUTH_BEARER:
                $this->addHeader('Authorization', 'Bearer ' . $value);
                break;

            default:
                throw new NostoException('Unsupported auth type.');
        }
    }

    /**
     * Convenience method for setting the bearer auth type.
     *
     * @param string $token the access token.
     */
    public function setAuthBearer($token)
    {
        $this->setAuth(self::AUTH_BEARER, $token);
    }

    /**
     * Setter for the end point path, e.g. one of the PATH_ constants.
     * The API base url is always prepended.
     *
     * @param string $path the endpoint path (use PATH_ constants).
     */
    public function setPath($path)
    {
        $this->setUrl(self::$baseUrl . $path);
    }

    /**
     * Setter for the request url.
     *
     * @param string $url the url.
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Sends a POST request.
     *
     * @param NostoSerializableInterface $content
     * @return NostoHttpResponse
     */
    public function post(NostoSerializableInterface $content)
    {
        $this->content = json_encode($content->getArray());
        $url = $this->url;
        if (!empty($this->replaceParams)) {
            $url = self::buildUri($url, $this->replaceParams);
        }
        return $this->adapter->post(
            $url,
            array(
                'headers' => $this->headers,
                'content' => $content,
            )
        );
    }

    /**
     * Builds an uri by replacing the param placeholders in $uri with the ones given in $$replaceParams.
     *
     * @param string $uri
     * @param array $replaceParams
     * @return string
     */
    public static function buildUri($uri, array $replaceParams)
    {
        return strtr($uri, $replaceParams);
    }

    /**
     * Sends a PUT request.
     *
     * @param NostoSerializableInterface $content
     * @return NostoHttpResponse
     */
    public function put(NostoSerializableInterface $content)
    {
        $this->content = json_encode($content->getArray());
        $url = $this->url;
        if (!empty($this->replaceParams)) {
            $url = self::buildUri($url, $this->replaceParams);
        }
        return $this->adapter->put(
            $url,
            array(
                'headers' => $this->headers,
                'content' => $content,
            )
        );
    }

    /**
     * Sends a GET request.
     *
     * @return NostoHttpResponse
     */
    public function get()
    {
        $url = $this->url;
        if (!empty($this->replaceParams)) {
            $url = self::buildUri($url, $this->replaceParams);
        }
        if (!empty($this->queryParams)) {
            $url .= '?' . http_build_query($this->queryParams);
        }
        return $this->adapter->get(
            $url,
            array(
                'headers' => $this->headers,
            )
        );
    }

    /**
     * Sends a DELETE request.
     *
     * @return NostoHttpResponse
     */
    public function delete()
    {
        $url = $this->url;
        if (!empty($this->replaceParams)) {
            $url = self::buildUri($url, $this->replaceParams);
        }
        return $this->adapter->delete(
            $url,
            array(
                'headers' => $this->headers,
            )
        );
    }

    /**
     * Converts the request to a string and returns it.
     * Used when logging http request errors.
     */
    public function __toString()
    {
        $url = $this->url;
        if (!empty($this->replaceParams)) {
            $url = self::buildUri($url, $this->replaceParams);
        }
        return serialize(
            array(
                'url' => $url,
                'headers' => $this->headers,
                'body' => $this->content,
            )
        );
    }
}
