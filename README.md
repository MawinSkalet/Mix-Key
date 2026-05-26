# Mix-Key

Security note:

- Do not commit real credentials. Copy `.env.example` to `.env` and fill values there.
- `.env.example` contains placeholders; replace with secrets only in your local `.env`.
- If any real credentials were accidentally exposed, rotate them immediately (DB passwords, MQTT users, API keys).

Quick start:

```bash
cp .env.example .env
# fill in secrets in .env
docker compose up -d --build
```
