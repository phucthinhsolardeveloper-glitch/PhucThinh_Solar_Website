Payment marks shown with the transfer QR on the thank-you page.

- `vietqr.svg`: VietQR logo, from Wikimedia Commons "File:VietQR Logo.svg" (CC0 / public-domain dedication).
- `banks/<badge>.png`: logos of the 30 banks in `App\Domain\Commerce\VietQr::BANKS`, named by their badge letters
  (`agr`, `vcb`, ...), taken once from the logo addresses in the public VietQR bank list, trimmed to 80 px high.
  `banks/<badge>-mark.png`: the separate emblem, for logos that open with one (agr, mb, ocb, tpb). Shown only to
  identify the receiving bank; a new bank in `BANKS` needs its file here, otherwise the coloured badge is shown.
