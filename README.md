# MessageHubDemo

`MessageHubDemo` is a small BASE3 plugin that demonstrates how another plugin can use `MessageHub`.

It is intentionally simple. The plugin does not implement its own queue, mail transport, database tables, or messaging infrastructure. Instead, it acts as a consumer of the generic MessageHub services.

The purpose of this plugin is to show the complete end-to-end flow:

```text
Consumer plugin
  -> provides a message type
  -> synchronizes that type into MessageHub
  -> renders a concrete message from the template
  -> adds recipients and metadata
  -> enqueues or sends the message through MessageHub
  -> delivery is handled by the configured MessageHub transport
```

In a BASE3/ILIAS installation this usually means:

```text
MessageHubDemo
  -> MessageHub
  -> PhpMailerMessageTransport
  -> PHP mail() or SMTP
```

---

## 1. Purpose

`MessageHubDemo` exists for three reasons.

First, it verifies that the `MessageHub` infrastructure works in a real consumer-plugin scenario.

Second, it documents the intended integration pattern for future plugins that want to send notifications through MessageHub.

Third, it provides a small admin display where a developer can trigger a test message without writing additional code.

The demo plugin tests:

```text
IMessageTypeProvider discovery
Message type synchronization
Template and variant creation
Message rendering
Recipient attachment through immutable Message DTOs
Queue insertion
Immediate sending
Transport selection
Delivery logging
```

---

## 2. What this plugin is not

`MessageHubDemo` is not a production notification plugin.

It is not responsible for:

```text
mail transport configuration
SMTP credentials
queue processing
delivery retries
delivery logs
template persistence
permission handling
ILIAS mail integration
```

Those concerns belong to `MessageHub`, `MessagingFoundation`, `Base3IliasLab`, or the surrounding BASE3/ILIAS project wiring.

`MessageHubDemo` is only a small example of a plugin that contributes one message type and uses the MessageHub API.

---

## 3. Dependencies

`MessageHubDemo` depends on the following BASE3 areas:

```text
BASE3 Framework
MessagingFoundation
MessageHub
Base3IliasLab integration for administration wiring
```

Conceptually:

```text
MessagingFoundation
  provides interfaces and DTOs

MessageHub
  provides implementation, queue, renderer, repositories and transports

MessageHubDemo
  provides one example message type and one test display
```

The plugin code should depend on interfaces from `MessagingFoundation` and BASE3 framework APIs, not on MessageHub internals.

---

## 4. Directory structure

Typical plugin structure:

```text
MessageHubDemo/
├── README.md
├── VERSION
├── src/
│   ├── MessageHubDemoPlugin.php
│   ├── Message/
│   │   └── DemoWelcomeMessageTypeProvider.php
│   └── Display/
│       └── MessageHubDemoAdminDisplay.php
├── tpl/
│   └── Display/
│       └── MessageHubDemoAdminDisplay.php
└── lang/
    └── Administration/
        ├── de.ini
        └── en.ini
```

### `src/MessageHubDemoPlugin.php`

The plugin class registers the plugin itself in the shared BASE3 container.

It does not need to register the message type provider manually. The provider is discoverable through the BASE3 class map because it lives under `src/` and implements the expected interface.

### `src/Message/DemoWelcomeMessageTypeProvider.php`

This class provides the demo message type.

It implements:

```php
MessagingFoundation\Api\IMessageTypeProvider
```

Its technical message type name is:

```text
messagehubdemowelcomemessage
```

### `src/Display/MessageHubDemoAdminDisplay.php`

This is the administration display used to install/sync the demo template and send a test message.

It depends on MessageHub services through interfaces:

```text
IMessageTypeSynchronizationService
IMessageRenderer
IMessageService
IMessageTransportRegistry
```

It also uses BASE3 services such as:

```text
IRequest
IMvcView
IAssetResolver
ILinkTargetService
ISystemService
```

### `tpl/Display/MessageHubDemoAdminDisplay.php`

This is the HTML/PHP template for the admin display.

The display class prepares data and endpoint URLs. The template renders the UI and sends JSON requests back to the display endpoint.

### `lang/Administration/*.ini`

These files provide labels for administration navigation, for example the `Demo` subtab.

---

## 5. Main concept: Message Type Provider

The most important class in this plugin is:

```text
src/Message/DemoWelcomeMessageTypeProvider.php
```

It defines a message type.

A message type is not a single sent email. It is a reusable technical definition of a category of messages.

Examples of message types could be:

```text
messagehubdemowelcomemessage
reportfinishedmessage
courseexpirationwarning
passwordresetmessage
adminalertmessage
```

The demo provider defines this technical type:

