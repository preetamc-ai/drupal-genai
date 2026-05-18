# AI Guardrails PII

A Drupal recipe that installs a set of regex-based AI guardrails to detect and block personally identifiable information (PII) in AI interactions. Apply this recipe to any site where AI features process user input or generate output that may contain sensitive personal data.

## Project Information

- **Drupal.org Project**: https://www.drupal.org/project/ai_recipe_guardrails_pii
- **Ecosystem**: AI (Artificial Intelligence)

### Maintainers

- [admitriiev](https://www.drupal.org/u/admitriiev)
- [breidert](https://www.drupal.org/u/breidert)

### Supporting Organizations

- [1xINTERNET](https://www.drupal.org/1xinternet)

---

## What This Recipe Does

This recipe installs four individual guardrails and one guardrail set (`pii_protection`) into a Drupal site running the [AI module](https://www.drupal.org/project/ai).

Each guardrail uses a regular expression to scan text for a specific type of PII. The `pii_protection` guardrail set applies all four guardrails to **both** the pre-generate (user input) and post-generate (AI output) phases, so PII is blocked in both directions.

**When to use this recipe:**
- Any public-facing AI interaction (chatbots, AI assistants, AI-powered search)
- Sites with GDPR or data protection obligations
- Environments where user content must not be forwarded to third-party AI providers containing sensitive identifiers

---

## Requirements

- Drupal 11.2 or later
- [`drupal/ai`](https://www.drupal.org/project/ai) ^1.3

---

## How to Apply

Run the following Drush command from your Drupal root:

```bash
drush recipe ../recipes/ai_recipe_guardrails_pii
```

---

## Configuration Installed

### Guardrail Set

| Machine name     | Label          | Description |
|------------------|----------------|-------------|
| `pii_protection` | PII Protection | Applies all four PII guardrails to both pre- and post-generate phases. Stop threshold: 0.8. |

### Guardrails

| Machine name              | Label                    | Detects |
|---------------------------|--------------------------|---------|
| `pii_email_address`       | PII: Email Address       | Email addresses |
| `pii_credit_card_number`  | PII: Credit Card Number  | Payment card numbers (Visa, Mastercard, Amex, Discover, etc.) |
| `pii_iban`                | PII: IBAN                | IBAN bank account numbers |
| `pii_phone_number`        | PII: Phone Number        | International phone numbers (E.164 / +XX format) |

All guardrails use the `regexp_guardrail` plugin. See the **Regex Patterns** section below for the full pattern details.

---

## Regex Patterns Reference

### 1. Email Addresses

**Guardrail:** `pii_email_address`

```
/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i
```

**How it works:**

| Part | Matches |
|------|---------|
| `[a-z0-9._%+\-]+` | Local part (before `@`) |
| `@` | Literal at-sign |
| `[a-z0-9.\-]+` | Domain name |
| `\.[a-z]{2,}` | Top-level domain (2+ letters) |
| `/i` | Case-insensitive |

**Test Data**\
test@example.com\
user.name+tag@domain.co.uk\
fake_email-123@test-mail.org\
contact@sub.domain.com

---

### 2. Credit Card Numbers

**Guardrail:** `pii_credit_card_number`

```
/(?<!\d)(?:\d[\s-]?){12,19}\d(?!\d)/
```

**How it works:**

| Part | Matches |
|------|---------|
| `(?<!\d)` / `(?!\d)` | Negative lookbehind/lookahead — prevents partial matches inside larger numbers |
| `(?:\d[\s-]?){12,19}\d` | 13–20 digits with optional spaces or dashes between digits |

Covers Visa, Mastercard, Amex, Diners Club, and Discover.

**Test Data**\
4111 1111 1111 1111\
4000056655665556\
5555-5555-5555-4444\
2223 0000 4848 0010\
3782 822463 10005\
3714 496353 98431\
3056 930902 5904\
6011 1111 1111 1117\
4111-1111-1111-1111

---

### 3. IBAN Bank Account Numbers

**Guardrail:** `pii_iban`

```
/(?<!\w)[A-Z]{2}\d{2}(?:\s?[A-Z0-9]){11,30}(?!\w)/i
```

**How it works:**

| Part | Matches |
|------|---------|
| `(?<!\w)` / `(?!\w)` | Word boundary guards — prevents partial matches |
| `[A-Z]{2}\d{2}` | Country code (2 letters) + check digits (2 digits) |
| `(?:\s?[A-Z0-9]){11,30}` | Remaining BBAN characters with optional spaces |
| `/i` | Allows lowercase input |

Matches IBANs of total length 15–34 characters, covering all current IBAN country formats.

**Test Data**\
DE89 3704 0044 0532 0130 00\
de89370400440532013000\
FR76 3000 6000 0112 3456 7890 189\
GB82 WEST 1234 5698 7654 32\
ES91 2100 0418 4502 0005 1332\
NL91 ABNA 0417 1643 00\
IT60 X054 2811 1010 0000 0123 456\
BE68 5390 0754 7034

---

### 4. International Phone Numbers

**Guardrail:** `pii_phone_number`

```
/(?<!\w)(?:\+|0|00)[1-9][0-9\s().-]{6,20}\d(?!\w)/
```

**How it works:**

| Part | Matches |
|------|---------|
| `(?<!\w)` / `(?!\w)` | Word boundary guards |
| `(?:\+\|0|00)` | Phone number prefix: `+`, `0`, or `00` |
| `[1-9]` | First digit of country code (non-zero) |
| `[0-9\s().-]{6,20}` | Remaining digits with optional spaces, parentheses, dots, dashes |
| `\d` | Must end with a digit |

Requires an explicit international dialling prefix, so local-format numbers (e.g. `0151 1234567`) are intentionally not matched.

**Test Data**\
+49 151 23456789\
+49-151-23456789\
+49 (151) 23456789\
+4915123456789\
0049 151 23456789\
+1 202 555 0143\
+1-202-555-0143\
+1 (202) 555-0143\
001 202 555 0143\
+44 20 7946 0018\
+44 (20) 7946 0018\
+33 6 12 34 56 78\
+33-6-12-34-56-78\
+33 (6) 12 34 56 78\
+31 6 12345678\
+31-6-12345678\
+31 (6) 12345678\
+91 98765 43210\
+91-98765-43210\
+91 (98765) 43210\
+61 412 345 678\
+61-412-345-678\
+61 (412) 345 678

**Negative Test Cases (should not match)**\
123456\
+123\
+49 abcdef\
1+2+3

---

## Known Limitations and False Positive Risks

All four guardrails use simple regular expressions without semantic validation. This means they match patterns by shape, not by meaning, and will block legitimate content that happens to resemble PII. Review the risks below before applying this recipe to production sites and adjust or extend the guardrails to fit your use case.

---

### Credit Card Numbers

**Pattern:** `/(?<!\d)(?:\d[\s-]?){12,19}\d(?!\d)/`

**Risk: High.** The pattern matches any 13–20 digit sequence with optional spaces or dashes. It performs no [Luhn checksum](https://en.wikipedia.org/wiki/Luhn_algorithm) validation, so it cannot distinguish real card numbers from other long numeric strings.

Common false positives in AI responses:

| Content type | Example |
|---|---|
| ISBN-13 barcodes | `978-3-16-148410-0` |
| EAN-13 / GTIN-14 product codes | `4006381333931` |
| Order or invoice numbers | `ORD-20240318-0045123` |
| Parcel tracking numbers | `1Z999AA10123456784` (UPS) |
| Millisecond Unix timestamps | `1710758400000` (13 digits) |
| Sequential database IDs | `10000000000001` |
| Bank account numbers (non-IBAN) | Plain 13–20 digit account numbers |

**Mitigation:** For stricter detection, replace this guardrail with one that also validates the Luhn checksum, or combine it with card-network prefix rules (e.g. Visa starts with `4`, Mastercard with `51–55`). Alternatively, scope the guardrail to post-generate only if user input rarely contains raw card numbers.

---

### Email Addresses

**Pattern:** `/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i`

**Risk: Low–Medium.** The pattern is broad by design and will match any `local@domain.tld`-shaped string, regardless of whether it is a real personal email address.

Common false positives in AI responses:

| Content type | Example |
|---|---|
| Git SSH remote URLs | `git@github.com` matches as an email address |
| System or role addresses | `noreply@github.com`, `mailer-daemon@domain.com` |
| Placeholder addresses in code examples | `user@example.com` in documentation or tutorials |
| Scoped npm package names in inline code | `@scope/package` does not match, but surrounding prose may |

**Note:** The pattern has no anchoring, so it will match email-shaped substrings inside longer strings (e.g. inside a URL such as `https://example.com?ref=user@example.com`).

---

### IBAN Bank Account Numbers

**Pattern:** `/(?<!\w)[A-Z]{2}\d{2}(?:\s?[A-Z0-9]){11,30}(?!\w)/i`

**Risk: Medium.** The pattern matches any string that begins with two letters and two digits followed by 11–30 alphanumeric characters. It performs no mod-97 checksum validation, so the country code and check digits are not verified.

Common false positives in AI responses:

| Content type | Example |
|---|---|
| Software licence keys | `AB12-CDEF-GHIJ-KLMN-OPQR` |
| Product or hardware serial numbers | Serials with a country-code-like prefix |
| Postal tracking numbers | `GB12345678901234567` (Royal Mail format) |
| Base32 or base36 encoded identifiers | Random-looking alphanumeric strings of sufficient length |
| ISO country code + numeric code combinations | Any ISO 3166 code followed by two digits and more alphanumeric content |

**Note:** The `/i` flag means lowercase and mixed-case strings also match, which increases the false positive surface area compared with a strict uppercase-only IBAN match.

**Mitigation:** Add mod-97 checksum validation in a custom guardrail plugin, or restrict matching to known valid IBAN country codes (there are fewer than 80).

---

### International Phone Numbers

**Pattern:** `/(?<!\w)(?:\+|0|00)[1-9][0-9\s().-]{6,20}\d(?!\w)/`

**Risk: Medium.** The pattern accepts a wide range of separator characters (`\s`, `(`, `)`, `.`, `-`) and allows the `0` prefix in addition to `+` and `00`. This broadens detection but also increases collisions with non-phone numeric strings.

Common false positives in AI responses:

| Content type | Example |
|---|---|
| GPS / geographic coordinates | `+51.509865` (decimal latitude) matches the full pattern |
| Positive numeric offsets or scores | `+1 234 points` may match if long enough |

**Documentation discrepancy:** The pattern prefix `(?:\+|0|00)` includes bare `0` as a valid match, which means local-format numbers beginning with `0` (e.g. `0151 23456789`) **are** matched by the pattern. The test data section notes these as intentional non-matches, but the regex does not enforce an international-only constraint. Depending on your site's audience this may be the desired behaviour or an unintended side effect.

---

### General Limitations Across All Guardrails

- **No semantic context.** A guardrail triggered on `4111 1111 1111 1111` in the sentence "Never use `4111 1111 1111 1111` as a real card number — it is a test value" cannot distinguish documentation from live PII.
- **Post-generate blocking affects the whole response.** If any part of an AI response triggers a guardrail, the entire response is blocked, not just the sensitive fragment.
- **No allow-listing.** There is no built-in mechanism to exempt known-safe strings (e.g. public support email addresses, example card numbers in documentation).
- **Regex patterns are not cryptographic.** Sophisticated actors can trivially reformat PII to evade these patterns (e.g. inserting extra spaces or characters). These guardrails are a defence-in-depth measure, not a security boundary.

---

## Testing

### Apply the recipe

```bash
drush recipe ../recipes/ai_recipe_guardrails_pii
drush cr
```

### Verify guardrails are installed

1. Navigate to `/admin/config/ai/guardrails`
   - You should see four guardrails: **PII: Email Address**, **PII: Credit Card Number**, **PII: IBAN**, and **PII: Phone Number**

2. Navigate to `/admin/config/ai/guardrails/guardrail-sets`
   - You should see one guardrail set: **PII Protection**
   - Open it and confirm all four guardrails are assigned to both pre-generate and post-generate phases

### Idempotency check

To verify the recipe can be safely re-applied:

1. Delete all four guardrails and the guardrail set from the UI
2. Re-run `drush recipe recipes/ai_recipe_guardrails_pii`
3. Confirm all five config entities reappear correctly

### Functional test

Assign the `pii_protection` guardrail set to an AI interaction (e.g. a chatbot or AI assistant field) and send a message containing one of the test values from the **Regex Patterns** section above. The interaction should be blocked and return the configured violation message.
