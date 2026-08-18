<?php
$serviceUrl = (string) $this->_['service'];
$typeName = (string) $this->_['typeName'];
$systemName = (string) $this->_['systemName'];
$demoCode = (string) $this->_['demoCode'];
$languageOptions = is_array($this->_['languageOptions'] ?? null) ? $this->_['languageOptions'] : [];
$selectedLanguage = (string) ($this->_['selectedLanguage'] ?? '');
$transportOptions = is_array($this->_['transportOptions'] ?? null) ? $this->_['transportOptions'] : [];
$selectedTransport = (string) ($this->_['selectedTransport'] ?? '');
$translations = is_array($this->_['translations'] ?? null) ? $this->_['translations'] : [];
$e = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$t = static fn(string $key, string $fallback): string => trim((string)($translations[$key] ?? '')) !== ''
	? (string)$translations[$key]
	: $fallback;
?>
<style>
	.messagehub-demo-shell {
		max-width: 1100px;
	}

	.messagehub-demo-shell h1 {
		margin: 0 0 8px 0;
		font-size: 24px;
		line-height: 1.2;
		font-weight: 600;
	}

	.messagehub-demo-shell p {
		margin: 0 0 16px 0;
		max-width: 900px;
		color: #555;
		line-height: 1.45;
	}

	.messagehub-demo-panel,
	.messagehub-demo-result {
		margin: 12px 0;
		padding: 12px;
		border: 1px solid #e2e2e2;
		border-radius: 8px;
		background: #fff;
	}

	.messagehub-demo-form {
		display: grid;
		grid-template-columns: minmax(180px, 240px) minmax(280px, 1fr);
		gap: 10px 14px;
		align-items: center;
		max-width: 900px;
	}

	.messagehub-demo-form label {
		color: #555;
		font-size: 13px;
	}

	.messagehub-demo-form input,
	.messagehub-demo-form select {
		width: 100%;
		min-height: 30px;
		padding: 4px 6px;
		border: 1px solid #cfcfcf;
		border-radius: 4px;
		font: inherit;
		font-size: 13px;
	}

	.messagehub-demo-hint {
		grid-column: 2;
		margin-top: -6px;
		color: #777;
		font-size: 12px;
		line-height: 1.35;
	}

	.messagehub-demo-actions {
		display: flex;
		gap: 8px;
		align-items: center;
		flex-wrap: wrap;
		margin-top: 14px;
	}

	.messagehub-demo-button {
		appearance: none;
		border: 1px solid #cfcfcf;
		border-radius: 4px;
		background: #fff;
		color: #222;
		cursor: pointer;
		font: inherit;
		font-size: 13px;
		line-height: 1.3;
		min-height: 28px;
		padding: 4px 10px;
		white-space: nowrap;
	}

	.messagehub-demo-button:disabled {
		cursor: not-allowed;
		opacity: 0.55;
	}

	.messagehub-demo-button-primary {
		background: #2f5d91;
		border-color: #2f5d91;
		color: #fff;
	}

	.messagehub-demo-result pre {
		margin: 0;
		white-space: pre-wrap;
	}

	.messagehub-demo-type {
		display: inline-flex;
		padding: 2px 6px;
		border: 1px solid #d6d6d6;
		border-radius: 999px;
		background: #fafafa;
		font-size: 12px;
	}
