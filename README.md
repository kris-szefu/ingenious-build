## Invoice Structure:

The invoice should contain the following fields:
* **Invoice ID**: Auto-generated during creation.
* **Invoice Status**: Possible states include `draft,` `sending,` and `sent-to-client`.
* **Customer Name** 
* **Customer Email** 
* **Invoice Product Lines**, each with:
  * **Product Name**
  * **Quantity**: Integer, must be positive. 
  * **Unit Price**: Integer, must be positive.
  * **Total Unit Price**: Calculated as Quantity x Unit Price. 
* **Total Price**: Sum of all Total Unit Prices.

## Required Endpoints:

1. **View Invoice**: Retrieve invoice data in the format above.
2. **Create Invoice**: Initialize a new invoice.
3. **Send Invoice**: Handle the sending of an invoice.

## Functional Requirements:

### Invoice Criteria:

* An invoice can only be created in `draft` status. 
* An invoice can be created with empty product lines. 
* An invoice can only be sent if it is in `draft` status. 
* An invoice can only be marked as `sent-to-client` if its current status is `sending`. 
* To be sent, an invoice must contain product lines with both quantity and unit price as positive integers greater than **zero**.

### Invoice Sending Workflow:

* **Send an email notification** to the customer using the `NotificationFacade`. 
  * The email's subject and message may be hardcoded or customized as needed. 
  * Change the **Invoice Status** to `sending` after sending the notification.

### Delivery:

* Upon successful delivery by the Dummy notification provider:
  * The **Notification Module** triggers a `ResourceDeliveredEvent` via webhook.
  * The **Invoice Module** listens for and captures this event.
  * The **Invoice Status** is updated from `sending` to `sent-to-client`.
  * **Note**: This transition requires that the invoice is currently in the `sending` status.

## Technical Requirements:

* **Preferred Approach**: Domain-Driven Design (DDD) is preferred for this project. If you have experience with DDD, please feel free to apply this methodology. However, if you are more comfortable with another approach, you may choose an alternative structure.
* **Alternative Submission**: If you have a different, comparable project or task that showcases your skills, you may submit that instead of creating this task.
* **Unit Tests**: Core invoice logic should be unit tested. Testing the returned values from endpoints is not required.
* **Documentation**: Candidates are encouraged to document their decisions and reasoning in comments or a README file, explaining why specific implementations or structures were chosen.

## Note on the Notification Module:

The Notification module included in this repository is a minimal, mock integration example. It is intentionally simple and should not be treated as a reference for DDD structure or for the expected invoice design.

## Setup Instructions:

* Start the project by running `./start.sh`.
* To access the container environment, use: `docker compose exec app bash`.

---

## Some comments on how I worked on this task

This is a test task, and working on one looks a bit different today than it did two years ago. I worked on it the way I usually work on a feature: decide the design first, write the decisions down, then implement in increments that are verifiable on their own - with an LLM as a pair throughout, for challenging my design decisions, for drafting specifications, and for the infrastructure and presentation boilerplate.

The code I added is split into four layers. The Domain holds the invoice aggregate, its value objects, the status rules and the repository interface - it's plain PHP, so nothing from Laravel.
The Application layer has one use case per action and the notifier port, and it doesn't depend on framework either.
Only Infrastructure (Eloquent repository, the adapter to the Notifications plus the service provider) and Presentation (routes, controller, request validation etc) know the framework, so the domain and the use cases are unit tested without booting Laravel.

Some of decisions that I made: a draft may hold a line that has a zero or negative amount. The task states positivity as a condition for sending, so send() method on invoice enforces it, together with the 'draft-only' and 'non empty' rules.
Sending follows the order the task describes - domain rules, then the email, then the save, so a failed email leaves the invoice a draft that can be retried.   
The Invoices module talks to Notifications through its own port and an adapter, and it listens to webhook event through a listener registered explicitly in a provider that is not deferred.
