<?php declare(strict_types=1);

namespace MessageHubDemo\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Api\ISystemService;
use Base3\Language\Api\ILanguage;
use Base3\LinkTarget\Api\ILinkTargetService;
use MessageHubDemo\Message\DemoWelcomeMessageTypeProvider;
use MessagingFoundation\Api\IMessageRenderer;
use MessagingFoundation\Api\IMessageService;
use MessagingFoundation\Api\IMessageTransport;
use MessagingFoundation\Api\IMessageTransportRegistry;
use MessagingFoundation\Api\IMessageTypeSynchronizationService;
use MessagingFoundation\Dto\MessageAddress;
use Throwable;

final class MessageHubDemoAdminDisplay implements IDisplay {

	public function __construct(
		private readonly IRequest $request,
		private readonly IMvcView $view,
		private readonly IAssetResolver $assetResolver,
		private readonly ILinkTargetService $linkTargetService,
		private readonly ISystemService $systemService,
		private readonly ILanguage $language,
		private readonly IMessageTransportRegistry $transportRegistry,
		private readonly IMessageTypeSynchronizationService $messageTypeSynchronizationService,
		private readonly IMessageRenderer $messageRenderer,
		private readonly IMessageService $messageService
	) {}

	public static function getName(): string {
		return 'messagehubdemoadmindisplay';
	}

	public function setData($data) {
		// no-op
	}

	public function getOutput(string $out = 'html', bool $final = false): string {
		$out = strtolower((string) $out);

		if($out === 'json') {
			return $this->handleJson($final);
		}

		return $this->handleHtml();
	}

	public function getHelp(): string {
		return 'MessageHub demo display.';
	}

	private function handleHtml(): string {
		$languageOptions = $this->getLanguageOptions();
		$transportOptions = $this->getEnabledTransportOptions();

		$this->view->setPath(DIR_PLUGIN . 'MessageHubDemo');
		$this->view->loadBricks('Display');
		$translations = $this->view->getBricks('messagehub_demo_admin_display');
		$translations = is_array($translations) ? $translations : [];
		$this->view->setTemplate('Display/MessageHubDemoAdminDisplay.php');
		$this->view->assign('translations', $translations);
		$this->view->assign(
			'service',
			$this->linkTargetService->getLink(
				[
					'name' => self::getName(),
					'out' => 'json'
				]
			)
		);
		$this->view->assign('resolve', fn($src) => $this->assetResolver->resolve((string) $src));
		$this->view->assign('typeName', DemoWelcomeMessageTypeProvider::getName());
		$this->view->assign('systemName', $this->getSystemName());
		$this->view->assign('demoCode', 'MH-' . date('Ymd-His'));
		$this->view->assign('languageOptions', $languageOptions);
		$this->view->assign('selectedLanguage', $this->getSelectedLanguage($languageOptions));
		$this->view->assign('transportOptions', $transportOptions);
		$this->view->assign('selectedTransport', $this->getSelectedTransport($transportOptions));

		return $this->view->loadTemplate();
	}

