# 301-Importer
Import 301 Redirects to Shopify

## Commands

This project exports 2 commands

1 - Get All the 301s for a given account
```bash
./console Redirect:Get API_KEY:API_PASSWORD@SHOPIFY_DOMAIN
```

2 - Import 301s into the shopify account
```bash
./console Redirect:Import API_KEY:API_PASSWORD@SHOPIFY_DOMAIN
```

## Roadmap
1 - Detect 301s that are already present and ask if you want to overwrite them.
2 - Allow for removing all 301s
3 - Allow output to logfile
