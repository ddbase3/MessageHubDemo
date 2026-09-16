# MessageHubDemo FAQ

## What is MessageHubDemo?

MessageHubDemo is a small consumer plugin that demonstrates the intended use of the MessagingFoundation contracts and MessageHub implementation.

It contributes one discoverable message type and one administration display for synchronizing, queueing, and immediately processing a test message.

It is a demonstration and verification component, not a general production notification system.

## Which message type does the demo provide?

The provider is:

```text
MessageHubDemo\Message\DemoWelcomeMessageTypeProvider
```

Its technical message type name is:

```text
messagehubdemowelcomemessage
```

The provider defines a label, description, default subject, default plain text body, default HTML body, placeholders, and an input schema.

## Which placeholders are defined?

The demo provider currently defines:

- `recipient_name`
- `demo_title`
- `demo_code`
- `sent_at`
- `system_name`

The administration display builds these values from user input and runtime information before calling `IMessageRenderer`.

## Does the provider send messages by itself?

No. The provider only describes the message type and its defaults.

The display uses:

- `IMessageTypeSynchronizationService`
- `IMessageRenderer`
- `IMessageService`
- `IMessageTransportRegistry`

for the actual workflow.

## What does the Sync action do?

The JSON mode `sync` calls `syncOne()` for the demo message type and selected language.

With the default MessageHub implementation, this creates the missing template and language variant without overwriting an existing customized template or variant.

## What does Queue do?

Queue mode:

1. synchronizes the demo message type for the selected language
2. renders the message
3. optionally adds a recipient
4. adds demo metadata
5. calls `IMessageService::enqueue()`

The response returns the new queue ID.

## What does Send now do?

Send-now mode uses `IMessageService::sendNow()`.

With the current MessageHub implementation, send-now still creates a persistent queue entry and then immediately attempts delivery. A delivery record is also created.

## Which transport can be selected?

The display lists transports discovered through `IMessageTransportRegistry` that are currently enabled according to their settings and schema defaults.

The configured default transport is selected when it is enabled. Otherwise the first enabled transport is used.

## Does the demo hard-code email?

No. The recipient field is deliberately generic.

Its placeholder text indicates that a recipient can be an email address, phone number, chat ID, or topic depending on the chosen transport.

Some transports, such as log, null, or a suitably configured webhook, may not require a recipient address.

## Does the demo validate the recipient format?

No. It only reads the entered recipient address as a string and adds it as a `to` `MessageAddress` when it is non-empty.

The selected transport decides how that address is interpreted and whether it is usable.

## Does the demo look up application users?

No. It does not resolve recipients from a user directory, role system, address book, or permission model.

The recipient name and address are entered directly in the demo UI.

## How is the system name determined?

The display uses `ISystemService`.

If host and embedded system names are both available and different, it combines them as:

```text
Host / Embedded
```

Otherwise it uses whichever system name is available.

The value can also be overridden by the JSON payload sent by the demo form.

## How are languages selected?

The display reads available languages from `ILanguage` and presents them as selectable options.

If the submitted language is not in the current language list, it falls back to the currently selected language or the first available option.

## Does the demo persist its own data?

No. MessageHubDemo defines no database schema, repository, state store, settings store, cache, or file persistence.

However, its actions deliberately call MessageHub services. The resulting template, variant, queue entry, and delivery history can therefore be persisted by MessageHub.

## Does the demo store browser data?

No explicit use of `localStorage`, `sessionStorage`, IndexedDB, or cookies is present in the MessageHubDemo template.

Form values exist in the rendered page and are sent in a JSON POST request when an action is triggered.

## Does the demo send data directly to external providers?

No. MessageHubDemo calls the generic messaging service.

External communication happens only if the selected MessageHub transport performs network or mail delivery.

## What metadata does the demo add?

The rendered message receives:

```text
demo = true
demo_display = messagehubdemoadmindisplay
```

These values become part of the `Message` metadata and can be persisted by MessageHub in queue and delivery JSON.

## Does the demo include attachments?

No. The current demo UI and provider do not attach files.

## Does the demo have its own permission checks?

No. The display does not inject a user or authorization service.

It should therefore only be exposed inside an administration boundary that already restricts access to trusted users.

## Does the demo implement its own CSRF protection?

No component-specific CSRF token is implemented in the demo display. It accepts JSON POST requests through `IRequest`.

The host environment must provide the appropriate browser request-forgery protection if the endpoint is exposed through an authenticated session.

## Can the demo be used in production?

It is intended as a demonstration and smoke-test component.

A production consumer should normally define its own message type provider, recipient rules, business authorization, validated context, and operational workflow rather than exposing the generic test display.

## How should a real consumer plugin use MessageHub?

A typical production consumer can follow the same core pattern without the demo UI:

1. implement `IMessageTypeProvider`
2. let the provider be discovered through the class map
3. synchronize or provision the message type
4. render with `IMessageRenderer`
5. add recipients and optional metadata
6. call `IMessageService::enqueue()` or `sendNow()`

The consumer should keep its own business rules at the point where the message is created.