```php
public static function getName(): string {
	return 'messagehubdemowelcomemessage';
}
```

That name is a stable technical identifier. It should not be treated like a label.

The provider also defines:

```text
label
description
default subject
default plain-text body
default HTML body
available placeholders
input schema
```

The provider does not send messages. It does not create queue entries. It does not write to the database by itself.

It only says:

```text
This plugin knows a message type called messagehubdemowelcomemessage.
Here are its default texts and expected placeholders.
```

---

## 6. Technical message type name

The value returned by `getName()` is the technical message type identifier:

```text
messagehubdemowelcomemessage
```

This value should be stable.

It may be used by application code like this:

```php
$message = $renderer->render(
	'messagehubdemowelcomemessage',
	$language,
	$context,
	$transportName
);
```

If the technical type name is changed in the database or in the UI, automated sending code may no longer find the template.

For that reason, provider-based message type names should normally be read-only in administration UIs.

Safe to change:

```text
label
description
subject
body_text
body_html
enabled state
default transport
variant text
```

Unsafe to change:

```text
technical type name
provider identity
placeholder keys used by source code
```

If a user renames the technical message type from:

```text
messagehubdemowelcomemessage
```

to:

```text
mycustomwelcomemessage
```

then the original provider still continues to provide:

```text
messagehubdemowelcomemessage
```

A later sync may recreate the original type because, from the provider's perspective, the original type is missing.

---

## 7. What the provider currently defines

The demo provider defines one default message.

### Type name

```text
messagehubdemowelcomemessage
```

### Label

```text
MessageHub demo welcome message
```

### Description

```text
A simple demo message type used to test MessageHub rendering, queueing and delivery.
```

### Default subject

```text
MessageHub Demo: {{demo_title}}
```

### Default plain text body

```text
Hallo {{recipient_name}},

dies ist eine Testnachricht aus MessageHub.

Demo: {{demo_title}}
Code: {{demo_code}}
Zeitpunkt: {{sent_at}}
System: {{system_name}}
```

### Default HTML body

```html
<p>Hallo {{recipient_name}},</p>
<p>dies ist eine Testnachricht aus <strong>MessageHub</strong>.</p>
<ul>
	<li>Demo: {{demo_title}}</li>
	<li>Code: {{demo_code}}</li>
	<li>Zeitpunkt: {{sent_at}}</li>
	<li>System: {{system_name}}</li>
</ul>
```

### Placeholders

```text
recipient_name
demo_title
demo_code
sent_at
system_name
```

### Schema

The provider declares that the message context should contain:

```text
recipient_name: string
demo_title: string
demo_code: string
sent_at: string
system_name: string
```

These values are used by the renderer to replace placeholders in subject and body.

---

## 8. Language handling

The current demo provider does not define language-specific templates.

It has methods such as:

```php
getDefaultSubject()
getDefaultBodyText()
getDefaultBodyHtml()
```

but not a method like:

```php
getTemplatesByLanguage()
```

That means the provider supplies one generic default text.

The language used during synchronization is therefore selected by the sync call, not by the provider.

In the demo UI there is a language field. If the language field contains:

```text
en
```

then the default template may be created as an `en` variant.

If the language field contains:

```text
de
```

then the default template may be created as a `de` variant.

Important: the provider text itself is currently German. If it is synchronized as `en`, the `en` variant will still contain German text until edited in the template administration UI.

For a production provider, language support should be more explicit. A future interface could provide something like:

```php
public function getDefaultTemplates(): array {
	return [
		[
			'language' => 'de',
			'variant' => 'default',
			'subject' => '...',
			'body_text' => '...',
			'body_html' => '...'
		],
		[
			'language' => 'en',
			'variant' => 'default',
			'subject' => '...',
			'body_text' => '...',
			'body_html' => '...'
		]
	];
}
```

The current demo plugin keeps this simpler.

---

## 9. What synchronization means

Synchronization is the step that copies provider definitions into MessageHub's persistent template system.

The provider is code.

MessageHub templates and variants are database-managed runtime records.

Sync connects both worlds:

```text
IMessageTypeProvider in plugin code
  -> MessageHub sync service
  -> MessageHub template and variant storage
```

A sync typically does this:

```text
1. Discover all IMessageTypeProvider implementations through the class map.
2. Read each provider's technical name, label, description, defaults and placeholders.
3. Check whether the message type already exists in MessageHub storage.
4. Create missing template records.
5. Create missing variant records for the selected/default language.
6. Leave existing edited template content unchanged where possible.
```

Sync is not message sending.

Sync does not process the queue.

Sync does not read mailboxes.

