# AI business reports

The optional OpenAI connector is off until configured. Report generation is an admin-initiated queue job through the Responses API with a strict JSON Schema response format. Bangla is the default report language; English is optional.

## Inputs

Reports use aggregate values for the requested 7, 30 or 90 day range: event row and unique event totals, order count and gross non-cancelled order value, pending/cancelled counts, unique funnel totals, and campaign source groups normalized to known sources or `other`. No raw search term, page URL, event property, product name, order record, identity, email, phone, IP or user-agent is sent.

## Output

The requested structure includes a summary and 7/30/90-day plans with focus, actions, evidence, confidence and ROI assumptions. The UI labels model output as advice; ROI is an assumption and is not guaranteed. Insufficient data (fewer than 20 unique events and fewer than 3 orders) is recorded locally without an API request.

## Limits and failure handling

- Configurable maximum reports per day (0–5; defaults to 1) and output token cap (100–3000).
- One attempt per report; the report stores token usage when returned.
- An asynchronous queue is mandatory. Sync/null queues are refused.
- API refusal, timeout, HTTP error and malformed structure are recorded with generic failure codes; provider response bodies are not shown to the admin.
- No fixed USD estimate is claimed because model pricing and project rates can change. The report/day and output-token limits constrain volume, but are not a spend guarantee. Set provider-side project budgets/alerts as well.

Official references: [Responses API text generation](https://developers.openai.com/api/docs/guides/text-generation) and [Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses).
