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
class ApiOntology extends ApiLinkedDataExport {

	protected function getSparqlConstructQuery( $params ) {


		$ontology = $params['title'];

		#$page = $this->getTitleOrPageId( $params );
		#if ( !$page->exists() ) {
		#	$this->dieWithError( 'apierror-missingtitle', 'download-notfound' );
		#}

		# note: Protege expects restrictions to be bnodes, otherwise the are displayed as classes
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
			{{ONTOLOGY}} rdf:type owl:Ontology .
			{{ONTOLOGY}} dct:title ?olabel .
			Category:OSW379d5a1589c74c82bc0de47938264d00 rdf:type owl:Class .
			Category:OSW379d5a1589c74c82bc0de47938264d00 rdfs:label 'OwlClass' .
			?c rdf:type owl:Class .
			?c rdfs:label ?name .
			?c rdfs:subClassOf ?supc .
			?c rdfs:subClassOf _:r .
			_:r rdf:type ?rt .
			_:r owl:onProperty ?rp .
			_:r owl:someValuesFrom ?ro
		} WHERE {
			{{ONTOLOGY}} rdfs:label ?olabel .
			?c Property:IsPartOf {{ONTOLOGY}} .
			?c Property:HasLabel-23aux ?label .
			?c rdfs:label ?name .
			?c Property:SubClassOf ?supc .
			OPTIONAL {
				BIND ( owl:Restriction AS ?rt) .
				?c Property:HasRestriction ?r .
				?r Property:HasProperty ?rp .
				?r Property:HasObject ?ro .
			} .
			FILTER NOT EXISTS {?c Property:Visible_to 'users'} .
			FILTER NOT EXISTS {?c Property:Visible_to 'whitelist'}
		} LIMIT 10000
		"; 
		$construct_sparql = str_replace("{{ONTOLOGY}}", $ontology, $construct_sparql);
		return $construct_sparql;
	}

	/**
	 * @param int $flags
	 *
	 * @return array
	 */
	public function getAllowedParams( $flags = 0 ) {
		$ret = parent::getAllowedParams ( $flags );
		$ret['title'] = [
				ParamValidator::PARAM_TYPE => 'string'
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