Sync does not contact SMTP.

Sync only ensures that message types from code are represented inside MessageHub's template administration.

---

## 10. StateStore and synchronization state

`MessageHubDemo` does not use `IStateStore`.

The provider does not remember:

```text
I have already been installed.
I have already been synchronized.
I created this template once.
```

The expected source of truth is MessageHub's own database tables.

The sync service can check:

```text
Does type messagehubdemowelcomemessage exist?
Does a variant for this language exist?
```

If not, it can create missing records.

The StateStore is intended for runtime coordination data such as timestamps, locks, cursors or last-run markers. Message type definitions and editable template records are not runtime state and should not be stored there.

---

## 11. What happens if a template is deleted

If the MessageHub template or variant generated from the provider is deleted, the provider still exists in code.

At the next sync, MessageHub may detect that the provider still offers:

```text
messagehubdemowelcomemessage
```

but the matching database record is missing.

In that case, sync may recreate the missing template or variant from the provider defaults.

This is expected behavior for provider-based definitions.

If an administrator wants to disable a provider-based message, disabling the template or variant is usually better than deleting or renaming the technical type.

---

## 12. What happens if a template is edited

Editing subject, plain text body or HTML body in MessageHub is expected.

The provider gives a default. The MessageHub template administration holds the operational version.

Typical rule:

```text
Provider default is used only to create missing content.
Existing admin-edited content should not be overwritten automatically.
```

A forced reset-to-default feature could be added later, but it should be explicit.

---

## 13. What happens if the message type is renamed

Renaming a provider-based technical message type is unsafe.

Example:

```text
Original provider type:
messagehubdemowelcomemessage

Admin renames database type to:
customwelcometest
```

Now the source code still asks for:

```text
messagehubdemowelcomemessage
```

but the database contains:

```text
customwelcometest
```

The renderer may no longer find the template.

At the next sync, MessageHub may recreate:

```text
messagehubdemowelcomemessage
```

because the provider still reports that original technical name.

Recommended behavior:

```text
Provider-owned type name should be read-only.
Labels and content should be editable.
Custom clones should use new custom names.
```

---

## 14. Admin display behavior

The display class is:

```text
MessageHubDemo\Display\MessageHubDemoAdminDisplay
```

Its technical display name is:

```text
messagehubdemoadmindisplay
```

The display provides a small administration UI with actions such as:

```text
Install demo template
Enqueue message
Send now
```

### Install demo template

This action calls the message type synchronization service for the demo provider.

It ensures that the demo message type exists in MessageHub.

### Enqueue message

This action renders a message and places it into the MessageHub queue.

The message is not necessarily sent immediately. It will be picked up by the queue processor or worker.

### Send now

This action renders a message and asks MessageHub to deliver it immediately through the configured transport.

Depending on MessageHub implementation details, this may still create a delivery log entry and may still use the same delivery service as queued messages.

---

## 15. Rendering flow

When a demo message is sent, the display builds a context:

```php
$context = [
	'recipient_name' => $recipientName,
	'demo_title' => $demoTitle,
	'demo_code' => $demoCode,
	'sent_at' => date('Y-m-d H:i:s'),
	'system_name' => $systemName
];
```

Then it renders:

```php
$message = $messageRenderer->render(
	DemoWelcomeMessageTypeProvider::getName(),
	$language,
	$context,
	$transportName
);
```

The renderer loads the matching MessageHub template and variant.

Then placeholders are replaced:

```text
{{recipient_name}} -> Demo Recipient
{{demo_title}}     -> First MessageHub test
{{demo_code}}      -> MH-DEMO
{{sent_at}}        -> 2026-06-30 12:34:56
{{system_name}}    -> ILIAS / BASE3
```

The result is a concrete `Message` DTO.

---

## 16. Immutable Message DTO helpers

The rendered `Message` is immutable.

That means the display does not change the original object directly. It creates a modified copy.

For example:

```php
$message = $message
	->withRecipient(new MessageAddress('to', $recipientAddress, $recipientName))
	->withMetadata([
		'consumer_plugin' => 'MessageHubDemo',
		'demo_code' => $demoCode
	]);
```

This pattern keeps message rendering separated from recipient selection.

The renderer handles template content.

The consumer plugin adds runtime details such as recipient and metadata.

---

## 17. Queue mode

In queue mode, the display calls:

```php
$messageService->enqueue($message, $transportName);
```

This creates a queue record in MessageHub.

The message can then be processed by:

```text
MessageHub queue worker
manual process button
cron/worker integration
```

Queue mode is useful for normal application behavior because the web request does not need to wait for actual delivery.

---

## 18. Send-now mode

