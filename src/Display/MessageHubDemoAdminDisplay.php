<?php declare(strict_types=1);

namespace MessageHubDemo\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IDisplay;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Api\ISystemService;
use Base3\LinkTarget\Api\ILinkTargetService;
use MessageHubDemo\Message\DemoWelcomeMessageTypeProvider;
use MessagingFoundation\Api\IMessageRenderer;
use MessagingFoundation\Api\IMessageService;
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
		$this->view->setPath(DIR_PLUGIN . 'MessageHubDemo');
		$this->view->setTemplate('Display/MessageHubDemoAdminDisplay.php');
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
		$language = $this->readString($payload, 'language', 'de');

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
		$language = $this->readString($payload, 'language', 'de');
		$transportName = $this->readString($payload, 'transport_name', 'phpmailer');
		$recipientAddress = $this->readString($payload, 'recipient_address');
		$recipientName = $this->readString($payload, 'recipient_name', 'MessageHub Demo');

		if(!filter_var($recipientAddress, FILTER_VALIDATE_EMAIL)) {
			return [
				'ok' => false,
				'error' => 'Please provide a valid recipient email address.'
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
			->withRecipients([
				new MessageAddress('to', $recipientAddress, $recipientName)
			])
			->withMetadata([
				'demo' => true,
				'demo_display' => self::getName()
			]);

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
