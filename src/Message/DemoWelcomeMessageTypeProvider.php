<?php declare(strict_types=1);

namespace MessageHubDemo\Message;

use MessagingFoundation\Api\IMessageTypeProvider;

final class DemoWelcomeMessageTypeProvider implements IMessageTypeProvider {

	public static function getName(): string {
		return 'messagehubdemowelcomemessage';
	}

	public function getLabel(): string {
		return 'MessageHub demo welcome message';
	}

	public function getDescription(): string {
		return 'A simple demo message type used to test MessageHub rendering, queueing and delivery.';
	}

	public function getDefaultSubject(): string {
		return 'MessageHub Demo: {{demo_title}}';
	}

	public function getDefaultBodyText(): string {
		return "Hallo {{recipient_name}},\n\n" .
			"dies ist eine Testnachricht aus MessageHub.\n\n" .
			"Demo: {{demo_title}}\n" .
			"Code: {{demo_code}}\n" .
			"Zeitpunkt: {{sent_at}}\n" .
			"System: {{system_name}}\n";
	}

	public function getDefaultBodyHtml(): string {
		return '<p>Hallo {{recipient_name}},</p>' .
			'<p>dies ist eine Testnachricht aus <strong>MessageHub</strong>.</p>' .
			'<ul>' .
			'<li>Demo: {{demo_title}}</li>' .
			'<li>Code: {{demo_code}}</li>' .
			'<li>Zeitpunkt: {{sent_at}}</li>' .
			'<li>System: {{system_name}}</li>' .
			'</ul>';
	}

	public function getPlaceholders(): array {
		return [
			[
				'name' => 'recipient_name',
				'label' => 'Recipient name',
				'description' => 'Display name used in the greeting.',
				'required' => true,
				'example' => 'Max Mustermann'
			], [
				'name' => 'demo_title',
				'label' => 'Demo title',
				'description' => 'Short title of the test message.',
				'required' => true,
				'example' => 'First MessageHub test'
			], [
				'name' => 'demo_code',
				'label' => 'Demo code',
				'description' => 'Random or user-provided reference code.',
				'required' => true,
				'example' => 'MH-12345'
			], [
				'name' => 'sent_at',
				'label' => 'Sent at',
				'description' => 'Formatted runtime timestamp.',
				'required' => true,
				'example' => '2026-06-29 12:34:56'
			], [
				'name' => 'system_name',
				'label' => 'System name',
				'description' => 'Name of the current host or embedded system.',
				'required' => true,
				'example' => 'ILIAS / BASE3'
			]
		];
	}

	public function getSchema(): array {
		return [
			'type' => 'object',
			'properties' => [
				'recipient_name' => ['type' => 'string'],
				'demo_title' => ['type' => 'string'],
				'demo_code' => ['type' => 'string'],
				'sent_at' => ['type' => 'string'],
				'system_name' => ['type' => 'string']
			],
			'required' => ['recipient_name', 'demo_title', 'demo_code', 'sent_at', 'system_name']
		];
	}
}
