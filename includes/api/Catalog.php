<?php

use ApiBase;
use ApiFormatRaw;
use ApiResult;
use Wikimedia\ParamValidator\ParamValidator;
use ApiFormatRawFile;

/**
 * Implements file downloads via the action api
 * for consumption by any client, including api-only clients
 * (e. g. via bot password or OAuth)
 * Based on Extension:TimedMediaHandler/includes/ApiTimedText.php
 *
 * @ingroup API
 * @emits error.code timedtext-notfound, invalidlang, invalid-title
 */
class ApiCatalog extends ApiLinkedDataExport {

	protected function getSparqlConstructQuery( $param ) {
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

		return $construct_sparql;
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