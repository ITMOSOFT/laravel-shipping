# Changelog

## [1.0.1] - 2026-11-25
- Require cumulative matching across different shipping-zone rule types.
- Apply flat-rate min/max thresholds to order subtotal.
- Validate quote inputs, currencies and rate values.
- Return one lowest-cost eligible quote per shipping method.
- Validate shipment statuses and recipient snapshots.
- Record shipment status changes and tracking events in one transaction.
- Add regression coverage for zone matching, thresholds, quote validation and shipment lifecycle.

## [1.0.0] - 2026-11-05
- Initial release: shipping methods, zones, rules, rates, quote calculation, shipments and tracking events.
