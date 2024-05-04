# mediawiki-extensions-DCAT
Exports Semantic Mediawiki content as dcat:Catalog

Currently a SPARQLStore is required to construct the dcat RDF.

## Install

In mediawiki root folder
```bash
COMPOSER=composer.local.json composer require --no-update sweetrdf/easyrdf:*
COMPOSER=composer.local.json composer require --no-update ml/json-ld:*
composer update  --no-dev --prefer-source
```

In LocalSettings.php:
```php
wfLoadExtension( 'DCAT' );
```