	private function handleJson(bool $final = false): string {
		try {
			$response = $this->buildJsonResponse();
		} catch(Throwable $e) {
			$response = [
				'ok' => false,
				'error' => 'MessageHub demo request failed.',
				'details' => $e->getMessage(),
			];
		}

		if($final && !headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
		}

		return (string) json_encode(
			$response,
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function buildJsonResponse(): array {
		$payload = $this->request->getJsonBody();

		if(!is_array($payload)) {
			$payload = [];
		}

		$mode = (string) ($payload['mode'] ?? 'sync');
		$language = $this->normalizeLanguage(
			$this->readString($payload, 'language', $this->language->getLanguage())
		);

		if($mode === 'sync') {
			return $this->messageTypeSynchronizationService->syncOne(DemoWelcomeMessageTypeProvider::getName(), $language);
		}

		if($mode === 'queue' || $mode === 'send-now') {
			return $this->sendDemoMessage($payload, $mode === 'send-now');
		}

		return [
			'ok' => false,
			'error' => 'Unsupported mode: ' . $mode
		];
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	private function sendDemoMessage(array $payload, bool $sendNow): array {
		$language = $this->normalizeLanguage(
			$this->readString($payload, 'language', $this->language->getLanguage())
		);
		$transportOptions = $this->getEnabledTransportOptions();
		$transportName = $this->readString(
			$payload,
			'transport_name',
			$this->getSelectedTransport($transportOptions)
		);
		$recipientAddress = $this->readString($payload, 'recipient_address');
		$recipientName = $this->readString($payload, 'recipient_name', 'MessageHub Demo');

		if(!$this->hasTransportOption($transportOptions, $transportName)) {
			return [
				'ok' => false,
				'error' => 'Please select an enabled message transport.'
			];
		}

		$this->messageTypeSynchronizationService->syncOne(DemoWelcomeMessageTypeProvider::getName(), $language);

		$context = [
			'recipient_name' => $recipientName,
			'demo_title' => $this->readString($payload, 'demo_title', 'MessageHub Demo'),
			'demo_code' => $this->readString($payload, 'demo_code', 'MH-' . date('Ymd-His')),
			'sent_at' => date('Y-m-d H:i:s'),
			'system_name' => $this->readString($payload, 'system_name', $this->getSystemName())
		];

		$message = $this->messageRenderer
			->render(DemoWelcomeMessageTypeProvider::getName(), $language, $context, $transportName)
			->withMetadata([
				'demo' => true,
				'demo_display' => self::getName()
			]);

		if($recipientAddress !== '') {
			$message = $message->withRecipients([
				new MessageAddress('to', $recipientAddress, $recipientName)
			]);
		}

		$queueId = $sendNow
			? $this->messageService->sendNow($message, $transportName)
			: $this->messageService->enqueue($message, $transportName, 100, time());

		return [
			'ok' => true,
			'mode' => $sendNow ? 'send-now' : 'queue',
			'queue_id' => $queueId,
			'type_name' => DemoWelcomeMessageTypeProvider::getName(),
			'transport_name' => $transportName,
			'language' => $language
		];
	}

	/**
	 * @return array<int,array{value:string,label:string}>
	 */
	private function getLanguageOptions(): array {
		$options = [];
		$currentLanguage = trim($this->language->getLanguage());

		foreach($this->language->getLanguages() as $language) {
			$language = trim((string) $language);
			if($language === '' || isset($options[$language])) {
				continue;
			}

			$options[$language] = [
				'value' => $language,
				'label' => $language
			];
		}

		if($currentLanguage !== '' && !isset($options[$currentLanguage])) {
			$options = [
				$currentLanguage => [
					'value' => $currentLanguage,
					'label' => $currentLanguage
				]
			] + $options;
		}

		return array_values($options);
	}

	/**
	 * @param array<int,array{value:string,label:string}> $options
	 */
	private function getSelectedLanguage(array $options): string {
		$currentLanguage = trim($this->language->getLanguage());

		if($this->hasOption($options, $currentLanguage)) {
			return $currentLanguage;
		}

		return isset($options[0]['value']) ? (string) $options[0]['value'] : 'en';
	}

	private function normalizeLanguage(string $language): string {
		$options = $this->getLanguageOptions();

		if($this->hasOption($options, $language)) {
			return $language;
		}

		return $this->getSelectedLanguage($options);
	}

	/**
	 * @return array<int,array{value:string,label:string}>
	 */
	private function getEnabledTransportOptions(): array {
		$options = [];

		foreach($this->transportRegistry->getTransports() as $name => $transport) {
			$settings = $this->transportRegistry->getTransportSettings($name);
			if(!$this->isTransportEnabled($transport, $settings)) {
				continue;
			}

			$options[] = [
				'value' => $name,
				'label' => $transport->getLabel() . ' (' . $name . ')'
			];
		}

		return $options;
	}

	/**
	 * @param array<int,array{value:string,label:string}> $options
	 */
	private function getSelectedTransport(array $options): string {
		$defaultTransport = $this->transportRegistry->getDefaultTransportName();

		if($this->hasTransportOption($options, $defaultTransport)) {
			return $defaultTransport;
		}

		return isset($options[0]['value']) ? (string) $options[0]['value'] : '';
	}

	private function isTransportEnabled(IMessageTransport $transport, array $settings): bool {
		if(array_key_exists('enabled', $settings)) {
			return $this->readBool($settings['enabled'], false);
		}

		$schema = $transport->getSchema();
		$properties = isset($schema['properties']) && is_array($schema['properties']) ? $schema['properties'] : [];
		$enabled = isset($properties['enabled']) && is_array($properties['enabled']) ? $properties['enabled'] : [];

		if(array_key_exists('default', $enabled)) {
			return $this->readBool($enabled['default'], false);
		}

		return $properties === [];
	}

	/**
	 * @param array<int,array{value:string,label:string}> $options
	 */
	private function hasOption(array $options, string $value): bool {
		foreach($options as $option) {
			if((string) ($option['value'] ?? '') === $value) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param array<int,array{value:string,label:string}> $options
	 */
	private function hasTransportOption(array $options, string $value): bool {
		return $value !== '' && $this->hasOption($options, $value);
	}

	private function readBool(mixed $value, bool $default): bool {
		if(is_bool($value)) {
			return $value;
		}

		if(is_scalar($value)) {
			$normalized = strtolower(trim((string) $value));
			if(in_array($normalized, ['1', 'true', 'yes', 'on', 'enabled'], true)) {
				return true;
			}
			if(in_array($normalized, ['0', 'false', 'no', 'off', 'disabled', ''], true)) {
				return false;
			}
		}

		return $default;
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function readString(array $payload, string $key, string $default = ''): string {
		$value = $payload[$key] ?? $default;

		if(!is_scalar($value) && $value !== null) {
			return $default;
		}

		$value = trim((string) $value);

		return $value !== '' ? $value : $default;
	}

	private function getSystemName(): string {
		$host = $this->systemService->getHostSystemName();
		$embedded = $this->systemService->getEmbeddedSystemName();

		if($host !== '' && $embedded !== '' && $host !== $embedded) {
			return $host . ' / ' . $embedded;
		}

		return $embedded !== '' ? $embedded : $host;
	}
}
