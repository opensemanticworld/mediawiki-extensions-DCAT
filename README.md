# mediawiki-extensions-RdfExport
Exports Semantic Mediawiki content as dcat:Catalog or owl:Ontology

Currently a SPARQLStore is required to construct the RDF.

## Install

In mediawiki root folder
```bash
COMPOSER=composer.local.json composer require --no-update sweetrdf/easyrdf:*
COMPOSER=composer.local.json composer require --no-update ml/json-ld:*
composer update  --no-dev --prefer-source
```

In LocalSettings.php:
```php
wfLoadExtension( 'RdfExport' );
```

## Usage

### Catalog

Navigate to `Special:ApiSandbox#action=rdfexport-dcat&format=json&rdf_format=turtle` or direct to `api.php?action=rdfexport-dcat&format=json&rdf_format=turtle`

Example: https://demo.open-semantic-lab.org/wiki/Special:ApiSandbox#action=rdfexport-dcat&format=json&rdf_format=turtle or https://demo.open-semantic-lab.org/w/api.php?action=rdfexport-dcat&format=json&rdf_format=turtle

Validation: https://www.itb.ec.europa.eu/shacl/dcat-ap/upload

### Ontology
Assuming you have created an ontology with ID `Item:OSWe9e86af83de842a68124d81c9792fe22`:

Navigate to `Special:ApiSandbox#action=rdfexport-owl&format=json&rdf_format=turtle&title=Item%3AOSWe9e86af83de842a68124d81c9792fe22` or direct to `api.php?action=rdfexport-owl&format=json&rdf_format=turtle&title=Item%3AOSWe9e86af83de842a68124d81c9792fe22`

Example: https://demo.open-semantic-lab.org/wiki/Special:ApiSandbox#action=rdfexport-owl&format=json&rdf_format=turtle&title=Item%3AOSWe9e86af83de842a68124d81c9792fe22 or https://demo.open-semantic-lab.org/w/api.php?action=rdfexport-owl&format=json&rdf_format=turtle&title=Item%3AOSWe9e86af83de842a68124d81c9792fe22
