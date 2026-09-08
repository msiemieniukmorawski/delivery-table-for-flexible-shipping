# What

<!-- One or two sentences. What changes for the shop manager or the developer? -->

## Why

<!-- The bug, the requirement, or the roadmap item this closes. -->

## How it was verified

<!-- Which of the test-matrix cases you exercised, and on what setup. -->

- [ ] Base branch is `staging` (never `main`)
- [ ] `npm run build` succeeds and `build/` is committed if the block changed
- [ ] `composer run lint` is clean
- [ ] Strings changed? `.pot`, `.po`, `.mo` and the editor `.json` regenerated
- [ ] No `fs_` / `fsmt_` prefixes, no Polish text, no client names
- [ ] No `error_log()` outside a `WP_DEBUG` guard
