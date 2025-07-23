<?php

namespace App\Tests\Crawl;

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\TestWith;
use Survos\CrawlerBundle\Tests\BaseVisitLinksTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CrawlAsVisitorTest extends BaseVisitLinksTest
{
	#[TestDox('/$method $url ($route)')]
	#[TestWith(['', '/api/docs', 200])]
	#[TestWith(['', '/api', 200])]
	#[TestWith(['', '/api/images', 200])]
	#[TestWith(['', '/api/products', 200])]
	#[TestWith(['', '/js/routing', 200])]
	#[TestWith(['', '/crawler/crawlerdata', 200])]
	#[TestWith(['', '/workflow/', 200])]
	#[TestWith(['', '/batch-translate', 500])]
	#[TestWith(['', '/webhook/media', 400])]
	#[TestWith(['', '/webhook/thumb', 404])]
	#[TestWith(['', '/images', 200])]
	#[TestWith(['', '/', 200])]
	public function testRoute(string $username, string $url, string|int|null $expected): void
	{
		parent::loginAsUserAndVisit($username, $url, (int)$expected);
	}
}
