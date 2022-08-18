<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

use App\Entity\Deposit;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

/**
 * Description of PingResult.
 */
class PingResult {
    /**
     * HTTP request response.
     *
     * @var ResponseInterface
     */
    private $response;

    /**
     * Content of the response body.
     *
     * @var string
     */
    private $content;

    /**
     * Parsed XML from the response.
     *
     * @var SimpleXMLElement
     */
    private $xml;

    /**
     * Error from parsing the XML response.
     *
     * @var array|string[]
     */
    private $errors;

    /**
     * Construct a ping result from an HTTP request.
     *
     * @param ResponseInterface $response
     * @param string $errors
     */
    public function __construct(ResponseInterface $response = null, $errors = null) {
        $this->response = $response;
        if ($response) {
            $this->content = $response->getBody()->getContents();
        } else {
            $this->content = '';
        }
        $this->errors = [];
        if ($errors) {
            $this->errors[] = $errors;
        }
        $this->xml = null;

        if ($response) {
            $oldErrors = libxml_use_internal_errors(true);
            $this->xml = simplexml_load_string($this->content);
            if (false === $this->xml) {
                foreach (libxml_get_errors() as $error) {
                    $this->errors[] = "{$error->line}:{$error->column}:{$error->code}:{$error->message}";
                }
            }
            libxml_use_internal_errors($oldErrors);
        }
    }

    /**
     * Get the HTTP response status.
     *
     * @return int
     */
    public function getHttpStatus() {
        if ($this->response) {
            return $this->response->getStatusCode();
        }

        return 500;
    }

    /**
     * Return true if the request generated an error.
     *
     * @return bool
     */
    public function hasError() {
        return count($this->errors) > 0;
    }

    public function addError($error) : void {
        $this->errors[] = $error;
    }

    /**
     * Get the XML processing error.
     *
     * @return string
     */
    public function getError() {
        return implode("\n", $this->errors);
    }

    /**
     * Get the response body.
     *
     * Optionally strips out the tags.
     *
     * @param bool $stripTags
     *
     * @return string
     */
    public function getBody($stripTags = true) {
        if ( ! $this->content) {
            return '';
        }
        if ($stripTags) {
            return strip_tags($this->content);
        }

        return $this->content;
    }

    /**
     * Check if the http response was XML.
     *
     * @return bool
     */
    public function hasXml() {
        return (bool) $this->xml;
    }

    /**
     * Get the response XML.
     *
     * @return SimpleXMLElement
     */
    public function getXml() {
        return $this->xml;
    }

    /**
     * Get an HTTP header.
     *
     * @param string $name
     *
     * @return string
     */
    public function getHeader($name) {
        if ( ! $this->response) {
            return '';
        }

        return $this->response->getHeader($name);
    }

    /**
     * Get the OJS release version.
     *
     * @return string
     */
    public function getOjsRelease() {
        if ( ! $this->xml) {
            return '';
        }

        return Xpath::getXmlValue($this->xml, '//ojsInfo/release', Deposit::DEFAULT_JOURNAL_VERSION);
    }

    /**
     * Get the plugin release version.
     *
     * @return string
     */
    public function getPluginReleaseVersion() {
        if ( ! $this->xml) {
            return '';
        }

        return Xpath::getXmlValue($this->xml, '//pluginInfo/release');
    }

    /**
     * Get the plugin release date.
     *
     * @return string
     */
    public function getPluginReleaseDate() {
        if ( ! $this->xml) {
            return '';
        }

        return Xpath::getXmlValue($this->xml, '//pluginInfo/releaseDate');
    }

    /**
     * Check if the plugin thinks its current.
     *
     * @return string
     */
    public function isPluginCurrent() {
        if ( ! $this->xml) {
            return '';
        }

        return Xpath::getXmlValue($this->xml, '//pluginInfo/current');
    }

    /**
     * Check if the terms of use have been accepted.
     *
     * @return string
     */
    public function areTermsAccepted() {
        if ( ! $this->xml) {
            return '';
        }

        return Xpath::getXmlValue($this->xml, '//terms/@termsAccepted');
    }

    /**
     * Get the journal title from the response.
     *
     * @param null|mixed $default
     *
     * @return string
     */
    public function getJournalTitle($default = null) {
        if ( ! $this->xml) {
            return '';
        }

        return Xpath::getXmlValue($this->xml, '//journalInfo/title', $default);
    }

    /**
     * Get the number of articles the journal has published.
     *
     * @return int
     */
    public function getArticleCount() {
        if ( ! $this->xml) {
            return '';
        }

        return Xpath::getXmlValue($this->xml, '//articles/@count');
    }

    /**
     * Get a list of article titles reported in the response.
     *
     * @return array[]
     *                 Array of associative array data.
     */
    public function getArticleTitles() {
        if ( ! $this->xml) {
            return [];
        }
        $articles = [];
        foreach (Xpath::query($this->xml, '//articles/article') as $node) {
            $articles[] = [
                'date' => (string) $node['pubDate'],
                'title' => trim((string) $node),
            ];
        }

        return $articles;
    }
}
