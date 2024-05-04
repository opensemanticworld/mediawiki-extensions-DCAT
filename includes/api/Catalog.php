<?php

use ApiBase;
use ApiFormatRaw;
use ApiResult;
use Wikimedia\ParamValidator\ParamValidator;
use ApiFormatRawFile;

// we need to patch this class, see https://github.com/sweetrdf/easyrdf/issues/47
class CustomSparqlClient extends \EasyRdf\Sparql\Client {

	/**
     * Build http-client object, execute request and return a response
     *
     * @param string $processed_query
     * @param string $type            Should be either "query" or "update"
     *
     * @return Http\Response|\Zend\Http\Response
     * @throws Exception
     */
    protected function executeQuery($processed_query, $type)
    {
		$this->queryUri = $GLOBALS['smwgSparqlEndpoint']['query'];
        $client = \EasyRdf\Http::getDefaultHttpClient();
        $client->resetParameters();

        // Tell the server which response formats we can parse
        $sparql_results_types = array(
            'application/sparql-results+json' => 1.0,
            'application/sparql-results+xml' => 0.8
        );
		$sparql_graph_types = array(
            'application/ld+json' => 1.0,
			'application/rdf+xml' => 0.9,
			'text/turtle' => 0.8,
			'application/n-quads' => 0.7,
			'application/n-triples' => 0.7,
        );

        if ($type == 'update') {
            // accept anything, as "response body of a […] update request is implementation defined"
            // @see http://www.w3.org/TR/sparql11-protocol/#update-success
            $accept = \EasyRdf\Format::getHttpAcceptHeader($sparql_results_types);
            $this->setHeaders($client, 'Accept', $accept);

            $client->setMethod('POST');
            $client->setUri($this->updateUri);
            $client->setRawData($processed_query);
            $this->setHeaders($client, 'Content-Type', 'application/sparql-update');
        } elseif ($type == 'query') {
            $re = '(?:(?:\s*BASE\s*<.*?>\s*)|(?:\s*PREFIX\s+.+:\s*<.*?>\s*))*'.
                '(CONSTRUCT|SELECT|ASK|DESCRIBE)[\W]';

            $result = null;
            $matched = mb_eregi($re, $processed_query, $result);

            if (false === $matched or count($result) !== 2) {
                // non-standard query. is this something non-standard?
                $query_verb = null;
            } else {
                $query_verb = strtoupper($result[1]);
            }

            if ($query_verb === 'SELECT' or $query_verb === 'ASK') {
                // only "results"
                $accept = \EasyRdf\Format::formatAcceptHeader($sparql_results_types);
            } elseif ($query_verb === 'CONSTRUCT' or $query_verb === 'DESCRIBE') {
                // only "graph"
                $accept = \EasyRdf\Format::formatAcceptHeader($sparql_graph_types);
            } else {
                // both
                $accept = \EasyRdf\Format::getHttpAcceptHeader($sparql_results_types);
            }

            $this->setHeaders($client, 'Accept', $accept);

            $encodedQuery = 'query=' . urlencode($processed_query);

            // Use GET if the query is less than 2kB
            // 2046 = 2kB minus 1 for '?' and 1 for NULL-terminated string on server
            if (strlen($encodedQuery) + strlen($this->queryUri) <= 2046) {
                $delimiter = $this->queryUri_has_params ? '&' : '?';

                $client->setMethod('GET');
                $client->setUri($this->queryUri . $delimiter . $encodedQuery);
            } else {
                // Fall back to POST instead (which is un-cacheable)
                $client->setMethod('POST');
                $client->setUri($this->queryUri);
                $client->setRawData($encodedQuery);
                $this->setHeaders($client, 'Content-Type', 'application/x-www-form-urlencoded');
            }
        } else {
            throw new Exception('unexpected request-type: '.$type);
        }

        if ($client instanceof \Zend\Http\Client) {
            return $client->send();
        } else {
            return $client->request();
        }
    }
}

/**
 * Implements file downloads via the action api
 * for consumption by any client, including api-only clients
 * (e. g. via bot password or OAuth)
 * Based on Extension:TimedMediaHandler/includes/ApiTimedText.php
 *
 * @ingroup API
 * @emits error.code timedtext-notfound, invalidlang, invalid-title
 */
class ApiCatalog extends ApiBase {

	/** @var RepoGroup */
	private $repoGroup;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 */
	public function __construct(
		ApiMain $main,
		$action
	) {
		parent::__construct( $main, $action );
		$this->repoGroup = $repoGroup;
	}

	/**
	 * This module uses a raw printer to directly output files
	 *
	 * @return ApiFormatRaw
	 */
	public function getCustomPrinter(): ApiFormatRaw {
		$printer = new ApiFormatRaw( $this->getMain(), null );
		$printer->setFailWithHTTPError( true );
		return $printer;
	}

