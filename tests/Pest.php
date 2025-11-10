<?php

use TheJano\MultiLang\Tests\TestCase;

// Feature tests use TestCase (with database)
uses(TestCase::class)->in('Feature');

// Unit tests can also use TestCase for Laravel features, but don't need database migrations
// Individual unit test files can choose to use TestCase or not
uses(TestCase::class)->in('Unit');
