# Discogs response samples

Real responses of the Discogs API, captured on 2026-11-18 with a test application and one user, and read by `ResponseSampleFactory`. Each sample is `<name>.headers.txt` (the status line and the headers) and its body, `<name>.txt` for the form-encoded token responses, `<name>.json` otherwise. The header lines end in CRLF, as sent, so `.gitattributes` keeps Git from normalizing the files.

Every OAuth request was sent with the `PLAINTEXT` signature, in the `Authorization: OAuth …` form of the Discogs documentation (values in double quotes, not percent-encoded), and with an `oauth_callback`.

| Sample | Status | What it shows |
|---|---|---|
| `OAuth/request-token` | 200 | `oauth_token`, `oauth_token_secret` and `oauth_callback_confirmed=true` in the body, form-encoded |
| `OAuth/request-token-wrong-key` | 401 | An unknown consumer key: `Invalid consumer.`, plain text under the form content type |
| `OAuth/request-token-wrong-secret` | 401 | A wrong consumer secret: `Invalid signature.` |
| `OAuth/access-token` | 200 | `oauth_token` and `oauth_token_secret`. Signed with the consumer secret and the request token secret |
| `OAuth/access-token-replayed` | 401 | The same request again: `Invalid request token: <token>` |
| `UserIdentity/identity` | 200 | `id`, `username`, `resource_url`, `consumer_name` |
| `UserIdentity/identity-no-authorization` | 401 | No credentials: `You must authenticate to access this resource.` |
| `UserIdentity/identity-wrong-secret` | 401 | A wrong token secret: the same message, and `www-authenticate` |
| `rate-limit-exceeded` | 429 | A 429 of another endpoint (a release), with a body of one newline and no `Retry-After` |

What isn't as Discogs sent it:

- Token values and token secrets are placeholders of the same length: `RequestToken`, `RequestTokenSecret`, `AccessToken` and `AccessTokenSecret`, each followed by `x`. `access-token-replayed` names the request token in its text, replaced the same way.
- In `UserIdentity/identity`, the user's ID is `1000000`, and the username is `xxxxx`, in `resource_url` too.
- `Set-Cookie` lines are removed from the headers.

A refusal on the authorize page has no response sample: Discogs sends the user to the callback URL with only `denied=<request token>`, and no verifier.
