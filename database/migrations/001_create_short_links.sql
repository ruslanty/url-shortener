CREATE TABLE IF NOT EXISTS short_links
(
    code            VARCHAR(8) PRIMARY KEY,
    destination_url TEXT        NOT NULL,
    created_at      TIMESTAMPTZ NOT NULL
);

CREATE TABLE IF NOT EXISTS click_outbox
(
    event_id     UUID PRIMARY KEY,
    short_code   VARCHAR(8)  NOT NULL REFERENCES short_links (code) ON DELETE CASCADE,
    occurred_at  TIMESTAMPTZ NOT NULL,
    published_at TIMESTAMPTZ NULL
);

CREATE INDEX IF NOT EXISTS click_outbox_pending_idx
    ON click_outbox (occurred_at, event_id)
    WHERE published_at IS NULL;

CREATE TABLE IF NOT EXISTS link_clicks
(
    event_id    UUID PRIMARY KEY,
    short_code  VARCHAR(8)  NOT NULL REFERENCES short_links (code) ON DELETE CASCADE,
    occurred_at TIMESTAMPTZ NOT NULL
);

CREATE INDEX IF NOT EXISTS link_clicks_short_code_idx ON link_clicks (short_code);