In send-now mode, the display calls:

```php
$messageService->sendNow($message, $transportName);
```

This is useful for smoke testing.

It verifies immediately whether:

```text
template rendering works
recipient handling works
transport configuration works
mail delivery works
delivery logging works
```

For production workflows, queue mode is usually preferred.

---

## 19. Transport selection

The demo display can use either:

```text
the MessageHub default transport
an explicitly selected transport
```

Typical transport names:

```text
phpmailer
log
```

In an ILIAS integration, `phpmailer` is usually provided by `Base3IliasLab`, not by the demo plugin.

The demo plugin does not know how PHPMailer is configured. It only passes the selected transport name to MessageHub.

---

## 20. Mail transport notes

If the active MessageHub transport is `phpmailer`, delivery depends on the PHPMailer settings stored for that transport.

Typical settings:

```json
{
  "enabled": true,
  "mode": "mail",
  "from_address": "danieldahme@googlemail.com",
  "from_name": "ILIAS",
  "reply_to_address": "",
  "reply_to_name": "",
  "smtp_host": "",
  "smtp_port": 587,
  "smtp_auth": true,
  "smtp_username": "",
  "smtp_password": "",
  "smtp_secure": "tls",
  "debug": false
}
```

For SMTP mode:

```json
{
  "enabled": true,
  "mode": "smtp",
  "from_address": "danieldahme@googlemail.com",
  "from_name": "ILIAS",
  "reply_to_address": "",
  "reply_to_name": "",
  "smtp_host": "smtp.gmail.com",
  "smtp_port": 587,
  "smtp_auth": true,
  "smtp_username": "danieldahme@googlemail.com",
  "smtp_password": "APP_PASSWORD_OR_CONFIG_VALUE",
  "smtp_secure": "tls",
  "debug": false
}
```

The demo plugin does not store or resolve these settings. That is handled by MessageHub and the transport implementation.

---

## 21. Administration integration

`MessageHubDemoAdminDisplay` should be linked from the project administration area, typically through `Base3IliasLabSettings`.

Example conceptual navigation:

```text
Messaging
  Dashboard
  Templates
  Variants
  Type Sync
  Queue
  Deliveries
  Transports
  Demo
```

The demo subtab should point to:

```text
messagehubdemoadmindisplay
```

The display itself uses BASE3's link target service to build its JSON endpoint.

---

## 22. Typical usage

### Step 1: Verify transport configuration

Open:

```text
Messaging -> Transports
```

Check that a usable transport exists, for example:

```text
phpmailer
```

Configure sender and mail settings.

### Step 2: Open the demo display

Open:

```text
Messaging -> Demo
```

### Step 3: Install or sync the demo template

Click:

```text
Install demo template
```

This synchronizes the provider definition into MessageHub.

### Step 4: Fill recipient data

Example:

```text
Recipient email: danieldahme@gmx.de
Recipient name: Daniel
Language: de
Transport: default or phpmailer
Demo title: First MessageHub test
Demo code: MH-DEMO
System name: ILIAS / BASE3
```

### Step 5: Send or enqueue

Use:

```text
Enqueue message
```

to test the queue flow.

Use:

```text
Send now
```

to test immediate delivery.

### Step 6: Check MessageHub

Open:

```text
Messaging -> Queue
Messaging -> Deliveries
```

Use these views to inspect queue state and delivery results.

---

## 23. How to build a real plugin from this demo

A real plugin should follow the same structure but use its own message type provider.

Example:

```text
ReportingNotificationPlugin/
├── src/
│   ├── ReportingNotificationPlugin.php
│   └── Message/
│       ├── ReportFinishedMessageTypeProvider.php
│       ├── ReportFailedMessageTypeProvider.php
│       └── ReportReminderMessageTypeProvider.php
└── tpl/
```

Each provider should use a stable technical name:

```php
public static function getName(): string {
	return 'reportfinishedmessage';
}
```

The name should not contain project-specific random IDs or labels.

Good:

```text
reportfinishedmessage
courseexpirationwarning
userimportcompleted
```

Bad:

```text
Report Finished!
Daniel Test Mail
Welcome Mail Copy 2
```

---

## 24. Recommended provider design for production

For production providers, prefer:

```text
stable lowercase technical names
clear labels
clear descriptions
explicit placeholder documentation
explicit schema
language-specific defaults if supported
```

Placeholders should be treated as part of the contract between the sending code and the template.

Example:

```text
report_name
download_url
finished_at
recipient_name
```

If source code renders a message and only passes:

```text
report_name
finished_at
```

but the template expects:

```text
download_url
```

then the rendered message may contain unresolved placeholders.