	/**
	 * @return void
	 * @throws ApiUsageException
	 * @throws MWException
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$formats = [
			"turtle" => ["extension" => "ttl", "mime" => "text/turtle"],
			"jsonld" => ["extension" => "jsonld", "mime" => "application/ld+json"]
		];

		$format = $params['rdf_format'] === null
			? 'jsonld'
			: $params['rdf_format'];

		if ( !array_key_exists( $format, $formats ) ) {
			$this->dieWithError( 'apierror-catalog-unknownformat', $format );
		}

		#$graphStore = new \EasyRdf\Graph($GLOBALS['smwgSparqlEndpoint']['query']);
		$graphStore = new CustomSparqlClient($GLOBALS['smwgSparqlEndpoint']['query']);
		
		$construct_sparql = "CONSTRUCT {?s ?p ?o. ?s <http://test.com/test> '{{SERVER}}'} WHERE {?s ?p ?o} LIMIT 10";
		$construct_sparql = "
		PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
		PREFIX dcat: <http://www.w3.org/ns/dcat#>
		PREFIX dcatde: <http://dcat-ap.de/def/dcatde/>
		PREFIX dct: <http://purl.org/dc/terms/>
		PREFIX foaf: <http://xmlns.com/foaf/0.1/>
		PREFIX hydra: <http://www.w3.org/ns/hydra/core#>
		PREFIX spdx: <http://spdx.org/rdf/terms#>
		PREFIX vcard: <http://www.w3.org/2006/vcard/ns#>
		PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
		PREFIX owl: <http://www.w3.org/2002/07/owl#>
		PREFIX wiki: <{{SERVER}}/id/>
		PREFIX Property: <{{SERVER}}/id/Property-3A>
		PREFIX File: <{{SERVER}}/id/File-3A>
		PREFIX Category: <{{SERVER}}/id/Category-3A>
		PREFIX Item: <{{SERVER}}/id/Item-3A>

		CONSTRUCT {
          	<{{SERVER}}> rdf:type dcat:Catalog .
          	<{{SERVER}}> dct:title 'OSL Catalog' .
          	<{{SERVER}}> dcat:dataset ?d .
			?d rdf:type dcat:Dataset .
			?d dct:title ?name .
          	?d dcat:distribution ?f .
          	?f rdf:type dcat:Distribution
		} WHERE {
			?d Property:HasType Category:OSW0e7fab2262fb4427ad0fa454bc868a0d .
			?d Property:HasLabel-23aux ?name .
          	?d Property:HasFileAttachment ?f .
          	FILTER NOT EXISTS {?d Property:Visible_to 'users'} .
            FILTER NOT EXISTS {?d Property:Visible_to 'whitelist'}
		} LIMIT 10000
		";
		$construct_sparql = str_replace("{{SERVER}}", $GLOBALS['wgServer'], $construct_sparql);
		$graph = $graphStore->query($construct_sparql);

		$res = $graph->serialise($format);

		$context = '
		{
			"odrl": "http://www.w3.org/ns/odrl/2/",
			"xsd": "http://www.w3.org/2001/XMLSchema#",
			"cred": "https://www.w3.org/2018/credentials#",
			"sec": "https://w3id.org/security#",
			"foaf": "http://xmlns.com/foaf/0.1/",
			"cc": "http://creativecommons.org/ns#",
			"dct": "http://purl.org/dc/terms/",
			"dcat": "http://www.w3.org/ns/dcat#",
			"dspace": "https://w3id.org/dspace/2024/1/"
		}';
		if ($format==='jsonld') $res = json_encode(\ML\JsonLD\JsonLD::compact(json_decode($res), json_decode($context)));

		// see https://doc.wikimedia.org/mediawiki-core/master/php/classApiFormatRaw.html#ac7a8488b591600333637c57c6c057a8d
		$result = $this->getResult();
		$result->addValue( null, 'text', $res );
		$result->addValue( null, 'mime', $formats[$format]['mime']);
		$result->addValue( null, 'filename', 'catalog.' . $formats[$format]['extension']);
	}

	/**
	 * @param int $flags
	 *
	 * @return array
	 */
	public function getAllowedParams( $flags = 0 ) {
		$ret = [
			'rdf_format' => [
				ParamValidator::PARAM_TYPE => 'string',
				// The default set of values
				ApiBase::PARAM_DFLT => 'jsonld',
				// All possible values
				ApiBase::PARAM_TYPE => [ 'jsonld', 'turtle' ],
				// Indicate that multiple values are accepted
				ApiBase::PARAM_ISMULTI => false,
			]
		];
		return $ret;
	}

	/**
	 * @see ApiBase::getExamplesMessages()
	 * @return array of examples
	 */
	protected function getExamplesMessages() {
		return [
			'action=catalog&rdf_format=jsonld'
				=> 'apihelp-catalog-example-1',
		];
	}

}