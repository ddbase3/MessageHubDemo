<?php declare(strict_types=1);

namespace MessageHubDemo;

use Base3\Api\IContainer;
use Base3\Api\IPlugin;

final class MessageHubDemoPlugin implements IPlugin {

	public function __construct(
		private readonly IContainer $container
	) {}

	public static function getName(): string {
		return 'messagehubdemoplugin';
	}

	public function init() {
		$this->container->set(
			self::getName(),
			$this,
			IContainer::SHARED
		);
	}
}