A schema allows MessageHub or the consumer plugin to validate context before sending.

---

## 25. Recommended sync behavior

Provider-based sync should follow these rules:

```text
Create missing type records.
Create missing template records.
Create missing variant records.
Update provider metadata if safe.
Do not overwrite admin-edited subject/body automatically.
Do not rename technical message types.
Do not delete custom admin-created variants.
```

If reset behavior is needed, it should be an explicit action:

```text
Reset variant to provider default
```

not a side effect of normal sync.

---

## 26. Troubleshooting

### Provider does not appear

Possible causes:

```text
ClassMap cache still contains old state.
Namespace does not match path.
Provider does not implement IMessageTypeProvider.
Plugin is not located under the expected plugin/component directory.
PHP syntax error prevents discovery.
```

Actions:

```text
Clear or regenerate BASE3 class map cache.
Check PHP syntax with php -l.
Check namespace and path.
Check that getName() returns a stable lowercase string.
```

### Template not found

Possible causes:

```text
The provider has not been synchronized.
The template was deleted.
The technical type name was renamed.
The requested language has no variant.
```

Actions:

```text
Run Type Sync.
Check Messaging -> Templates.
Check Messaging -> Variants.
Use the original technical name messagehubdemowelcomemessage.
Check the selected language.
```

### Message remains in queue

Possible causes:

```text
Worker is not running.
Queue processing was not triggered.
Transport failed.
Message is waiting for retry.
```

Actions:

```text
Open Messaging -> Queue.
Run manual process if available.
Check Messaging -> Deliveries.
Check server logs if transport is PHPMailer/mail().
```

### PHPMailer needs from_address

The PHPMailer transport requires either:

```text
message sender address
```

or:

```text
transport setting from_address
```

Set `from_address` in the `phpmailer` transport settings.

### Mail is accepted but not delivered

If using `mode = mail`, PHPMailer only hands the message to PHP `mail()` and the local MTA.

Actual delivery depends on:

```text
Postfix/Exim/sendmail
DNS
SPF
DKIM
DMARC
recipient spam policy
```

Check:

```text
Messaging -> Deliveries
/var/log/mail.log
mailq
postqueue -p
```

### SMTP authentication fails

If using Microsoft 365, the tenant or mailbox may have SMTP AUTH disabled.

If using Gmail, use an app password rather than the normal Google password.

---

## 27. Security notes

The demo display should not be exposed to untrusted users.

It can send arbitrary test emails to user-provided recipients.

Recommended protections:

```text
only register it in admin navigation
guard it by project-level permissions
disable/remove it in production if not needed
do not store real SMTP passwords in plain text if avoidable
use ConfigValueResolver/env mode for secrets
```

For production transport secrets, prefer:

```json
{
  "mode": "env",
  "name": "SMTP_PASSWORD"
}
```

rather than storing secrets directly in editable settings.

---

## 28. Design limitations

Current demo limitations:

```text
The provider has only one generic default template.
The provider does not define language-specific defaults.
The demo UI is intentionally simple.
The plugin has no own permission model.
The plugin has no own persistence.
The plugin does not validate deliverability.
The plugin does not know ILIAS users or roles.
```

These limitations are intentional for a demo plugin.

---

## 29. Suggested future improvements

Useful improvements for MessageHub and demo-like consumer plugins:

```text
Make provider-owned technical message type names read-only.
Add explicit provider origin tracking.
Add sync status: missing, synchronized, customized, stale.
Add language-specific provider template definitions.
Add explicit reset-to-provider-default action.
Add placeholder validation before send.
Add preview rendering without enqueue/send.
Add recipient presets for smoke testing.
Add better transport test endpoint.
```

For production usage, the most important improvements are:

```text
read-only technical names
explicit language templates
context validation
clear sync status
preview mode
```

---

## 30. Summary

`MessageHubDemo` demonstrates the correct integration pattern for MessageHub consumer plugins.

It contributes one discoverable message type:

```text
messagehubdemowelcomemessage
```

The provider defines defaults and placeholders.

The sync service copies that definition into MessageHub's editable template system.

The admin display renders a concrete message, adds a recipient, and either enqueues or sends it.

The actual mail delivery is handled by MessageHub transports.

The important separation is:

```text
Provider
  defines available message types and defaults

Sync
  imports missing provider definitions into MessageHub

Templates / Variants
  hold editable operational content

Renderer
  turns templates plus context into a concrete Message DTO

MessageService
  queues or sends the message

Transport
  performs actual delivery
```

This plugin should be used as a reference when building real BASE3 plugins that need notifications, mails, alerts or other message-based communication.
