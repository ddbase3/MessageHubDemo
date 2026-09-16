# Privacy and Data Processing in MessageHubDemo

This document describes the privacy-relevant behavior of MessageHubDemo itself. MessageHubDemo is a demonstration consumer for the MessagingFoundation contracts and MessageHub services. It does not provide its own messaging persistence or transport implementation.

Its main privacy relevance comes from the data entered into the demo form and the fact that the demo deliberately forwards that data into the active MessageHub workflow.

## Component scope

MessageHubDemo currently provides:

- one discoverable message type provider
- one administration display
- one browser form for synchronization and test sending
- queue and send-now actions through `IMessageService`

It does not define a database table, repository, file store, settings store, state store, background job, or external HTTP client.

## Data entered in the demo UI

The form can collect or derive:

- recipient address
- recipient name
- demo title
- demo code
- selected language
- selected transport
- system name

A recipient address can be personal data. Depending on the selected transport, it can be an email address, telephone number, chat ID, topic, or another provider-specific destination.

The free-text demo fields can also contain personal or confidential information if the operator enters such content.

## Data sent to the server

The demo form sends its actions as JSON POST requests to the display's JSON endpoint.

For queue or send-now actions, the request can contain the entered recipient and demo context values.

MessageHubDemo itself does not write these request bodies to a local log or database, but the surrounding web server, request infrastructure, or diagnostics can have their own logging behavior.

## Message rendering

Before sending, the display creates a rendering context containing:

- recipient name
- demo title
- demo code
- current timestamp
- system name

These values are substituted into the stored MessageHub template and variant.

The rendered message can therefore contain any personal or confidential information entered into those fields.

## Message metadata

MessageHubDemo adds these metadata values to the rendered message:

```text
demo = true
demo_display = messagehubdemoadmindisplay
```

The metadata does not itself identify the recipient, but it becomes part of the message object and can be persisted by MessageHub together with the rest of the message.

## No own persistent storage

MessageHubDemo has no own database schema or persistent repository.

It does not independently store:

- recipients
- message bodies
- test form values
- transport choices
- message IDs
- queue IDs

However, its calls to MessageHub are intended to create persistent MessageHub records.

## Persistence through MessageHub

When the demo queues or sends a message, the active MessageHub implementation can persist:

- rendered subject
- plain text body
- HTML body
- recipient address and name
- selected transport
- demo metadata
- queue timestamps and status
- delivery result and error information

Send-now is not persistence-free in the current MessageHub implementation because it first creates a queue row and then processes delivery.

The retention rules of MessageHub therefore apply to messages created by the demo.

## Template synchronization

The Sync action can create a MessageHub template and language variant from the demo provider defaults.

Those records are stored by MessageHub, not by MessageHubDemo.

If administrators later edit the synchronized template or variant, the content remains part of MessageHub storage even if the demo display is no longer used.

## External transfer

MessageHubDemo does not contact external providers directly.

If the selected transport is external, MessageHub can transmit the rendered message and destination to mail infrastructure, webhooks, SMS services, chat platforms, or other providers according to that transport's configuration.

Operators should use only test recipients and test content that are appropriate for the selected provider and environment.

## No recipient directory lookup

The demo does not obtain recipient data from an application user directory.

It does not automatically determine whether the entered address belongs to the current user or whether the recipient has consented to the test message.

The operator is responsible for entering an appropriate destination.

## No deliverability validation

The demo does not validate whether the recipient address is syntactically or operationally valid for the chosen transport.

The selected transport performs its own minimum checks and delivery attempt.

## Browser storage

The current demo template does not intentionally use:

- `localStorage`
- `sessionStorage`
- IndexedDB
- persistent browser cookies

Input values remain in the DOM while the page is open and are sent to the server only when an action is triggered.

Normal browser features such as form memory, extensions, developer tools, accessibility tooling, or enterprise browser management are outside the component's control.

## Authentication and authorization

MessageHubDemo has no component-specific permission model.

The administration display does not inject a user manager or access-control service. Anyone who can reach the endpoint can potentially trigger synchronization, queueing, or immediate delivery through currently enabled transports.

The host application should expose this display only to trusted administrative or development users.

## Request-forgery boundary

The demo JSON endpoint does not implement its own CSRF token.

Because the endpoint can cause message creation and external delivery, a browser-authenticated deployment should provide request-forgery protection at the surrounding application boundary.

## Error responses

The JSON handler catches exceptions and returns the exception message in the `details` field.

Depending on the failure, such text can reveal operational information from the messaging implementation or transport layer. Access to the demo endpoint should therefore remain restricted.

## System information

The demo uses `ISystemService` to display and insert the current host or embedded system name into the example message.

This is runtime metadata, not a user identity. It can still reveal internal product or environment naming if a test message is sent outside the organization.

## Logging

MessageHubDemo itself does not inject `ILogger` and does not write logs.

The MessageHub implementation and selected transport can log delivery events, subjects, errors, or other operational data. Those logs are outside the demo plugin but can contain data originating from the demo request.

## Retention and deletion

MessageHubDemo has no own retention mechanism because it owns no persistent store.

Deleting or disabling the demo plugin does not delete templates, queue rows, delivery history, recipients, or transport-side records previously created through MessageHub.

Any test data created with the demo should be covered by the MessageHub retention and cleanup policy and, where applicable, by the external provider's retention policy.

## Recommended use

MessageHubDemo should be treated as a development and administrative verification tool.

For privacy-safe use:

- use non-sensitive test content
- use controlled test recipients
- avoid real personal data where synthetic data is sufficient
- do not expose the display publicly
- verify the selected transport before sending
- clean up MessageHub test records according to the environment's retention policy
- remove or disable the demo from production navigation when it is not required