</style>
<div class="messagehub-demo-shell">
	<h1><?php echo $e($t('title', 'MessageHub Demo')); ?></h1>
	<p><?php echo $e($t('lead', 'This display is a small consumer plugin for MessageHub. It provides one message type, synchronizes it into MessageHub templates and sends a test message through an enabled transport.')); ?></p>
	<p><?php echo $e($t('message_type', 'Message type')); ?>: <span class="messagehub-demo-type"><?php echo htmlspecialchars($typeName, ENT_QUOTES); ?></span></p>

	<div class="messagehub-demo-panel">
		<div class="messagehub-demo-form">
			<label for="messagehub-demo-recipient-address"><?php echo $e($t('recipient_address', 'Recipient address')); ?></label>
			<input id="messagehub-demo-recipient-address" type="text" value="" placeholder="<?php echo $e($t('recipient_placeholder', 'Email, phone number, chat ID or topic')); ?>" autocomplete="off" />
			<div class="messagehub-demo-hint"><?php echo $e($t('recipient_hint', 'Some webhook, log and null transports do not require a recipient address.')); ?></div>

			<label for="messagehub-demo-recipient-name"><?php echo $e($t('recipient_name', 'Recipient name')); ?></label>
			<input id="messagehub-demo-recipient-name" type="text" value="<?php echo $e($t('recipient_name_default', 'MessageHub Demo')); ?>" />

			<label for="messagehub-demo-title"><?php echo $e($t('demo_title', 'Demo title')); ?></label>
			<input id="messagehub-demo-title" type="text" value="<?php echo $e($t('demo_title_default', 'First MessageHub test')); ?>" />

			<label for="messagehub-demo-code"><?php echo $e($t('demo_code', 'Demo code')); ?></label>
			<input id="messagehub-demo-code" type="text" value="<?php echo htmlspecialchars($demoCode, ENT_QUOTES); ?>" />

			<label for="messagehub-demo-system-name"><?php echo $e($t('system_name', 'System name')); ?></label>
			<input id="messagehub-demo-system-name" type="text" value="<?php echo htmlspecialchars($systemName, ENT_QUOTES); ?>" />

			<label for="messagehub-demo-language"><?php echo $e($t('language', 'Language')); ?></label>
			<select id="messagehub-demo-language">
				<?php foreach($languageOptions as $option): ?>
					<?php $value = (string) ($option['value'] ?? ''); ?>
					<option value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>"<?php echo $value === $selectedLanguage ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($option['label'] ?? $value), ENT_QUOTES); ?></option>
				<?php endforeach; ?>
			</select>

			<label for="messagehub-demo-transport"><?php echo $e($t('transport', 'Transport')); ?></label>
			<select id="messagehub-demo-transport">
				<?php if($transportOptions === []): ?>
					<option value=""><?php echo $e($t('no_enabled_transports', 'No enabled transports')); ?></option>
				<?php else: ?>
					<?php foreach($transportOptions as $option): ?>
						<?php $value = (string) ($option['value'] ?? ''); ?>
						<option value="<?php echo htmlspecialchars($value, ENT_QUOTES); ?>"<?php echo $value === $selectedTransport ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) ($option['label'] ?? $value), ENT_QUOTES); ?></option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
		</div>

		<div class="messagehub-demo-actions">
			<button type="button" class="messagehub-demo-button" id="messagehub-demo-sync"><?php echo $e($t('sync_template', 'Sync template')); ?></button>
			<button type="button" class="messagehub-demo-button messagehub-demo-button-primary" id="messagehub-demo-queue"><?php echo $e($t('queue_message', 'Queue message')); ?></button>
			<button type="button" class="messagehub-demo-button" id="messagehub-demo-send-now"><?php echo $e($t('send_now', 'Send now')); ?></button>
		</div>
	</div>

	<div class="messagehub-demo-result"><pre id="messagehub-demo-result"><?php echo $e($t('ready', 'Ready.')); ?></pre></div>
</div>
<script>
(() => {
	const serviceUrl = <?php echo json_encode($serviceUrl, JSON_UNESCAPED_SLASHES); ?>;
	const I18N = <?php echo json_encode($translations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
	const resultElement = document.getElementById('messagehub-demo-result');
	const transportElement = document.getElementById('messagehub-demo-transport');
	const queueButton = document.getElementById('messagehub-demo-queue');
	const sendNowButton = document.getElementById('messagehub-demo-send-now');

	function tr(key, fallback, replacements = {}) { let text = String(I18N[key] || fallback || key); Object.entries(replacements).forEach(([name, value]) => { text = text.split('{' + name + '}').join(String(value)); }); return text; }

	function value(id) {
		return document.getElementById(id).value || '';
	}

	function updateTransportActions() {
		const enabled = !!(transportElement && transportElement.value);
		queueButton.disabled = !enabled;
		sendNowButton.disabled = !enabled;
	}

	function payload(mode) {
		return {
			mode,
			recipient_address: value('messagehub-demo-recipient-address'),
			recipient_name: value('messagehub-demo-recipient-name'),
			demo_title: value('messagehub-demo-title'),
			demo_code: value('messagehub-demo-code'),
			system_name: value('messagehub-demo-system-name'),
			language: value('messagehub-demo-language'),
			transport_name: value('messagehub-demo-transport')
		};
	}

	async function postJson(data) {
		const response = await fetch(serviceUrl, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(data)
		});

		return await response.json();
	}

	async function execute(mode) {
		resultElement.textContent = tr('running_mode', 'Running {mode} ...', { mode });
		const result = await postJson(payload(mode));
		resultElement.textContent = JSON.stringify(result, null, 2);
	}

	document.getElementById('messagehub-demo-sync').addEventListener('click', () => execute('sync'));
	queueButton.addEventListener('click', () => execute('queue'));
	sendNowButton.addEventListener('click', () => execute('send-now'));
	transportElement.addEventListener('change', updateTransportActions);
	updateTransportActions();
})();
</script>
