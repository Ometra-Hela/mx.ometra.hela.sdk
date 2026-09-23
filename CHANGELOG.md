# Changelog

## 0.5.1 - 2026-09-23

- Add token-authenticated portal session exchange for dynamically provisioned Zephyr instances.
- Keep signed portal assertion exchange available for existing clients.

## 0.4.0 - 2026-07-31

- Require the caller-selected `subscriber_type` (`INDIVIDUAL` or `BUSINESS`) in portability validation and request payloads.
- Expose the selected portability subscriber type through `PortabilityDto::subscriberType` and canonical constants.
- Add effective client catalog pricing and purchase capability fields to `OfferDto`.
- Add typed clients API dashboard and report contracts.
- Preserve `publicPrice` compatibility for legacy catalog consumers.
