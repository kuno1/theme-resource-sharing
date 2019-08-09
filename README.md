# theme-resource-sharing

Sharing resources in your theme directory.

## Installation

```
composer require kunoichi/theme-resource-sharing
```

## How to Use

This composer library add CLI to your WordPress installation.
Import/Export contents from the resource folder in your theme(default `themes/your-theme/resource`) and you can share your settings among development team.

```
// In your theme's functions.php
// 
Kunoichi\ThemeResourceSharing::enable( 'data' );
```

Now run commands below whenever you like.

### Export

`wp theme-resource export`

This command will export your WordPress settings below:

- Database. Deafautl name is `wordpress.sql`.
- Uploads directry.

### Import

`wp theme-resource import --site_url=https://example.com`

This command will import all of data from your soruce folder.
In case someone uses `https://example.local` but others use 'http://localhost:8888', specifiy `--site_url` to change imported data.

## Notice

- This command is intened to use inside private repository. Be careful on public repo. It may cause [Identity Flaud](https://en.wikipedia.org/wiki/Identity_fraud).
- Do not deploy `resources` directory to public space!
