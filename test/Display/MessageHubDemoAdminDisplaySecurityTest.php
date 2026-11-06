<?php declare(strict_types=1);

namespace MessageHubDemo\Test\Display;

use Base3\Api\IAssetResolver;
use Base3\Api\IMvcView;
use Base3\Api\IRequest;
use Base3\Api\ISystemService;
use Base3\Authorization\Api\IAuthorizationService;
use Base3\Authorization\Dto\AuthorizationDecision;
use Base3\Language\Api\ILanguage;
use Base3\LinkTarget\Api\ILinkTargetService;
use Base3\Session\Api\ISession;
use MessageHubDemo\Display\MessageHubDemoAdminDisplay;
use MessagingFoundation\Api\IMessageRenderer;
use MessagingFoundation\Api\IMessageService;
use MessagingFoundation\Api\IMessageTransportRegistry;
use MessagingFoundation\Api\IMessageTypeSynchronizationService;
use PHPUnit\Framework\TestCase;

final class MessageHubDemoAdminDisplaySecurityTest extends TestCase {

	public function testDeniedFeatureRejectsDirectJsonRequestsBeforeAnyMessageIsSent(): void {
		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn(AuthorizationDecision::deny());
		$messages = $this->createMock(IMessageService::class);
		$messages->expects(self::never())->method('sendNow');
		$messages->expects(self::never())->method('enqueue');
		$display = $this->display($authorization, $messages);
		self::assertSame('', $display->getOutput('json'));
		self::assertSame(403, http_response_code());
		http_response_code(200);
	}

	public function testInvalidSessionTokenPreventsMessageActions(): void {
		$authorization = $this->createMock(IAuthorizationService::class);
		$authorization->method('getDefaultAuthority')->willReturn('ilias');
		$authorization->method('decide')->willReturn(AuthorizationDecision::allow());
		$messages = $this->createMock(IMessageService::class);
		$messages->expects(self::never())->method('sendNow');
		$messages->expects(self::never())->method('enqueue');
		$request = $this->createMock(IRequest::class);
		$request->method('server')->willReturn('POST');
		$request->method('getJsonBody')->willReturn(['mode' => 'send-now', 'csrf' => 'wrong']);
		$session = $this->createMock(ISession::class);
		$session->method('get')->willReturn(str_repeat('a', 64));
		$display = $this->display($authorization, $messages, $request, $session);
		$response = json_decode($display->getOutput('json'), true, 512, JSON_THROW_ON_ERROR);
		self::assertFalse($response['ok']);
		self::assertSame(403, http_response_code());
		http_response_code(200);
	}

	private function display(
		IAuthorizationService $authorization,
		IMessageService $messages,
		?IRequest $request = null,
		?ISession $session = null
	): MessageHubDemoAdminDisplay {
		return new MessageHubDemoAdminDisplay(
			$request ?? $this->createStub(IRequest::class),
			$this->createStub(IMvcView::class),
			$this->createStub(IAssetResolver::class),
			$this->createStub(ILinkTargetService::class),
			$this->createStub(ISystemService::class),
			$this->createStub(ILanguage::class),
			$this->createStub(IMessageTransportRegistry::class),
			$this->createStub(IMessageTypeSynchronizationService::class),
			$this->createStub(IMessageRenderer::class),
			$messages,
			$authorization,
			$session ?? $this->createStub(ISession::class)
		);
	}
}
