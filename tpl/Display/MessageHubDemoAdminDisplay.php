<?php
$serviceUrl = (string) $this->_['service'];
$typeName = (string) $this->_['typeName'];
$systemName = (string) $this->_['systemName'];
$demoCode = (string) $this->_['demoCode'];
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
	<h1>MessageHub Demo</h1>
	<p>This display is a small consumer plugin for MessageHub. It provides one message type, synchronizes it into MessageHub templates and sends a test message through the configured transport.</p>
	<p>Message type: <span class="messagehub-demo-type"><?php echo htmlspecialchars($typeName, ENT_QUOTES); ?></span></p>

	<div class="messagehub-demo-panel">
		<div class="messagehub-demo-form">
			<label for="messagehub-demo-recipient-address">Recipient email</label>
			<input id="messagehub-demo-recipient-address" type="email" value="" placeholder="name@example.org" autocomplete="email" />

			<label for="messagehub-demo-recipient-name">Recipient name</label>
			<input id="messagehub-demo-recipient-name" type="text" value="MessageHub Demo" />

			<label for="messagehub-demo-title">Demo title</label>
			<input id="messagehub-demo-title" type="text" value="First MessageHub test" />

			<label for="messagehub-demo-code">Demo code</label>
			<input id="messagehub-demo-code" type="text" value="<?php echo htmlspecialchars($demoCode, ENT_QUOTES); ?>" />

			<label for="messagehub-demo-system-name">System name</label>
			<input id="messagehub-demo-system-name" type="text" value="<?php echo htmlspecialchars($systemName, ENT_QUOTES); ?>" />

			<label for="messagehub-demo-language">Language</label>
			<input id="messagehub-demo-language" type="text" value="de" maxlength="12" />

			<label for="messagehub-demo-transport">Transport</label>
			<input id="messagehub-demo-transport" type="text" value="phpmailer" />
		</div>

		<div class="messagehub-demo-actions">
			<button type="button" class="messagehub-demo-button" id="messagehub-demo-sync">Sync template</button>
			<button type="button" class="messagehub-demo-button messagehub-demo-button-primary" id="messagehub-demo-queue">Queue message</button>
			<button type="button" class="messagehub-demo-button" id="messagehub-demo-send-now">Send now</button>
		</div>
	</div>

	<div class="messagehub-demo-result"><pre id="messagehub-demo-result">Ready.</pre></div>
</div>
<script>
(() => {
	const serviceUrl = <?php echo json_encode($serviceUrl, JSON_UNESCAPED_SLASHES); ?>;
	const resultElement = document.getElementById('messagehub-demo-result');

	function value(id) {
		return document.getElementById(id).value || '';
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
		resultElement.textContent = 'Running ' + mode + ' ...';
		const result = await postJson(payload(mode));
		resultElement.textContent = JSON.stringify(result, null, 2);
	}

	document.getElementById('messagehub-demo-sync').addEventListener('click', () => execute('sync'));
	document.getElementById('messagehub-demo-queue').addEventListener('click', () => execute('queue'));
	document.getElementById('messagehub-demo-send-now').addEventListener('click', () => execute('send-now'));
})();
</script>
