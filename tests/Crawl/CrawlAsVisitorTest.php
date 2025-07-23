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
	#[TestWith(['', '/admin', 200])]
	#[TestWith(['', '/admin/image', 200])]
	#[TestWith(['', '/js/routing', 200])]
	#[TestWith(['', '/crawler/crawlerdata', 200])]
	#[TestWith(['', '/inspection/analysis/json-ld', 200])]
	#[TestWith(['', '/workflow/', 200])]
	#[TestWith(['', '/admin/messenger', 200])]
	#[TestWith(['', '/admin/messenger/transport', 200])]
	#[TestWith(['', '/admin/messenger/_workers', 200])]
	#[TestWith(['', '/admin/messenger/_transports', 200])]
	#[TestWith(['', '/admin/messenger/_snapshot', 200])]
	#[TestWith(['', '/admin/messenger/_recent-messages', 200])]
	#[TestWith(['', '/images', 200])]
	#[TestWith(['', '/', 200])]
	#[TestWith(['', '/admin/image?page=1&sort%5Bcode%5D=DESC', 200])]
	#[TestWith(['', '/admin/image?page=1&sort%5BoriginalUrl%5D=DESC', 200])]
	#[TestWith(['', '/admin/image?page=1&sort%5Bmarking%5D=DESC', 200])]
	public function testRoute(string $username, string $url, string|int|null $expected): void
	{
		parent::loginAsUserAndVisit($username, $url, (int)$expected);
	}
}
