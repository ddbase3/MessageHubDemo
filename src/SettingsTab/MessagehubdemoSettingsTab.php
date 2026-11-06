<?php declare(strict_types=1);

namespace MessageHubDemo\SettingsTab;

use UiFoundation\Settings\AbstractAdministrationSettingsTab;

/** Discoverable navigation entry; display operations authorize independently. */
final class MessagehubdemoSettingsTab extends AbstractAdministrationSettingsTab {

	public function getParentTabName(): ?string {
		return 'messagingsettingstab';
	}

	public function getDisplayName(): ?string {
		return 'messagehubdemoadmindisplay';
	}

	public function getOrder(): int {
		return 150;
	}
}
