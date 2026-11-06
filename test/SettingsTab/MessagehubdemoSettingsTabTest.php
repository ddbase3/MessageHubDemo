<?php declare(strict_types=1);

namespace MessageHubDemo\Test\SettingsTab;

use MessageHubDemo\SettingsTab\MessagehubdemoSettingsTab;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class MessagehubdemoSettingsTabTest extends TestCase {

	public function testDiscoverableSettingsTabOwnsItsFeatureAndParent(): void {
		$tab = (new ReflectionClass(MessagehubdemoSettingsTab::class))->newInstanceWithoutConstructor();
		self::assertSame('messagehubdemosettingstab', MessagehubdemoSettingsTab::getName());
		self::assertSame('messagingsettingstab', $tab->getParentTabName());
		self::assertSame('messagehubdemoadmindisplay', $tab->getDisplayName());
		self::assertSame(150, $tab->getOrder());
		self::assertSame(['group' => 'development'], $tab->getDefaultAudience());
		self::assertSame(MessagehubdemoSettingsTab::getName(), $tab->getPermissions()[0]->permission);
	}
}
